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

class Groups_Model_Policy extends Unwired_Model_Generic implements Zend_Acl_Resource_Interface
{
	protected $_policyId = null;

	protected $_name = null;

	protected $_roleId = null;

	protected $_priority = 1000;

	protected $_rulesReply = array();

	protected $_rulesCheck = array();

	/**
	 * @return the $policyId
	 */
	public function getPolicyId() {
		return $this->_policyId;
	}

	/**
	 * @param field_type $policyId
	 */
	public function setPolicyId($policyId) {
		$this->_policyId = $policyId;
		return $this;
	}

	/**
	 * @return the $name
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * @param field_type $name
	 */
	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	/**
	 * @return the $roleId
	 */
	public function getRoleId() {
		return $this->_roleId;
	}

	/**
	 * @param field_type $roleId
	 */
	public function setRoleId($roleId) {
		$this->_roleId = $roleId;
		return $this;
	}

	/**
	 * @return the $priority
	 */
	public function getPriority() {
		return $this->_priority;
	}

	/**
	 * @param field_type $priority
	 */
	public function setPriority($priority) {
		$this->_priority = (int) $priority;

		return $this;
	}

	/**
	 * @return the $rules
	 */
	public function getRulesCheck() {
		return $this->_rulesCheck;
	}

	/**
	 * @param field_type $rules
	 */
	public function setRulesCheck($rules) {
		if (is_string($rules)) {
			$rules = @unserialize($rules);
		}

		if (!is_array($rules)) {
			$rules = array();
		}
		$this->_rulesCheck = $rules;
		return $this;
	}

	public function addRuleCheck($attribute, $value, $op = ':=')
	{
		$this->_rulesCheck[] = array ( 'attribute' => $attribute,
									   'value' => $value,
									   'op' => $op);
		return $this;
	}
	/**
	 * @return the $rules
	 */
	public function getRulesReply() {
		return $this->_rulesReply;
	}

	/**
	 * @param field_type $rules
	 */
	public function setRulesReply($rules) {
		if (is_string($rules)) {
			$rules = @unserialize($rules);
		}

		if (!is_array($rules)) {
			$rules = array();
		}
		$this->_rulesReply = $rules;
		return $this;
	}

	public function addRuleReply($attribute, $value, $op = ':=')
	{
		$this->_rulesReply[] = array ( 'attribute' => $attribute,
									   'value' => $value,
									   'op' => $op);
		return $this;
	}

	/* (non-PHPdoc)
	 * @see Zend_Acl_Resource_Interface::getResourceId()
	 */
	public function getResourceId() {
		return 'groups_policy';
	}
}