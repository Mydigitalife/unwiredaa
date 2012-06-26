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
		$select = "SELECT SUM(i.bytes_down) as bytes_down, SUM(i.bytes_up) as bytes_up, SUM(i.bytes_up+i.bytes_down) as bytes_total, i.node_id, i.node_name, i.node_mac, i.group_name
FROM (
(SELECT 0 as type, SUM(r.total_bytes_down) as bytes_down, SUM(r.total_bytes_up) as bytes_up
, n.node_id, n.name as node_name, n.mac as node_mac, g.name as group_name
FROM acct_internet_roaming r INNER JOIN node n ON r.node_id = n.node_id
INNER JOIN `group` g ON g.group_id = n.group_id
WHERE g.group_id IN (".implode(",",$groupRel).")
AND r.start_time >= '$dateFrom' AND r.start_time < '$dateTo'
AND r.stop_time >= '$dateFrom' AND r.stop_time < '$dateTo'
AND NOT ISNULL(r.stop_time)
GROUP BY n.node_id)
UNION
(SELECT 1 as type, MAX(m.bytes_down)-MIN(m.bytes_down) as bytes_down, MAX(m.bytes_up)-MIN(m.bytes_up) as bytes_up
, n.node_id, n.name as node_name, n.mac as node_mac, g.name as group_name
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
				'name' => array('data' => 'report_result_title_bytes_total'
						,'translatable' => true
					)
				,'down' => 0
				,'up' => 0
				,'total' => 0
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
                                            'device' => $record['group_name']." / ".$record['node_name'],
                                            'down' => round($record['bytes_down']/(1024*1024)),/*$this->_convertTraffic() not useable here as chart uses this data*/
                                            'up' => round($record['bytes_up']/(1024*1024)),
                                            'total' => round($record['bytes_total']/(1024*1024))
                                          ),
                                          'class' => array(
                                            'device' => '', 'down' => "right", 'up' => "right", 'total' => "right"
                                          ));

            //$graphics[/*$record['node_id']*/] = array($record['node_name'], round($record['bytes_total']/(1024*1024)));

            $totals_down += $record['bytes_down'];
            $totals_up += $record['bytes_up'];
            $totals_total += $record['bytes_total'];
        }

        $totals['data']['down'] = round($totals_down/(1024*1024));//use something smarter as calling $this->_convertTraffic
        $totals['data']['up'] = round($totals_up/(1024*1024));
        $totals['data']['total'] = round($totals_total/(1024*1024));
	$totals['device']=1;//do not show in chart

//        array_unshift($results, $totals);
        array_push($results, $totals);

//average AP
        $totals['data']['name'] = '[Average AP]'; //!? use translatable: 'report_result_average_ap';
        $totals['data']['down'] = round($totals_down/$l/(1024*1024));
        $totals['data']['up'] = round($totals_up/$l/(1024*1024));
        $totals['data']['total'] = round($totals_total/$l/(1024*1024));
	$totals['device']=0;//show in chart

        array_unshift($results, $totals);
//        array_push($results, $totals);

/*!!??
todo: use same result for chart (removed chart as first step)
use an bar chart instead of pie
(use full tree names of APs?) -> not trivial
+provide avg AP, real total number
 and maybe percentage of each topAP against real total (might need second pass as we have total total only after reading in results once) -> but this second pass woudl be quite fast, as number of APs (and especially if limited) is small
+configureable TopLimit, via options
 an issue is that we need GB/MB formating but chart wants numerical values, so we need a falg for view.phtml to do the formatting when printing the table
*/

/*hmm we give back numeric values put view.phtml can not detect them anymore -> chart will not work!
print(serialize($results[0]['data']['total'])."<hr>");
print(serialize(is_numeric(($results[0]['data']['total'])))."<hr>");
die(serialize(is_float(($results[0]['data']['total']))));
*/
        return array(/*'graphics' => array(
                        array('name' => 'report_most_active_device',
                              'type' => 'PieChart',
                              'headers' => array('report_device_name', 'report_result_title_bytes_total'),
                              'rows' => $graphics)
                     ),*/
                     'tables' => array(
                          array(
                                'type' => 'both' //strtolower($this->getReportGroup()->getFormatSelected())
				,'name' => 'TopAP by Traffic (in MByte)' //use name of report? or a translated field
                                ,'chartOptions'=>array(
                                        'type'=>'BarChart' //ColumnChart, LineChart
                                        ,'width'=>800 //max 370 for 2 charts sidebyside
                                        ,'height'=>900
                                        //,'switchAxes'=>($this->innerCount>1)
                                        //,'depths'=>array(0,1)//either single value, or an array -> multiple charts
                                        ,'nativeOptions'=>"legend:{position :'right'}") //passed 1:1 to googleCharts options
                                ,'colDefs' => array(//array of coldef-arrays
					array(//array of coldefs
						array(
							'name'=>'report_result_title_device_group_and_name'
							,'translatable'=>true
							,'chartFormat'=>'string'
							,'class'=>'bold'
							)
						,array(
							'name'=>'report_result_title_mbytes_down'
							,'translatable'=>true
							,'chartFormat'=>'number'
                                                        ,'class'=>'bold'
                                                        )
						,array(
							'name'=>'report_result_title_mbytes_up'
							,'translatable'=>true
                                                        ,'chartFormat'=>'number'
                                                        ,'class'=>'bold'
                                                        )
						,array(
							'name'=>'report_result_title_mbytes_total'
							,'translatable'=>true
                                                        ,'chartFormat'=>'number'
                                                        ,'class'=>'bold'
                                                        )
					))
				,'rows' => $results
                          )
                      ));
	}
}
