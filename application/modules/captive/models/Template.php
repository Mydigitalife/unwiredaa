<?php

class Captive_Model_Template extends Unwired_Model_Generic implements Zend_Acl_Resource_Interface
{
    protected $_templateId = null;

    protected $_name = null;

    protected $_filename = null;

    protected $_groupsAssigned = array();

    protected $_groups = array();

    protected $_settings = array();

    protected $_layouts = array();

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
     * @return the $filename
     */
    public function getFilename()
    {
        return $this->_filename;
    }

	/**
     * @param field_type $filename
     */
    public function setFilename($filename)
    {
        $this->_filename = $filename;

        return $this;
    }

    public function getGroupsAssigned()
    {
        return $this->_groupsAssigned;
    }

    public function setGroupsAssigned($groups = array())
    {
        if (!is_array($groups)) {
            $groups = array();
        }
        $this->_groupsAssigned = $groups;

        return $this;
    }

    public function setGroups(array $groups = array())
    {
        $this->_groups = $groups;
    }

    public function getGroups()
    {
        return $this->_groups;
    }

    public function getSettings()
    {
        return $this->_settings;
    }

    public function setSettings($settings = array())
    {
        if (!is_array($settings)) {
            $settings = array();
        }

        $this->_settings = $settings;

        return $this;
    }

    public function setLayouts($layouts = array())
    {
        foreach ($layouts as $layout) {
            $this->addLayout($layout);
        }

        return $this;
    }

    public function addLayout(Captive_Model_Layout $layout)
    {
        $layout->setSplashId(null)
               ->setTemplateId($this->getTemplateId());

        $this->_layouts[] = $layout;

        return $this;
    }
    public function getLayouts()
    {
        return $this->_layouts;
    }
    public function getResourceId()
    {
        return 'captive_template';
    }
}
