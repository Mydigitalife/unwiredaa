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
					'type'=>strtolower($this->getReportGroup()->getFormatSelected())
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
        	                                        ,array( /*advanced column def as array*/
                	                                        'name'=>'Percentage'
                        	                                ,'translatable'=>false
                                	                        ,'class'=>''
                                        	        )
	                                        ) /* end of first coldef*/
        	                        ) /*end of coldefs*/
                	                ,'rows'=>$rows
				); /*end of table*/
	}

/*abstractable default line handler!?*/
	private function handleLine($name,$value,$perc,$bold,$hide)
	{
		return array(/*data row*/
			'data'=>array($name,$value,$perc."%")
			,'translatable'=>false
			,'device'=>$hide
			,'class'=>array(($bold?'bold':''),($bold?'bold right':'right'),($bold?'bold right':'right'))
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
				$ttotal[$row[0]]=$row[1];
				$totaltotal+=$row[1];
			}
			$total=array();
			$total[]=$this->handleLine('Total '.$row[0],$row[1],100,true,true);

			/*query top 50 virus threats*/
			if ($virus) $tstmt=$db->query("SELECT virusname, count(*) AS cnt
FROM proxy_log WHERE category like '%virus%' and stamp BETWEEN '$dateFrom' AND '$dateTo'
GROUP BY virusname ORDER BY cnt DESC limit 50;");
			else $tstmt=$db->query("SELECT substring_index(domain,'.','-2') AS tld, count(*) AS cnt
FROM proxy_log WHERE category='$row[0]' and stamp BETWEEN '$dateFrom' AND '$dateTo' GROUP BY tld ORDER BY cnt DESC limit 20;");

			while ($trow=$tstmt->fetch()){
				$rows[]=$this->handleLine($trow[0],$trow[1],round($trow[1]*1000/$row[1])/10,false,false);
			}

			$tables[str_replace(",","_",str_replace(" ","",$row[0]))]=$this->getTable(array_merge($total,$rows,$total),($virus?'Top 50 virus':'Top 20 '.$row[0]));
		}
		if ($empty) {
		// no virus found
			$rows[]=$this->handleLine("[No Data!]",0,0,true,false);
			$tables[]=$this->getTable($rows,'No Data!');
		}
		//add overview chart(and table) based on totals ?
		if ($virus) $overview=array();
		else {
			//build total with perc
			foreach ($ttotal as $name=>$value)
                        $tltotal[]=$this->handleLine($name,$value,round($value*1000/$totaltotal)/10,false,false);

			$total=array();
                        $total[]=$this->handleLine('Total',$totaltotal,100,true,true);
			$overview['overview']=$this->getTable(array_merge($total,$tltotal,$total),'Thread Overview');
		}

		return array('tables'=>array_merge($overview,$tables));
	}
}
