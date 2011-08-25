<?php

class Groups_Service_Role extends Unwired_Service_Tree
{
	/**
	 * Get root node with children which are applicable for an admin user
	 * @param Users_Model_Admin $admin
	 * @return Groups_Model_Role
	 */
	public function getRoleTreeByAdmin(Users_Model_Admin $admin = null)
	{
		$roles = $this->getRolesByAdmin($admin);

		if (!$roles) {
			return null;
		}

		$root = $roles[0];

		while ($parent = $root->getParent()) {
			$parent->addChild($root);
			$root = $parent;
		}

		return $root;
	}

	/**
	 * Get roles that the user belongs to
	 *
	 * @param Users_Model_Admin $admin
	 * @return array()
	 */
	public function getRolesByAdmin(Users_Model_Admin $admin = null)
	{
		if (null === $admin) {
			$admin = Zend_Auth::getInstance()->getIdentity();
		}

		if (!$admin->getGroupsAssigned()) {
			return array();
		}

		$roles = array();
		foreach (array_unique($admin->getGroupsAssigned()) as $roleId) {
			$role = $this->_getDefaultMapper()->find($roleId);
			$this->loadTree($role);
			$roles[] = $role;
		}

		return $roles;
	}
}