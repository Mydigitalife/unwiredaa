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
class Reports_Service_CodeTemplate_Sample extends Reports_Service_CodeTemplate_Abstract {/*!!?? base on _Structured after moving && sanitzing all structure/helper functions into it*/
	protected $db; /*just to reduce parameters in functions*/
	protected $rid; /*internal/temporary report id*/
	protected $dcache; /*depth_cache array (holding id of last report of a given depth)*/
	protected $ncache; /*depth_cache array (holding number of last report resultline (in $rows) of a given depth)*/
	protected $rgroup; /*report_group array (holding a list of all reports by id (each report is a array (name,depth))*/

	private function addChildGroups($group,$name,$depth,$parent) {/*summable, maxdepth, listnodes*/ /*listnodes=1 will not work well with maxdepth, may merge into special maxdepth avlue like 254, just groups (nearly unlimited, 255, listnode s too)*/
		/*if maxdepth not reached (else just seach nodes and subgroups, but do not add new reoprtgroups)*/
		$rid=$this->rid;
		$this->rid++;
		$this->dcache[$depth]=$group;
		/*create groups reportgroup entry*/

		$this->rgroup[$rid]=array($name,$depth,$parent);/*or just encode depth into name?*/

		/*search direct attached nodes*/ /*create own nodes of group pseudogroup, to seperate from subgroups!?*/
		$res=$this->db->fetchAll("SELECT node_id, name from `node` WHERE group_id=".$group); /*order by name*/
		foreach ($res as $line) {
			/*if ($listnodes && $depth < $maxdepth) {
				also create own reportgroup for each node (in array and table)
				$this->rid++;
				$this->rgroup[$this->rid]=array($line[1],$depth+1);
				$this->db->query("INSERT INTO node_reportgroup VALUES(".$line[0].",".$this->rid);
			}
			else */
			$this->db->query("INSERT INTO node_reportgroup VALUES(".$line[0].",".$rid.")");
			/*if !summable add node to all parent reportgroups using dcache array*/
		}
		/*search child groups*/
		{
			/*search childs*/
			$res=$this->db->fetchAll("SELECT group_id, name from `group` WHERE parent_id=".$group);/*order by name*/
			foreach ($res as $line) {
				$this->addChildGroups($line[0],$line[1],$depth+1,$rid);
			}
		}
	}

	private function prepareTemporaryTable($groupIds) {/*summable, maxdepth, listnodes*/
		$this->dcache=array();
		$this->rcache=array();
		$this->rgroup=array();
		/*if there is more than one group add an ReportTotal*/
		if (count($groupIds))
		{
			$this->rgroup[0]=array("Report Total",0,false);
			$this->rid=1;
			$depth=1;
		}
		else {
			$this->rid=0;
			$depth=0;
		}
		$this->db->query("CREATE TEMPORARY TABLE node_reportgroup (node_id SMALLINT NOT NULL, reportgroup SMALLINT NOT NULL)");/*depth SMALLINT engine=memory*/
		$this->db->setFetchMode(Zend_Db::FETCH_NUM);

/*additional table/array for name and depth of al report groups (for formatting)*/

		$res=$this->db->fetchAll("SELECT group_id, name from `group` WHERE group_id in (".implode(",",$groupIds).") ORDER by name");
		/*build group structure*/
		foreach ($res as $line) {
			/*fetch group name*/
			$this->addChildGroups($line[0],$line[1],$depth,false);
		}
/*list temporary table
                $res=$this->db->fetchAll("SELECT * from node_reportgroup");
                foreach ($res as $line) {
                        echo "<hr>".serialize($line);
                }*/

		/*fill with node_id report_part_id relations (depending on report type (summarizeable), and planned depth)*/
	}

/*inner interval i.e. run multiple rounds over the group structure*/

/*add parameter to leave empty leaves -> not needed!!*/
/*add parameter to adapt to various columns, or add a callback function to style the line, or calculate the sums*/
	private function appendStructureRange(&$rows,&$running_sum,$rid,$last_rid,&$last_depth)
	{
/*use ncache to find all parent lines and calc there sums if applicable*/
		while ($last_depth>=0)
		{
			$rows[$this->ncache[$last_depth]]['data'][1]+=$running_sum;
			$last_depth--;
		}
		$last_depth=0;
		$running_sum=0;$path="";
		while ($rid<=$last_rid)
		{
			$last_depth=$this->rgroup[$rid][1];
			$this->ncache[$last_depth]=count($rows);
			$pid=$this->rgroup[$rid][2];$path=$this->rgroup[$rid][0];
			while ($pid) {
				$path=$this->rgroup[$pid][0]."/".$path;
				$pid=$this->rgroup[$pid][2];
			}
			$rows[count($rows)]=array(
				'data'=>array("<span title='".$path."'>".str_repeat("&nbsp; &nbsp; ",$last_depth).$this->rgroup[$rid][0]."</span>",0)/*initialize sum with 0 (if summable)*/
				,'translatable'=>false
				,'class'=>array('bold','right bold')
			);
			$rid++;
		}
		return $path;
	}

/*get report specific parts out of this function (ie.e find a method to do runnings sums (also multiple ones) either fully classspecific or not, or overwiteable)*/
/*also add support to inner intervals*/
	public function getData($groupIds, $dateFrom, $dateTo) {
	        $this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$this->prepareTemporaryTable($groupIds);

		$tables=array();

		$res=$this->db->fetchall("SELECT * from node_reportgroup ORDER by reportgroup");

		/*use the temporary table for our own query*/
		$res=$this->db->fetchAll("SELECT r.reportgroup, n.name, 1 from node n INNER JOIN node_reportgroup r ON n.node_id = r.node_id WHERE (n.billable = 1) AND (n.status = 'enabled') ORDER BY r.reportgroup");
		$last_rid=0;
		$last_depth=-1;/*-1 to indicate first appendStructureRange() that there is no previous sum*/
		$rows=array();
		$this->ncache=array(0=>0);
                foreach ($res as $line) {
			if ($line[0]!=$last_rid) {
				/*print missing headers since last group*/
				$path=$this->appendStructureRange(&$rows,&$running_sum,$last_rid,$line[0],&$last_depth);
				$last_rid=$line[0];
			}
			/*do the running_sum (if applicable)*/
			$running_sum+=$line[2];
			/*print line*/
			$rows[]=array(
				'data'=>array("<span title='".$path."/".$line[1]."'>".str_repeat("&nbsp; &nbsp; ",$last_depth+1).$line[1]."</span>",$line[2])/*($line[2]?"online":"offline")*/
				,'translatable'=>false
				,'class'=>array('','right')
			);
                }
	 	$this->appendStructureRange(&$table,&$running_sum,$last_rid,$this->rid,&$last_depth);

	        return array(
			'tables'=>array(/*array of tables*/
                                array( /*table 1*/
                                        'colDefs'=>array(/*array of coldefs*/
                                                array(/*first coldef*/
                                                        array( /*advanced column def as array*/
								'name'=>'name'
                                                                ,'translatable'=>false
                                                                ,'class'=>'bold'
                                                        )
                                                        ,array( /*advanced column def as array*/
								'name'=>'number of nodes'
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
