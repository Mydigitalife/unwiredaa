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
class Reports_Service_CodeTemplate_Sample extends Reports_Service_CodeTemplate_AbstractStructured {/*!!?? base on _Structured after moving && sanitzing all structure/helper functions into it*/
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
		$this->summable=false;/*false fails in prepeare Table*/
		/*verify if groupdepth is activated for this report!!??*/
		if ($this->getReportGroup()->getCodeTemplate()->isGroupDepthSupported()) $this->maxdepth=$this->getReportGroup()->getGroupDepthMax();
		else $this->maxdepth=2;
		$this->prepareTemporaryTable($groupIds);
		$tables=array();
/*
echo "<pre>";
print_r($this->rgroup);
die("</pre>");
*/

		/*use the temporary table for our own query (hmm count is timmer 0) DISTINCT s.user_mac*/
		$res=$this->db->fetchall("SELECT rg.reportgroup, GROUP_CONCAT(rg.node_id), COUNT(DISTINCT s.user_mac)
FROM acct_internet_session s
INNER JOIN acct_internet_roaming r ON s.session_id=r.session_id
INNER JOIN node_reportgroup rg ON r.node_id = rg.node_id
WHERE
(       r.start_time BETWEEN '$dateFrom' AND '$dateTo'
        OR r.stop_time BETWEEN '$dateFrom' AND '$dateTo'
        OR ( r.start_time < '$dateFrom' AND ( r.stop_time > '$dateFrom' OR ISNULL(r.stop_time)))
)
GROUP BY rg.reportgroup");
		$last_rid=-1;/*-1 to indicate first appendStructureRange() that there is no previous sum*/
		$last_depth=0;
		$rows=array();
		$this->ncache=array(0=>0);
                foreach ($res as $line) {
//echo serialize($line)."<br>";
			if ($line[0]!=$last_rid) {
				//print missing headers since last group
				if ($this->summable) $path=$this->appendStructureRange(&$rows,&$running_sum,$last_rid,$line[0],&$last_depth);
				/*instead of running sum give db result -> !!! causes value to be lost!!*/
				else $path=$this->appendStructureRange(&$rows,&$line[2],$last_rid,$line[0],&$last_depth);
				$last_rid=$line[0];
			}
			//do the running_sum (if applicable)
			if ($this->summable) $running_sum+=$line[2];
			//print line
			/*at this depth level printing the single resultlines of this node list report is pointless*/
			/*and if not summable, every group has its own result presented with structure anyways*/
			if (($this->maxdepth!=-1) && ($this->summable==true)) 
				$rows[]=array( /*if we have only one result per reportgroup we can use rgroup array to get its name*/
				'data'=>array("<span title='".$path."/'>".str_repeat("&nbsp;.&nbsp; ",$last_depth+1).$this->rgroup[$line[0]][0]."!</span>",$line[2])
				,'translatable'=>false
				,'class'=>array('','right')
				);
                }
		/*if ($last_rid >= (count($this->rgroup)-1) )*/
		$this->appendStructureRange(&$rows,&$running_sum,$last_rid,count($this->rgroup)-1,&$last_depth); /*sometimes causes a endless loop*/
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
if ($this->maxdepth==1) $cdepths=array(1);
else $cdepths=array(1,2);

	        return array(
			'tables'=>array(/*array of tables*/
                                'main'=>array( /*table 1*/
/*use title specifed by user?*/
					'type'=>$type
					,name=>'Unique Users'/*!!?? move to chartOptions?*/
					,'chartOptions'=>array(
						'type'=>'ColumnChart'
						,'width'=>360 /*max 370 for 2 charts sidebyside*/
						,'height'=>350
						,'depths'=>$cdepths/*either single value, or an array -> multiple charts*/
						,'nativeOptions'=>"legend:{position :'none'}")/*passed 1:1 to googleCharts options*/
                                        ,'colDefs'=>array(/*array of coldefs*/
                                                array(/*first coldef*/
                                                        array( /*advanced column def as array*/
								'name'=>'name'
                                                                ,'translatable'=>false
                                                                ,'class'=>'bold'
                                                        )
                                                        ,array( /*advanced column def as array*/
								'name'=>'unique users'
                                                                ,'translatable'=>false
                                                                ,'class'=>'bold'
                                                        )
                                                ) /* end of first coldef*/
                                        ) /*end of coldefs*/
                                        ,'rows'=>$rows
                                ) /*end of table*/
                        )/*end of array of tables*/
		);
	}
}
