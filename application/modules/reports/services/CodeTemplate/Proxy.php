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
		$mode=$this->getReportGroup()->getCodeTemplate()->getOption('mode');
                if (!$mode) $mode='threat';

		/*query total total*/
		if ($mode=='virus') $stmt=$db->query("SELECT 'virus', count(*) AS cnt
FROM proxy_log WHERE stamp >= '$dateFrom' AND  stamp < '$dateTo' AND category like '%virus%'
AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.'");
		else if ($mode == 'threat') /*threat total*/ $stmt=$db->query("SELECT category, count(*) AS cnt
FROM proxy_log WHERE stamp >= '$dateFrom' AND stamp < '$dateTo' 
AND category not like '%=%' AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.' GROUP BY category ORDER BY cnt DESC;");/*ignore potentially wrong-parsed lines in log !!?? remove (to regain some performance) when gregors parsing script actually works*/
		else /*maleware loc*/ $stmt=$db->query("SELECT 'malware', count(*) AS cnt
FROM proxy_log WHERE stamp >= '$dateFrom' AND  stamp < '$dateTo'
AND category != 'filesharing' AND category != 'porn' 
AND category not like '%=%' AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.'");

		$totaltotal=0;
		while ($row=$stmt->fetch()) {
			$empty=false;
			$rows=array();
			if ($mode=='threat') {
				$ttotal[$row[0]]=$row[1];
				$totaltotal+=$row[1];
			}
			$total=array();
			$total[]=$this->handleLine('Total '.$row[0],$row[1],100,true,true);

			/*query top 50 virus threats*/
			if ($mode=='virus') $tstmt=$db->query("SELECT virusname, count(*) AS cnt
FROM proxy_log WHERE category like '%virus%' and stamp BETWEEN '$dateFrom' AND '$dateTo'
AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.'
GROUP BY virusname ORDER BY cnt DESC limit 50;");
			else if ($mode=='threat') $tstmt=$db->query("SELECT substring_index(domain,'.','-2') AS tld, count(*) AS cnt
FROM proxy_log WHERE category='$row[0]'
AND stamp >= '$dateFrom' AND stamp < '$dateTo'
AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.' GROUP BY tld ORDER BY cnt DESC limit 20;"); /*ignore potentially wrong-parsed lines in log !!?? remove (to regain some performance) when gregors parsing script actually works*/
			else /*malware users*/ {$tstmt=$db->query("SELECT CONCAT('MAC: ',CONV(user_mac,10,16)) as mac, count(*) AS cnt
FROM proxy_log INNER JOIN acct_garden_session 
ON start_time <= stamp AND stop_time >= stamp
AND ((substring_index(substring_index(client,'.','-2'),'.','1')*256)+(substring_index(client,'.','-1')))=user_ip
WHERE start_time >= DATE_SUB('$dateFrom',INTERVAL 1 DAY) AND start_time < '$dateTo'
AND stop_time >= '$dateFrom' AND stop_time < DATE_ADD('$dateTo',INTERVAL 1 DAY)
AND stamp >= '$dateFrom' AND stamp < '$dateTo'
AND category != 'filesharing' AND category != 'porn'
AND category not like '%=%' AND domain not like '%=%' AND domain RLIKE '\.[a-z]\.'
GROUP BY mac
ORDER BY cnt desc
LIMIT 20");
//order by does not work?
//HAVING cnt > 4;

//without filsharing and porn categories it might work,..

//damn slow -> do not join -> create list of ips and stamps -> TEMP TABLE
//select matching sessions
//show matching locations, grouped
}

			while ($trow=$tstmt->fetch()){
				$rows[]=$this->handleLine($trow[0],$trow[1],round($trow[1]*1000/$row[1])/10,false,false);
			}

			$tables[str_replace(",","_",str_replace(" ","",$row[0]))]=$this->getTable(array_merge($total,$rows,$total),(($mode=='virus')?'Top 50 virus':($mode=='threat'?'Top 20 '.$row[0]:'Top 20 Malware MAC Adresses')));
		}
		if ($empty) {
		// no virus found
			$rows[]=$this->handleLine("[No Data!]",0,0,true,false);
			$tables[]=$this->getTable($rows,'No Data!');
		}
		//add overview chart(and table) based on totals ?
		if (($mode=='virus')||($mode=='loc')) $overview=array();
		else {
			//build total with perc
			foreach ($ttotal as $name=>$value)
                        $tltotal[]=$this->handleLine($name,$value,round($value*1000/$totaltotal)/10,false,false);

			$total=array();
                        $total[]=$this->handleLine('Total',$totaltotal,100,true,true);
			$overview['overview']=$this->getTable(array_merge($total,$tltotal,$total),'Threat Overview');
		}

		return array('tables'=>array_merge($overview,$tables));
	}
}
