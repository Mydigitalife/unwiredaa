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
 *
 * @author B. Krastev <bkrastev@web-teh.net>
 */

class Reports_Job_GenerateReport {

    protected $_view = null;

    public function getView()
    {
        if (null === $this->_view) {
            $this->setView(Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view);
        }

        return $this->_view;
    }

    public function setView(Zend_View_Interface $view)
    {
        $this->_view = $view;

        $this->_view->addBasePath(APPLICATION_PATH . '/modules/reports/views', 'Reports_View')
                   /* ->setScriptPath(APPLICATION_PATH . '/report/views/scripts')*/;
        return $this;
    }

    public function run()
    {
        $reports = $this->getPendingReports();

        $success = 0;
        $emailTotal = 0;
        $emailSuccess = 0;

        foreach ($reports as $report) {
            $result = $this->generateReport($report);
            if (!$result) {
                Unwired_Exception::getLog()->log("Cannot generate report (ID:{$report->getReportGroupId()})", Zend_Log::WARN);
                continue;
            }

            $success++;

            if (!$report->getRecepients()) {
                continue;
            }

            $emailTotal++;

            if ($this->_emailReport($report, $result)) {
                $emailSuccess++;
            }
        }

        echo "Total: " . count($reports) . "; Generated: {$success}; Failed: " . (count($reports) - $success) . "\n";
        echo "Total emails: {$emailTotal}; Sent: {$emailSuccess}; Failed: " . ($emailTotal - $emailSuccess) . "\n";
    }

    public function getPendingReports()
    {
        $reportGroupMapper = new Reports_Model_Mapper_Group();

        $periodicalReports = $reportGroupMapper->findBy(array('report_type' => 'interval'));

        $pendingReports = array();

        $serviceReports = new Reports_Service_Reports();

        /**
         * Loop over pending periodical reports
         */
        foreach ($periodicalReports as $report) {
            $interval = $report->getReportInterval();

            $now = new Zend_Date();

            switch ($interval) {
                case 'year':
                    if ((int) $now->toString(Zend_Date::DAY) !== 1 || (int) $now->toString(Zend_Date::MONTH) !== 1) {
                        continue;
                    }
                    $timeframe = 'lastyear';
                break;

                case 'quarter':
                    $month = (int) $now->toString(Zend_Date::MONTH);

                    if ((int) $now->toString(Zend_Date::DAY) !== 1) {
                        continue;
                    }

                    if (!in_array((int) $now->toString(Zend_Date::MONTH), array(1,4,7,10))) {
                        continue;
                    }
                    $timeframe = 'lastquarter';
                break;

                case 'month':
                    if ((int)$now->toString(Zend_Date::DAY) !== 1) {
                        continue;
                    }
                    $timeframe = 'lastmonth';
                break;

                case 'week':
                    if ((int) $now->getWeekday()->toString(Zend_Date::WEEKDAY_DIGIT) !== 1) {
                        continue;
                    }
                    $timeframe = 'lastweek';
                break;

                case 'day':
                default:
                    /**
                     * Assumed that the report job will be ran once daily
                     */
                    $timeframe = 'yesterday';
                break;
            }

            $shiftedDates = $serviceReports->calculateTimeframeOffset($report);

            $report->setDateFrom($shiftedDates['from'])
                   ->setDateTo($shiftedDates['to']);

            $pendingReports[] = $report;
        }

        return $pendingReports;
    }

    public function generateReport(Reports_Model_Group $report)
    {
        try {
            $codeTemplateMapper = new Reports_Model_Mapper_CodeTemplate();
    		$codeTemplate = $codeTemplateMapper->find($report->getCodetemplateId());

    		if (!$codeTemplate || !class_exists($codeTemplate->getClassName())) {
    		    return false;
    		}

    		$className = $codeTemplate->getClassName();

    		$reportGenerator = new $className;
            $reportGenerator->setReportGroup($report);

    		$result = $reportGenerator->getData(array_keys($report->getGroupsAssigned()),
		                                        $report->getDateFrom()->toString('yyyy-MM-dd HH:mm:ss'),
		                                        $report->getDateTo()->toString('yyyy-MM-dd HH:mm:ss'));

    		$resultMapper = new Reports_Model_Mapper_Result();
    		$entity = $resultMapper->getEmptyModel();

    		$entity->setDateAdded(date('Y-m-d H:i:s'));
    		$entity->setDateFrom(clone $report->getDateFrom());
    		$entity->setDateTo(clone $report->getDateTo());
    		$entity->setData($result);
    		$entity->setReportGroupId($report->getReportGroupId());

    		$resultMapper->setEventsDisabled()
    		             ->save($entity);

    		return $entity;
        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') {
                Unwired_Exception::getLog()->log($e->getMessage(), Zend_Log::ALERT);
                Unwired_Exception::getLog()->log($e->getTraceAsString(), Zend_Log::DEBUG);
            }
            return false;
        }
    }

    protected function _emailReport(Reports_Model_Group $report, Reports_Model_Items $result)
    {
        $recepients = $report->getRecepients();

        if (!is_array($recepients)) {
            $recepients = explode(',', $recepients);
        }

        $view = $this->getView();

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