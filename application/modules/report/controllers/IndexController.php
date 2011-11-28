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

class Report_IndexController extends Unwired_Controller_Crud
{

	public function indexAction()
	{
		$groupService = new Groups_Service_Group();
		$reportMapper = new Report_Model_Mapper_Index();

		$filter = $this->_getFilters();
		
		$groupService->prepareMapperListingByAdmin($reportMapper, null, false, $filter);

		$this->_index();
	}
	

	protected function _getFilters()
	{
		$filter = array();

		$filter['title'] = $this->getRequest()->getParam('title', null);
		
		$this->view->filter = $filter;

		foreach ($filter as $key => $value) {
			if (null == $value || empty($value)) {
				unset($filter[$key]);
				continue;
			}

			$filter[$key] = '%' . preg_replace('/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/iu', '', $value) . '%';
		}

		return $filter;
	}

}