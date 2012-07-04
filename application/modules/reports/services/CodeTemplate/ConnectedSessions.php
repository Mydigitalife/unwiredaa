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

class Reports_Service_CodeTemplate_ConnectedSessions extends Reports_Service_CodeTemplate_Abstract {

	protected function getTemplate($groupIds, $data) {
		$result = $data ['data'];
		$totals = $data ['totals'];
		$nullstr = "-";
		$html = '';

		foreach ( $result as $k => $v ) {

			$html .= '<table class="listing">';
			$html .= '<tr>
<th rowspan=2 style="vertical-align: bottom;">Group Name</th>
<th style="text-align: center;">Offline</th>
<th colspan=3 style="text-align: center;">Online</th>
<th style="text-align: center; vertical-align: top;" rowspan=2>Total</th>
</tr><tr>
<th style="text-align: center;">Garden</th>
<th style="text-align: center;">Guest</th>
<th style="text-align: center;">MAC Auth.</th>
<th style="text-align: center;">Password A.</th>
</tr>';

			$totals_garden = $totals ['garden'] [$k] - ($totals ['guest'] [$k] + $totals ['macauth'] [$k] + $totals ['auth'] [$k]);
			$html .= '<tr><td><strong>Total</td>
<td style="text-align:right;"><strong>' . ((( int ) $totals_garden > 0) ? $totals_garden : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['guest'] [$k] > 0) ? $totals ['guest'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['macauth'] [$k] > 0) ? $totals ['macauth'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['auth'] [$k] > 0) ? $totals ['auth'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['garden'] [$k] > 0) ? $totals ['garden'] [$k] : $nullstr) . '</strong></td>
</tr>';
			foreach ( $v as $key => $value ) {
				$value_garden = ($value ['garden'] * 1) - (($value ['guest'] * 1) + ($value ['macauth'] * 1) + ($value ['auth'] * 1));
				$html .= '<tr><td>' . $value ['name'] . '</td>
<td style="text-align:right;">' . ((( int ) $value_garden > 0) ? $value_garden : $nullstr) . '</td>
<td style="text-align:right;">' . ((( int ) $value ['guest'] > 0) ? $value ['guest'] : $nullstr) . '</td>
<td style="text-align:right;">' . ((( int ) $value ['macauth'] > 0) ? $value ['macauth'] : $nullstr) . '</td>
<td style="text-align:right;">' . ((( int ) $value ['auth'] > 0) ? $value ['auth'] : $nullstr) . '</td>
<td style="text-align:right;">' . ((( int ) $value ['garden'] > 0) ? $value ['garden'] : $nullstr) . '</td>
</tr>';
			}
			$html .= '<tr><td><strong>Total</td>
<td style="text-align:right;"><strong>' . ((( int ) $totals_garden > 0) ? $totals_garden : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['guest'] [$k] > 0) ? $totals ['guest'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['macauth'] [$k] > 0) ? $totals ['macauth'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['auth'] [$k] > 0) ? $totals ['auth'] [$k] : $nullstr) . '</strong></td>
<td style="text-align:right;"><strong>' . ((( int ) $totals ['garden'] [$k] > 0) ? $totals ['garden'] [$k] : $nullstr) . '</strong></td>
</tr>';
		}

		return $html;
	}

	public function getData($groupIds, $dateFrom, $dateTo) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$result = array ();
		$totals = array (); //Garden,Guest,MacAuth,Auth
		$user_types = array ("guest" => "Guest", "macauth" => "MACAuthenticated", "auth" => "Authenticated" );

		foreach ( $groupIds as $k => $v ) {

			$groupRel = $this->_getGroupRelations ( array ($v ) );

			/*all users (garden)*/
			$select = $db->select ()->from ( array ('a' => 'acct_garden_session' ), array ('COUNT(*) as cnt_by_group' )	)
				->join ( array ('b' => 'acct_garden_roaming' ), 'a.session_id = b.session_id' )
				->join ( array ('c' => 'node' ), 'b.node_id = c.node_id' )
				->join ( array ('d' => 'group' ), 'c.group_id = d.group_id', array ('d.name as group_name', 'd.group_id' ) )
				->where ( 'd.group_id IN (?)', $groupRel )
				->where ( "DATE(a.start_time) BETWEEN '$dateFrom' AND '$dateTo'" )
				->where ( 'NOT ISNULL(a.stop_time)' )
				->group ( 'd.group_id' );

			$totals ['garden'] [$v] = 0;
			foreach ( $db->fetchAll ( $select ) as $key => $value ) {
				$totals ['garden'] [$v] += $value ['cnt_by_group'];
				$result [$v] [$value ['group_id']] ['garden'] = $value ['cnt_by_group'];
				$result [$v] [$value ['group_id']] ['name'] = $value ['group_name'];
			}

			foreach ( $user_types as $user_type_key => $user_type_val ) {
				$select = $db->select ()->from ( array ('a' => 'acct_internet_session' ), array ('COUNT(*) as cnt_by_group' ) )->join ( array ('b' => 'acct_internet_roaming' ), 'a.session_id = b.session_id' )->join ( array ('c' => 'node' ), 'b.node_id = c.node_id' )->where ( 'c.group_id IN (?)', $groupRel )->where ( "DATE(a.start_time) BETWEEN '$dateFrom' AND '$dateTo'" )->where ( 'NOT ISNULL(a.stop_time)' )->where ( "a.groupname = '$user_type_val'" )->group ( 'c.group_id' );

				$totals [$user_type_key] [$v] = 0;
				foreach ( $db->fetchAll ( $select ) as $key => $value ) {
					$totals [$user_type_key] [$v] += $value ['cnt_by_group'];
					$result [$v] [$value ['group_id']] [$user_type_key] = $value ['cnt_by_group'];
				}
			}
		}

		$tables = array();

		$nullstr = '-';


		foreach ($result as $k => $v) {
			$table = array(
					'colDefs' => array(
							array(
									array(
										'name' => 'report_result_group'
										, 'translatable' => true
										, 'rowspan' => 2
										),
									array(
										'name' => 'report_result_offline'
										, 'translatable' => true
										, 'width' => '16%'
										),
									array(
										'name' => 'report_result_online'
										, 'translatable' => true
										, 'colspan' => 3
										),
									array(
										'name' => 'report_result_total'
										, 'translatable' => true
										, 'rowspan' => 2
										, 'class' => 'right'
										, 'width' => '14%'
										),
							),
							array(
									array(
										'name' => 'report_result_garden'
										, 'translatable' => true
										, 'class' => 'right'
										),
									array(
										'name' => 'report_result_guest'
										, 'translatable' => true
										, 'class' => 'right'
										, 'width' => '16%'
										),
									array(
										'name' => 'report_result_macauth'
										, 'translatable' => true
										, 'class' => 'right'
										),
									array(
										'name' => 'report_result_auth'
										, 'translatable' => true
										, 'class' => 'right'
										),
							),
					)
			);

			$totals_garden = $totals ['garden'] [$k] - ($totals ['guest'] [$k] + $totals ['macauth'] [$k] + $totals ['auth'] [$k]);

			$total_row =  array(
				'data' => array(
					array('data' => 'report_result_total', 'translatable' => true),
					((( int ) $totals_garden > 0) ? $totals_garden : $nullstr),
					((( int ) $totals ['guest'] [$k] > 0) ? $totals ['guest'] [$k] : $nullstr),
					((( int ) $totals ['macauth'] [$k] > 0) ? $totals ['macauth'] [$k] : $nullstr),
					((( int ) $totals ['auth'] [$k] > 0) ? $totals ['auth'] [$k] : $nullstr),
					((( int ) $totals ['garden'] [$k] > 0) ? $totals ['garden'] [$k] : $nullstr)
				),
				'class' => array('bold', 'bold right', 'bold right', 'bold right', 'bold right', 'bold right')
			);

			$table['rows'][] = $total_row;

			foreach ( $v as $key => $value ) {

				$value_garden = ($value ['garden'] * 1) - (($value ['guest'] * 1) + (isset($value ['macauth']) ? $value['macauth'] * 1 : 0) + (isset($value ['auth']) ? $value['auth'] * 1 : 0));

				$table['rows'][] = array(
					'data' => array(
						$value['name'],
						((( int ) $value_garden > 0) ? $value_garden : $nullstr),
						((( int ) $value ['guest'] > 0) ? $value ['guest'] : $nullstr),
						((isset($value['macauth']) && ( int ) $value ['macauth'] > 0) ? $value ['macauth'] : $nullstr),
						((isset($value['auth']) && ( int ) $value ['auth'] > 0) ? $value ['auth'] : $nullstr),
						((( int ) $value ['garden'] > 0) ? $value ['garden'] : $nullstr),
					),
					'class' => array('', 'right', 'right', 'right', 'right', 'right')
				);
			}

			$table['rows'][] = $total_row;

			$tables[] = $table;
		}

		$k = $groupIds[0];
		$report = array(
			'graphics' => array(
				array(
					'name' => 'report_status_sessions_status',
					'type' => 'PieChart',
					'headers' => array('Session type', 'Session count'),//report_result_sessions', 'report_result_session_count
					'rows' => array(
						array('Offline/Garden', ($totals ['garden'] [$k])),//report_result_offline
						array('Online/Internet', ($totals ['guest'] [$k] + $totals ['macauth'] [$k] + $totals ['auth'] [$k]))//report_result_online
					)
				),
				array(
					'name' => 'report_status_sessions_authmethod',
					'type' => 'PieChart',
					'headers' => array('Authenthication method', 'Session count'),//'report_result_authmethod', 'report_result_session_count'
					'rows' => array(
						array('Guest users', ($totals ['garden'] [$k])),//report_result_guest
						array('Mac-auth. users', ($totals ['macauth'] [$k])),//report_result_macauth
						array('Authenticated users', ($totals ['auth'] [$k]))//report_result_auth
					)
				),
			),
			'tables' => $tables
		);

		return $report;
	}
}
