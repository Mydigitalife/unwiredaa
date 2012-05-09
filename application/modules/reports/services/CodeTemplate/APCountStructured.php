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

/*reportspecific options (called after defaults and db options got evaluated)*/
/*reportsspecific chart and table headers/options*/
	private function getResult($rows,$type)
	{
	        return
//die(serialize(
		array(
			'tables'=>array(/*array of tables*/
                                'main'=>array( /*table 1*/
					'type'=>$type
					,'name'=>($this->billingreport?'Billable Access Points':'AP Count')
					,'chartOptions'=>array(
						'type'=>($this->billingreport?'PieChart':'BarChart')
						,'width'=>780 /*max 370 for 2 charts sidebyside*/
						,'height'=>500
						,'depths'=>array(1)/*either single value, or an array -> multiple charts*/
						/*nativeOptions are passed 1:1 to googleCharts options*/
						,'nativeOptions'=>($this->billingreport?
							"legend:{position :'rigth'}"
							:"legend:{position :'right'}
								, isStacked:true
								, colors:['#44ff44','#ff4444','#4444ff','#aaaaaa']"
							)
						)
                                        ,'colDefs'=>array(/*array of coldefs*/
						($this->billingreport?
							array(
                                                                array('name'=>'Group','translatable'=>false,'class'=>'bold')
                                                                ,array('name'=>'billable','translatable'=>false,'class'=>'bold')
							)
							:array(
								array('name'=>'Group','translatable'=>false,'class'=>'bold')
								,array('name'=>'online','translatable'=>false,'class'=>'bold')
								,array('name'=>'offline','translatable'=>false,'class'=>'bold')
								,array('name'=>'planning','translatable'=>false,'class'=>'bold')
								,array('name'=>'disabled','translatable'=>false,'class'=>'bold')
								//,array('name'=>'billable','translatable'=>false,'class'=>'bold')
							)
						)
                                        ) /*end of coldefs*/
                                        ,'rows'=>$rows
                                ) /*end of table*/
                        )/*end of array of tables*/
		)
//))
;
	}

	private function setReportOptions()
	{
		//$this->summable=true;
		if ($this->getReportGroup()->getCodeTemplate()->getOption('mode')=='billable') $this->billingreport=true;
		else $this->billingreport=false;
	}

/*reportspecific query (column 2++ can be reportspecific)*/
	private function doQuery($groupIds, $dateFrom, $dateTo)
	{
		if ($this->billingreport) /*billable*/
		$this->res=$this->db->fetchall("SELECT rg.reportgroup, 0 as intv
                , count(*) as billable
                FROM node n
                INNER JOIN node_reportgroup rg ON n.node_id = rg.node_id
                WHERE deleted=0 AND billable=1
                GROUP BY reportgroup");

		else $this->res=$this->db->fetchall("SELECT rg.reportgroup, 0 as intv
		, SUM(IF(n.status='enabled',IF(n.online_status=1,1,0),0)) as online
		, SUM(IF(n.status='enabled',IF(n.online_status=0,1,0),0)) as offline
		, SUM(IF(n.status='planning',1,0)) as planning
		, SUM(IF(n.status='disabled',1,0)) as disabled
		FROM node n
		INNER JOIN node_reportgroup rg ON n.node_id = rg.node_id
		WHERE deleted=0
		GROUP BY reportgroup");
		//, SUM(IF(n.billable=1,1,0)) as billable
	}

/*reportspecific line data array initialize*/
	private function initLine()
	{
		if ($this->billingreport) return array(0);
		else return array(0,0,0,0);
	}

/*reportspecific line sum handler*/
	private function sumLine(&$value,$line)
	{
		$value[0]+=$line[2];
		if (!$this->billingreport) {
			$value[1]+=$line[3];
			$value[2]+=$line[4];
			$value[3]+=$line[5];
			//$value[4]+=$line[6];
		}
	}

/*reportspecific line handler*/
	private function handleLine($groupname,$values,$depth,$path,$is_device)
	{
		/*mis-using the class field for style and title is not the finest,..**/
		$c1style='" style="padding-left:'.$depth.'0px;" title="'.$path.$groupname;
		return array(
			'data'=>array_merge(array($groupname),$values)
			,'translatable'=>false
			,'depth'=>$depth
			,'device'=>$is_device
			,'class'=>($is_device?array(''.$c1style,'right','right','right','right')
			:array('bold'.$c1style,'right bold','right bold','right bold','right bold'))
		);
	}

/*add parameter to leave empty leaves -> not needed!!*/
/*add parameter to adapt to various columns, or add a callback function to style the line, or calculate the sums*/
	private function appendStructureRange(&$rows,&$values,$rid,$last_rid,&$last_depth)
	{
		/*use ncache to find all parent lines and calc there sums if applicable*/
$limit=0;
		if ($this->summable) {
			if ($last_depth && ($rid>=0)) while ($last_depth>=0) {
if (($limit++)>100) {die("/[dloop!]".$last_depth);}
				$v=1;
				foreach ($values as $value) {
					$rows[$this->ncache[$last_depth]]['data'][$v]+=$value;
					$v++;
				}
				$last_depth--;
			}
			$last_depth=0;
			$values=$this->initLine();
		}
		$rid++;
$limit=0;
		/*print out missing structures*/
		$path="";
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
			,($this->summable?$this->initLine():(($rid!=$last_rid)?$this->initLine():(is_array($values)?$values:$this->initLine())))
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
		$values=$this->initLine();
                foreach ($this->res as $line) {
			$ri++;
			if ($line[0]!=$last_rid) {
				//print missing headers since last group
				if ($this->summable) $path=$this->appendStructureRange($rows,$values,$last_rid,$line[0],$last_depth);
				/*instead of running sum give db result -> !!! causes value to be lost!!*/
				else $path=$this->appendStructureRange($rows,$line[2],$last_rid,$line[0],$last_depth);
				$last_rid=$line[0];
			}
			//do the running sums on the values (if applicable)
			if ($this->summable) $this->sumLine($values,$line);
			//print line
			/*at this depth level printing the single resultlines of this node list report is pointless*/
			/*and if not summable, every group has its own result presented with structure anyways*/
			if (($this->maxdepth!=-1) && ($this->summable==true) && ($last_depth < $this->maxdepth) )
				/*if we have only one result per reportgroup we can use rgroup array to get its name*/
				$rows[]=$this->handleLine($this->rgroup[$line[0]][0],array_slice($line,2),($last_depth+1),$path,true);
                }
		/*if ($last_rid >= (count($this->rgroup)-1) )*/
		$this->appendStructureRange($rows,$values,$last_rid,count($this->rgroup)-1,$last_depth); /*sometimes causes a endless loop*/

		$dtype=strtolower($this->getReportGroup()->getCodeTemplate()->getFormatDefault());
		if ($dtype=='notuserdefineable') $type='both'; /*our own hardcoded default*/
		else {
			$type=strtolower($this->getReportGroup()->getFormatSelected());
			/*if user did not choose a format (even if its poiible now, but maybe not when the report was created)*/
			if ($type=='notuserdefineable') $type=$dtype; /*we choose the actual default*/
		}
/*
echo "<pre>";
print_r($rows);
die("</pre>");
*/
		return $this->getResult($rows,$type);
	}
}
