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

class Reports_Service_CodeTemplate_Proxy extends Reports_Service_CodeTemplate_Abstract {

	private function getTable($rows,$cat)
	{
		return 		array(/*table definition*/
					'type'=>strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault())
                                        ,'name'=>$cat
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
                                	                        'name'=>$cat
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
				); /*end of table*/
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
		$empty=true;

		//show either url-blocks or viruses
		if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"irus")!==false) $virus=true;
		else $virus=false;

		/*query total total*/
		if ($virus) $stmt=$db->query("SELECT 'virus', count(*) AS cnt
FROM proxy_log WHERE stamp BETWEEN '$dateFrom' AND '$dateTo' AND category like '%virus%'");
		else $stmt=$db->query("SELECT category, count(*) AS cnt
FROM proxy_log WHERE stamp BETWEEN '$dateFrom' AND '$dateTo' GROUP BY category ORDER BY cnt DESC;");

		while ($row=$stmt->fetch()) {
			$empty=false;
			$rows=array();
			if (!$virus) {
				$ttotal[]=$this->handleLine($row[0],$row[1],true,false);
				$totaltotal+=$row[1];
			}
			$total=array();
			$total[]=$this->handleLine('Total '.$row[0],$row[1],true,true);

			/*query top 50 virus threats*/
			if ($virus) $tstmt=$db->query("SELECT virusname, count(*) AS cnt
FROM proxy_log WHERE category like '%virus%' and stamp BETWEEN '$dateFrom' AND '$dateTo'
GROUP BY virusname ORDER BY cnt DESC limit 50;");
			else $tstmt=$db->query("SELECT substring_index(domain,'.','-2') AS tld, count(*) AS cnt
FROM proxy_log WHERE category='$row[0]' and stamp BETWEEN '$dateFrom' AND '$dateTo' GROUP BY tld ORDER BY cnt DESC limit 20;");

			while ($trow=$tstmt->fetch()){
				$rows[]=$this->handleLine($trow[0],$trow[1],false,false);
			}
			$tables[$row[0]]=$this->getTable(array_merge($total,$rows,$total),($virus?'Top 50 virus':'Top 20 '.$row[0].' domains'));
		}
		if ($empty) {
		// no virus found
			$total=array();
			$rows[]=$this->handleLine("[No Data!]",0,true,false);
			$tables[]=$this->getTable(array_merge($total,$rows,$total),'No Data!');
		}
		//add overview chart(and table) based on totals ?
		if ($virus) $overview=array();
		else {
			$total=array();
                        $total[]=$this->handleLine('Total',$totaltotal,true,true);
			$overview['overview']=$this->getTable(array_merge($total,$ttotal,$total),'Thread Overview');
		}

		return array('tables'=>array_merge($overview,$tables));
	}
}
