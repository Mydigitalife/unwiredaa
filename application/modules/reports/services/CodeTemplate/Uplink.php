<?php
/**
 * Unwired AA GUI
 *
 * Author & Copyright (c) 2011 Unwired Networks GmbH
 * Markus.Kittenberger@gmx.at
 *
 * Licensed under the terms of the Affero Gnu Public License version 3
 * (AGPLv3 - http://www.gnu.org/licenses/agpl.html) or our proprietory
 * license available at http://www.unwired.at/license.html
 */

class Reports_Service_CodeTemplate_Uplink extends Reports_Service_CodeTemplate_Abstract {

	private function getTable($rows)
	{
		return 		array(/*table definition*/
					'type'=>strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault())
                                        ,'name'=>'Server Uplink Usage'
                                        ,'chartOptions'=>array(
                                                'type'=>'AreaChart'
                                                ,'width'=>780 /*max 370 for 2 charts sidebyside*/
                                                ,'height'=>500
                                                /*nativeOptions are passed 1:1 to googleCharts options*/
                                                ,'nativeOptions'=>"legend:{position :'rigth'}"
                                                )
        		        	,'colDefs'=>array(/*array of coldefs*/
                	                        array(/*coldef*/
                        	                        array( /*advanced column def as array*/
                                	                        'name'=>'Uplink Usage'
                                        	                ,'translatable'=>false
                                                	        ,'width'=>'60%'
                                                        	,'class'=>''
	                                                )
        	                                        ,array( /*advanced column def as array*/
                	                                        'name'=>'Down (mbit)'
                        	                                ,'translatable'=>false
                                	                        ,'class'=>''
                                        	        )
        	                                        ,array( /*advanced column def as array*/
                	                                        'name'=>'Up (mbit)'
                        	                                ,'translatable'=>false
                                	                        ,'class'=>''
                                        	        )
	                                        ) /* end of first coldef*/
        	                        ) /*end of coldefs*/
                	                ,'rows'=>$rows
				); /*end of table*/
	}

/*abstractable default line handler!?*/
	private function handleLine($line,$bold,$hide)
	{
		return array(/*data row*/
			'data'=>array($line[0],$line[1],$line[2])
			,'translatable'=>false
			,'device'=>$hide
			,'class'=>array(($bold?'bold':''),($bold?'bold right':'right'),($bold?'bold right':'right'))
		); /*end of data row*/
	}

	public function getData($groupIds, $dateFrom, $dateTo) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$db->setFetchMode(Zend_Db::FETCH_NUM);
		$empty=true;
		$total=array();//total or average!?

		//get inner interval
		$iintv=$this->innerInterval=$this->getReportGroup()->getInnerInterval()*60;

		$tstmt=$db->query("SELECT CONCAT(LEFT(MIN(time),16),' - ',LEFT(MAX(time),16))
, (sum(vpn_eth1_RX)+sum(nat_eth2_TX))*8/($iintv*1024) as server_down
, (sum(vpn_eth1_TX)+sum(nat_eth2_RX))*8/($iintv*1024) as server_up
, (UNIX_TIMESTAMP(time)-UNIX_TIMESTAMP('$dateFrom')) DIV $iintv as intv
FROM bw_log WHERE NOT ISNULL(nat_eth1_RX) AND time BETWEEN '$dateFrom' AND '$dateTo' GROUP BY intv;");

		while ($row=$tstmt->fetch()){
			$rows[]=$this->handleLine($row,false,false);
		}

		$tables['main']=$this->getTable(array_merge($total,$rows,$total));

		return array('tables'=>$tables);
	}
}
