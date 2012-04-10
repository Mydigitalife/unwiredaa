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

class Nodes_Model_Status extends Unwired_Model_Generic
{
	protected $_nodeId = null;

	protected $_monthlyTraffic = null;

	protected $_aptype = null;

	protected $_kernelbuild = null;

	protected $_tunnelip = null;

	protected $_uptime = null;

	protected $_lastupdate = null;

	/**
	 * @return the $nodeId
	 */
	public function getNodeId() {
		return $this->_nodeId;
	}

	/**
	 * @param field_type $nodeId
	 */
	public function setNodeId($nodeId) {
		$this->_nodeId = $nodeId;
		return $this;
	}

	/**
	 * @return the $monthlyTraffic
	 */
	public function getMonthlyTraffic() {
		return $this->_monthlyTraffic;
	}

	/**
	 * @param field_type $monthlyTraffic
	 */
	public function setMonthlyTraffic($monthlyTraffic = null) {
		$this->_monthlyTraffic = $monthlyTraffic;
		return $this;
	}

	/**
	 * @return the $aptype
	 */
	public function getAptype() {
		return $this->_aptype;
	}

	/**
	 * @param field_type $aptype
	 */
	public function setAptype($aptype = null) {
		$this->_aptype = $aptype;
		return $this;
	}

	/**
	 * @return the $kernelbuild
	 */
	public function getKernelbuild() {
		return $this->_kernelbuild;
	}

	/**
	 * @param field_type $kernelbuild
	 */
	public function setKernelbuild($kernelbuild = null) {
		$this->_kernelbuild = $kernelbuild;
		return $this;
	}

	/**
	 * @return the $tunnelip
	 */
	public function getTunnelip() {
		return $this->_tunnelip;
	}

	/**
	 * @param field_type $tunnelip
	 */
	public function setTunnelip($tunnelip = null) {
		$this->_tunnelip = $tunnelip;
		return $this;
	}

	/**
	 * @return the $uptime
	 */
	public function getUptime() {
		return $this->_uptime;
	}

	/**
	 * @param field_type $uptime
	 */
	public function setUptime($uptime = null) {
		$this->_uptime = $uptime;
		return $this;
	}

	/**
	 * @return the $lastupdate
	 */
	public function getLastupdate() {
		return $this->_lastupdate;
	}

	/**
	 * @param field_type $lastupdate
	 */
	public function setLastupdate($lastupdate) {
		$this->_lastupdate = $lastupdate;
		return $this;
	}



}