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

/**
 *
 * @author B. Krastev <bkrastev@web-teh.net>
 */

class Default_Job_UpdateTranslations
{

    public function run()
    {
        $appDir = new DirectoryIterator(realpath(APPLICATION_PATH . '/modules'));

        foreach ($appDir as $dir) {
            if ($dir->isDot() || !$dir->isDir()) {
                continue;
            }

            $englishPath = realpath($dir->getPathname() . '/languages/en');

            if (!file_exists($englishPath)) {
                continue;
            }


            $this->_processModule($dir->getPathname());
        }
    }

    protected function _processModule($path)
    {
        $path = realpath($path . '/languages');

        $languageIterator = new DirectoryIterator($path);

        foreach ($languageIterator as $languageDir) {
            if ($languageDir->isDot() || !$languageDir->isDir() || $languageDir->getBasename() == 'en') {
                continue;
            }

            $this->_updateLanguage($languageDir->getPathname(), $path . '/en');
        }
    }

    protected function _updateLanguage($updatePath, $fromPath)
    {
        $fromPath = realpath($fromPath);
        $updatePath = realpath($updatePath);

        $translationIterator = new DirectoryIterator($fromPath);

        foreach ($translationIterator as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() != 'ini') {
                continue;
            }

            $targetPath = realpath($updatePath) . '/' . $file->getBasename();


            if (!file_exists($targetPath)) {
                if (!copy($file->getPathname(), $targetPath)) {
                    echo "Cannot copy {$file->getPathname()} to {$targetPath}\n";
                }
                continue;
            }

            $this->_mergeLanguageFile($file->getPathname(), $targetPath);
        }
    }

    protected function _mergeLanguageFile($fromFile, $toFile)
    {
	try {
		$fromFile = realpath($fromFile);
		$toFile = realpath($toFile);

        $fromConfig = new Zend_Config_Ini($fromFile, null, true);
        $toConfig = new Zend_Config_Ini($toFile, null, true);

        $fromConfig->merge($toConfig);

        $writer = new Zend_Config_Writer_Ini();

		$writer->setFilename($toFile)
			   ->setConfig($fromConfig);

        $writer->write();
		} catch (Exception $e) {
			Zend_Debug::dump($fromFile);
			Zend_Debug::dump($toFile);
			Zend_Debug::dump($e->getMessage());
			Zend_Debug::dump($e->getTrace());
		}
		echo "Updated $toFile\n";
    }
}