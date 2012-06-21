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

    public function getSplashPageFiles($splash)
    {
        return $this->_getFiles($splash, 'splash');
    }

    public function getTemplateFiles($template)
    {
        return $this->_getFiles($template, 'template');
    }

    protected function _getFiles($id, $type = 'splash')
    {
        if (!$id) {
            return array();
        }

        if ($type == 'splash') {
            $splash = $id;

            if (!$splash instanceof Captive_Model_SplashPage) {

                $mapperSplash = new Captive_Model_Mapper_SplashPage();

                $splash = $mapperSplash->find($splash);

                if (!$splash) {
                    return array();
                }
            }

            $id = $splash->getSplashId();

            $path = $this->getSplashPagePath($id);

        } else {

            $template = $id;

            if (!$template instanceof Captive_Model_Template) {

                $mapperTemplate = new Captive_Model_Mapper_Template();

                $template = $mapperTemplate->find($template);

                if (!$template) {
                    return array();
                }
            }

            $id = $template->getTemplateId();

            $path = $this->getTemplatePath($id);
        }


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
        if (!Zend_Registry::isRegistered('splashpages')) {
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
                    exec($mkdirCmd, $output, $cmdResuls);
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