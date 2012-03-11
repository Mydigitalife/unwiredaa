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
		$res=$db->fetchAll($node_id_query); $node_ids=array();
		foreach ($res as $value) $node_ids[]=$value[0];
		$node_id_str="node_id IN (".implode(",",$node_ids).") AND";

		/*query auth_log*/
		$from="FROM auth_log";
		$where="WHERE $node_id_str time BETWEEN '$dateFrom' AND '$dateTo'";
		$limit="LIMIT 50";
		switch ($mode) {
			case "lang":
//filter strange accept_languages, or language-variations!?
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT accept_language, count(*) as cnt $from $where GROUP BY accept_language ORDER BY cnt desc $limit");
				break;
			case "os":
//extract os from user_agent!?
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT
IF( (@pos:=LOCATE('iPhone',user_agent)) > 0
	,IF( (@pos1:=LOCATE(' OS ',user_agent,@pos+6)) > 0
		,IF( (@pos2:=LOCATE(' ',user_agent,@pos1+4)) > 0
	                ,CONCAT('iOS|iPhone iOS ',REPLACE(SUBSTRING(user_agent,@pos1+4,@pos2-@pos1-4),'_','.'))
	        	,'iOS|iPhone Unknown')
        	,'iOS|iPhone Other')
,IF(user_agent like '%Linux%'
	,IF( (@pos1:=LOCATE('Android ',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+8)) > 0
			,CONCAT('Linux|',SUBSTRING(user_agent,@pos1,@pos2-@pos1))
			,'Linux|Android Other')
		,'Linux|Other')
,IF(user_agent like '%Blackberry%'
	,IF( (@pos1:=LOCATE(' Blackberry ',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+12)) > 0
			,CONCAT('Blackberry|Blackberry ',SUBSTRING(user_agent,@pos1+12,@pos2-@pos1-12))
			,'Blackberry|Blackberry Other')
		,'Blackberry|Blackberry Other')
,IF(user_agent like '%Windows %'
	,IF( (@pos1:=LOCATE('Windows NT',user_agent)) > 0
		,CONCAT('Windows|NT ',SUBSTRING(user_agent,@pos1+11,3),IF(user_agent like '%WOW64%','64bit',''))
		,IF( (@pos1:=LOCATE('Windows Phone',user_agent)) > 0
			,IF( (@pos2:=LOCATE('=',user_agent,@pos1+17)) > 0
				,CONCAT('Windows|Phone ',SUBSTRING(user_agent,@pos1+17,@pos2-@pos1-17))
				,'Windows|Phone Other')
			,'Windows Other')
		)
,IF(user_agent like '%Macintosh%'
	,IF( (@pos1:=LOCATE(' Mac OS X ',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+10)) > 0
			,CONCAT('Macintosh|Mac OS X ',REPLACE(SUBSTRING(user_agent,@pos1+10,@pos2-@pos1-10),'_','.'))
			,'Macintosh|Mac OS X Other')
		,'Macintosh Other')
,IF( (@pos:=LOCATE('iPad',user_agent)) > 0
	,IF( (@pos1:=LOCATE(' OS ',user_agent,@pos+4)) > 0
		,IF( (@pos2:=LOCATE(' ',user_agent,@pos1+4)) > 0
	                ,CONCAT('iOS|iPad iOS ',REPLACE(SUBSTRING(user_agent,@pos1+4,@pos2-@pos1-4),'_','.'))
	        	,'iOS|iPad Unknown')
        	,'iOS|iPad Other')
,IF(user_agent like '%iPod%','iOS|iPod'
,IF(user_agent like '%MeeGo%','Linux|MeeGo'
,IF(user_agent like '%Bada%','Samsung|Bada'
,IF(user_agent like '%RIM Tablet%','Blackberry|RIM Tablet OS'
,IF(user_agent like '%Symb%'
	,IF( (@pos1:=LOCATE('SymbianOS',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+9)) > 0
			,CONCAT('Symbian|SymbianOS ',SUBSTRING(user_agent,@pos1+9,@pos2-@pos1-9))
			,'Symbian|SymbianOS Other')
		,'Symbian|Other')
,IF(user_agent like '%SAMSUNG%','Samsung|Other'
,IF(user_agent like '%Nokia%','Nokia'
,IF(user_agent like '%SonyEricsson%','SonyEricsson'
,IF(user_agent like '%webOS%','HP WebOs'
,IF(user_agent like '%=28LG%','LG'
,IF(user_agent like 'LG-%','LG'
,'Other|'
))))))))))))))))) as os
,count(*) as cnt $from $where GROUP BY os ORDER BY cnt desc");//$limit

/*
,CONCAT('Other|',user_agent)
*/
				break;
			case "vendor":
//auch nur guest zählen (45% apple), oder eben alles (inclusive mac-auths (65% apple))
//use user_id and vendor table
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT v.name, count(*) as cnt $from l
INNER JOIN vendors v ON v.prefix = LEFT(l.username,8) $where GROUP BY v.name ORDER BY cnt desc LIMIT 20");//$limit
				break;
			case "start":
//only use domain of userurl!?
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT LEFT(user_url,LOCATE('/',user_url,9)) as domain, count(*) as cnt $from $where GROUP BY domain HAVING domain<>'' ORDER BY cnt desc $limit");
				break;
		}
		$other=$totalcount=$total[0][0];
		if ($other>0) $rows[]=$this->handleLine("Total",$other,round($other*1000/$totalcount)/10,true,true);

		while ($row=$stmt->fetch()) {
			$empty=false;
			$other-=$row[1];
			$rows[]=$this->handleLine($row[0],$row[1],round($row[1]*1000/$totalcount)/10,false,false);
		}
		if ($empty) {// nothing found
			$rows[]=$this->handleLine("[No Data!]",0,0,true,false);
		}
		else if ($other>0) {
			$rows[]=$this->handleLine("Other",$other,round($other*1000/$totalcount)/10,true,false);
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
