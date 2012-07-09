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

class Reports_Service_Reports
{

    /**
     * Calculates the neccessary dates offset for next report generation
     *
     * @param Reports_Model_Group $report
     * @param Zend_Date $referenceDate
     * @return array Array with two Zend_Date objects 'from' and 'to'
     */
    public function calculateTimeframeOffset(Reports_Model_Group $report, Zend_Date $referenceDate = null)
	{
        if (!$referenceDate) {
            $referenceDate = new Zend_Date();
        }

        $fromDate = clone $report->getDateFrom();
        $toDate = clone $report->getDateTo();


        /**
         * Adjust report interval to match timeframe as report is generated manually
         */
	    if ($report->getReportType() == 'interval') {
            $reportInterval = $report->getReportInterval();

            switch ($reportInterval) {
                case 'week':
                    $timeframe = 'lastweek';
                break;

                case 'month':
                    $timeframe = 'lastmonth';
                break;

                case 'quarter':
                    $timeframe = 'lastquarter';
                break;

                case 'year':
                    $timeframe = 'lastyear';
                break;

                default:
                    $timeframe = 'yesterday';
                break;
            }
	    } else {
	        $timeframe = $report->getTimeframe();
	    }

	    /**
	     * Adjust dates to match the selected timeframe relative to current date and time
	     */
        switch ($timeframe) {
            case 'today':
                $fromDate = clone $referenceDate;
                $toDate = clone $fromDate;

                $fromDate->setHour(0)
                         ->setMinute(0)
                         ->setSecond(0);
                $toDate->addDay(1);
            break;
            case 'yesterday':
                $toDate = clone $referenceDate;

                $toDate->setHour(0)
                       ->setMinute(0)
                       ->setSecond(0);
                $fromDate = clone $toDate;
                $fromDate->subDay(1);
            break;
            case 'currweek':
                $fromDate = clone $referenceDate;

                $weekday = $fromDate->toValue(Zend_Date::WEEKDAY_DIGIT);

                $fromDate->subDay($weekday-1);
                $fromDate->setHour(0)
                         ->setMinute(0)
                         ->setSecond(0);

                $toDate = clone $fromDate;
                $toDate->addDay(7);
            break;
            case 'lastweek':
                $toDate = clone $referenceDate;

                $weekday = $toDate->toValue(Zend_Date::WEEKDAY_DIGIT);

                $toDate->subDay($weekday-1);
                $toDate->setHour(0)
                       ->setMinute(0)
                       ->setSecond(0);

                $fromDate = clone $toDate;
                $fromDate->subDay(7);
            break;
            case 'currmonth':
                $fromDate = clone $referenceDate;
                $fromDate->setDay(1)
                         ->setHour(0)
                         ->setMinute(0)
                         ->setSecond(0);

                $toDate = clone $fromDate;
                $toDate->addMonth(1);
            break;
            case 'lastmonth':
                $toDate = clone $referenceDate;
                $toDate->setDay(1)
                       ->setHour(0)
                       ->setMinute(0)
                       ->setSecond(0);

                $fromDate = clone $toDate;
                $fromDate->subMonth(1);
            break;
            case 'currquarter':
                $fromDate = clone $referenceDate;
                $monthNum = (int) $fromDate->toString(Zend_Date::MONTH);

                $fromDate->setDay(1)
                         ->setHour(0)
                         ->setMinute(0)
                         ->setSecond(0);

                if ($monthNum < 4) {
                    $fromDate->setMonth(1);
                } else if ($monthNum < 7) {
                    $fromDate->setMonth(4);
                } else if ($monthNum < 10) {
                    $fromDate->setMonth(7);
                } else {
                    $fromDate->setMonth(10);
                }

                $toDate = clone $fromDate;
                $toDate->addMonth(3);

            break;
            case 'lastquarter':
                $toDate = clone $referenceDate;
                $monthNum = (int) $toDate->toString(Zend_Date::MONTH);

                $toDate->setDay(1)
                       ->setHour(0)
                       ->setMinute(0)
                       ->setSecond(0);

                if ($monthNum < 4) {
                    $toDate->setMonth(1);
                } else if ($monthNum < 7) {
                    $toDate->setMonth(4);
                } else if ($monthNum < 10) {
                    $toDate->setMonth(7);
                } else {
                    $toDate->setMonth(10);
                }

                $fromDate = clone $toDate;
                $fromDate->subMonth(3);
            break;
            case 'curryear':
                $fromDate = clone $referenceDate;
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

                $toDate = new Zend_Date($report->getDateTo());
                $fromDate = new Zend_Date($report->getDateFrom());

                $period = $toDate->sub($fromDate);

                $offsetFromDateAdded = $dateAdded->sub($fromDate);

                $fromDate = clone $referenceDate;
                $fromDate = $fromDate->sub($offsetFromDateAdded);

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

        if (APPLICATION_ENV == 'development') {
            Unwired_Exception::getLog()->debug('-- timeframe adjusted --');
            Unwired_Exception::getLog()->debug('Report ID: ' . (string) $report->getReportGroupId());
            Unwired_Exception::getLog()->debug('Report Type: ' . (string) $report->getReportType());
            Unwired_Exception::getLog()->debug('Timeframe: ' . (string) $timeframe);
            Unwired_Exception::getLog()->debug('Date added: ' . (string) new Zend_Date($report->getDateAdded()));
            Unwired_Exception::getLog()->debug('Report From: ' . (string) $report->getDateFrom());
            Unwired_Exception::getLog()->debug('Report To: ' . (string) $report->getDateTo());
            Unwired_Exception::getLog()->debug('Ref date: ' . (string) $referenceDate);
            Unwired_Exception::getLog()->debug('Adjusted From: ' . $fromDate->toString());
            Unwired_Exception::getLog()->debug('Adjusted To: ' . $toDate->toString());
            Unwired_Exception::getLog()->debug('-- end timeframe --');
        }

        return array('from' => $fromDate, 'to' => $toDate);
	}
}