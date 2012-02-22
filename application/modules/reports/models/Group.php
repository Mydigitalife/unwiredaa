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
 * Report Group
 * @author G. Sokolov <joro@web-teh.net>
 */
class Reports_Model_Group extends Unwired_Model_Generic implements Zend_Acl_Role_Interface,
																   Zend_Acl_Resource_Interface
{
	protected $_reportGroupId = null;

	protected $_codetemplateId = null;

	protected $_codeTemplate = null;

	protected $_title = null;

	protected $_dateAdded = null;

	protected $_nodeId = null;

	protected $_options = array();

	protected $_groupDepthMax = null;

	protected $_dateFrom = null;

	protected $_dateTo = null;

	protected $_reportType = null;

	protected $_reportInterval = 'none';

	protected $_description = null;

	protected $_groupsAssigned = array();

	protected $_recepients = array();

	protected $_timeframe = null;

	protected $_innerInterval = 0;

	protected $_formatSelected = null;

	/**
	 * @return the $_recepients
	 */
	public function getRecepients() {
		return $this->_recepients;
	}

	/**
	 * @param multitype: $_recepients
	 */
	public function setRecepients($_recepients) {
		if (is_array($_recepients)) {
			$this->_recepients = $_recepients;
		} elseif (is_string($_recepients)) {
			$this->_recepients = explode(',', $_recepients);
		}
	}

	/**
	 * @return the $groupsAssigned
	 */
	public function getGroupsAssignedFormatted() {
		$groups = $this->getGroupsAssigned();
		$result = array();
		foreach ($groups as $key => $value) {
			$result[] = $value->getName();
		}
		return implode("\n", $result);
	}

	/**
	 * @return the $groupsAssigned
	 */
	public function getGroupsAssigned() {
		return $this->_groupsAssigned;
	}

	/**
	 * key = group id, value = role id
	 * @param array $groupsAssigned
	 */
	public function setGroupsAssigned(array $groupsAssigned = array()) {
		$this->_groupsAssigned = $groupsAssigned;
		return $this;
	}

	public function getGroupAssignedRoleId($groupId)
	{
		/**
		 * @todo Possible ACL problem with false as result
		 */
		return isset($this->_groupsAssigned[$groupId]) ? $this->_groupsAssigned[$groupId] : false;
	}

	/**
	 * @return Reports_Model_CodeTemplate
	 */
	public function getCodeTemplate()
	{
	    return $this->_codeTemplate;
	}

	public function setCodeTemplate(Reports_Model_CodeTemplate $codeTemplate)
	{
	    if ($codeTemplate && !$this->getReportGroupId()) {
	        $this->setGroupDepthMax($codeTemplate->getGroupDepthDefault());
	    }

	    $this->_codeTemplate = $codeTemplate;
	    return $this;
	}

	/**
	 * @return the $_reportType
	 */
	public function getReportType() {
		return $this->_reportType;
	}

	/**
	 * @return the $_reportInterval
	 */
	public function getReportInterval() {
		return $this->_reportInterval;
	}

	/**
	 * @param field_type $_reportType
	 */
	public function setReportType($_reportType) {
		$this->_reportType = $_reportType;
	}

	/**
	 * @param field_type $_reportInterval
	 */
	public function setReportInterval($_reportInterval) {
		$this->_reportInterval = $_reportInterval;
	}

	/**
	 * @return the $groupId
	 */
	public function getReportGroupId() {
		return $this->_reportGroupId;
	}

	/**
	 * @param integer $reportGroupId
	 */
	public function setReportGroupId($reportGroupId) {
		$this->_reportGroupId = $reportGroupId;

		return $this;
	}

	/**
	 * @return the $templateId
	 */
	public function getCodetemplateId() {
		return $this->_codetemplateId;
	}

	/**
	 * @param integer $codetemplateId
	 */
	public function setCodetemplateId($codetemplateId) {
		$this->_codetemplateId = $codetemplateId;

		$codeTemplate = $this->getCodeTemplate();
		if ($codeTemplate && $codeTemplate->getCodeTemplateId() !== $codetemplateId) {
		    $this->_codeTemplate = null;
		}

		return $this;
	}

	/**
	 * @return the $title
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * @param the $title
	 */
	public function setTitle($title) {
		$this->_title = $title;

		return $this;
	}

	/**
	 * @return sql date $dateAdded
	 */
	public function getDateAdded() {
		return $this->_dateAdded;
	}

	/**
	 * @param sql date $dateAdded
	 */
	public function setDateAdded($dateAdded) {
		$this->_dateAdded = $dateAdded;

		return $this;
	}

	/**
	 * @return the $nodeId
	 */
	public function getNodeId() {
		return $this->_nodeId;
	}

	/**
	 * @param integer $groupId
	 */
	public function setNodeId($nodeId) {
		$this->_nodeId = $nodeId;

		return $this;
	}

	/**
	 * @return Zend_Date
	 */
	public function getDateFrom()
	{
	    if (null == $this->_dateFrom) {
	        $this->_dateFrom = new Zend_Date();

	        $this->_dateFrom->setDay(1);
	    }

	    return $this->_dateFrom;
	}

	/**
	 * @param Zend_Date|string $dateFrom
	 */
	public function setDateFrom($dateFrom)
	{
	    if ($dateFrom instanceof Zend_Date) {
	        $this->_dateFrom = $dateFrom;
	    } else if (is_string($dateFrom)) {
	        $format = Zend_Date::DATETIME_SHORT;
	        if (preg_match('/\d{4}\-\d{2}\-\d{2}/i', $dateFrom)) {
	            $format = 'yyyy-MM-dd HH:mm:ss';
	        }
            $this->getDateFrom()->set($dateFrom, $format);
	    } else {
	        $this->_dateFrom = null;
	    }

		return $this;
	}

	/**
	 * @return Zend_Date
	 */
	public function getDateTo()
	{
	    if (null == $this->_dateTo) {
	        $this->_dateTo = new Zend_Date();

	        $this->_dateTo->setDay(1)
                           ->addMonth(1)
                           ->subDay(1);
	    }

	    return $this->_dateTo;
	}

	/**
	 * @param Zend_Date|string $dateTo
	 */
	public function setDateTo($dateTo) {
	    if ($dateTo instanceof Zend_Date) {
	        $this->_dateTo = $dateTo;
	    } else if (is_string($dateTo)) {
	        $format = Zend_Date::DATETIME_SHORT;
	        if (preg_match('/\d{4}\-\d{2}\-\d{2}/i', $dateTo)) {
	            $format = 'yyyy-MM-dd HH:mm:ss';
	        }
	        $this->getDateTo()->set($dateTo, $format);
	    } else {
	        $this->_dateTo = null;
	    }

		return $this;
	}

	public function getGroupDepthMax()
	{
	    if (null == $this->_groupDepthMax) {
	        $codeTemplate = $this->getCodeTemplate();
            if (null === $codeTemplate) {
                $this->_groupDepthMax = -1;
            } else {
                $this->_groupDepthMax = $codeTemplate->getGroupDepthDefault();
            }
	    }

	    return $this->_groupDepthMax;
	}

	public function setGroupDepthMax($depth)
	{
	    $this->_groupDepthMax = (int) $depth;
	    return $this;
	}

	/**
	 * @return the $description
	 */
	public function getDescription() {
		return $this->_description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->_description = $description;

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

	/* (non-PHPdoc)
	 * @see Zend_Acl_Resource_Interface::getResourceId()
	*/
	public function getResourceId() {
		return 'reports_group';
	}
	/**
     * @return the $_options
     */
    public function getOptions()
    {
        return $this->_options;
    }

	/**
     * @param field_type $_options
     */
    public function setOptions($_options)
    {
        $this->_options = $_options;

        return $this;
    }

	/**
     * @return the $_timeframe
     */
    public function getTimeframe()
    {
        if (null == $this->_timeframe) {
            $codeTemplate = $this->getCodeTemplate();

            if (null === $codeTemplate) {
                $this->_timeframe = 'today';
            } else {
                $this->_timeframe = $codeTemplate->getTimeframeDefault();
            }
        }
        return $this->_timeframe;
    }

	/**
     * @param field_type $_timeframe
     */
    public function setTimeframe($_timeframe)
    {
        $this->_timeframe = $_timeframe;

        return $this;
    }

	/**
     * @return the $_innerInterval
     */
    public function getInnerInterval()
    {
        return $this->_innerInterval;
    }

	/**
     * @param field_type $_innerInterval
     */
    public function setInnerInterval($_innerInterval)
    {
        $this->_innerInterval = $_innerInterval;

        return $this;
    }

	/**
     * @return the $_formatSelected
     */
    public function getFormatSelected()
    {
        if (null == $this->_formatSelected) {
            $codeTemplate = $this->getCodeTemplate();
            if (null === $codeTemplate) {
                $this->_formatSelected = 'Both';
            } else {
                $this->_formatSelected = $codeTemplate->getFormatDefault();
            }
        }

        return $this->_formatSelected;
    }

	/**
     * @param field_type $format
     */
    public function setFormatSelected($format)
    {
        $this->_formatSelected = $format;

        return $this;
    }

    public function hasOutputGraph()
    {
        return (bool) ($this->_formatSelected == 'Graph' || $this->_formatSelected == 'Both');
    }

    public function hasOutputTable()
    {
        return (bool) ($this->_formatSelected == 'Table' || $this->_formatSelected == 'Both');
    }

}