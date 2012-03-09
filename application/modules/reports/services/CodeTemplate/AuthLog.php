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

class Reports_Service_CodeTemplate_AuthLog extends Reports_Service_CodeTemplate_Abstract {

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
		$empty=true;

		//show either Language, Startpage, OS, or Vendor
		if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"anguage")!==false) $mode='lang';
		else if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"OS")!==false) $mode='os';
		else if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"endor")!==false) $mode='vendor';
		else $mode='start';

		$node_id_query="SELECT node_id from node WHERE group_id in (".implode(",",$this->_getGroupRelations($groupIds)).")";
		$db->setFetchMode(Zend_Db::FETCH_NUM);

		/*get list of nodes*/
		$res=$db->fetchAll($node_id_query);
		foreach ($res as $value) $node_ids[]=$value[0];
		$node_id_str="node_id IN (".implode(",",$node_ids).") AND";

		/*query auth_log*/
		switch ($mode) {
			case "lang":
//filter strange accept_languages, or language-variations!?
				$stmt=$db->query("SELECT accept_language, count(*) as cnt FROM auth_log
WHERE $node_id_str time BETWEEN '$dateFrom' AND '$dateTo' GROUP BY accept_language ORDER BY cnt desc");
				break;
			case "os":
//extract os from user_agent!?
				$stmt=$db->query("SELECT user_agent, count(*) as cnt FROM auth_log
WHERE $node_id_str time BETWEEN '$dateFrom' AND '$dateTo' GROUP BY user_agent ORDER BY cnt desc");
				break;
			case "vendor":
//die("under construction");
//use user_id and vendor table
				$stmt=$db->query("SELECT LEFT(username,8), count(*) as cnt FROM auth_log
WHERE type='guest' AND $node_id_str time BETWEEN '$dateFrom' AND '$dateTo' GROUP BY LEFT(username,8) ORDER BY cnt desc");
				break;
			case "start":
//only use domain of userurl!?
				$stmt=$db->query("SELECT user_url, count(*) as cnt FROM auth_log
WHERE $node_id_str time BETWEEN '$dateFrom' AND '$dateTo' GROUP BY user_url ORDER BY cnt desc");
				break;
		}

/*collect lines, calc total, and create lines later!?*/
		while ($row=$stmt->fetch()) {
			$empty=false;
			$rows[]=$this->handleLine($row[0],$row[1],"-"/*round($trow[1]*1000/$row[1])/10*/,false,false);
		}
		if ($empty) {// nothing found
			$rows[]=$this->handleLine("[No Data!]",0,0,true,false);
		}
		$names=array('lang'=>'Most Frequent browser language'
		,'os'=>'Most frequent operating system'
		,'vendor'=>'Most frequent device vendor'
		,'start'=>'Most frequent startpages');
/*
echo "<pre>";
print_r($rows);
die("</pre>");*/
		return array('tables'=>array('main'=>$this->getTable($rows,$names[$mode])));
	}
}
