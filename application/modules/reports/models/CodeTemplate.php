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
 * Report Codetemplate
 * @author G. Sokolov <joro@web-teh.net>
 */
class Reports_Model_CodeTemplate extends Unwired_Model_Generic implements Zend_Acl_Role_Interface,
																 Zend_Acl_Resource_Interface
{
	protected $_codetemplateId = null;

	protected $_className = null;

	protected $_title = null;

	protected $_groupSelectionSupported = 1;

	protected $_groupDepthSupported = 0;

	protected $_groupDepthDefault = -1;

	protected $_groupDepthChartDefault = -1;

	protected $_innerIntervalSupported = 0;

	protected $_innerCountMin = 0;

	protected $_innerCountMax = 0;

	protected $_innerCountDefault = 0;

	protected $_timeframeDefault = 0;

	protected $_timeframeLiveMax = null;

	protected $_formatDefault = 'NotUserDefineable';

	protected $_options = array();

	/**
	 * @return the $codeTemplateId
	 */
	public function getCodeTemplateId() {
		return $this->_codetemplateId;
	}

	/**
	 * @param integer $codeTemplateId
	 */
	public function setCodeTemplateId($codeTemplateId) {
		$this->_codetemplateId = $codeTemplateId;

		return $this;
	}

	/**
	 * @return the $className
	 */
	public function getClassName() {
		return $this->_className;
	}

	/**
	 * @param string $className
	 */
	public function setClassName($className) {
		$this->_className = $className;

		return $this;
	}

	/**
	 * @return the $title
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * @param string $nodeId
	 */
	public function setTitle($title) {
		$this->_title = $title;

		return $this;
	}

	/**
	 * ACL role unique identifier
	 *
	 * @see Zend_Acl_Role_Interface::getRoleId()
	 */
	public function getRoleId()
	{
		return $this->getTitle();
	}

	public function setCapabilities($capabilities)
	{
	    $this->_capabilites = (int) $capabilities;
	    return $this;
	}

	public function getCapabilities()
	{
	    return $this->_capabilities;
	}

	public function isCapable($capability)
	{
	    $capability = (int) $capability;

	    return (bool) ($this->_capabilites & $capability);
	}

	public function addCapability($capability)
	{
	    $capability = (int) $capability;
	    $this->_capabilites |= $capability;
	    return $this;
	}

	public function removeCapability($capability)
	{
	    $capability = (int) $capability;
	    $this->_capabilites &= ~$capability;
	    return $this;
	}

	public function getInnerCountMin()
	{
	    return $this->_innerCountMin;
	}

	public function setInnerCountMin($min = 0)
	{
	    $this->_innerCountMin = (int) $min;
	    return $this;
	}

	public function getInnerCountMax()
	{
	    return $this->_innerCountMax;
	}

	public function setInnerCountMax($max = 0)
	{
	    $this->_innerCountMax = (int) $max;
	    return $this;
	}

	public function getInnerCountDefault()
	{
	    return $this->_innerCountDefault;
	}

	public function setInnerCountDefault($count = 0)
	{
	    $this->_innerCountDefault = (int) $count;
	    return $this;
	}

	public function getGroupDepthDefault()
	{
	    return $this->_groupDepthDefault;
	}

	public function setGroupDepthDefault($depth = -1)
	{
	    $this->_groupDepthDefault = (int) $depth;

	    return $this;
	}

	public function getGroupDepthChartDefault()
	{
	    return $this->_groupDepthChartDefault;
	}

	public function setGroupDepthChartDefault($depth = -1)
	{
	    $this->_groupDepthChartDefault = (int) $depth;

	    return $this;
	}

	public function setOptions($options = null)
	{
	    if (null === $options || empty($options)) {
	        $options = array();
	    }

	    if (is_string($options)) {
	        $options = @unserialize($options);
	        if (!$options) {
	            $options = array();
	        }
	    } elseif (!is_array($options)) {
	        $options = array();
	    }

	    $this->_options = $options;
	    return $this;
	}

	public function getOptions()
	{
	    return $this->_options;
	}

	public function hasOption($option)
	{
	    return isset($this->_options[$option]);
	}

	public function getOption($option)
	{
	    return $this->hasOption($option) ? $this->_options[$option] : null;
	}

	public function setOption($option, $value)
	{
	    $this->_options[$option] = $value;
	    return $this;
	}

	/* (non-PHPdoc)
	 * @see Zend_Acl_Resource_Interface::getResourceId()
	 */
	public function getResourceId() {
		return 'reports_index';
	}

	/**
     * @return the $groupSelectionSupported
     */
    public function isGroupSelectionSupported()
    {
        return $this->_groupSelectionSupported;
    }

	/**
     * @param field_type $groupSelectionSupported
     */
    public function setGroupSelectionSupported($groupSelectionSupported)
    {
        $this->_groupSelectionSupported = $groupSelectionSupported;

        return $this;
    }

	/**
     * @return the $groupDepthSupported
     */
    public function isGroupDepthSupported()
    {
        return $this->_groupDepthSupported;
    }

	/**
     * @param field_type $groupDepthSupported
     */
    public function setGroupDepthSupported($groupDepthSupported)
    {
        $this->_groupDepthSupported = $groupDepthSupported;

        return $this;
    }

	/**
     * @return the $innerIntervalSupported
     */
    public function isInnerIntervalSupported()
    {
        return $this->_innerIntervalSupported;
    }

	/**
     * @param field_type $innerIntervalSupported
     */
    public function setInnerIntervalSupported($innerIntervalSupported)
    {
        $this->_innerIntervalSupported = $innerIntervalSupported;

        return $this;
    }

	/**
     * @return the $timeframeDefault
     */
    public function getTimeframeDefault()
    {
        return $this->_timeframeDefault;
    }

	/**
     * @param field_type $timeframeDefault
     */
    public function setTimeframeDefault($timeframeDefault)
    {
        $this->_timeframeDefault = $timeframeDefault;

        return $this;
    }

	/**
     * @return the $timeframeLiveMax
     */
    public function getTimeframeLiveMax()
    {
        return $this->_timeframeLiveMax;
    }

	/**
     * @param field_type $timeframeLiveMax
     */
    public function setTimeframeLiveMax($timeframeLiveMax = null)
    {
        if (null !== $timeframeLiveMax) {
            $timeframeLiveMax = (int) $timeframeLiveMax;
        }

        $this->_timeframeLiveMax = $timeframeLiveMax;

        return $this;
    }

	/**
     * @return the $formatDefault
     */
    public function getFormatDefault()
    {
        return $this->_formatDefault;
    }

	/**
     * @param field_type $formatDefault
     */
    public function setFormatDefault($formatDefault)
    {
        $this->_formatDefault = $formatDefault;

        return $this;
    }
}