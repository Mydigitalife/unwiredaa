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
class Reports_Service_CodeTemplate_APCountStructured extends Reports_Service_CodeTemplate_AbstractStructured {/*!!?? base on _Structured after moving && sanitzing all structure/helper functions into it*/
/*todo: support inner interval i.e. run multiple rounds over the group structure*/

/*reportsspecific chart and table headers/options*/
	private function getResult($rows)
	{
	        return array(
			'tables'=>array(/*array of tables*/
                                'main'=>array( /*table 1*/
/*use title specifed by user?*/
					'type'=>$type
					,name=>'AP Count'/*!!?? move to chartOptions?*/
					,'chartOptions'=>array(
						'type'=>'PieChart'
						,'width'=>360 /*max 370 for 2 charts sidebyside*/
						,'height'=>350
						,'depths'=>1/*either single value, or an array -> multiple charts*/
						,'nativeOptions'=>"legend:{position :'none'}")/*passed 1:1 to googleCharts options*/
                                        ,'colDefs'=>array(/*array of coldefs*/
						array(
							array('name'=>'Group','translatable'=>false,'class'=>'bold')
							,array('name'=>'AP Count','translatable'=>false,'class'=>'bold')
						)
                                        ) /*end of coldefs*/
                                        ,'rows'=>$rows
                                ) /*end of table*/
                        )/*end of array of tables*/
		);
	}

/*reportspecific options (called after defaults and db options got evaluated)*/
	private function setReportOptions()
	{
		//$this->summable=true;
	}

/*reportspecific line sum handler*/
	private function sumLine(&$value,$line)
	{
		$value+=$line[2];
	}

/*reportspecific line handler*/
	private function handleLine($groupname,$value,$depth,$path,$is_device)
	{
		/*mis-using the class field for style and title is not the finest,..**/
		$c1style='" style="padding-left:'.$depth.'0px;" title="'.$path.$groupname;
		return array(
			'data'=>array($groupname,$value)
//			'data'=>array_merge(array($groupname),$values)
			,'translatable'=>false
			,'depth'=>$depth
			,'class'=>($is_device?array(''.$c1style,'right'):array('bold'.$c1style,'right bold'))
		);
	}

/*reportspecific query (column 2++ can be reportspecific)*/
	private function doQuery($groupIds, $dateFrom, $dateTo)
	{
		$this->res=$this->db->fetchall("SELECT rg.reportgroup, 0 as intv, COUNT(n.node_id) as cnt
		FROM node n
		INNER JOIN node_reportgroup rg ON n.node_id = rg.node_id
		WHERE n.status='enabled' AND n.online_status=0
		GROUP BY reportgroup");
	}

/*add parameter to leave empty leaves -> not needed!!*/
/*add parameter to adapt to various columns, or add a callback function to style the line, or calculate the sums*/
	private function appendStructureRange(&$rows,&$running_sum,$rid,$last_rid,&$last_depth)
	{
//echo "appendStructureRange running_sum:".$running_sum.",rid: ".$rid.",lastrid: ".$last_rid.",last_depth: ".$last_depth." <hr>";
		/*use ncache to find all parent lines and calc there sums if applicable*/
$limit=0;
		if ($this->summable) {
			if ($last_depth && ($rid>=0)) while ($last_depth>=0) {
if (($limit++)>100) {die("/[dloop!]".$last_depth);}
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
if (($limit++)>1000) {die("/[kloop!]");}
			$last_depth=$this->rgroup[$rid][1];
			$this->ncache[$last_depth]=count($rows);
			$pid=$this->rgroup[$rid][2];$path=$this->rgroup[$rid][0];
$plimit=0;
			/*prepare path for tooltip*/
			$path="";
			while ($pid) {
if (($plimit++)>100) {$path.="/[ploop!]";break;}
				$path=$this->rgroup[$pid][0]."/".$path;
				$pid=$this->rgroup[$pid][2];
			}
			$path=$this->rgroup[0][0]."/".$path;

			$rows[count($rows)]=$this->handleLine($this->rgroup[$rid][0]
			/*initialize sum with 0 (if summable) else take actual value (for actual row, else 0 (for missing rows))*/
			,($this->summable?0:(($rid!=$last_rid)?0:($running_sum?$running_sum:0)))
                        ,$last_depth,$path,$this->rgroup[$rid][3]);
			$rid++;
		}
		return $path;
	}

/*get report specific parts out of this function (ie.e find a method to do runnings sums (also multiple ones) either fully classspecific or not, or overwiteable)*/
/*also add support to inner intervals*/
	public function getData($groupIds, $dateFrom, $dateTo) {
	        $this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$this->summable=true;/*default summable*/
		/*verify if groupdepth is activated for this report!!??*/
		if ($this->getReportGroup()->getCodeTemplate()->isGroupDepthSupported()) $this->maxdepth=$this->getReportGroup()->getGroupDepthMax();
		else $this->maxdepth=-3;
		$this->startTime=$this->duration=$this->getReportGroup()->getDateTo()->getTimestamp();
		$this->duration=$this->startTime-$this->getReportGroup()->getDateFrom()->getTimestamp();
		$this->innerCount=0;//means no inner interval
		if ($this->getReportGroup()->getCodeTemplate()->isInnerIntervalSupported()) {
			$this->innerInterval=$this->getReportGroup()->getInnerInterval()*60;
			if (($this->innerInterval)&&($this->innerInterval>0)) $this->innerCount=ceil($this->duration/$this->innerInterval);
			else $this->innerInterval=$this->duration;
		}
		else $this->innerInterval=$this->duration;
		$tables=array();

		$this->setReportOptions();//maxdepth -3 means unspecified -> codetemplate may choose
		if ($this->maxdepth==-3) $this->maxdepth=2;

		$this->prepareTemporaryTable($groupIds);

		$this->doQuery($groupIds, $dateFrom, $dateTo);

		$last_rid=-1;/*-1 to indicate first appendStructureRange() that there is no previous sum*/
		$last_depth=0;
		$rows=array();
		$this->ncache=array(0=>0);
		$ri=-1;
                foreach ($this->res as $line) {
			$ri++;
			if ($line[0]!=$last_rid) {
				//print missing headers since last group
				if ($this->summable) $path=$this->appendStructureRange(&$rows,&$running_sum,$last_rid,$line[0],&$last_depth);
				/*instead of running sum give db result -> !!! causes value to be lost!!*/
				else $path=$this->appendStructureRange(&$rows,&$line[2],$last_rid,$line[0],&$last_depth);
				$last_rid=$line[0];
			}
			//do the running_sum (if applicable)
			if ($this->summable) $this->sumLine(&$running_sum,$line);
			//print line
			/*at this depth level printing the single resultlines of this node list report is pointless*/
			/*and if not summable, every group has its own result presented with structure anyways*/
			if (($this->maxdepth!=-1) && ($this->summable==true) && ($last_depth < $this->maxdepth) ) 
				/*if we have only one result per reportgroup we can use rgroup array to get its name*/
				$rows[]=$this->handleLine($this->rgroup[$line[0]][0],$line[2],($last_depth+1),$path,true);
                }
		/*if ($last_rid >= (count($this->rgroup)-1) )*/
		$this->appendStructureRange(&$rows,&$running_sum,$last_rid,count($this->rgroup)-1,&$last_depth); /*sometimes causes a endless loop*/

		$dtype=strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault());
		if ($dtype=='notuserdefineable') $type='both'; /*our own hardcoded default*/
		else {
			$type=strtolower($this->getReportGroup()->getFormatSelected());
			/*if user did not choose a format (even if its poiible now, but maybe not when the report was created)*/
			if ($type=='notuserdefineable') $type=$dtype; /*we choose the actual default*/
		}

		return $this->getResult($rows);
	}
}
