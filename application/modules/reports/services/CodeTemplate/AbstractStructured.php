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

abstract class Reports_Service_CodeTemplate_AbstractStructured extends Reports_Service_CodeTemplate_Abstract {
        protected $db; /*just to reduce parameters in functions*/
        protected $rid; /*internal/temporary report id*/
        protected $dcache; /*depth_cache array (holding id of last report group of a given depth)*/
        protected $ncache; /*depth_cache array (holding number of last report resultline (in $rows) of a given depth)*/
        protected $rgroup; /*report_group array (holding a list of all reports by id (each report is a array (name,depth,parent_rid,is_device))*/
        protected $summable; /*whether results of subgroups are summable (i.e. nodes of reprot-subgroups are not additionall assigned to all parent report-groups)*/
        protected $maxdepth; /*number of level to go into subgroups, 0 means all, -1 all + listnodes*/

        private function addChildGroups($group,$name,$depth,$parent) {/*listnodes=1 will not work well with maxdepth, may merge into special maxdepth avlue like 254, just groups (nearly unlimited, 255, listnode s too$
                /*if maxdepth not reached (else just seach nodes and subgroups, but do not add new reoprtgroups)*/
                if ( ($this->maxdepth <= 0) || ($depth <= $this->maxdepth) ) { /*if depth is allowed create new rgroup entry*/
                        $this->rid++;
                        $this->dcache[$depth]=$this->rid;

                        /*create groups reportgroup entry*/
//echo serialize(array($name,$depth,$parent,false))." new group for ".$this->rid."<br>";
                        $this->rgroup[$this->rid]=array($name,$depth,$parent,false);
                }
                $prid=$this->rid;

                /*search direct attached nodes*/ /*create own nodes of group pseudogroup, to seperate from subgroups!?*/
                $res=$this->db->fetchAll("SELECT node_id, name from `node` WHERE group_id=".$group); /*order by name*/
                foreach ($res as $line) {
                        if ( $this->maxdepth == -1 ) {
                                /*also create own reportgroup for each node (in array and table)*/
                                $this->rid++;
                                $this->rgroup[$this->rid]=array($line[1],$depth+1,$prid,true);
//print("L (".$line[0].",".$this->rid.",$depth)\n");
                                $this->db->query("INSERT INTO node_reportgroup VALUES(".$line[0].",".$this->rid.")");
                        } else {
//print("N (".$line[0].",".$this->rid.",$depth)\n");
                                $this->db->query("INSERT INTO node_reportgroup VALUES(".$line[0].",".$this->rid.")");
                        }
                        if (!$this->summable) {
                                /*start with node itself or parent reportgroup dcache entry based on listnodes or not*/
                                if ( $this->maxdepth == -1 ) $d=$depth;
				else {
					$d=$depth-1;
					if ($this->maxdepth > 0) {
						if ($d >= $this->maxdepth) $d=$this->maxdepth-1;
					}
				}
                                /*add node to all parent reportgroups using dcache array*/
                                while ($d >= 0) {
/*here we create entries without valid reportgroups!!!*/
	                                if (is_numeric($this->dcache[$d])) {
//echo serialize($this->dcache[$d]).$d." rgroup<br>";
//print("S (".$line[0].",".$this->dcache[$d].",$d)\n");
						$this->db->query("INSERT INTO node_reportgroup VALUES(".$line[0].",".$this->dcache[$d].")");
					}
					else echo "no parentgroup ".serialize($this->dcache[$d])." of depth $d found for ".$line[0]." ($depth)<br>";
                                        $d--;
                                }
                        }
                }
                /*search child groups*/
                {
                        /*search childs*/
                        $res=$this->db->fetchAll("SELECT group_id, name from `group` WHERE parent_id=".$group." ORDER BY name");
                        foreach ($res as $line) {
                                $this->addChildGroups($line[0],$line[1],$depth+1,$prid);
                        }
                }
        }

        protected function prepareTemporaryTable($groupIds) {
                $this->dcache=array();
                $this->rcache=array();
                $this->rgroup=array();
//print("<pre>");

                /*if there is more than one group add an ReportTotal*/
                if (count($groupIds)>1)
                {
                        $this->rgroup[0]=array("Report Total",0,false);
			$this->dcache[0]=0;
                        $parent=0;
                        $this->rid=0;
                        $depth=1;
                }
                else {
                        $this->rid=-1; /*as it will get incremented in addChildGroup before use*/
                        $depth=0;
                        $parent=false;
                }

                /*prepare temporary table*/
                $this->db->query("CREATE TEMPORARY TABLE node_reportgroup (node_id SMALLINT NOT NULL, reportgroup SMALLINT NOT NULL, KEY `node` (node_id)) ENGINE HEAP");
                $this->db->setFetchMode(Zend_Db::FETCH_NUM);

                /*get all top level groupid to start with ...*/
                $res=$this->db->fetchAll("SELECT group_id, name from `group` WHERE group_id in (".implode(",",$groupIds).") ORDER by name");
                /*... building the complete group structure*/
                foreach ($res as $line) $this->addChildGroups($line[0],$line[1],$depth,$parent);
        }
}
