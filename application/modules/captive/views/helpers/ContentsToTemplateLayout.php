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

class Captive_View_Helper_ContentsToTemplateLayout extends Zend_View_Helper_Abstract
{
    public function contentsToTemplateLayout($contents, $template)
    {
        $templateDesktopLayout = PUBLIC_PATH . "/data/templates/{$template->getTemplateId()}/layout.phtml";

        if (!file_exists($templateDesktopLayout)) {
            throw new Unwired_Exception("Template layout {$templateDesktopLayout} does not exist!");
        }

        $viewScript = file_get_contents($templateDesktopLayout);

        if (!preg_match_all('/\$this->layout\(\)->{?"?(container-?\d+|content)"?}?/ius', $viewScript, $containers)) {
            throw new Unwired_Exception("No content placeholders found in layout!");
        }

        $sorted = array();
        $containers[1];

        foreach ($containers[1] as $key => $containerName) {
            if ($containerName == 'content') {
                $containerName = 'main';
            } else {
                $colNumber = (int) str_ireplace('container', '', $containerName);
                if ($colNumber < 0) {
                    $containerName = 'special';
                }
            }

            $sorted[$containerName] = array();
        }

        if (isset($sorted['special'])) {
            unset($sorted['special']);
            $sorted = array('special' => array()) + $sorted;
        }

        foreach ($contents as $content) {
            $column = (int) $content->getColumn();

            switch ($column) {
                case 0:
                    $sorted['main'][] = $content;
                break;

                /**
                 * 0 < 0 == true ?!?!?!?
                 */
                case ($column < 0):
                    $sorted['special'][] = $content;
                break;

                default:
                    $sorted['container' . $column][] = $content;
                break;
            }
        }

        foreach ($sorted as &$column) {
            usort($column, array($this, 'sortDesktopColumn'));
        }

        return $sorted;
    }

    public function sortDesktopColumn($contentA, $contentB)
    {
        $dataA = null;
        $dataB = null;

        foreach ($contentA->getData() as $dataA) {
            if (!$dataA->isMobile()) {
                break;
            }
        }

        foreach ($contentB->getData() as $dataB) {
            if (!$dataB->isMobile()) {
                break;
            }
        }

        return ($dataA->getOrder() < $dataB->getOrder()) ? -1 : 1;
    }
}