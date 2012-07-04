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
 * Report Items
 * @author G. Sokolov <joro@web-teh.net>
 */
class Reports_Model_Items extends Unwired_Model_Generic  implements Zend_Acl_Role_Interface,
																 Zend_Acl_Resource_Interface
{

	protected $_itemId = null;

	protected $_reportGroupId = null;

	protected $_title = null;

	protected $_dateAdded = null;

	protected $_data = array();

	protected $_dateFrom = null;

	protected $_dateTo = null;

	/**
	 * @return the $_itemId
	 */
	public function getItemId() {
		return $this->_itemId;
	}

	/**
	 * @return the $_groupId
	 */
	public function getReportGroupId() {
		return $this->_reportGroupId;
	}

	/**
	 * @return the $_title
	 */
	public function getTitle() {
		return $this->_title;
	}

	/**
	 * @return the $_dateAdded
	 */
	public function getDateAdded() {
		return $this->_dateAdded;
	}

	/**
	 * @return the $_data
	 */
	public function getData($deserialize = false, $assoc = false) {
		if ($deserialize) {
			return json_decode($this->_data, $assoc);
		} else {
			return $this->_data;
		}
	}

	/**
	 * @param int $_itemId
	 */
	public function setItemId($_itemId) {
		$this->_itemId = $_itemId;
	}

	/**
	 * @param int $_groupId
	 */
	public function setReportGroupId($_groupId) {
		$this->_reportGroupId = $_groupId;
	}

	/**
	 * @param string $_title
	 */
	public function setTitle($_title) {
		$this->_title = $_title;
	}

	/**
	 * @param sql date $_dateAdded
	 */
	public function setDateAdded($_dateAdded) {
		$this->_dateAdded = $_dateAdded;
	}

	/**
	 * @param array $_data
	 */

	/**
	 * @todo Serialized data in DB is in fact object of stdClass
	 *       Fix this mess and keep constent data types
	 */
	public function setData($_data) {
		if (is_array($_data)) {
			$this->_data = json_encode($_data);
		} elseif (is_string($_data)) {
			$this->_data = $_data;
		} else {
			$this->_data = json_encode(array());
		}

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
	        //$format = Zend_Date::DATETIME_SHORT;
	        $format = 'dd.MM.yyyy HH:mm';
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
	        //$format = Zend_Date::DATETIME_SHORT;
	        $format = 'dd.MM.yyyy HH:mm';
	        if (preg_match('/\d{4}\-\d{2}\-\d{2}/i', $dateTo)) {
	            $format = 'yyyy-MM-dd HH:mm:ss';
	        }
	        $this->getDateTo()->set($dateTo, $format);
	    } else {
	        $this->_dateTo = null;
	    }

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
		return 'reports_items';
	}


}