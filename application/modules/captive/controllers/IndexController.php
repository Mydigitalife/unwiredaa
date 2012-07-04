<?php

class Captive_IndexController extends Unwired_Controller_Crud
{
    protected $_defaultMapper = 'Captive_Model_Mapper_SplashPage';

    public function init()
    {
        $this->_actionsToReferer[] = 'group-templates';

        parent::init();
    }

    public function indexAction()
    {
        $groupService = new Groups_Service_Group();

        $splashMapper = $groupService->prepareMapperListingByAdmin($this->_getDefaultMapper(), null, false);

        $this->_index($splashMapper);
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
		     * Redirect to splash page content editing on update
		     */
		    if ($entity && $entity->getSplashId()) {
                $this->_referer = $this->view->serverUrl($this->_helper->url->url(array('module' => 'captive',
                                                            'controller' => 'content',
                                                            'action' => 'splashpage',
                                                            'id' => $entity->getSplashId()),
                									  'default',
                                                      true));
		    } else {
		    /**
		     * Redirect to splash page content editing on add
		     */
		        $this->_autoRedirect = false;

		        if (!$entity) {
		            $entity = new Captive_Model_SplashPage();

		            if (!$this->getAcl()->isAllowed($this->_currentUser, $entity, 'add')) {
            			$this->view->uiMessage('access_not_allowed_add', 'error');
            			$this->_setAutoRedirect(true);
            			$this->_gotoIndex();
            			return false;
        			}
		        }

		        $result = parent::_add($mapper, $entity, $form);

		        if (!$result) {
		            return;
		        }

		        $this->_autoRedirect = true;

		        $this->_referer = $this->view->serverUrl($this->_helper->url->url(array('module' => 'captive',
                                                            'controller' => 'content',
                                                            'action' => 'splashpage',
                                                            'id' => $entity->getSplashId()),
                									  'default',
                                                      true));
                $this->_gotoIndex();
		    }
		}

		return parent::_add($mapper, $entity, $form);
    }

    public function addAction()
    {
        $this->_add(null, null, new Captive_Form_SplashPage());
        $this->_helper->viewRenderer->setScriptAction('edit');
    }

    public function editAction()
    {
        $this->_edit(null, new Captive_Form_SplashPage());
    }

    public function deleteAction()
    {
        parent::_delete();
    }

    public function groupTemplatesAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $id = $this->getRequest()->getParam('id');
        header('Content-type: application/json');

        if (!$id) {
            echo $this->view->json(array());
            return;
        }

        $serviceSplash = new Captive_Service_SplashPage();

        $templates = $serviceSplash->getGroupTemplates($id);

        $templateArray = array();

        foreach ($templates as $template) {
            $templateArray[] = array('id'   => $template->getTemplateId(),
                                     'name' => $template->getName());
        }

        echo $this->view->json($templateArray);
    }
}