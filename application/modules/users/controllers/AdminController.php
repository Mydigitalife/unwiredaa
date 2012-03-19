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

class Users_AdminController extends Unwired_Rest_Controller
{
	public function indexAction()
	{
		$groupService = new Groups_Service_Group();

		$adminMapper = new Users_Model_Mapper_Admin();

		$filter = $this->_getFilters();
		$user = $this->getCurrentUser();

		$lowerOnly = true;
		if ($this->getAcl()->isAllowed($user, $adminMapper->getEmptyModel(), 'special')) {
			$lowerOnly = false;
		}

		$groupService->prepareMapperListingByAdmin($adminMapper, null, $lowerOnly, $filter);

		$this->_index($adminMapper);
	}

	public function autocompleteAction()
	{
	    $this->_helper->layout->disableLayout();
	    $this->_helper->viewRenderer->setNoRender();

        $field = $this->getRequest()->getParam('field', 'email');

        $term = $this->getRequest()->getParam('term', null);

        if (!$term) {
            echo $this->view->json(array());
            return;
        }

        $this->getRequest()->setParam($field, $term);

        $this->indexAction();

        $results = array();
        foreach ($this->view->paginator as $user) {
            $results[] = array('id' => $user->getUserId(),
                              'label' => $user->getFirstName() . ' ' . $user->getLastName() . '<' . $user->getEmail() . '>',
                              'value' => $user->getEmail());
        }
        echo $this->view->json($results);
	}

	protected function _getFilters()
	{
		$filter = array();

		$filter['email'] = $this->getRequest()->getParam('email', null);
		$filter['firstname'] = strtoupper($this->getRequest()->getParam('firstname', null));
		$filter['lastname'] = $this->getRequest()->getParam('lastname', null);
		$filter['city'] = $this->getRequest()->getParam('city', null);
		$filter['country'] = $this->getRequest()->getParam('country', null);

		$this->view->filter = $filter;

		foreach ($filter as $key => $value) {
			if (null == $value || empty($value)) {
				unset($filter[$key]);
				continue;
			}

			$filter[$key] = '%' . preg_replace('/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value) . '%';
		}

		return $filter;
	}

	protected function _add(Unwired_Model_Mapper $mapper = null,
							Unwired_Model_Generic $entity = null,
							Zend_Form $form = null)
	{
		if (null !== $entity && $entity->getUserId() == $this->getCurrentUser()->getUserId()) {
			$this->_helper->redirector->gotoRouteAndExit(array('module' => 'users',
															   'controller' => 'profile',
															   'action' => 'index'),
														'default',
														true);
		}

		$groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		parent::_add($mapper, $entity, $form);
	}

	public function addAction()
	{
		$this->_add();
		$this->_helper->viewRenderer->setScriptAction('edit');
	}

	public function editAction()
	{
		$form = new Users_Form_Admin();

		$form->getElement('password')->setRequired(false);
		$form->getElement('cfmpassword')->setRequired(false);
		$this->_edit(null, $form);
	}

	public function deleteAction()
	{
		$this->_delete();
		// @todo user deletion
	}
}