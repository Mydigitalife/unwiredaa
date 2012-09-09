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

class Captive_Model_Layout extends Unwired_Model_Generic
{
    protected $_layoutId = null;

    protected $_splashId = null;

    protected $_templateId = null;

    protected $_name = null;

    protected $_layout = null;

    protected $_criteria = array();

    protected $_content = array();

	/**
     * @return the $layoutId
     */
    public function getLayoutId()
    {
        return $this->_layoutId;
    }

	/**
     * @param field_type $layoutId
     */
    public function setLayoutId($layoutId)
    {
        $this->_layoutId = $layoutId;

        return $this;
    }

	/**
     * @return the $splashId
     */
    public function getSplashId()
    {
        return $this->_splashId;
    }

	/**
     * @param field_type $splashId
     */
    public function setSplashId($splashId)
    {
        $this->_splashId = $splashId;

        return $this;
    }

	/**
     * @return the $templateId
     */
    public function getTemplateId()
    {
        return $this->_templateId;
    }

	/**
     * @param field_type $templateId
     */
    public function setTemplateId($templateId)
    {
        $this->_templateId = $templateId;

        return $this;
    }

	/**
     * @return the $name
     */
    public function getName()
    {
        return $this->_name;
    }

	/**
     * @param field_type $name
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

	/**
     * @return the $layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

	/**
     * @param field_type $layout
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;

        return $this;
    }

	/**
     * @return the $criteria
     */
    public function getCriteriaArray()
    {
        return $this->_criteria;
    }

	/**
     * @return the $criteria
     */
    public function getCriteria()
    {
        return serialize($this->_criteria);
    }

	/**
     * @param field_type $criteria
     */
    public function setCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = @unserialize($criteria);
        } elseif (is_object($criteria) && method_exists($criteria, 'toArray')) {
            $criteria = $criteria->toArray();
        }

        $this->_criteria = $criteria;

        return $this;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function setContent($content = array())
    {
        foreach($content as $singleContent) {
            $this->addContent($singleContent);
        }

        return $this;
    }

    public function addContent(Captive_Model_Content $content)
    {
        $content->setLayoutId($this->getLayoutId());
        $this->_content[] = $content;

        return $this;
    }

}