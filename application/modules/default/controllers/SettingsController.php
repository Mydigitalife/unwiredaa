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

class Default_SettingsController extends Unwired_Controller_Crud
{
	public function indexAction()
	{
		$this->_index();
	}

	public function editAction()
	{
	    if (Zend_Registry::isRegistered('Unwired_Event_Broker')) {
	        $broker = Zend_Registry::get('Unwired_Event_Broker');

	        $broker->addHandler(new Default_Service_Htaccess());
	    }

		$this->_edit();
	}
}