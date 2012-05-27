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
class Reports_Service_CodeTemplate_MostActiveAPs extends Reports_Service_CodeTemplate_Abstract {

	protected function getTemplate($groupIds, $data) {
		//$groupRel = $this->_getGroupRelations($groupIds);

		$result = $data ['data'];

		$html = '<tr><th>Group</th><th>Node/AP Name</th>
<th style="text-align: center;">Download</th>
<th style="text-align: center;">Upload</td>
<th style="text-align: center;">Total</td></tr>';

		foreach ( $result as $k => $vv ) {
			$html .= '<tr><td>'.$vv['group_name'].'</td><td><strong>'.$vv['node_name'].'</strong> ('.$vv['node_mac'].') </td>
<td style="text-align: right;">' . $this->_convertTraffic ( $vv ['bytes_down'] ) . '</td>
<td style="text-align: right;">' . $this->_convertTraffic ( $vv ['bytes_up'] ) . '</td>
<td style="text-align: right;">' . $this->_convertTraffic ( $vv ['bytes_total'] ) . '</td></tr>';

		}

		return '<table class="listing">'.$html.'</table>';
	}

	public function getData($groupIds, $dateFrom, $dateTo) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();

		$groupRel = $this->_getGroupRelations ( $groupIds );

		//use specifyable limit, and default to 1000 if out of range (3..1000)
		$limit=$this->getReportGroup()->getCodeTemplate()->getOption('limit');
		if (!(($limit>2) && ($limit<=1000))) $limit=5;//0;
		$select = "SELECT SUM(i.bytes_down) as bytes_down, SUM(i.bytes_up) as bytes_up, SUM(i.bytes_up+i.bytes_down) as bytes_total, i.node_id, i.node_name, i.node_mac
FROM (
(SELECT 0 as type, SUM(r.total_bytes_down) as bytes_down, SUM(r.total_bytes_up) as bytes_up
, n.node_id, n.name as node_name, n.mac as node_mac
FROM acct_internet_roaming r INNER JOIN node n ON r.node_id = n.node_id
INNER JOIN `group` g ON g.group_id = n.group_id
WHERE g.group_id IN (".implode(",",$groupRel).")
AND r.start_time >= '$dateFrom' AND r.start_time < '$dateTo'
AND r.stop_time >= '$dateFrom' AND r.stop_time < '$dateTo'
AND NOT ISNULL(r.stop_time)
GROUP BY n.node_id)
UNION
(SELECT 1 as type, MAX(m.bytes_down)-MIN(m.bytes_down) as bytes_down, MAX(m.bytes_up)-MIN(m.bytes_up) as bytes_up
, n.node_id, n.name as node_name, n.mac as node_mac
FROM acct_internet_roaming r INNER JOIN acct_internet_interim m ON (m.roaming_count=r.roaming_count AND m.session_id=r.session_id)
INNER JOIN node n ON r.node_id = n.node_id
INNER JOIN `group` g ON g.group_id = n.group_id
WHERE g.group_id IN (".implode(",",$groupRel).")
AND
(
(r.start_time >= '$dateFrom' AND r.start_time < '$dateTo' AND ISNULL(r.stop_time))
OR (r.start_time < '$dateFrom' AND  r.stop_time > '$dateFrom')
OR (r.stop_time > '$dateFrom' AND  r.start_time < '$dateFrom')
)
AND m.time >= '$dateFrom' AND m.time < '$dateTo'
GROUP BY n.node_id)
) AS i
GROUP BY i.node_id, i.node_name, i.node_mac
ORDER BY bytes_total DESC;";

//print(serialize(microtime()));
		$records = $db->fetchAll ( $select );
//die(serialize(microtime()));

	$tables = array();

        $totals = array('data' => array(
                            'name' => array('data' => 'report_result_total',
                                  'translatable' => true
                            ),
            '',
            'down' => 0,
            'up' => 0,
            'total' => 0
          ),
          'class' => array(
             'name' => "bold", '', 'down' => "bold right", 'up' => "bold right", 'total' => "bold right"
          ));

        $results = array();
        //$graphics = array();

	$totals_down=$totals_up=$totals_total=$l=0;
        foreach ($records as $record) {
	  $l++;
	  if ($l <= $limit)
            $results[$record['node_id']] = array('data' => array(
                                            'device' => $record['node_name'],
                                            'group' => $record['group_name'],
                                            'down' => $this->_convertTraffic($record['bytes_down']),
                                            'up' => $this->_convertTraffic($record['bytes_up']),
                                            'total' => $this->_convertTraffic($record['bytes_total'])
                                          ),
                                          'class' => array(
                                            'device' => '', 'group'=>'', 'down' => "right", 'up' => "right", 'total' => "right"
                                          ));

            //$graphics[/*$record['node_id']*/] = array($record['node_name'], round($record['bytes_total']/(1024*1024)));

            $totals_down += $record['bytes_down'];
            $totals_up += $record['bytes_up'];
            $totals_total += $record['bytes_total'];
        }

        $totals['data']['down'] = $this->_convertTraffic($totals_down);
        $totals['data']['up'] = $this->_convertTraffic($totals_up);
        $totals['data']['total'] = $this->_convertTraffic($totals_total);

        array_unshift($results, $totals);
        array_push($results, $totals);

//average AP
        $totals['data']['name']['data'] = 'report_average';
        $totals['data']['down'] = $this->_convertTraffic($totals_down/$l);
        $totals['data']['up'] = $this->_convertTraffic($totals_up/$l);
        $totals['data']['total'] = $this->_convertTraffic($totals_total/$l);

        array_unshift($results, $totals);
        array_push($results, $totals);

/*!!??
todo: use same result for chart (removed chart as first step)
use an bar chart instead of pie
(use full tree names of APs?) -> not trivial
+provide avg AP, real total number
 and maybe percentage of each topAP against real total (might need second pass as we have total total only after reading in results once) -> but this second pass woudl be quite fast, as number of APs (and especially if limited) is small
+configureable TopLimit, via options
*/
        return array(/*'graphics' => array(
                        array('name' => 'report_most_active_device',
                              'type' => 'PieChart',
                              'headers' => array('report_device_name', 'report_result_total'),
                              'rows' => $graphics)
                     ),*/
                     'tables' => array(
                          array(
                                'type' => 'both' //!? should be userselectable
                                ,'chartOptions'=>array(
                                        'type'=>'ColumnChart' //LineChart
                                        ,'width'=>770 //max 370 for 2 charts sidebyside
                                        ,'height'=>900
                                        //,'switchAxes'=>($this->innerCount>1)
                                        ,'depths'=>array(0,1)//either single value, or an array -> multiple charts
                                        ,'nativeOptions'=>"legend:{position :'right'}") //passed 1:1 to googleCharts options
                                ,'colDefs' => array(array('report_device_name', 'report_device_group', 'report_result_download', 'report_result_upload', 'report_result_total'))
				,'rows' => $results
                          )
                      ));
	}
}
