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

/**
 * Index controller
 *
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Default_IndexController extends Unwired_Controller_Action
{

    protected $_cache = null;

    protected function _getCache()
    {
        if (null === $this->_cache) {
            $cacheMgr = $this->getInvokeArg('bootstrap')->getResource('Cachemanager');

            $this->_cache = $cacheMgr->getCache('default');
        }

        return $this->_cache;
    }

    public function indexAction()
    {
    	/**
    	 * @todo Make ajax calls to load nodes only in current viewport
    	 */
		
            $nodes = $this->_getCache()->load('device_map_data');
	
	        if (!$nodes) {
	            $mapper = new Nodes_Model_Mapper_Node();
	
	            $nodes = $mapper->fetchAll();
	
	            $this->_getCache()->save($nodes, 'device_map_data', array('node'), 3600);
	        }
	
	        $this->view->currentUser = Zend_Auth::getInstance()->getIdentity();
	
	       // $this->view->nodes = $nodes;
	
	        if (Zend_Auth::getInstance()->hasIdentity()) {
	            $netstatsService = new Default_Service_NetworkStats();
	            $this->view->networkStats = $netstatsService->getStatistics();
	        }
	        $allNodes = array();
	        foreach ($nodes as $node) {
	        	$nodeArray = $node->toArray();
	        	$nodeArray['trafficlimit'] = $nodeArray['settings']['trafficlimit'];
	        	$nodeArray['status_extended']['monthly_traffic'] = (isset($nodeArray['status_extended']['monthly_traffic'])) ? $nodeArray['status_extended']['monthly_traffic'] : 0;
	        	unset($nodeArray['settings']);
	        	$allNodes[] = $nodeArray;
	        }
	        $this->view->jscript = $this->createJS($allNodes,'index');
	        
    }
    public function nodesearchAction(){
    
    	/* TODO
    	 * As whether there should be like %name% or 'equals' name
    	*/
    
    	$filter = $this->_getFilters();
    	
    	$this->view->currentUser = Zend_Auth::getInstance()->getIdentity();
    	
    	$mapper = new Nodes_Model_Mapper_Node();
    	//$this->view->nodes = $mapper->findBy($filter);
    	$nodes = $mapper->findBy($filter);
    	if(count($nodes) > 1):
    		$this->view->nodes = $nodes;
    	endif; 
    	
    		$this->view->nodeCount = count($nodes);
    	
    	
    	$allNodes = array();
    	foreach ($nodes as $node) {
    		$nodeArray = $node->toArray();
    		$nodeArray['trafficlimit'] = $nodeArray['settings']['trafficlimit'];
    		$nodeArray['status_extended']['monthly_traffic'] = (isset($nodeArray['status_extended']['monthly_traffic'])) ? $nodeArray['status_extended']['monthly_traffic'] : 0;
    		unset($nodeArray['settings']);
    		$allNodes[] = $nodeArray;
    	}
    	if(count($allNodes)):
    	$this->view->jscript = $this->createJS($allNodes,'nodesearch');
    		endif;
    }
    
    
    
    private function createJS($nodes,$page){
    	$acl = Zend_Registry::get('acl'); 
    	$ret=$this->getToDate().
    	  $this->getFormatDate().
    	  $this->loadStatistics().
    	  $this->loadNetworkStatistics();
    	
    	
    	if(count($nodes)):
    	// center of map/zoom lvl depend on which view is displayed
    	if($page == 'index' || count($nodes) > 1){
    		$centerLatitude = $this->view->systemSettings['node_map_center_lat']->getValue();
    		$centerLongitude = $this->view->systemSettings['node_map_center_lng']->getValue();
    		$zoomLevel = $this->view->systemSettings['node_map_zoom']->getValue();
    	}
    	else if($page =='nodesearch' && count($nodes) == 1){
    		
    		foreach($nodes as $node):
    			$centerLatitude = $node['location']['latitude'];
    			$centerLongitude = $node['location']['longitude'];
    		endforeach;
    		$zoomLevel = "13";
    	}
    	
    	$ret .= "var nodes = ". Zend_Json::encode($nodes).";
    		var map;
    		var infowindow;
    	
    	
    		var lastDevice = null;
    		var statsTimer = null;
    		var networkTimer = null;
    	
    		var networkUpGraph = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
    		var networkDownGraph = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
    	
    		
    	
    		
    	$(document).ready(function(){
		$('#nodemap').height(450);
		
		
		var latlng = new google.maps.LatLng(". $centerLatitude.",
										    ". $centerLongitude.");
		var myOptions = {
      		zoom: ". (int) $zoomLevel.",
      		center: latlng,
      		mapTypeId: google.maps.MapTypeId.ROADMAP
    	};

    	map = new google.maps.Map(document.getElementById(\"nodemap\"),
        							  myOptions);

		infowindow = new google.maps.InfoWindow();

		var markers = [];

		$.each(nodes, function(idx, data) {
			var mimage = \"". $this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-red.png")."\";
			var onlinemsg = '';

			var formattedDate = formatDbDate(data.online_status_changed);


			if (data.online_status > 0) {
				onlinemsg = ''; // 'Online users: ' + data.online_users_count;
	    		onlinemsg = onlinemsg + '</div><div>Online since: ' + formattedDate;
	    		mimage = \" ". $this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-green.png")."\";
	    	} else {
	    		onlinemsg = 'Offline since: ' + formattedDate;
	    	}

			if (data.status == 'planning') {
				mimage = \"". $this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-black.png")."\";
			}

			if (data.status == 'disabled') {
				mimage = \"".$this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-grey.png")."\";
			}

			if (data.trafficlimit > 0 && data.status_extended.monthly_traffic >= data.trafficlimit) {
				mimage = \"". $this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-yellow.png")."\";
			}";
			if(!isset($this->view->currentUser)):
			$ret.="
				mimage = \"". $this->view->baseUrl("themes/" . $this->view->theme . "/images/icons/32x32/marker-grey.png")."\";";
			endif;
			
			$ret.="
			var marker = new google.maps.Marker({
		        position: new google.maps.LatLng(data.location.latitude, data.location.longitude),
		        title: data.name,
		        draggable: false,
		        icon: mimage
		    });



			marker.setMap(map);

			markers.push(marker);

		    google.maps.event.addListener(marker, 'click', function(event) {
		        if (statsTimer) {
		        	clearTimeout(statsTimer);
		        }

				infowindow.setContent('<div id=\"infowindowcontainer\">' +
				
									  '<h5>' + marker.getTitle() +'</h5>' +
									  ";
				 
				 // add view/edit/delete buttons to tooltip	
				 $actions = array('view', 'edit', 'delete');
				 $ret.="'<table><tbody><tr class=\"odd\">'+";
				 foreach ($actions as $action) :
				 if (!$acl->isAllowed($this->view->currentUser, 'nodes_node', $action)) {
				 	continue;
				 }
				 $ret.="'<td class=\"tools\"><a href=\"/nodes/index/".$action."/id/'+data.node_id+'\" class=\"icon ".$action."\">".$this->view->translate('nodes_index_index_button_' . $action)."</a></td>'+";
									  
				 
				 endforeach;
				 	
				 
				$ret.=" '</tr></tbody></table>'+
									  '<div>AP type: ' + ((data.status_extended.aptype) ? data.status_extended.aptype : \"". $this->view->translate('default_index_index_ap_unknown')."\") + '</div>' +
									  '<div>MAC: ' + data.mac + '</div>' +
									  '<div>Address: ' + data.location.address + ', ' + data.location.city +', ' + data.location.country + '</div>' +";
			
				 if (isset($this->view->currentUser)) {
				 $ret.="
									  '<div>' + onlinemsg + '</div>' +
									  '<div class=\"stats\">' +
									  '<div><strong>".$this->view->translate('default_index_index_ap_users').": </strong><ul>' +
									  '<li><strong>". $this->view->translate('default_index_index_ap_users_total').": </strong>0</li>' +
									  '<li><strong>". $this->view->translate('default_index_index_ap_users_online').": </strong>0</li>' +
									  '<li><strong>". $this->view->translate('default_index_index_ap_users_garden').": </strong>0</li></ul></div>' +
									  '<div><strong>".$this->view->translate('default_index_index_ap_speed_up').": </strong> 0</div>' +
									  '<div><strong>".$this->view->translate('default_index_index_ap_speed_down').": </strong> 0</div>' +
									  '</div>' +
									  '<div><strong>". $this->view->translate('default_index_index_ap_monthly_usage')." </strong>' + data.status_extended.lastupdate +': ' + data.status_extended.monthly_traffic + 'MB</div>' +
									'</div>');";
				 
					$ret.="if (data.online_status > 0) {
						loadStatistics(data);
					}";
				 }
				else{ 
									  $ret.="'</div>');";
				}
									  
		
				
				$ret.="
				
				infowindow.open(map,marker);

		    });
		    ";
		   
     		$ret.="
		});
    	
    	";
				
				
				endif;
		return $ret;
    	    	
    }
	private function getToDate(){
		return "function stringToDate(datestring)
    		{
    			var match = /(\d{4})\-0?(\d{1,2})\-0?(\d{1,2})\s0?(\d{1,2}):0?(\d{1,2}):0?(\d{1,2})/i.exec(datestring);
    	
    			if (!match.length) {
    				return null;
    			}
    	
    			var date = new Date();
    	
    			date.setFullYear(match[1]);
    			date.setMonth(parseInt(match[2])-1);
    			date.setDate(match[3]);
    			date.setHours(match[4]);
    			date.setMinutes(match[5]);
    			date.setSeconds(match[6]);
    	
    			return date;
    		}";
	}
	
    protected function _getFilters()
    {
    	
    	$filter = array();
    	
    
    	$filter['name'] = $this->getRequest()->getParam('name', null);
    	$filter['mac'] = strtoupper($this->getRequest()->getParam('mac', null));
    	
    	if(isset($filter['name']) || isset($filter['mac'])){
    	
    		$this->view->filter = $filter;
    
    	foreach ($filter as $key => $value) {
    		if (null == $value || (!is_numeric($value) && empty($value))) {
    			unset($filter[$key]);
    			continue;
    		}
    
    		$filter[$key] = "%".preg_replace('/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value)."%";
    		if ($key == 'mac') {
    			$filter[$key] == str_replace('-', '', $filter[$key]);
    		}
    	}
    
    	return $filter;
    	}else{
    		return false;
    	}
    }
    private function loadStatistics(){
    	return "
    	function loadStatistics(deviceData)
    	{
    		lastDevice = deviceData;
    		
    		$.ajax({
    			url: '". $this->view->url(array('module' => 'default',
    			'controller' => 'index',
    			'action' => 'stats'), 'default', true)."/location/' + parseInt(deviceData.node_id).toString(16),
    			
    			success: function(data) {
    				if (lastDevice !== deviceData) {
    					return;
    				}
    				 
    				var  defaultData = {
    					\"session_count\": \"0\",
    					\"internet_users\": \"0\",
    					\"session_count\": 0,
    					\"total_kbps_up\": \"0\",
    					\"total_kbps_down\": \"0\"
    				} ;
    				 
    				if (data) {
    				 
    				var garden_users = '0';
    				if (data.session_count == -1) {
    				data = defaultData;
    				} else {
    				garden_users = data.session_count - data.internet_users;
    				}
    				 
    				$('#infowindowcontainer div.stats').html(
    				'<div><strong>".$this->view->translate('default_index_index_ap_users').": </strong><ul>' +
    				'<li><strong>".$this->view->translate('default_index_index_ap_users_total').": </strong>' + data.session_count + '</li>' +
    				'<li><strong>". $this->view->translate('default_index_index_ap_users_online').": </strong>' + data.internet_users + '</li>' +
    				'<li><strong>". $this->view->translate('default_index_index_ap_users_garden').": </strong>' + (data.session_count - data.internet_users) + '</li></ul></div>' +
    				'<div><strong>". $this->view->translate('default_index_index_ap_speed_up').":</strong> ' + data.total_kbps_up + '</div>' +
    				'<div><strong>". $this->view->translate('default_index_index_ap_speed_down').":</strong> ' + data.total_kbps_down + '</div>'
    				);
    				}
    				 
    				if ($('#infowindowcontainer').is(':visible')) {
    				statsTimer = setTimeout(\"loadStatistics(lastDevice);\", 3000);
    				}
    				}
    				});
    				}";
    }
    private function loadNetworkStatistics(){
    	return "function loadNetworkStatistics()
    		{
    			$.ajax({
    				url: '". $this->view->url(array('module' => 'default',
    				                                  'controller' => 'index',
    				                                  'action' => 'stats',
    				                                  'interface' => 'total'), 'default', true)."',
    				success: function(data) {
    	
    					if (data) {
    					    // $('#devicesonline').text(online_ap_count);
    						// $('#usersonline').text(data.active_users);
    						$('#bandwidthup').text(data.kbps_tx);
    						$('#bandwidthdown').text(data.kbps_rx);
    	
    						networkUpGraph.push(data.kbps_tx);
    						networkUpGraph.splice(0,1);
    						$('#networkgraphup').sparkline(networkUpGraph, { width: 510 })
    	
    						networkDownGraph.push(data.kbps_rx);
    						networkDownGraph.splice(0,1);
    	
    						$('#networkgraphdown').sparkline(networkDownGraph, { width: 510 })
    					}
    	
    	 				networkTimer = setTimeout(\"loadNetworkStatistics();\", 5000);
    				}
    			});
    		}";
    }
    private function getFormatDate(){
    	return "function formatDbDate(datestring)
    	{
    		if (datestring == '0000-00-00 00:00:00') {
    			return 'unknown';
    		}
    		 
    		var match = /(\d{4})\-(\d{1,2})\-(\d{1,2})\s(\d{1,2}:\d{1,2}:\d{1,2})/i.exec(datestring);
    		 
    		if (!match.length) {
    			return '';
    		}
    		 
    		return match[3] + '/' + match[2] + '/' + match[1] + ' ' + match[4];
    	}";
    }
    
    public function statsAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $location = $this->getRequest()->getParam('location', null);

        $interface = $this->getRequest()->getParam('interface', null);

        $stats = array();

        if (!$location && !$interface) {
             echo $this->view->json(array());
             return;
        }

        $serviceChilli = new Default_Service_Chilli();

        if ($location) {
            $stats = $this->_getCache()->load('device_map_stats_' . $location);

            if (empty($stats)) {
                $stats = $serviceChilli->getDeviceStatistics($location);

                $this->_getCache()->save($stats, 'device_map_stats_' . $location, array('node', $location), 3);
            }
        } else {
            $stats = $this->_getCache()->load('device_map_stats_total');

            if (empty($stats)) {
                $stats = $serviceChilli->getInterfaceStatistics($interface);

                $this->_getCache()->save($stats, 'device_map_stats_total', array('node'), 3);
            }
        }

        echo $this->view->json($stats);
    }


}

