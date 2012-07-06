<?php
/**
* Unwired AA GUI
*
* Author & Copyright (c) 2011 Unwired Networks GmbH
* alexander.szlezak@unwired.at
*
* Licensed under the terms of the Affero Gnu Public License version 3
* (AGPLv3 - http://www.gnu.org/licenses/agpl.html) or our proprietory
* license available at http://www.unwired.at/license.html
*/

class Captive_Service_Files implements Unwired_Event_Handler_Interface
{
    /* (non-PHPdoc)
	 * @see Unwired_Event_Handler_Interface::handle()
	 */
	public function handle(Unwired_Event_Message $message)
	{

		$data = $message->getData();

		if (!$data->entity instanceof Captive_Model_Template && !$data->entity instanceof Captive_Model_SplashPage) {
		    return;
		}

		if ($message->getMessageId() !== 'delete') {
		    return;
		}

		if ($data->entity instanceof Captive_Model_Template) {
		    $localPath = $this->getTemplatePath($data->entity->getTemplateId());
		} else {
		    $localPath = $this->getSplashPagePath($data->entity->getSplashId());
		}

		$localPath = str_ireplace('/upload', '', $localPath);

		$this->deleteRecursive($localPath);

		/**
		 * @todo Do remote delete
		 */
	}

	/**
	 * Get file list for a splashpage/template
	 *
	 * @param Captive_Model_SplashPage|Captive_Model_Template $entity
	 */
	public function getFiles($entity)
	{
	    if ($entity instanceof Captive_Model_SplashPage) {
	        return $this->_getFiles($entity, 'splash');
	    } else if ($entity instanceof Captive_Model_Template) {
	        return $this->_getFiles($entity, 'template');
	    }

	    return array();
	}

    protected function _getFiles($entity)
    {
        if (!$entity) {
            return array();
        }

        if ($entity instanceof Captive_Model_SplashPage) {
            /**
             * Check and get path for splashpage
             */
            if (!$entity->getSplashId()) {
                return array();
            }

            $id = $entity->getSplashId();

            $path = $this->getSplashPagePath($id);

        } else if ($entity instanceof Captive_Model_Template) {

            /**
             * Check and get path for template
             */
            if (!$entity->getTemplateId()) {
                return array();
            }

            $id = $entity->getTemplateId();

            $path = $this->getTemplatePath($id);

        } else {
            return array();
        }

        /**
         * Path does not exist
         */
        if (!file_exists($path)) {
            @mkdir($path, 0755, true);
            return array();
        }

        $iterator = new DirectoryIterator($path);

        $files = array();

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $files[] = array('name' => $file->getFilename(),
                             'path' => str_replace(array(PUBLIC_PATH . '/', '\\'), array('', '/'), $file->getPathname()));
        }

        return $files;
    }

    public function copyToSplashpages($files)
    {
        if (!Zend_Registry::isRegistered('splashpages') || strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return true;
        }

        $splashpages = Zend_Registry::get('splashpages');
        $paths = Zend_Registry::get('shellpaths');

        foreach ($splashpages as $splashpage) {
            foreach ($files as $file) {
                $cmd = "{$paths['scp']} -r -p ";

                /**
                 * Build ssh/scp command
                 */
                $sshOptions = '';
                if (isset($splashpage['sshoptions'])) {
                    foreach ($splashpage['sshoptions'] as $switch => $value) {
                        $sshOptions .= " -{$switch} {$value}";
                    }
                }

                $cmd .= $sshOptions;

                $localPath = "{$file['destination']}/{$file['name']}";

                $cmd .= " {$localPath} ";

                $remotePath = "{$splashpage['publicpath']}"
                              . str_replace(PUBLIC_PATH, '', $localPath);

                $remoteUserHost = (isset($splashpage['user']) ? " {$splashpage['user']}@" : '') . "{$splashpage['host']}";

                $cmd .= " {$remoteUserHost}:{$remotePath}";

                exec($cmd, $output, $cmdResult);
                if ($cmdResult) {
                    $mkdirCmd = "{$paths['ssh']} {$sshOptions} {$remoteUserHost} --cmd \" mkdir -m 0777 -p " . dirname($remotePath) . '"';
                    exec($mkdirCmd, $output, $cmdResult);
                    exec($cmd, $output, $cmdResult);
                }

                if ($cmdResult) {

                    @unlink($localPath);

                    return false;
                }
            }
        }

        return true;
    }

    protected function _executeRemote($cmd)
    {
        /**
         * Don't try to do anything if there are no splashpages defined or OS is Windows ;)
         */
        if (!Zend_Registry::isRegistered('splashpages') || strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return true;
        }

        $splashpages = Zend_Registry::get('splashpages');
        $paths = Zend_Registry::get('shellpaths');

        foreach ($splashpages as $splashpage) {
    		/**
             * Build ssh/scp command
             */
            $sshOptions = '';
            if (isset($splashpage['sshoptions'])) {
                foreach ($splashpage['sshoptions'] as $switch => $value) {
                    $sshOptions .= " -{$switch} {$value}";
                }
            }

            $remoteUserHost = (isset($splashpage['user']) ? " {$splashpage['user']}@" : '') . "{$splashpage['host']}";

            $cmd = str_replace(PUBLIC_PATH, $splashpage['publicpath'], $cmd);

            $shellCommand = "{$paths['ssh']} {$sshOptions} {$remoteUserHost} --cmd \"{$cmd}\"";

            /**
             * Exec the remote command
             */
            exec($shellCommand, $output, $cmdResult);

            /**
             * Non zero exit status means error
             */
            if ($cmdResult) {
                return false;
            }
        }

        return true;
    }

    public function copyContentDirectory(Unwired_Model_Generic $source, Unwired_Model_Generic $target)
    {
        if ($source instanceof Captive_Model_Template) {
            $localSourcePath = $this->_getPath(false) . 'templates/' . $source->getTemplateId();
            $localTargetPath = $this->_getPath(false) . 'templates/' . $target->getTemplateId();
            $localTargetId = $target->getTemplateId();
        } else if ($source instanceof Captive_Model_SplashPage) {
            $localSourcePath = $this->_getPath(false) . 'splashpages/' . $source->getSplashId();
            $localTargetPath = $this->_getPath(false) . 'splashpages/' . $target->getSplashId();
            $localTargetId = $target->getSplashId();
        } else {
            throw new Unwired_Exception('Invalid source object provided for copy operation', 500);
        }


        if (!$this->_copyRecursive($localSourcePath, $localTargetPath)) {
            $this->deleteRecursive($localTargetPath);
            return false;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' &&
            !$this->copyToSplashpages(array('destination' => str_ireplace('/' . $localTargetId, '', $localTargetPath),
                                            'name' => $localTargetId)))
        {
            $this->deleteRecursive($localTargetPath);
            return false;
        }

        return true;
    }

    public function deleteRecursive($path)
    {
        foreach(glob($path . '/*') as $file) {
            if(is_dir($file))
                $this->deleteRecursive($file);
            else
                @unlink($file);
        }

        @rmdir($path);

        return true;
    }

    /**
     * Rename a file to a new name preserving the extension.
     *
     * @param string $file
     * @param string $newFileName
     * @param Captive_Model_SplashPage|Captive_Model_Template $entity
     * @return false|string False on failure and new filename on success
     */
    public function renameFile($file, $newFileName, $entity)
    {
         if ($entity instanceof Captive_Model_SplashPage) {
             $path = $this->getSplashPagePath($entity->getSplashId());
         } else if ($entity instanceof Captive_Model_Template) {
             $path = $this->getTemplatePath($entity->getTemplateId());
         } else {
             return false;
         }

         $filePath = $path . '/' . $file;

         if (!file_exists($filePath)) {
             return false;
         }

         $fileInfo = explode('.', $file);

         $newFilePath = $path . '/' . $newFileName . '.' . end($fileInfo);
         if (!@rename($filePath, $newFilePath)) {
            return false;
         }

         $this->_executeRemote("/bin/mv {$filePath} {$newFilePath}");

         return $newFileName . '.' . end($fileInfo);
    }

    public function deleteFile($file, $entity)
    {
         if ($entity instanceof Captive_Model_SplashPage) {
             $path = $this->getSplashPagePath($entity->getSplashId());
         } else if ($entity instanceof Captive_Model_Template) {
             $path = $this->getTemplatePath($entity->getTemplateId());
         } else {
             return false;
         }

         $filePath = $path . '/' . $file;

         if (!file_exists($filePath)) {
             return false;
         }

         if (!@unlink($filePath)) {
             return false;
         }

         $this->_executeRemote("/bin/rm -f {$filePath}");

         return true;
    }

    protected function _copyRecursive($source, $target)
    {
        $sourceDir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source),
                                                   RecursiveIteratorIterator::SELF_FIRST);

        try {
            if (!file_exists($source)) {
                return false;
            }

            if (!file_exists($target) && !mkdir($target, null, true)) {
                return false;
            }

            foreach ($sourceDir as $node) {

                $filename = $node->__toString();
                $targetFilename = str_ireplace($source, $target, $filename);
                if ($node->isDir()) {
                    if (!file_exists($targetFilename)) {
                        @mkdir($targetFilename, null, true);
                    }
                    continue;
                }

                copy($filename, $targetFilename);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getSplashPagePath($splashId)
    {
        return $this->_getPath() . 'splashpages/' . (int) $splashId . '/upload';
    }

    public function getTemplatePath($templateId)
    {
       return $this->_getPath() . 'templates/' . (int) $templateId . '/upload';
    }

    protected function _getPath($relative = false)
    {
        if ($relative) {
            return 'data/';
        }

        return PUBLIC_PATH . '/data/';
    }
}