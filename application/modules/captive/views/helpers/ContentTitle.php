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

class Captive_View_Helper_ContentTitle extends Zend_View_Helper_Abstract
{
    protected $_language = null;

    /**
     * Try to get the DB record of captive portal language
     * that matches the current admin interface language
     *
     * @return Captive_Model_Language
     */
    public function getLanguage()
    {
        if (null === $this->_language) {
            $languageCode = $this->view->translate()->getLocale();

            if ($languageCode instanceof Zend_Locale) {
                $languageCode = $languageCode->getLanguage();
            }

            $mapperLanguages = new Captive_Model_Mapper_Language();

            $this->_language = $mapperLanguages->findOneBy(array('code' => (string) $languageCode));
        }

        return $this->_language;
    }

    /**
     * Find the title of splashpage/template content which matches the admin area language
     * Return any title available if language is not available.
     *
     * @param Captive_Model_Content|Captive_Model_ContentData $content
     * @param boolean $mobile
     * @return string
     */
    public function contentTitle($content, $mobile = false)
    {
        if ($content instanceof Captive_Model_ContentData) {
            $content = $content->getParent();
        }

        /**
         * $content at this point should be Captive_Model_Content
         * Return an empty string if not.
         */
        if (!$content instanceof Captive_Model_Content) {
            return '';
        }

        /**
         * Get the current interface language if available in captive portal language table
         */
        $language = $this->getLanguage();

        $title = null;

        $lastNonEmptyTitle = null;

        /**
         * Loop over language data of content
         */
        foreach ($content->getData() as $contentData) {
            $contentTitle = $contentData->getTitle();

            if (empty($contentTitle)) {
                continue;
            }

            $lastNonEmptyTitle = $contentTitle;

            /**
             * Check if we have a language match
             */
            if ($language && $language->getLanguageId() == $contentData->getLanguageId()) {
               /**
                * The title is empty till now. Fill it with the language match value
                */
               if (empty($title)) {
                   $title = $contentTitle;
               }

               /**
                * We have exact language/layout match!
                * Break the loop.
                */
               if ($mobile == $contentData->isMobile()) {
                   $title = $contentTitle;
                   break;
               }
            } else if ($mobile == $contentData->isMobile() && empty($title)) {
                /**
                 * Languages do not match but layouts do and the title is still empty.
                 * Assign current title to the one that's going to be returned.
                 */
                $title = $contentTitle;
            }
        }

        /**
         * If title is empty then we don't have exact match;
         * Show any/last available translation
         */
        if (empty($title)) {
            $title = $lastNonEmptyTitle;
        }

        return $title;
    }
}