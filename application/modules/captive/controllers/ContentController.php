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

class Captive_ContentController extends Unwired_Controller_Crud
{
    protected $_actionsToReferer = array('template', 'splashpage', 'delete', 'files', 'upload');

    protected $_defaultMapper = 'Captive_Model_Mapper_Content';

	public function splashpageAction()
	{
	    if (!$this->getAcl()->isAllowed($this->_currentUser, 'captive_splashpage', 'edit')
	        || !$this->getAcl()->isAllowed($this->_currentUser, 'captive_content', 'edit')) {
			$this->view->uiMessage('access_not_allowed_view', 'error');
			$this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
		}

        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->_gotoIndex();
        }

        /**
         * Get the splashpage
         */
        $mapperSplash = new Captive_Model_Mapper_SplashPage();

        $splashPage = $mapperSplash->find($id);

        if (!$splashPage) {
            $this->_gotoIndex();
        }

        $mapperSplash = null;

        $settings = $splashPage->getSettings();

        $serviceSplashPage = new Captive_Service_SplashPage();

        /**
         * Get template languages and language content
         */

        $mapperLanguages = new Captive_Model_Mapper_Language();

        $languages = $mapperLanguages->findBy(array('language_id' => $settings['language_ids']));

        $languagesSorted = array();
        $uiLanguage = null;

        $translate = Zend_Registry::get('Zend_Translate');

        foreach ($languages as $language) {
            if ($language->getCode() == $translate->getAdapter()->getLocale()) {
                $uiLanguage = $language;
            }
            $languagesSorted[$language->getLanguageId()] = $language;
        }

        if (null === $uiLanguage) {
            $uiLanguage = $languages[0];
        }

        $languages = null;
        $this->view->languages = $languagesSorted;
        $this->view->uiLanguage = $uiLanguage;

        $mapperLanguages = null;

        $this->view->entity = $splashPage;
        $this->view->splashPage = $splashPage;
        $this->view->template = $splashPage->getTemplate();

        /**
         * We don't need content anymore. Layouts content will be loaded via AJAX calls
         */
        // $contents = $serviceSplashPage->getSplashPageContents($splashPage);
        // $this->view->contents = $contents;
        $this->_helper->viewRenderer->setScriptAction('contents');
	}

	/**
	 * Returns a json representation of the layout content
	 *
	 * @throws Unwired_Exception
	 */
	public function layoutContentAction()
	{
        $this->_helper->layout->disableLayout();

        $layoutId = (int) $this->getRequest()->getParam('id', 0);

        if (!$layoutId) {
            throw new Unwired_Exception('Invalid layout ID requested');
        }

        $mapperLayout = new Captive_Model_Mapper_Layout();

        $layout = $mapperLayout->find($layoutId);

        if (!$layout) {
            throw new Unwired_Exception('Invalid layout ID requested');
        }

        $splashId = (int) $this->getRequest()->getParam('splashId', 0);
        $templateId = (int) $this->getRequest()->getParam('templateId', 0);

        $serviceSplash = new Captive_Service_SplashPage();

        $layoutContent = $serviceSplash->getLayoutContentSortedArray($layout, $splashId, $templateId);

        echo $this->view->json($layoutContent);
        $this->_helper->viewRenderer->setNoRender();
	}

	public function reorderAction()
	{
	    $this->_helper->layout->disableLayout();
	    $this->_helper->viewRenderer->setNoRender();

        $desktop = $this->getRequest()->getParam('desktop', null);
        $mobile = $this->getRequest()->getParam('mobile', null);

        $splashId = (int) $this->getRequest()->getParam('splashId', 0);
        $templateId = (int) $this->getRequest()->getParam('templateId', 0);

        if (!$desktop || !$mobile || !is_array($desktop) || !is_array($mobile)) {
            throw new Unwired_Exception('Invalid parameters');
        }

        $serviceSplash = new Captive_Service_SplashPage();

        if (!$serviceSplash->updateOrder($desktop, $mobile, (!$splashId) ? true : false, $splashId, $templateId)) {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->json(array('error'=>'Error updating widget order'));
        } else {
            echo $this->view->json(array('success'=>'Widget order updated'));
        }
	}

	public function deleteAction()
	{
	    $this->_delete();
	}

	public function addAction(Captive_Model_Content $content = null)
	{
	    if ($this->getRequest()->isXmlHttpRequest()) {
	        $this->_helper->layout()->disableLayout();
	    }

        $templateId = (int) $this->getRequest()->getParam('templateId', 0);
        $splashId = (int) $this->getRequest()->getParam('splashId', 0);

        $layoutId = (int) $this->getRequest()->getParam('layoutId', 0);
        $widget = $this->getRequest()->getParam('widget', 'Html');
        $contentType = $this->getRequest()->getParam('type', 'content');

        if (!$content) {
            $column = (int) $this->getRequest()->getParam('column', 0);
        } else {
            $column = $content->getColumn();
        }

        if (!in_array($contentType, array('content', 'terms', 'imprint'))) {
            $contentType = 'content';
        }

        $widget = ucfirst($widget);
        if (!in_array($widget, array('Html','Links','Iframe','Login'))) {
            throw new Unwired_Exception('Invalid widget specified', 500);
        }

        if (!$templateId && !$splashId) {
            throw new Unwired_Exception('No template or splashpage specified', 500);
        }

        if (!$layoutId) {
            throw new Unwired_Exception('No layout specified', 500);
        }

        $splashPage = null;
        $template = null;

        if ($splashId) {
            $mapperSplash = new Captive_Model_Mapper_SplashPage();
            $splashPage = $mapperSplash->find($splashId);
            if ($splashPage) {
                $template = $splashPage->getTemplate();
            }
        } else {
            $mapperTemplate = new Captive_Model_Mapper_Template();
            $template = $mapperTemplate->find($templateId);
        }

        if (!$template) {
            throw new Unwired_Exception('No template or splashpage specified', 500);
        }

        /**
         * @todo Move this to mapper or service?
         */
        $templateSettings = $template->getSettings();
        if (!$content) {
            $content = new Captive_Model_Content();

            foreach ($templateSettings['language_ids'] as $language) {
                $contentData = new Captive_Model_ContentData();
                $contentData->setLanguageId($language);
                $content->addData($contentData);

                //$mobileContentData = clone $contentData;
                //$mobileContentData->setMobile(1);

                //$content->addData($mobileContentData);
            }
            $content->setWidget($widget)
                    ->setType($contentType);

        } else {
            // Prefill missing languages?!
        }

        if ($splashPage) {
            $content->setSplashId($splashId);
        } else {
            $content->setTemplateId($templateId);
        }


        $content->setColumn($column)
                ->setLayoutId($layoutId);

        $mapperLanguages = new Captive_Model_Mapper_Language();

        $languages = $mapperLanguages->findBy(array('language_id' => $templateSettings['language_ids']));

        $languagesSorted = array();

        foreach ($languages as $language) {
            $languagesSorted[$language->getLanguageId()] = $language;
        }

        $this->view->languages = $languagesSorted;

        $this->view->content = $content;
        $this->view->splashPage = $splashPage;
        $this->view->template = $template;

        $this->_helper->viewRenderer->setScriptAction('edit');

        if (!$this->getRequest()->isPost()) {
            return;
        }

        /**
         * Process posted data
         */
        $postedContentData = $this->getRequest()->getPost('content', null);
        if (!$postedContentData) {
            throw new Unwired_Exception('No content provided');
        }


        $content->setOrder((int) $this->getRequest()->getParam('order', 1));
        $content->setEditable((int) $this->getRequest()->getParam('editable', $content->isEditable()));
        $content->setRestricted((int) $this->getRequest()->getParam('restricted', $content->isRestricted()));

        $contentModified = array();

        $contentData = $content->getData();

        foreach ($postedContentData as $languageId => $postedData) {
            $postedData['language_id'] = $languageId;

            $foundContent = null;

            foreach ($contentData as $key => $data) {
                if ($data->getLanguageId() != $languageId) {
                    continue;
                }

                $foundContent = $data;
                unset($contentData[$key]);
            }

            if (!$foundContent) {
                $foundContent = new Captive_Model_ContentData();
                $foundContent->setLanguageId($languageId);
            }

            $foundContent->fromArray($postedData);
            $contentModified[] = $foundContent;
        }


        $postedContentData = null;
        $contentData = null;
        $mobileContent = null;
        $desktopContent = null;
//        $contentModified = $contentModified + $contentData;

        $content->setData($contentModified);

        $mapperContent = new Captive_Model_Mapper_Content();
        $mapperContent->save($content);
	}

	public function editAction()
	{
	    $id = (int) $this->getRequest()->getParam('id', 0);

	    if (!$id) {
	        throw new Unwired_Exception('Content not found');
	    }

	    $mapperContent = new Captive_Model_Mapper_Content();
	    $content = $mapperContent->find($id);

	    if (!$content) {
	        throw new Unwired_Exception('Content not found');
	    }

	    if ($this->getRequest()->isPost()) {
    	    $splashId = (int) $this->getRequest()->getParam('splashId', 0);

    	    if ($content->getTemplateId() && $splashId) {
                $content->setTemplateContent($content->getContentId())
                        ->setSplashId($splashId)
                        ->setTemplateId(null)
                        ->setContentId(null);
    	    }
	    }

	    $this->addAction($content);
	}


    public function templateAction()
    {
    	if (!$this->getAcl()->isAllowed($this->_currentUser, 'captive_template', 'edit')
    	    || !$this->getAcl()->isAllowed($this->_currentUser, 'captive_content', 'edit')) {
			$this->view->uiMessage('access_not_allowed_view', 'error');
			$this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
		}

        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->_gotoIndex();
        }

        /**
         * Get the template
         */
        $mapperTemplate = new Captive_Model_Mapper_Template();

        $template = $mapperTemplate->find($id);

        if (!$template) {
            $this->_gotoIndex();
        }

        $mapperTemplate = null;

        $settings = $template->getSettings();

        /**
         * Get template languages
         */
        $mapperLanguages = new Captive_Model_Mapper_Language();

        $languages = $mapperLanguages->findBy(array('language_id' => $settings['language_ids']));

        $languagesSorted = array();

        $uiLanguage = null;

        $translate = Zend_Registry::get('Zend_Translate');

        foreach ($languages as $language) {
            if ($language->getCode() == $translate->getAdapter()->getLocale()) {
                $uiLanguage = $language;
            }
            $languagesSorted[$language->getLanguageId()] = $language;
        }

        if (null === $uiLanguage) {
            $uiLanguage = $languages[0];
        }

        $languages = null;
        $this->view->languages = $languagesSorted;
        $this->view->uiLanguage = $uiLanguage;

        $mapperLanguages = null;

        $serviceSplashPage = new Captive_Service_SplashPage();

        /**
         * Try to save contents
         */
        if ($this->getRequest()->isPost())
        {
            $contents = $this->getRequest()->getPost('content');
            if (!empty($contents) && is_array($contents)) {
                try {
                    $serviceSplashPage->saveTemplateContents($template, $contents);
                    $this->view->uiMessage('captive_content_template_content_saved', 'success');
                    $this->_gotoIndex();
                } catch (Exception $e) {
                    $this->view->uiMessage('captive_content_template_content_error', 'error');
                }
            } else {
                $this->view->uiMessage('captive_content_template_no_content_provided', 'error');
            }
        }

        /**
         * We don't need content anymore. Layouts content will be loaded via AJAX calls
         */
        // $contents = $serviceSplashPage->getTemplateContent($template);
        // $this->view->contents = $contents;

        $this->view->entity = $template;
        $this->view->template = $template;

        $this->_helper->viewRenderer->setScriptAction('contents');
    }


    /**
     * Get file list for splashpage/template
     */
    public function filesAction()
    {
        $this->_helper->layout->disableLayout();

        $splashId = (int) $this->getRequest()->getParam('splash', 0);

        if (!$splashId) {
            $id = (int) $this->getRequest()->getParam('template', 0);
            $mapper = new Captive_Model_Mapper_Template();

        } else {
            $id = $splashId;
            $mapper = new Captive_Model_Mapper_SplashPage();
        }

        if (!$id) {
            return;
        }

        $entity = $mapper->find($id);

        if (!$entity) {
            return;
        }

        /**
         * Get specific command for file operation
         */
        $command = $this->getRequest()->getParam('cmd', 'list');

        /**
         * Get file on which we are going to do the operation.
         * It might be null if we are listing files
         */
        $file = $this->getRequest()->getParam('file', null);

        $serviceFiles = new Captive_Service_Files();

        $files = array();

        switch ($command) {
            case 'delete':
                if (!$file) {
                    return;
                }
                $result = $serviceFiles->deleteFile($file, $entity);

                if ($result) {
                    $files[] = array('name' => $file,
                             		 'path' => '',
                                     'deleted' => 1);
                }
            break;

            case 'rename':
                if (!$file) {
                    return;
                }

                $newFile = trim($this->getRequest()->getParam('new_name', null));
                if ($newFile) {
                     $result = $serviceFiles->renameFile($file, $newFile, $entity);

                     if ($result) {
                         $files[] = array('name' => $result,
                             		      'old_name' => $file,
                                          'renamed' => 1);
                     }
                }
            break;

            default:
                $files = $serviceFiles->getFiles($entity);
            break;
        }

        $this->view->files = $files;
    }

    /**
     * Handle file upload for a splashpage/template files
     */
    public function uploadAction()
    {
        $this->_helper->layout->disableLayout();

        /**
         * Allow only post to hit this action/controller
         */
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $serviceFiles = new Captive_Service_Files();

        $splashId = (int) $this->getRequest()->getParam('splash', 0);

        $path = $serviceFiles->getSplashPagePath($splashId);

        if (!$splashId) {
            $templateId = (int) $this->getRequest()->getParam('template', 0);
            $path = $serviceFiles->getTemplatePath($templateId);
        }

        if (!$splashId && !$templateId) {
            $this->view->uploadError = 'content_upload_error_no_destination';
            return;
        }

        $upload = new Zend_File_Transfer();

        $upload->setDestination($path);

        // Returns all known internal file information
        $files = $upload->getFileInfo();

        foreach ($files as $file => $info) {
            // file uploaded ?
            if (!$upload->isUploaded($file)) {
                $this->view->uploadError = 'content_upload_error_no_file';
                return;
            }

            // validators are ok ?
            if (!$upload->isValid($file)) {
                $this->view->uploadError = 'content_upload_error_invalid_file';
                return;
            }
        }

        $upload->receive();

        if (!$serviceFiles->copyToSplashpages($files)) {
            $this->view->uploadError = 'content_upload_error_replicate_file';
        }
    }
}