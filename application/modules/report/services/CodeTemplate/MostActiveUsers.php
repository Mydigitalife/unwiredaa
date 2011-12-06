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
class Report_Service_CodeTemplate_MostActiveUsers extends Report_Service_CodeTemplate_Abstract {

    //protected $_node = 'node';
    //protected $_group = 'group';
    //protected $_internet_sess = 'acct_internet_session';
    //protected $_network_user = 'network_user';

    protected function getTemplate($groupIds, $data) {
        
        $result = $data['data'];
        $groupTotals = $data['totals'];
         
        
        /*
        foreach ($groupIds as $gid) {
            if (!isset($groupTotals[$gid]) || $groupTotals[$gid]['cnt'] == 0){
                continue;
            }
        */

        $html = '';
        foreach ($groupTotals as $k => $v) {
			$html .= '<table class="listing">';
			$html .= '<tr><th>Device Name</th><th>Download</th><th>Upload</th></tr>';
			
        	
			$htmlGroupTot = '<tr><td><strong>Total: </strong></td><td style="text-align: right;"><strong>' . $this->_convertTraffic($groupTotals[$k]['down_total']) .'</strong></td><td style="text-align: right;"><strong>' . $this->_convertTraffic($groupTotals[$k]['up_total']) .'</strong></td></tr>';
	        $html .= $htmlGroupTot;
	            
			foreach ($result[$k] as $key => $value) {
				$html .= '<tr><td>'.$value['username'].'</td><td style="text-align: right;">'.$this->_convertTraffic($value['down_total']).'</td><td style="text-align: right;">'.$this->_convertTraffic($value['up_total']).'</td></tr>';
			}
	            
			$html .= $htmlGroupTot;
			$html .= '</table><br/>';
        }

        //}

        return $html;
    }

    protected function getData($groupIds, $dateFrom, $dateTo) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$groupTotals = array();
		
        foreach ($groupIds as $k => $v) {
        	
        	
        	$groupTotals[$v] = array('cnt' => 0, 'down_total' => 0, 'up_total' => 0);
	        $groupRel = $this->_getGroupRelations(array($v));
	        
	        $select = $db->select()
        			->from(array('a' => 'network_user'), 'a.*')
	                ->join(array('b' => 'acct_internet_session'), 'a.user_id = b.user_id', 'SUM(b.total_bytes_up) as up_total, SUM(b.total_bytes_down) as down_total')
	                ->join(array('c' => 'acct_internet_roaming'), 'b.session_id = c.session_id')
	                ->join(array('d' => 'node'), 'c.node_id = d.node_id')
	                ->join(array('e' => 'group'), 'd.group_id = e.group_id', array('group_id', 'name as group_name'))
	                ->where('e.group_id IN (?)', $groupRel)
	                ->where('b.start_time >= ?', $dateFrom)
	                ->where('b.start_time <= ?', $dateTo)
	                ->group('a.user_id')
	                ->order('down_total DESC')
	                ->limit(50);
	        
	        $result[$v] = $db->fetchAll($select);
			
            foreach ($result[$v] as $key => $value) {

				$groupTotals[$v]['down_total'] += $value['down_total'];
				$groupTotals[$v]['up_total'] += $value['up_total'];
            }
        }
        
        return array('data' => $result, 'totals' => $groupTotals);
        
       
    }

}