<?php

class Captive_Model_ContentData extends Unwired_Model_Generic
{
    protected $_contentId = null;

    protected $_languageId = null;

    protected $_title = null;

    protected $_content = null;

    protected $_order = 1;

    protected $_mobile = 0;

    protected $_parent = null;

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

        return $this;
    }

    /**
     *
     * @return Captive_Model_Content
     */
    public function getParent()
    {
        return $this->_parent;
    }

    public function setParent(Captive_Model_Content $parent)
    {
        $this->_parent = $parent;
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
     * @return the $content
     */
    public function getContent()
    {
        return $this->_content;
    }

	/**
     * @param field_type $content
     */
    public function setContent($content)
    {
        if (is_array($content)) {
            $content = serialize($content);
        }

        $this->_content = $content;

        return $this;
    }

	/**
     * @return the $order
     */
    public function getOrder()
    {
        return $this->_order;
    }

	/**
     * @param field_type $order
     */
    public function setOrder($order)
    {
        $this->_order = (int) $order;

        return $this;
    }

    public function isMobile()
    {
        return $this->_mobile;
    }

    public function setMobile($mobile = 1)
    {
        $this->_mobile = (int) (bool) $mobile;
        return $this;
    }

}