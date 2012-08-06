<?php

class Captive_TemplateController extends Unwired_Controller_Crud
{
    protected $_defaultMapper = 'Captive_Model_Mapper_Template';

    protected $_actionsToReferer = array('view', 'add', 'edit', 'delete', 'copy');

    public function indexAction()
    {
        $groupService = new Groups_Service_Group();

        $rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

        $filters = $this->_getFilters();

        $this->view->filters = $filters;

        $group = null;

        if (isset($filters['group_id']) && $filters['group_id'] > 1) {

            $groupMapper = new Groups_Model_Mapper_Group();

	    	$group = $groupMapper->find($filters['group_id']);
        }

        if ($group) {
            /**
             * Filter by group
             */
            try {
                $templateMapper = $groupService->prepareMapperListing($group, $this->_getDefaultMapper(), null, false, $filters);
                $this->view->group = $group;
            } catch (Exception $e) {
                /**
                 * Probably the admin has no access to that group so list all his groups
                 */
                $templateMapper = $groupService->prepareMapperListingByAdmin($this->_getDefaultMapper(), null, false, $filters);
                $this->view->filters['group_id'] = null;
            }
        } else {
            /**
             * Plain listing of all templates for all groups for this admin
             */
            $templateMapper = $groupService->prepareMapperListingByAdmin($this->_getDefaultMapper(), null, false, $filters);
        }

        $this->_index($templateMapper);
    }

    protected function _add(Unwired_Model_Mapper $mapper = null,
							Unwired_Model_Generic $entity = null,
							Zend_Form $form = null)
    {
        $groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		if ($this->getRequest()->isPost() && $this->getRequest()->getParam('form_element_submit_edit')) {
		    /**
		     * Redirect to template content editing on update
		     */
		    if ($entity && $entity->getTemplateId()) {
                $this->_referer = $this->view->serverUrl($this->_helper->url->url(array('module' => 'captive',
                                                            'controller' => 'content',
                                                            'action' => 'template',
                                                            'id' => $entity->getTemplateId()),
                									  'default',
                                                      true));
		    } else {
		    /**
		     * Redirect to template content editing on add
		     */
		        $this->_autoRedirect = false;

		        $result = parent::_add($mapper, $entity, $form);

		        if (!$result) {
		            return;
		        }

		        $this->_autoRedirect = true;

		        $this->_referer = $this->view->serverUrl($this->_helper->url->url(array('module' => 'captive',
                                                            'controller' => 'content',
                                                            'action' => 'template',
                                                            'id' => $entity->getTemplateId()),
                									  'default',
                                                      true));
                $this->_gotoIndex();
		    }
		}

		return parent::_add($mapper, $entity, $form);
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
    }

    public function copyAction()
    {
		if (!$this->getAcl()->isAllowed($this->_currentUser, $this->_getDefaultMapper()->getEmptyModel(), 'add')) {
			$this->view->uiMessage('access_not_allowed_add', 'error');
			$this->_gotoIndex();
		}

		$this->_edit();

		$serviceSplashPage = new Captive_Service_SplashPage();

		try {
		    $newTemplate = $serviceSplashPage->copyTemplate($this->view->entity);
		    $this->view->uiMessage($this->view->translate('captive_template_copy_success', $newTemplate->getName()), 'success');
		} catch (Exception $e) {
		    $this->view->uiMessage('captive_template_copy_error', 'error');
		    if (APPLICATION_ENV == 'development') {
		        $this->view->uiMessage($e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage(), 'warning');
		    }
		}

	    $this->_gotoIndex();
    }

	protected function _getFilters()
	{
		$filter = array();

		$filter['title'] = $this->getRequest()->getParam('title', null);
		$filter['group_id'] = (int) $this->getRequest()->getParam('group_id', 0);

		$this->view->filter = $filter;

		foreach ($filter as $key => $value) {
			if (null == $value || (!is_numeric($value) && empty($value))) {
				unset($filter[$key]);
				continue;
			}

			if (!is_numeric($filter[$key])) {
			    $filter[$key] = '%' . preg_replace('/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value) . '%';
			}
		}

		return $filter;
	}
}