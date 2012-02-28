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

class Reports_View_Helper_CanGenerateManual extends Zend_View_Helper_Abstract
{

    public function canGenerateManual($report)
    {
        if (!$report instanceof Reports_Model_Group) {
            return false;
        }

        if ($report->getCodeTemplate()->getTimeframeLiveMax() === 0) {
            return false;
        }

        $dateFrom = $report->getDateFrom();
        $dateTo = $report->getDateTo();

        $liveMax = $report->getCodeTemplate()->getTimeframeLiveMax();

    	if (null === $liveMax) {
    	    $liveMax = 527040;
    	}

    	$dateFrom->addMinute($liveMax);

    	$allowedGenerate = $dateFrom->isLater($dateTo);

    	return $allowedGenerate;
    }
}