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

	private function getTable($rows,$name)
	{
		return 		array(/*table definition*/
					'type'=>strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault())
                                        ,'name'=>$name
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
                                	                        'name'=>$name
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

	private function getTables($rows,$mode)
	{
		$names=array('lang'=>'Most Frequent browser language'
		,'langf'=>'Most Frequent browser language flavour'
		,'os'=>'Most frequent operating system'
		,'vendor'=>'Most frequent device vendor'
		,'start'=>'Most frequent startpages');
		$windowsnames=array('NT 6.2'=>'8'/*probybly*/
		,'NT 6.1'=>'7'
		,'NT 6.0'=>'Vista'
		,'NT 5.1'=>'XP'
		,'NT 5.0'=>'2000');
		if ($mode=='os') {
/*			echo "<pre>";
			print_r($this->OSoverview);
			die("<pre>");*/
			$tables=array();
			$orows=array();
			arsort($this->OSoverview);
			//generate multiple tables and charts
			foreach ($this->OSoverview as $cat => $sum) {
				if (count($this->OSrows[$cat])==1) {
					if ($this->OSrows[$cat][0][0]) $cat=$this->OSrows[$cat][0][0];
					$details=false;
				} else {
					//do detail table
					$rows=array();
					foreach ($this->OSrows[$cat] as $row) {
						$appendix='';
						if ($cat=='Windows') {
							$wname=str_replace(' 64bit','',$row[0]);
							if (isset($windowsnames[$wname])) $appendix=' (e.g. '.$windowsnames[$wname].')';
						}
						$rows[]=$this->handleLine($row[0].$appendix,$row[1]
						,round($row[1]*1000/$sum)/10,false,false);
					}
					$total=array($this->handleLine('Total',$sum,100,true,true));
					$tables[$cat]=$this->getTable(array_merge($total,$rows,$total)
					,'Details: '.$cat.' ('.(round($sum*1000/$this->OStotal)/10).'%)');

					$details=true;
				}
				$orows[]=$this->handleLine($cat.($details?' *':''),$sum
				,round($sum*1000/$this->OStotal)/10,false,false);

			/*legacy: $rows[]=$this->handleLine(str_replace('|',', ',$row[0]),$row[1]
				,round($row[1]*1000/$this->OStotal)/10,false,false);*/
			}
			$total=array($this->handleLine('Total',$this->OStotal,100,true,true));
			$otable=array('overview'=>$this->getTable(array_merge($total,$orows,$total),$names[$mode]." ( * Details available )"));
			return array('tables'=>array_merge($otable,$tables));
		}
		return array('tables'=>array('main'=>$this->getTable($rows,$names[$mode])));
	}

	private function handleOSLine($line)
	{
		$cats=explode("|",$line[0]);
		$cat=$cats[0];
		if (count($cats)>1 && $cats[1]) $name=$cats[1]; else $name=$cats[0];
		$value=$line[1];
		if (isset($this->OSoverview[$cat])) {
			$this->OSoverview[$cat]+=$value;
			$this->OSrows[$cat][]=array($name,$value);
		}
		else {
			$this->OSoverview[$cat]=$line[1];
			$this->OSrows[$cat]=array();
			$this->OSrows[$cat][]=array($name,$value);
		}
		$this->OStotal+=$line[1];
		//legacy: $this->OSrows[]=$line;
	}

	private function OSInit()
	{
		$this->OSrows=array();
		$this->OSoverview=array();
		$this->OStotal=0;
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
		if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"lavour")!==false) $mode='langf';
		else if (strpos($this->getReportGroup()->getCodeTemplate()->getTitle(),"anguage")!==false) $mode='lang';
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
			case "langf":
			case "lang":
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where AND accept_language<>''");
				//limit to results >= 0.2% (0.15% = 1/667)
				$stmt=$db->query("SELECT lower(".($mode=='langf'?'SUBSTRING(accept_language,1,2)':'accept_language').") as lang, count(*) as cnt $from $where AND accept_language<>'' GROUP BY lang HAVING cnt > ".ceil($total[0][0]/667)." ORDER BY cnt desc $limit");
				break;
			case "os":
//extract os from user_agent!?
				$where.="AND type='guest'";
				//$total=$db->fetchAll("SELECT count(*) $from $where");
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
	,IF( user_agent like '%Android%'
	,'Linux|Android Other'
	,IF( user_agent like '%gingerbread%'
	,'Linux|Android Other'
	,IF( user_agent like '%Maemo%'
	,'Linux|Maemo'
	,IF( user_agent like '%Ubuntu%'
	,'Linux|Ubuntu'
	,IF( user_agent like '%WebOS%'
	,'Linux|WebOS'
	,IF( user_agent like '%zbov%'
	,'Linux|Android Other'
	,IF( user_agent like '%Tizen%'
	,'Linux|Tizen'
	,'Linux|Linux Other'))))))))
,IF( (@posb:=LOCATE('Blackberry',user_agent)) > 0
	,IF( (@pos:=LOCATE(' Blackberry ',user_agent)) > 0
		,IF ( (@pos1:=LOCATE('Version/',user_agent)) > 0
			,IF( (@pos2:=LOCATE('.',user_agent,@pos1+11)) > 0
				,CONCAT('Blackberry|Blackberry ',SUBSTRING(user_agent,@pos1+8,@pos2-@pos1-8))
				,'Blackberry|Blackberry Other')
			,'Blackberry|Blackberry Other')
		,IF ( (@pos1:=LOCATE('/',user_agent,@posb+12)) > 0
			,IF( (@pos2:=LOCATE('.',user_agent,@pos1+4)) > 0
				,CONCAT('Blackberry|Blackberry ',SUBSTRING(user_agent,@pos1+1,@pos2-@pos1-1))
				,'Blackberry|Blackberry Other')
			,'Blackberry|Blackberry Other')
		)
,IF(user_agent like '%Windows %'
	,IF( (@pos1:=LOCATE('Windows NT',user_agent)) > 0
		,CONCAT('Windows|NT ',SUBSTRING(user_agent,@pos1+11,3),IF(user_agent like '%WOW64%',' 64bit',''))
		,IF( (@pos1:=LOCATE('Windows Phone',user_agent)) > 0
			,IF( (@pos2:=LOCATE('=',user_agent,@pos1+17)) > 0
				,CONCAT('Windows|Phone ',SUBSTRING(user_agent,@pos1+17,@pos2-@pos1-17))
				,'Windows|Phone Other')
			,'Windows|Windows Other')
		)
,IF(user_agent like '%Macintosh%'
	,IF( (@pos1:=LOCATE(' Mac OS X ',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+10)) > 0
			,CONCAT('Macintosh|Mac OS X ',REPLACE(SUBSTRING(user_agent,@pos1+10,@pos2-@pos1-10),'_','.'))
			,'Macintosh|Mac OS X Other')
		,'Macintosh|Macintosh Other')
,IF( (@pos:=LOCATE('iPad',user_agent)) > 0
	,IF( (@pos1:=LOCATE(' OS ',user_agent,@pos+4)) > 0
		,IF( (@pos2:=LOCATE(' ',user_agent,@pos1+4)) > 0
	                ,CONCAT('iOS|iPad iOS ',REPLACE(SUBSTRING(user_agent,@pos1+4,@pos2-@pos1-4),'_','.'))
	        	,'iOS|iPad Unknown')
        	,'iOS|iPad Other')
,IF(user_agent like '%iPod%','iOS|iPod'
,IF(user_agent like '%MeeGo%','Linux|MeeGo'
,IF(user_agent like '%Bada%','Linux|Samsung Bada'
,IF(user_agent like '%RIM Tablet%','Blackberry|RIM Tablet OS'
,IF(user_agent like '%Symb%'
	,IF( (@pos1:=LOCATE('SymbianOS',user_agent)) > 0
		,IF( (@pos2:=LOCATE('=',user_agent,@pos1+10)) > 0
			,CONCAT('Symbian|SymbianOS ',SUBSTRING(user_agent,@pos1+10,@pos2-@pos1-10))
			,'Symbian|SymbianOS Other')
		,IF(user_agent like '%Symbian/3%'
		,'Symbian|Symbian^3 (Anna)'
		,'Symbian|Symbian Other'))
,IF(user_agent like '%SAMSUNG%','Samsung'
,IF(user_agent like '%Nokia%','Nokia'
,IF(user_agent like '%SonyEricsson%','SonyEricsson'
,IF(user_agent like '%webOS%','Linux|WebOs'
,IF(user_agent like '%Tizen%','Linux|Tizen'
,IF(user_agent like '%=28LG%','LG'
,IF(user_agent like 'LG-%','LG'
,'Other OS|'
)))))))))))))))))) as os
,count(*) as cnt $from $where GROUP BY os ORDER BY cnt desc");//$limit

/* notes:
 bada moved into linux? (as it merges with tizen, and anyways already had a linux kernel and a gnu toolchain)
 'Linux zbov' is used by opera as desktop useragent (running on android)
*/
				break;
			case "vendor":
//auch nur guest zählen (45% apple), oder eben alles (inclusive mac-auths (65% apple))
//use user_id and vendor table
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT v.name, count(*) as cnt $from l
INNER JOIN vendors v ON v.prefix = LEFT(l.username,8) $where GROUP BY v.name HAVING cnt > ".ceil($total[0][0]/667)." ORDER BY cnt desc $limit");
				break;
			case "start":
//only use domain of userurl!?
				$where.="AND type='guest'";
				$total=$db->fetchAll("SELECT count(*) $from $where");
				$stmt=$db->query("SELECT LEFT(user_url,LOCATE('/',user_url,9)) as domain, count(*) as cnt $from $where GROUP BY domain HAVING domain<>'' AND cnt > ".ceil($total[0][0]/667)." ORDER BY cnt desc $limit");
				break;
		}
		$rows=array();
		if ($mode=='os') $this->OSInit();
		else {
			$other=$totalcount=$total[0][0];
			if ($other>0) $rows[]=$this->handleLine("Total",$other,round($other*1000/$totalcount)/10,true,true);
		}

		while ($row=$stmt->fetch()) {
			if ($mode!='os') {
				$empty=false;
				$other-=$row[1];
				$rows[]=$this->handleLine($row[0],$row[1],round($row[1]*1000/$totalcount)/10,false,false);
			}
			else $this->handleOSLine($row);
		}
		if ($mode!='os') {
			if ($empty) {// nothing found
				$rows[]=$this->handleLine("[No Data!]",0,0,true,false);
			}
			else if ($other>0) {
				$topic=array('lang'=>'language','langf'=>'language','vendor'=>'vendor','start'=>'startpage');
				$rows[]=$this->handleLine("Other ".$topic[$mode],$other,round($other*1000/$totalcount)/10,true,false);
			}
		}
		return $this->getTables($rows,$mode);
	}
}
