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

class Reports_Service_CodeTemplate_DNS extends Reports_Service_CodeTemplate_Abstract {

	private function getTable($rows,$name)
	{
		return 		array(/*table definition*/
//					'type'=>strtolower($this->getReportGroup()->getFormatSelected())
					'type'=>'both'
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

	private function getTables($rows,$name)
	{
		return array('tables'=>array('main'=>$this->getTable($rows,$name)));
	}

/*abstractable default line handler!?*/
	private function handleLine($name,$value,$perc,$bold,$hide,$link)
	{
		/*if (!$link) $a_start=$a_end='';
		else {
			$a_start='<a href="'.$_SERVER['REQUEST_URI'].'?domain='.$name.'">';
			$a_end='</a>';
		}*/
		$a=array(/*data row*/
			'data'=>array($a_start.$name.$a_end,$value,$perc."%")
			,'translatable'=>false
			,'device'=>$hide
			,'class'=>array(($bold?'bold':''),($bold?'bold right':'right'),($bold?'bold right':'right'))
		); /*end of data row*/
		if ($link) return array_merge($a,array('url'=>$_SERVER['REQUEST_URI'].'?domain='.$name));
		else return $a;
	}

	public function getData($groupIds, $dateFrom, $dateTo) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$db->setFetchMode(Zend_Db::FETCH_NUM);
		$empty=true;

		//show either Language, Startpage, OS, or Vendor
		$mode=$this->getReportGroup()->getCodeTemplate()->getOption('mode');
                if (!$mode) $mode='linked';
		$where="WHERE time >= '$dateFrom' AND time < '$dateTo'";
		$limit="LIMIT 100";
		if (isset($_GET['domain'])) {
			$from="FROM dns_log_sum";
			$where.=" AND sld = '".$_GET['domain']."'";

			$total=$db->fetchAll("SELECT SUM(count) $from $where");
			$stmt=$db->query("SELECT CONCAT(prefix,'.',sld) as domain, SUM(count) as cnt $from $where GROUP BY domain HAVING cnt > ".ceil($total[0][0]/667)." ORDER BY cnt desc $limit");
			$name='DNS detail report of '.$_GET['domain'];
			$link=false;
		}
		else {
			$from="FROM dns_log_sum_sld";

			$total=$db->fetchAll("SELECT SUM(count) $from $where");
			$stmt=$db->query("SELECT sld as domain, SUM(count) as cnt $from $where GROUP BY domain HAVING cnt > ".ceil($total[0][0]/667)." ORDER BY cnt desc $limit");
			$name='DNS overview';
			$link=true;
		}

		$rows=array();
		$other=$totalcount=$total[0][0];
		if ($other>0) $rows[]=$this->handleLine("Total",$other,round($other*1000/$totalcount)/10,true,true,false);

		while ($row=$stmt->fetch()) {
			$rows[]=$this->handleLine($row[0],$row[1],round(1000*$row[1]/$totalcount)/10,false,false,$link);
			$other-=$row[1];
			$empty=false;
		}

                if ($empty) {// nothing found
                        $rows[]=$this->handleLine("[No Data!]",0,0,true,false,false);
                }
                else if ($other>0) {
                        $rows[]=$this->handleLine("Other",$other,round($other*1000/$totalcount)/10,true,false,false);
                }
/*
print('<pre>');
print_r($this->getTables($rows,$name));
die('</pre>');*/
		return $this->getTables($rows,$name);
	}
}
