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
 * Users/Index controller
 * @author B. Krastev <bkrastev@web-teh.net>
 */

class Users_IndexController extends Unwired_Controller_Action {

	public function indexAction() {
		$this->_helper->redirector->gotoRouteAndExit(array('controller' => 'admin'), null, false);
	}

	public function loginAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
		}

		$form = new Users_Form_Login();

		$this->view->form = $form;

		if (!$form->isValid($this->getRequest()->getPost())) {
			return;
		}

		$data = $form->getValues();

		$service = new Users_Service_Admin();

		if (!$service->login($data['username'], $data['password'])) {
			$this->view->uiMessage('user_login_failed', 'error');
            $this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
			return;
		}

		$this->view->uiMessage('user_login_success', 'success');

		if ($this->getInvokeArg('bootstrap')->hasResource('session')) {
			$session = $this->getInvokeArg('bootstrap')->getResource('session');

			if (isset($session->loginRedirect)) {
			    $loginRedirect = $session->loginRedirect;
			    $session->loginRedirect = null;
			    $this->_helper->redirector->gotoUrlAndExit($loginRedirect, array('prependBase' => false));
			}
		}

		$this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
	}

	public function logoutAction()
	{
		$service = new Users_Service_Admin();

		$service->logout();

		$this->view->uiMessage('user_logout_success', 'success');

		$this->_helper->redirector->gotoRouteAndExit(array(), 'default', true);
	}

}
