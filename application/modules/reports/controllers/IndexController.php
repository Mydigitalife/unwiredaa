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

class Reports_IndexController extends Unwired_Controller_Crud
{

    protected $_defaultMapper = 'Reports_Model_Mapper_CodeTemplate';

	public function indexAction()
	{
		$filter = $this->_getFilters();

		$mapperCodeTemplate = $this->_getDefaultMapper();

		$filter[] = new Zend_Db_Expr('`title` not like "\_%"');
		$mapperCodeTemplate->findBy($filter, 0, 'title ASC');

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