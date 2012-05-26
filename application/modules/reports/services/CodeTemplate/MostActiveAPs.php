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
		if (!(($limit>2) && ($limit<=1000))) $limit=50;
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
ORDER BY bytes_total DESC
LIMIT $limit;";
/*use the combination of roaming and interim, to query only traffic wihtin date_from-date_to!!?? 
i.e. use interim for all roamings that start before date_from, but end after date_from, or end after date_to, but start before date_to
i.e use roaming only for ones that start after date_from, and end before date_to*/

		$records = $db->fetchAll ( $select );
//die(serialize(microtime()));

/*additional select max(bytes_up|down)-min(bytes_up|down) from interim records (within timeframe) having roaming (seession_id + roaming_count) with null as stop_time */


		//Zend_Debug::dump($records); die();

		$tables = array();
        $graphics = array();


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
        $graphics = array();

        foreach ($records as $record) {
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

            $graphics[/*$record['node_id']*/] = array($record['node_name'], round($record['bytes_total']/(1024*1024)));

            $totals['data']['down'] += $record['bytes_down'];
            $totals['data']['up'] += $record['bytes_up'];
            $totals['data']['total'] += $record['bytes_total'];
        }

        $totals['data']['down'] = $this->_convertTraffic($totals['data']['down']);
        $totals['data']['up'] = $this->_convertTraffic($totals['data']['up']);
        $totals['data']['total'] = $this->_convertTraffic($totals['data']['total']);

        array_unshift($results, $totals);
        array_push($results, $totals);

/*!!??
todo: use same result for chart
use an bar chart
(use full tree names of APs?)
provide avg AP, real total number, and maybe percentage of each topAP against real total
configureable TopLimit, via options
*/
        return array('graphics' => array(
                        array('name' => 'report_most_active_device',
                              'type' => 'PieChart',
                              'headers' => array('report_device_name', 'report_result_total'),
                              'rows' => $graphics)
                     ),
                     'tables' => array(
                        array(
                            'colDefs' => array(array('report_device_name', 'report_device_group', 'report_result_download', 'report_result_upload', 'report_result_total')),
                            'rows' => $results
                        )
                      ));

/* what is this never reached code doing?

        $user = array();
        foreach ($groupTotals as $k => $v) {
        	foreach ($result[$k] as $key => $value) {
        		$user[$value['username']] = $value['down_total'];
        	}
        }

        foreach ($user as $key => $value):
        	$graphics[] = array($key, $value);
        endforeach;

        foreach ($groupTotals as $k => $v) {
        	$table = array(
        		'colDefs' => array(
        			array(
        				'report_device_name', 'report_result_download', 'report_result_upload', 'report_result_total'
        			)
        		)
        	);

        	$total_row = array(
        		'data' => array(array('data' => 'report_result_total', 'translatable' => true), $this->_convertTraffic($v['down_total']), $this->_convertTraffic($v['up_total']), $this->_convertTraffic($v['down_total']+$v['up_total'])),
        		'class' => array('bold', 'bold right', 'bold right', 'bold right')
        	);

        	$table['rows'][] = $total_row;

        	foreach ($result[$k] as $key => $value) {
        		$table['rows'][] = array(
        				'data' => array($value['username'], $this->_convertTraffic($value['down_total']), $this->_convertTraffic($value['up_total']), $this->_convertTraffic($value['down_total']+$value['up_total'])),
        				'class' => array('', 'right', 'right', 'right')
        		);
        	}

        	$table['rows'][] = $total_row;

        	$tables[] = $table;

        }


        $report = array(
        	'graphics' => array(
        			array(
        					'name' => 'report_status_ap_count',
        					'type' => 'PieChart',
        					'headers' => array('report_result_user', 'report_result_traffic'),
        					'rows' => $graphics
        			),
        	),
        	'tables' => $tables
        );

        return $report;*/
	}
}
