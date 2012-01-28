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

class Default_Job_ZipTranslations
{
    /**
     * @var ZipArchive
     */
    protected $_zipArch = null;

    public function run()
    {
        $this->_zipArch = new ZipArchive();

        if ($this->_zipArch->open(APPLICATION_PATH . '/data/translations_' . date('Y-m-d_H-i-s'). '.zip', ZIPARCHIVE::CREATE) !== TRUE) {
            echo "Could not create archive\n";
            return;
        }

        $appDir = new DirectoryIterator(realpath(APPLICATION_PATH . '/modules'));

        foreach ($appDir as $dir) {
            if ($dir->isDot() || !$dir->isDir()) {
                continue;
            }

            $englishPath = realpath($dir->getPathname() . '/languages');

            if (!file_exists($englishPath)) {
                continue;
            }

            if (!$this->_zipArch->addEmptyDir('application/modules/' . $dir->getBasename() . '/languages')) {
                echo "Cannot add dir: {$dir->getPathname()}\n";
                continue;
            }

            $this->_processModule($dir->getPathname(), $dir->getBasename());
        }

        try {
            $this->_zipArch->close();
        } catch (Exception $e) {
            echo "Error adding files into archive\n";
        }
    }

    protected function _processModule($path, $module)
    {
        $path = realpath($path . '/languages');

        $languageIterator = new DirectoryIterator($path);

        foreach ($languageIterator as $languageDir) {
            if ($languageDir->isDot() || !$languageDir->isDir()) {
                continue;
            }

            if (!$this->_zipArch->addEmptyDir('application/modules/' . $module . '/languages/' . $languageDir->getBasename())) {
                echo "Cannot add dir: {$languageDir->getPathname()}\n";
                continue;
            }

            $this->_addFiles($languageDir->getPathname(), 'application/modules/' . $module . '/languages/' . $languageDir->getBasename());
        }
    }

    protected function _addFiles($filePath, $zipPath)
    {
        $filePath = realpath($filePath);

        $filesIterator = new DirectoryIterator($filePath);

        foreach ($filesIterator as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() != 'ini') {
                continue;
            }

            if (!$this->_zipArch->addFile($file->getPathname(), $zipPath . '/' . $file->getBasename())) {
                echo "Cannot add file: {$file->getPathname()}\n";
            }
        }
    }
}