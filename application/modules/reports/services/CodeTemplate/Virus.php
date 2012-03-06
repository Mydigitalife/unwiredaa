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

class Reports_Service_CodeTemplate_Virus extends Reports_Service_CodeTemplate_Abstract {

	private function getResult($rows)
	{
		return array(
			'tables'=>array(/*array of tables*/
				'main'=>array(/*table definition*/
					'type'=>strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault())
                                        ,'name'=>'Virus'
                                        ,'chartOptions'=>array(
                                                'type'=>'PieChart'
                                                ,'width'=>780 /*max 370 for 2 charts sidebyside*/
                                                ,'height'=>500
                                                /*nativeOptions are passed 1:1 to googleCharts options*/
                                                ,'nativeOptions'=>"legend:{position :'rigth'}"
                                                )
        		        	,'colDefs'=>array(/*array of coldefs*/
                	                        array(/*coldef*/
                        	                        array( /*advanced column def as array*/
                                	                        'name'=>'Virusname'
                                        	                ,'translatable'=>false
                                                	        ,'width'=>'80%'
                                                        	,'class'=>''
	                                                )
        	                                        ,array( /*advanced column def as array*/
                	                                        'name'=>'Count'
                        	                                ,'translatable'=>false
                                	                        ,'class'=>''
                                        	        )
	                                        ) /* end of first coldef*/
        	                        ) /*end of coldefs*/
                	                ,'rows'=>$rows
				) /*end of table*/
                        )/*end of array of tables*/
		);
	}

/*abstractable default line handler!?*/
	private function handleLine($name,$value,$bold,$hide)
	{
		return array(/*data row*/
			'data'=>array($name,$value)
			,'translatable'=>false
			,'device'=>$hide
			,'class'=>array(($bold?'bold':''),($bold?'bold right':'right'))
		); /*end of data row*/
	}

	public function getData($groupIds, $dateFrom, $dateTo) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$db->setFetchMode(Zend_Db::FETCH_NUM);

		/*query total total*/
		$stmt=$db->query("SELECT 'virus', count(*) AS cnt
FROM proxy_log WHERE stamp BETWEEN '$dateFrom' AND '$dateTo' AND category like '%virus%'");

		$row=$stmt->fetch();
		if (is_array($row) && $row[1]>0) {
			$rows=array();
			$total[]=$this->handleLine('Total',$row[1],true,true);

			/*query top 50 virus threats*/
			$tstmt=$db->query("SELECT virusname, count(*) AS cnt
FROM proxy_log WHERE category like '%virus%' and stamp BETWEEN '$dateFrom' AND '$dateTo'
GROUP BY virusname ORDER BY cnt DESC limit 50;");
			while ($trow=$tstmt->fetch()){
				$rows[]=$this->handleLine($trow[0],$trow[1],false,false);
			}
		} else {
		// no virus found
			$total=array();
			$rows[]=$this->handleLine("[No Virus was found!]",0,true,false);
		}
		return $this->getResult(array_merge($total,$rows,$total));
	}
}
