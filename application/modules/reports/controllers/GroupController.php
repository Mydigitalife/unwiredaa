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

class Reports_GroupController extends Unwired_Controller_Crud {

	public function init()
	{
		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addContext('csv', array(
                        				'suffix'    => 'csv',
                        				'headers'   => array('Content-Type' => 'text/csv; charset=utf-8',
                        						'Content-disposition' => 'attachment; filename='
                        						. date("Y-m-d_H-i-s") . '.csv'),
		                           ))
		              ->addContext('pdf', array(
                            				'suffix'    => 'csv',
                            				'headers'   => array(/*'Content-Type' => 'application/pdf',
                            						'Content-disposition' => 'attachment; filename='
                            						. date("Y-m-d_H-i-s") . '.pdf'*/),
                            		))
            		  ->addActionContext('view', 'csv')
            		  ->addActionContext('view', 'pdf')
            		  ->addActionContext('instant', 'csv')
            		  ->addActionContext('instant', 'pdf')
            		  ->initContext();

	    $this->_actionsToReferer[] = 'instant';

		parent::init();
	}

	public function indexAction() {
		$groupService = new Groups_Service_Group();
		$reportMapper = new Reports_Model_Mapper_Group();
		$reportCodeTemplateMapper = new Reports_Model_Mapper_CodeTemplate();

		$parentId = (int) $this->getRequest()->getParam('id');

		if (!$parentId) {
		    $this->view->uiMessage('reports_index_codetemplate_not_found');
		    $this->_helper->redirector->gotoRouteAndExit(array('module' => 'reports',
		                                                       'controller' => 'index',
		                                                       'action' => 'index'),
		                                                 'default',
		                                                 true);
		}

		$parent = $reportCodeTemplateMapper->find($parentId);
		if (!$parent) {
		    $this->view->uiMessage('reports_index_codetemplate_not_found');
		    $this->_helper->redirector->gotoRouteAndExit(array('module' => 'reports',
		                                                       'controller' => 'index',
		                                                       'action' => 'index'),
		                                                 'default',
		                                                 true);
		}
		$filter = $this->_getFilters ();

		$filter['codetemplate_id'] = $parentId;

		$groupService->prepareMapperListingByAdmin($reportMapper, null, false, $filter);
		//$reportMapper->findby(array('codetemplate_id' => $this->getRequest()->getParam('id')), 0, 'date_added DESC');

		$this->view->parent = $parent;

		$this->_index($reportMapper);
	}

	protected function _getFilters() {
		$filter = array ();

		$filter ['title'] = $this->getRequest ()->getParam ( 'title', null );
		$filter ['codetemplate_id'] = $this->getRequest ()->getParam ( 'id', null );

		$this->view->filter = $filter;

		foreach ( $filter as $key => $value ) {
			if (null == $value || empty ( $value )) {
				unset ( $filter [$key] );
				continue;
			}

			$filter [$key] = '%' . preg_replace ( '/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value ) . '%';
		}

		return $filter;
	}

	protected function _add(Unwired_Model_Mapper $mapper = null, Unwired_Model_Generic $entity = null, Zend_Form $form = null)
	{

		$groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$this->view->rootGroup = $rootGroup;

		parent::_add($mapper, $entity, $form);
	}

	public function addAction()
	{
		$codeTemplateId = (int) $this->getRequest()->getParam('id', 1);

		$entity = $this->_getDefaultMapper()->getEmptyModel();

		$entity->setCodetemplateId($codeTemplateId);

		$mapperTemplate = new Reports_Model_Mapper_CodeTemplate();

		$template = $mapperTemplate->find($codeTemplateId);
		$entity->setCodeTemplate($template);

		$entity->setDateAdded(date('Y-m-d H:i:s'));
		$entity->setRecepients($this->getRequest()->getParam('email'));

		$this->_add(null, $entity);

		$this->_helper->viewRenderer->setScriptAction('edit');
	}

	public function editAction() {
		/*
		if ($this->getRequest()->isPost()) {
			$entity->setRecepients($this->getRequest()->getParam('email'));
		}
		*/
		$this->_edit ();
	}

	public function reportsAction() {
		$reportMapper = new Reports_Model_Mapper_Group ();
		$resultMapper = new Reports_Model_Mapper_Result();

		$ctMapper = new Reports_Model_Mapper_CodeTemplate();


		$resultMapper->findBy(array('report_group_id' => $this->getRequest()->getParam('id')), 0, 'date_added DESC');

		$this->_defaultMapper = $resultMapper;

		$parent_id = $this->getRequest ()->getParam ( 'id' );

		$parent = $reportMapper->find ( $parent_id );

		$parent_parent = $ctMapper->find($parent->getCodetemplateId());

		$this->view->assign ( 'parent_parent', $parent_parent );
		$this->view->assign ( 'parent', $parent );

		$this->_index($resultMapper);
	}

	public function generateAction() {
		$ctMapper = new Reports_Model_Mapper_CodeTemplate();
		$rMapper = new Reports_Model_Mapper_Group();
		$iMapper = new Reports_Model_Mapper_Result();
		$report = $rMapper->find($this->getRequest()->getParam('id'));

		$parent = $ctMapper->find($report->getCodetemplateId());

		if (!$this->view->canGenerateManual($report)) {
		    $this->view->uiMessage('reports_group_report_cannot_generate_manual','error');

		    $this->_helper->redirector->gotoUrlAndExit('/reports/group/reports/id/'.$report->getReportGroupId());
		}
		$className = $parent->getClassName();
		$reportGenerator = new $className;

        $reportGenerator->setReportGroup($report);

        $shiftedDates = $this->_calcDateOffset($report, null);

        $report->setDateFrom($shiftedDates['from'])
               ->setDateTo($shiftedDates['to']);

		$result = $reportGenerator->getData(array_keys($report->getGroupsAssigned()),
		                                    $report->getDateFrom()->toString('yyyy-MM-dd HH:mm:ss'),
		                                    $report->getDateTo()->toString('yyyy-MM-dd HH:mm:ss'));

		$entity = new Reports_Model_Items();
		$entity->setDateAdded(date('Y-m-d H:i:s'));
		$entity->setData($result);
		$entity->setReportGroupId($this->getRequest()->getParam('id'));
		$iMapper->save($entity);

		if ($report->getRecepients()) {
		    $this->_emailReport($report, $entity);
		}

		//$this->_helper->redirector->gotoUrlAndExit('/reports/group/view/id/'.$entity->getItemId());
		$this->_helper->redirector->gotoRouteAndExit(array('module' => 'reports',
		                                                   'controller' => 'group',
		                                                   'action' => 'view',
		                                                   'id' => $entity->getItemId()),
		                                             'default',
		                                             true);
	}

	protected function _calcDateOffset($report, $referenceDate = null)
	{
        if (!$referenceDate) {
            $referenceDate = new Zend_Date();
        }

        $fromDate = $report->getDateFrom();
        $toDate = $report->getDateTo();

	  /*  if ($report->getReportType == 'interval') {
            $interval = $report->getReportInterval();

            $dateAdded = $report->getDateAdded();

            if (is_string($dateAdded)) {
                $dateAdded = new Zend_Date($dateAdded, 'yyyy-MM-dd HH:mm');
            }


            $now = $referenceDate;

            $toDate = new Zend_Date($report->getDateTo());
            $fromDate = new Zend_Date($report->getDateFrom());

            $period = $toDate->sub($fromDate);

            $offsetFromDateAdded = $dateAdded->sub($fromDate);

            switch ($interval) {
                case 'year':
                    $dateAdded->setYear($now->getYear());
                break;

                case 'month':
                    $dateAdded->setYear($now->getYear());
                    $dateAdded->setMonth($now->getMonth());
                break;

                case 'week':
                    $diffStamp = $now->getDate()
                                          ->subDate($fromDate->getDate())
                                               ->getTimestamp();

                    if (fmod($diffStamp, (7 * 24 * 3600)) == 0) {
                        $dateAdded->setDate($now);
                    }
                ;
                break;

                case 'day':
                default:
                    $dateAdded = $referenceDate;
                break;
            }


            $dateAdded->setHour($fromDate->getHour())
                      ->setMinute($fromDate->getMinute())
                      ->setSecond($fromDate->getSecond());

            $fromDate = clone $dateAdded;
            $fromDate = $fromDate->sub($offsetFromDateAdded);
            $toDate = clone $fromDate;
            $toDate->add($period);

        } else { */
            switch ($report->getTimeframe()) {
                case 'today':
                    $fromDate = $referenceDate;
                    $toDate = clone $fromDate;

                    $fromDate->setHour(0)
                             ->setMinute(0)
                             ->setSecond(0);
                    $toDate->addDay(1);
                break;
                case 'yesterday':
                    $toDate = $referenceDate;

                    $toDate->setHour(0)
                           ->setMinute(0)
                           ->setSecond(0);
                    $fromDate = clone $toDate;
                    $fromDate->subDay(1);
                break;
                case 'currweek':
                    $fromDate = $referenceDate;

                    $weekday = $fromDate->toValue(Zend_Date::WEEKDAY_DIGIT);

                    $fromDate->subDay($weekday-1);
                    $fromDate->setHour(0)
                             ->setMinute(0)
                             ->setSecond(0);

                    $toDate = clone $fromDate;
                    $toDate->addDay(7);
                break;
                case 'lastweek':
                    $toDate = $referenceDate;

                    $weekday = $toDate->toValue(Zend_Date::WEEKDAY_DIGIT);

                    $toDate->subDay($weekday-1);
                    $toDate->setHour(0)
                           ->setMinute(0)
                           ->setSecond(0);

                    $toDate = clone $fromDate;
                    $toDate->subDay(7);
                break;
                case 'currmonth':
                    $fromDate = $referenceDate;
                    $fromDate->setDay(1)
                             ->setHour(0)
                             ->setMinute(0)
                             ->setSecond(0);

                    $toDate = clone $fromDate;
                    $toDate->addMonth(1);
                break;
                case 'lastmonth':
                    $toDate = $referenceDate;
                    $toDate->setDay(1)
                           ->setHour(0)
                           ->setMinute(0)
                           ->setSecond(0);

                    $fromDate = clone $toDate;
                    $fromDate->subMonth(1);
                break;
                case 'curryear':
                    $fromDate = $referenceDate;
                    $fromDate->setMonth(1)
                             ->setDay(1)
                             ->setHour(0)
                             ->setMinute(0)
                             ->setSecond(0);

                    $toDate = clone $fromDate;
                    $toDate->addYear(1);
                break;
                case 'lastyear':
                    $toDate = $referenceDate;
                    $toDate->setMonth(1)
                           ->setDay(1)
                           ->setHour(0)
                           ->setMinute(0)
                           ->setSecond(0);

                    $fromDate = clone $toDate;
                    $fromDate->subYear(1);
                break;

                default:
                    $dateAdded = $report->getDateAdded();

                    if (is_string($dateAdded)) {
                        $dateAdded = new Zend_Date($dateAdded, 'yyyy-MM-dd HH:mm');
                    }


                    $now = $referenceDate;

                    $toDate = new Zend_Date($report->getDateTo());
                    $fromDate = new Zend_Date($report->getDateFrom());

                    $period = $toDate->sub($fromDate);

                    $offsetFromDateAdded = $dateAdded->sub($fromDate);

                    $fromDate = $now;
                    $fromDate->sub($offsetFromDateAdded);

                    $toDate = clone $fromDate;
                    $toDate->add($period);

                    $fromDate->setHour($report->getDateFrom()->getHour())
                             ->setMinute($report->getDateFrom()->getMinute())
                             ->setSecond($report->getDateFrom()->getSecond());

                    $toDate->setHour($report->getDateTo()->getHour())
                           ->setMinute($report->getDateTo()->getMinute())
                           ->setSecond($report->getDateTo()->getSecond());
                break;
            }
       /* } */

        return array('from' => $fromDate, 'to' => $toDate);
	}

	public function instantAction() {

	    $codeTemplateId = (int) $this->getRequest()->getParam ( 'id',  1);

	    $mapperCodeTemplate = new Reports_Model_Mapper_CodeTemplate();

	    $codeTemplate = $mapperCodeTemplate->find($codeTemplateId);

	    if (!$codeTemplate) {
	        $this->view->uiMessage('report_group_instant_codetemplate_notselected', 'error');
			$this->_gotoIndex();
	    }

	    if ($codeTemplate->getTimeframeLiveMax() === 0) {
	        $this->view->uiMessage('reports_group_report_cannot_generate_manual', 'error');
			$this->_gotoIndex();
	    }
	    $groupService = new Groups_Service_Group();

		$rootGroup = $groupService->getGroupTreeByAdmin();

		$report = new Reports_Model_Group();

		$report->setCodetemplateId($codeTemplate->getCodetemplateId())
		       ->setCodeTemplate($codeTemplate)
		       ->setDateAdded(date('Y-m-d H:i:s'));

		$this->view->rootGroup = $rootGroup;

		$this->view->instant = true;

	    $form = new Reports_Form_Instant(array('view' => $this->view, 'entity' => $report));

	    $this->view->form = $form;
	    $this->_helper->viewRenderer->setScriptAction('edit');

		$this->view->entity = $report;

	    if (!$this->getRequest()->isPost() && !$this->getRequest()->getParam('groups_assigned')) {
	        /*$date = new Zend_Date();
	        $date->setDay(1);
	        $form->getElement('date_from')->setValue($date->toString(Zend_Date::DATETIME_SHORT));

	        $date = new Zend_Date();
	        $date->addMonth(1)
	             ->subDay(1);

	        $form->getElement('date_to')->setValue($date->toString(Zend_Date::DATE_SHORT));*/

	        return;
	    }

	    $groupsAssigned = $this->getRequest()->getParam('groups_assigned');

	    if (is_string($groupsAssigned)) {
	        $groupsAssigned = array($groupsAssigned => $groupsAssigned);
	        $this->getRequest()->setParam('groups_assigned', $groupsAssigned);
	    }

	    if (!$form->isValid($this->getRequest()->getParams())) {
	            try {
					$report->fromArray($form->getValues());
				} catch (Exception $e) {
					// nothing
				}
	        return;
	    }

	    $report->fromArray($form->getValues());
        $report->setRecepients($this->getRequest()->getParam('email',''));
	    $report->setTitle('Instant report');

	    $groupsAssigned = $report->getGroupsAssigned();

	    if (!$this->getRequest()->isPost()) {
	        $groupsAssigned = array_combine($groupsAssigned, $groupsAssigned);
	    }

	    foreach ($groupsAssigned as $groupId => $value) {
	        $group = $groupService->findGroup($groupId);
	        $groupsAssigned[$groupId] = $group;
	    }

	    $report->setGroupsAssigned($groupsAssigned);

		$className = $codeTemplate->getClassName();
		$reportGenerator = new $className;

		$reportGenerator->setReportGroup($report);

		$result = $reportGenerator->getData(array_keys($report->getGroupsAssigned()),
		                                    $report->getDateFrom()->toString('yyyy-MM-dd HH:mm:ss'),
		                                    $report->getDateTo()->toString('yyyy-MM-dd HH:mm:ss'));

		$items = new Reports_Model_Items();
		$items->setDateAdded(date('Y-m-d H:i:s'));
		$items->setData($result);
		$items->setReportGroupId($codeTemplate->getCodetemplateId());

		if ($this->getRequest()->isPost() && $report->getRecepients()) {
		    $this->_emailReport($report, $items);
		}

		$this->view->parent_parent = $codeTemplate;
		$this->view->parent = $report;

		$this->view->report = $items;

		$this->view->data = $items->getData(true);
		$this->_helper->viewRenderer->setScriptAction('view');

		$this->_exportReportData($report, $items);
	}

	public function viewAction() {

		$rMapper = new Reports_Model_Mapper_Result();
		$report = $rMapper->find($this->getRequest()->getParam('id'));
		$ctMapper = new Reports_Model_Mapper_CodeTemplate();


		$gMapper = new Reports_Model_Mapper_Group();
		$parent = $gMapper->find($report->getReportGroupId());
		$parent_parent = $ctMapper->find($parent->getCodetemplateId());

		$this->view->parent_parent = $parent_parent;
		$this->view->parent = $parent;

		$shiftedDates = $this->_calcDateOffset($parent, new Zend_Date($report->getDateAdded(), 'yyyy-MM-dd HH:mm'));

		$parent->setDateFrom($shiftedDates['from'])
		       ->setDateTo($shiftedDates['to']);

		$this->view->report = $report;

		$this->view->data = $report->getData(true);
//Zend_Debug::dump($this->view->data); die();
		$this->_exportReportData($parent, $report);
	}

	protected function _exportReportData(Reports_Model_Group $reportGroup, Reports_Model_Items $reportData)
	{
	    $filename = str_replace(' ', '_', $reportGroup->getTitle()) . '_' . str_replace(array(' ', '-'), '_', $reportData->getDateAdded())
			        . '.' . $this->_helper->contextSwitch->getCurrentContext();

		if ($this->_helper->contextSwitch->getCurrentContext() == 'csv'
		    || $this->_helper->contextSwitch->getCurrentContext() == 'pdf') {
			$this->getResponse()->setHeader('Content-disposition',
					"attachment; filename=" . str_replace(' ', '_', $reportGroup->getTitle()) . '_' . str_replace(array(' ', '-'), '_', $reportData->getDateAdded())
			        . '_' . rand(1,10000) . '.' . $this->_helper->contextSwitch->getCurrentContext(),
					true);
		}

		if (file_exists(PUBLIC_PATH . '/data/reports/' . $filename)) {
		    $this->_helper->viewRenderer->setNoRender();
		    $this->_helper->layout->disableLayout();

		    echo file_get_contents(PUBLIC_PATH . '/data/reports/' . $filename);
		    return;
		}

		if ($this->_helper->contextSwitch->getCurrentContext() == 'pdf') {
		    $this->_helper->viewRenderer->setNoRender();
		    $this->_helper->layout->disableLayout();

		    $html = $this->view->render('group/view.pdf.phtml');

		    if (!class_exists('DOMPDF')) {
                require_once('dompdf/dompdf_config.inc.php');
                $autoloader = Zend_Loader_Autoloader::getInstance();
                $autoloader->pushAutoloader('DOMPDF_autoload', '');
		    }

            $dompdf = new DOMPDF();
            $dompdf->set_paper("a4","portrait");
            $dompdf->load_html($html);
            $dompdf->set_base_path(PUBLIC_PATH);
            $dompdf->render();
            $dompdf->stream(PUBLIC_PATH . '/data/reports/' . $filename);
            echo $dompdf->output();
		}
	}

	public function deleteAction()
	{
		$this->_delete();
	}

	public function deleteresultAction()
	{
		$rMapper = new Reports_Model_Mapper_Result();

		if (!$this->getAcl()->isAllowed($this->_currentUser, $rMapper->getEmptyModel(), 'delete')) {
			$this->view->uiMessage('access_not_allowed_delete', 'error');
			$this->_setAutoRedirect(true);
			$this->_gotoIndex();
		}

		$id = (int) $this->getRequest()->getParam('id');
		$entity = $rMapper->find($id);
		$rMapper->delete($entity);


		$this->_helper->redirector->gotoRouteAndExit(array( 'module' => $this->getRequest()->getParam('module'),
															'controller' => $this->getRequest()->getParam('controller'),
															'action' => 'reports',
															'id' => $entity->getReportGroupId()), 'default', true);

	}

    protected function _emailReport(Reports_Model_Group $report, Reports_Model_Items $result)
    {
        $recepients = $report->getRecepients();

        if (!is_array($recepients)) {
            $recepients = explode(',', $recepients);
        }

        $view = $this->view;

        try {
            $view->report = $result;
            $view->reportGroup = $report;

            $csv = $view->render('group/view.csv.phtml');

            if (!$csv) {
                return false;
            }

            $mailer = new Zend_Mail();

            $at = new Zend_Mime_Part($csv);
            $at->type        = 'text/csv';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = str_replace(' ', '_', 'Reports_' . $report->getTitle() . '_' . $result->getDateAdded() . '.csv');

            $mailer->addAttachment($at);

            $mailer->setSubject($view->systemName . ' Report: ' . $report->getTitle() . ' ' . $result->getDateAdded());

            $mailBody = $view->render('group/report-email.phtml');
            $mailer->setBodyText($mailBody, 'utf-8');

            foreach ($recepients as $to) {
                $mailer->clearRecipients()
                       ->addTo($to)
                       ->send();
            }
        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') {
                Unwired_Exception::getLog()->log($e->getMessage(), Zend_Log::ALERT);
                Unwired_Exception::getLog()->log($e->getTraceAsString(), Zend_Log::DEBUG);
            }
            return false;
        }

        return true;
    }

}