<?php

class Captive_Model_Content extends Unwired_Model_Generic
{
    protected $_contentId = null;

    protected $_splashId = null;

    protected $_templateId = null;

    protected $_type = null;

    protected $_column = 1;

    protected $_widget = null;

    protected $_templateContent = null;

    protected $_editable = 1;

    protected $_restricted = 0;

    protected $_data = array();

	/**
     * @return the $contentId
     */
    public function getContentId()
    {
        return $this->_contentId;
    }

	/**
     * @param field_type $contentId
     */
    public function setContentId($contentId)
    {
        $this->_contentId = $contentId;

        foreach ($this->_data as $data) {
            $data->setContentId($contentId);
        }

        return $this;
    }

	/**
     * @return the $languageId
     */
    public function getLanguageId()
    {
        return $this->_languageId;
    }

	/**
     * @param field_type $languageId
     */
    public function setLanguageId($languageId)
    {
        $this->_languageId = $languageId;

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

    public function getData()
    {
        return $this->_data;
    }

    public function setData(array $data)
    {
        $this->_data = array();

        foreach ($data as $contentData) {
            $this->addData($contentData);
        }
        return $this;
    }

    public function addData(Captive_Model_ContentData $data)
    {
        $data->setContentId($this->getContentId())
             ->setParent($this);

        $this->_data[] = $data;

        return $this;
    }

	/**
     * @return the $title
     */
    public function getTitle()
    {
        return $this->_title;
    }

	/**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

	/**
     * @return the $type
     */
    public function getType()
    {
        return $this->_type;
    }

	/**
     * @param field_type $type
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }

	/**
     * @return the desktop layout order $order
     */
    public function getOrder()
    {
        foreach ($this->getData() as $contentData) {
            if ($contentData->isMobile()) {
                continue;
            }

            return $contentData->getOrder();
        }

        return 1;
    }

	/**
     * @param field_type $order
     */
    public function setOrder($order)
    {
        foreach ($this->getData() as $contentData) {
            if ($contentData->isMobile()) {
                continue;
            }

            $contentData->setOrder($order);
        }

        return $this;
    }

	/**
     * @return the $orderMobile
     */
    public function getOrderMobile()
    {
        foreach ($this->getData() as $contentData) {
            if (!$contentData->isMobile()) {
                continue;
            }

            return $contentData->getOrder();
        }

        return $this;
    }

	/**
     * @param field_type $orderMobile
     */
    public function setOrderMobile($order)
    {
        foreach ($this->getData() as $contentData) {
            if (!$contentData->isMobile()) {
                continue;
            }

            $contentData->setOrder($order);
        }

        return $this;
    }

	/**
     * @return the $column
     */
    public function getColumn()
    {
        return $this->_column;
    }

	/**
     * @param field_type $column
     */
    public function setColumn($column)
    {
        $this->_column = $column;

        return $this;
    }

	/**
     * @return the $widget
     */
    public function getWidget()
    {
        return $this->_widget;
    }

	/**
     * @param field_type $widget
     */
    public function setWidget($widget)
    {
        $this->_widget = $widget;

        return $this;
    }

	/**
     * @return the $templateContent
     */
    public function getTemplateContent()
    {
        return $this->_templateContent;
    }

	/**
     * @param field_type $templateContent
     */
    public function setTemplateContent($templateContent)
    {
        $this->_templateContent = $templateContent;

        return $this;
    }

    public function isEditable()
    {
        return $this->_editable;
    }

    public function setEditable($editable = true)
    {
        $this->_editable = (int)(bool) $editable;
        return $this;
    }

    public function isRestricted()
    {
        return $this->_restricted;
    }

    public function setRestricted($restricted = true)
    {
        $this->_restricted = (int)(bool) $restricted;
        return $this;
    }

    /**
     * Render content
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->getWidget()) {
             return $this->getContent();
        }

        $widgetClass = 'Widget_' . ucfirst($this->getWidget());
        $widget = new $widgetClass;

        return $widget->render($this);
    }

}