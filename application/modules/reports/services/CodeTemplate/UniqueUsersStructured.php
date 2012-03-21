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
class Reports_Service_CodeTemplate_UniqueUsersStructured extends Reports_Service_CodeTemplate_AbstractStructured {/*!!?? base on _Structured after moving && sanitzing all structure/helper functions into it*/
/*todo: support inner interval i.e. run multiple rounds over the group structure*/

/*add parameter to leave empty leaves -> not needed!!*/
/*add parameter to adapt to various columns, or add a callback function to style the line, or calculate the sums*/
	private function appendStructureRange(&$rows,&$running_sum,$rid,$last_rid,&$last_depth)
	{
//echo "appendStructureRange running_sum:".$running_sum.",rid: ".$rid.",lastrid: ".$last_rid.",last_depth: ".$last_depth." <hr>";
		/*use ncache to find all parent lines and calc there sums if applicable*/
$limit=0;
		if ($this->summable) {
			if ($last_depth && ($rid>=0)) while ($last_depth>=0) {
if (($limit++)>10) {die("/[dloop!]".$last_depth);}
				$rows[$this->ncache[$last_depth]]['data'][1]+=$running_sum;
				$last_depth--;
			}
			$last_depth=0;
			$running_sum=0;
		}
		$rid++;
$limit=0;
		$path="";
		/*print out missing structures*/
		while ($rid<=$last_rid)
		{
if (($limit++)>1000) {die("/[kloop!]");/*with summable 10 is enough?*/}
			$last_depth=$this->rgroup[$rid][1];
			$this->ncache[$last_depth]=count($rows);
			$pid=$this->rgroup[$rid][2];$path=$this->rgroup[$rid][0];
$plimit=0;
			/*prepare path for tooltip*/
			$path="";
			while ($pid) {
if (($plimit++)>10) {$path.="/[ploop!]";break;}
				$path=$this->rgroup[$pid][0]."/".$path;
				$pid=$this->rgroup[$pid][2];
			}
/*mis-using the class field for style and title is not the finest,..**/
			$c1style='" style="padding-left:'.$last_depth.'0px;" title="'.$path;
			$rows[count($rows)]=array(
				'data'=>array($this->rgroup[$rid][0],($this->summable?0:(($rid!=$last_rid)?0:($running_sum?$running_sum:0))))/*initialize sum with 0 (if summable) else take actual value (for actual row, else 0)*/
/*!!?? for nun summable reports running sum should not be used more than once*/
				,'translatable'=>false
				,'depth'=>$last_depth
				,'class'=>($this->rgroup[$rid][3]?array(''.$c1style,'right'):array('bold'.$c1style,'right bold'))/*format devices different*/
			);
			$rid++;
		}
		return $path;
	}

/*get report specific parts out of this function (ie.e find a method to do runnings sums (also multiple ones) either fully classspecific or not, or overwiteable)*/
/*also add support to inner intervals*/
	public function getData($groupIds, $dateFrom, $dateTo) {
	        $this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$mode=$this->getReportGroup()->getCodeTemplate()->getOption('mode');
		if (!$mode) $mode='unique';

		if ($mode=='unique') $this->summable=false;
		else $this->summable=true;

		if ($mode!='billingb') {
			/*verify if groupdepth is activated for this report!!??*/
			if ($this->getReportGroup()->getCodeTemplate()->isGroupDepthSupported()) $this->maxdepth=$this->getReportGroup()->getGroupDepthMax();
			else $this->maxdepth=2;
		} else $this->maxdepth=1;

		$this->startTime=$this->duration=$this->getReportGroup()->getDateTo()->getTimestamp();
		$this->duration=$this->startTime-$this->getReportGroup()->getDateFrom()->getTimestamp();
		$this->innerCount=0;//means no inner interval
		if (($mode!='billingb')&&($this->getReportGroup()->getCodeTemplate()->isInnerIntervalSupported())) {
			$this->innerInterval=$this->getReportGroup()->getInnerInterval()*60;
			if (($this->innerInterval)&&($this->innerInterval>0)) $this->innerCount=ceil($this->duration/$this->innerInterval);
			else $this->innerInterval=$this->duration;
//with inner intervals summing does not work currently!?
$this->summable=false;
		}
		else $this->innerInterval=$this->duration;

		$this->prepareTemporaryTable($groupIds);
		$tables=array();
/*
echo "<pre>";
print_r($this->rgroup);
die("</pre>");
*/

                /*prepare temporary table*/
                $this->db->query("CREATE TEMPORARY TABLE unique_session
 (session_id BIGINT NOT NULL, node_id INT NOT NULL, intv INT NOT NULL)");
//hmm none of this keys show speed miproves on smaller sets (at least with < 1k unique sessions)
//, KEY `main` (session_id))");
//, UNIQUE KEY `main` (node_id, `intv`, session_id))");
//echo microtime()." 1<hr>";

		$ids=array();
		$resi=$this->db->fetchall("SELECT DISTINCT node_id from node_reportgroup");//GROUP_CONCAT(DISTINCT macht desweilen zuviele "," *G
		foreach ($resi as $line) if ($line[0]) $ids[]=$line[0];

		if ($mode=='unique') {
			$this->db->query("INSERT INTO unique_session
SELECT DISTINCT i.session_id, i.node_id, ( (UNIX_TIMESTAMP(time)-UNIX_TIMESTAMP('$dateFrom') ) DIV $this->innerInterval) as intv
FROM acct_internet_interim i
WHERE i.time >= '$dateFrom' AND i.time < '$dateTo' AND i.node_id IN (".implode(",",$ids).")");
//!!??unix_timestamp might need an offset if starttime is not correctly aligned to interval!?
//innerinterval grouping: UNIX_TIMESTAMP(time) DIV @intv

echo microtime()." 2<hr>";
//die();//test large: 47 seconds
//echo serialize($this->db->fetchall("SELECT count(*) FROM unique_session"));

//test speed of simpler join
			$this->db->query("CREATE TEMPORARY TABLE unique_mac
(user_mac BIGINT NOT NULL, node_id INT NOT NULL, intv INT NOT NULL, KEY `node` (node_id))");

			$resi=$this->db->query("INSERT INTO unique_mac SELECT DISTINCT s.user_mac, us.node_id, us.intv
FROM acct_internet_session s
INNER JOIN unique_session us ON s.session_id=us.session_id");
echo microtime()." 3<hr>";

//prepare same table-"size" result for inner interval counts, as for total counts
			if ($this->innerCount>1)
			$resi=$this->db->fetchall("SELECT i.reportgroup, GROUP_CONCAT(i.intv), GROUP_CONCAT(i.cnt) FROM
(SELECT rg.reportgroup, um.intv, COUNT(DISTINCT um.user_mac) as cnt
FROM unique_mac um
INNER JOIN node_reportgroup rg ON um.node_id = rg.node_id
GROUP BY reportgroup, um.intv) i
GROUP BY i.reportgroup");
echo microtime()." 4<hr>";

//for summable reports, we could calc this query in php, but it shoudl be quite fast anyways
			$res=$this->db->fetchall("SELECT rg.reportgroup, -1 as intv, COUNT(DISTINCT um.user_mac)
FROM unique_mac um
INNER JOIN node_reportgroup rg ON um.node_id = rg.node_id
GROUP BY reportgroup");
		} else if (($mode=='billingb')||($mode=='traffic')) {/*traffic mode*/
			$resi=$this->db->fetchall("SELECT i.reportgroup, GROUP_CONCAT(i.intv), GROUP_CONCAT(i.cnt) FROM
(SELECT rg.reportgroup, ((UNIX_TIMESTAMP(time)-UNIX_TIMESTAMP('$dateFrom')) DIV $this->innerInterval) as intv, ceiling(SUM(bytes_up+bytes_down)/(1024*1024)) as cnt
FROM acct_total_node_sum ns
INNER JOIN node_reportgroup rg ON ns.node_id = rg.node_id
WHERE ns.time BETWEEN '$dateFrom' AND '$dateTo'
GROUP BY reportgroup, intv) i
GROUP BY i.reportgroup");
//die(serialize($resi));

			$res=$this->db->fetchall("SELECT rg.reportgroup, -1 as intv, ceiling(SUM(bytes_up+bytes_down)/(1024*1024))
FROM acct_total_node_sum ns
INNER JOIN node_reportgroup rg ON ns.node_id = rg.node_id
WHERE ns.time BETWEEN '$dateFrom' AND '$dateTo'
GROUP BY reportgroup");
		}
/*
echo "<pre>";
print_r($res);
die("</pre>".microtime());
*/
		$last_rid=-1;/*-1 to indicate first appendStructureRange() that there is no previous sum*/
		$last_depth=0;
		$rows=array();
		$this->ncache=array(0=>0);
		$ri=-1;
                foreach ($res as $line) {
			$ri++;
//echo serialize($line)."<br>";
			if ($line[0]!=$last_rid) {
				//print missing headers since last group
				if ($this->summable) $path=$this->appendStructureRange($rows,$running_sum,$last_rid,$line[0],$last_depth);
				/*instead of running sum give db result -> !!! causes value to be lost!!*/
				else $path=$this->appendStructureRange($rows,$line[2],$last_rid,$line[0],$last_depth);
				$last_rid=$line[0];
//experimental inner count append
//!!?? append structure range does not fill with zeros !!??
//!!?? innerintervals are not summed up with summable reports
				if ($this->innerCount>1) {
					$idx=explode(",",$resi[$ri][1]);
					$vals=explode(",",$resi[$ri][2]);
					//precreate cells (to be able to access them over their numeric index)
					//if ($this->innerCount>1)
					for ($x=1;$x<=$this->innerCount;$x++) $rows[count($rows)-1]['data'][]=0;
					$ii=-1;
					foreach ($idx as $idt) {
						$ii++;
						$idxt=($idt*1)+2;
						$rows[count($rows)-1]['data'][$idxt]=$vals[$ii];
					}
				}
			}
			//do the running_sum (if applicable)
			if ($this->summable) $running_sum+=$line[2];
			//print line
			/*at this depth level printing the single resultlines of this node list report is pointless*/
			/*and if not summable, every group has its own result presented with structure anyways*/
			if (($this->maxdepth!=-1) && ($this->summable==true))
				$c1style='" style="padding-left:'.($last_depth+1).'0px;" title="'.$path;
				$rows[]=array( /*if we have only one result per reportgroup we can use rgroup array to get its name*/
				//'data'=>array("<span title='".$path."/'>".str_repeat("&nbsp;.&nbsp; ",$last_depth+1).$this->rgroup[$line[0]][0]."!</span>",$line[2])
				'data'=>array($this->rgroup[$line[0]][0],$line[2])
				,'translatable'=>false
				,'depth'=>$last_depth+1
				,'class'=>array(''.$c1style,'right')
				);
                }
		/*if ($last_rid >= (count($this->rgroup)-1) )*/
		$this->appendStructureRange($rows,$running_sum,$last_rid,count($this->rgroup)-1,$last_depth); /*sometimes causes a endless loop*/
/*
echo "<pre>";
print_r($this->rgroup);
print_r($rows);
die("");
*/

/*move this into abstrac class!??*/
$dtype=strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault());
if ($dtype=='notuserdefineable') $type='both'; /*our own hardcoded default*/
else {
	$type=strtolower($this->getReportGroup()->getFormatSelected());
	/*if user did not choose a format (even if its poiible now, but maybe not when the report was created)*/
	if ($type=='notuserdefineable') $type=$dtype; /*we choose the actual default*/
}

/*configure mathcing chart depths (for maxdepth 0 or -1 we should check if real available depth >= 2)*/
/*if ($this->maxdepth==1) $cdepths=array(1);
else $cdepths=array(1,2);*/

$innerColumns=array();
if ($this->innerCount>0) {
  //use somewhat sane time-formats (always a bit too m,uch as starttime might be not necesarrily midnight)
  if ($this->innerInterval >= 200000) $format='Y/m/d';
  else if ($this->innerInterval >= 8000) $format='Y/m/d H\h';
  else $format='Y/m/d H:i';
  for ($x=0;$x<$this->innerCount;$x++) {
    $s=($this->startTime*1)+($this->innerInterval*($x))+(3600);
    $e=$s+$this->innerInterval;
    $n=date($format,$s)." - ".date($format,$e);
    $innerColumns[]=array('name'=>$n,'class'=>'bold');
// 'height'=>100,'width'=>30,'iclass'=>'bold" style="white-space:nowrap; position:absolute; margin-top:-20px; margin-left:-33px; -webkit-transform: rotate(-85deg);');
  }
//!!?? calculate date
}

	        return array(
			'tables'=>array(/*array of tables*/
                                'main'=>array( /*table 1*/
/*use title specifed by user?*/
					'type'=>$type
					,'name'=>($mode!='unique'?'Traffic in MByte':'Unique Users')/*!!?? move to chartOptions?*/
					,'chartOptions'=>array(
						'type'=>(($this->innerCount>1)?'LineChart':'ColumnChart')
						,'width'=>770 /*max 370 for 2 charts sidebyside*/
						,'height'=>900
						,'switchAxes'=>($this->innerCount>1)
						,'depths'=>array(0,1)/*either single value, or an array -> multiple charts*/
						,'nativeOptions'=>"legend:{position :'right'}")/*passed 1:1 to googleCharts options*/
                                        ,'colDefs'=>array(/*array of coldefs*/
/*                                                array(//first coldef
                                                        array( //advanced column def as array
								'name'=>'name'
                                                                ,'translatable'=>false
                                                                ,'class'=>'bold'
                                                        )
                                                        ,array( //advanced column def as array
								'name'=>'unique users'
                                                                ,'translatable'=>false
                                                                ,'class'=>'bold'
								,'colspan'=>(1+$this->innerCount)
                                                        )
                                                ) // end of first coldef
						,*/
						array_merge(
							array(
								array('name'=>($mode!='unique'?'Traffic':'Unique Users').' of','translatable'=>false,'class'=>'bold')
								,array('name'=>'Total','translatable'=>false,'class'=>'bold')
							)
							,$innerColumns
						)
                                        ) /*end of coldefs*/
                                        ,'rows'=>$rows
                                ) /*end of table*/
                        )/*end of array of tables*/
		);
	}
}
