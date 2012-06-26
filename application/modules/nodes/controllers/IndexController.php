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

class Nodes_IndexController extends Unwired_Rest_Controller
{
	public function init()
	{
		parent::init();
		$this->_defaultMapper = new Nodes_Model_Mapper_Node();
	}

	/**
	 * Get the cache instance
	 * @return Zend_Cache_Frontend_File
	 */
    protected function _getCache()
    {
        if (null === $this->_cache) {
            $cacheMgr = $this->getInvokeArg('bootstrap')->getResource('Cachemanager');

            $this->_cache = $cacheMgr->getCache('default');
        }

        return $this->_cache;
    }

	public function indexAction()
	{

		$groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		$filter = $this->_getFilters();

		$groupService->prepareMapperListingByAdmin($this->_getDefaultMapper(),
													null,
													false,
													$filter);
		$this->_index();
	}


	public function showGroupAction()
	{
		$groupId = (int) $this->getRequest()->getParam('id', 0);

		$group = null;

		if ($groupId > 0) {
			$groupMapper = new Groups_Model_Mapper_Group();

			$group = $groupMapper->find($groupId);
		}

		if (null === $group) {
			$this->view->uiMessage('nodes_index_showgroup_notfound', 'error');
			$this->_helper->redirector->gotoRouteAndExit(array('module' => 'nodes'),
														 'default',
														 true);
		}

		$groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		$filter = $this->_getFilters();

		$groupService->prepareMapperListing($group,
											$this->_getDefaultMapper(),
											true,
											$filter);

		$this->view->group = $group;

		$this->_index();
	}

	public function viewAction()
	{
	    $this->_view();
	}

	protected function _getFilters()
	{
		$filter = array();

		$filter['name'] = $this->getRequest()->getParam('name', null);
		$filter['mac'] = strtoupper($this->getRequest()->getParam('mac', null));
		$filter['ipaddress'] = $this->getRequest()->getParam('ipaddress', null);
		$filter['billable'] = $this->getRequest()->getParam('billable', null);

		$this->view->filter = $filter;

		foreach ($filter as $key => $value) {
			if (null == $value || (!is_numeric($value) && empty($value))) {
				unset($filter[$key]);
				continue;
			}

			$filter[$key] = '%' . preg_replace('/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value) . '%';
			if ($key == 'mac') {
				$filter[$key] == str_replace('-', '', $filter[$key]);
			}
		}

		return $filter;
	}

	protected function _add(Unwired_Model_Mapper $mapper = null,
							Unwired_Model_Generic $entity = null,
							Zend_Form $form = null)
	{
		$groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		$this->_setAutoRedirect(false);

		if (null !== $entity) {
			$entity->setUpdateConfig(true);

			/**
			 * Do not mark empty mac/planning APs to be updated
			 */
			if ($this->getRequest()->isPost() &&
			   (!$this->getRequest()->getParam('mac', null)
			   || $this->getRequest()->getParam('status', 'planning') == 'planning')) {

			   	$entity->setUpdateConfig(false);
			}

		} else {
			$groupId = (int) $this->getRequest()->getParam('group_id', 0);

			if ($groupId > 0) {

				if (null === $mapper) {
					$mapper = $this->_getDefaultMapper();
				}

				$entity = $mapper->getEmptyModel();


				$entity->setGroupId($groupId);
			}
		}

		if ($entity && $entity->getNodeId()) {
			if (!$form) {
				$form = new Nodes_Form_Node();
			}

			$form->getElement('mac')
					  ->getValidator('Db_NoRecordExists')
					  	   ->setExclude(array('field' => 'node_id',
					  	   					  'value' => $entity->getNodeId()));
		}

		$result = parent::_add($mapper, $entity, $form);
		if ($result) {
		    $this->_getCache()->clean('matchingTag', array('node'));

			$nodeService = new Nodes_Service_Node();
			if ($nodeService->writeUci($this->view->entity)) {
				$this->_setAutoRedirect(true)
					 ->_gotoIndex();
			}

			$this->view->uiMessage('nodes_index_edit_cannot_write_uci', 'warning');
		}

		return $result;
	}

	public function addAction()
	{
		$this->_add();
		$this->_helper->viewRenderer->setScriptAction('edit');
	}

	public function editAction()
	{
		$this->_edit();
	}

	public function deleteAction()
	{
		$this->_delete();
		// @todo node deletion
	}

	public function undeleteAction()
	{
		$mapper = $this->_getDefaultMapper();

		$id = (int) $this->getRequest()->getParam('id');

		if (!$id) {
			$this->view->uiMessage('entity_not_found', 'error');
			$this->_gotoIndex();
			return false;
		}

		$entity = $mapper->find($id, true);

		if (!$entity) {
			$this->view->uiMessage('entity_not_found', 'error');
			$this->_gotoIndex();
			return false;
		}

		if (!$this->_acl->isAllowed($this->_currentUser, $entity, 'edit')) {
			$this->view->uiMessage('access_not_allowed_edit', 'error');
			$this->_gotoIndex();
			return false;
		}

		if (!$entity->isDeleted()) {
			$this->view->uiMessage('entity_not_found', 'error');
			$this->_gotoIndex();
			return false;
		}

		$entity->setDeleted(0);

		$this->_add($mapper, $entity, null);

		$this->_helper->viewRenderer->setScriptAction('edit');
	}

	public function checkMacAction()
	{
	    $this->_helper->viewRenderer->setNoRender(true);
	    $this->_helper->layout->disableLayout();


	    $mac = $this->getRequest()->getParam('mac', null);
	    if (!$mac) {
	        echo $this->view->json(array('node' => null));
	        return;
	    }

	    $mac = str_replace(':', '', $mac);

	    $mapperNodes = new Nodes_Model_Mapper_Node();
	    $result = $mapperNodes->findBy(array('mac' => $mac), 1, true);

	    $response = array('node' => null);
	    if (!empty($result)) {
	        $response['node'] = $result[0]->toArray();
	    }

	    if ($response['node'] && !$this->_acl->isAllowed($this->_currentUser, $result[0], 'edit')) {
	        $response['node']->setDeleted(0);
	    }

	    echo $this->view->json($response);
	}
}