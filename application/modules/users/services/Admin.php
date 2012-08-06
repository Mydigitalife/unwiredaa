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

class Users_Service_Admin implements Zend_Acl_Assert_Interface
{
	/**
	 * Try to authenticate user
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function login($username, $password)
	{
		$mapper = new Users_Model_Mapper_Admin();

		$user = $mapper->findOneBy(array('email' => $username,
										 'password' => sha1($password)));

		if (!$user) {
			return false;
		}

		/**
		 * Persist user info for logged in user
		 *
		 */
		$auth = Zend_Auth::getInstance();
		$auth->getStorage()->write($user);

		if (Zend_Registry::isRegistered('Unwired_Event_Broker')) {
			$broker = Zend_Registry::get('Unwired_Event_Broker');

			$data = array('user' => $user);

			$broker->dispatch(new Unwired_Event_Message('login', $data));
		}

		return true;
	}

	public function logout()
	{
	    if (!Zend_Auth::getInstance()->hasIdentity()) {
	        return true;
	    }

		$user = Zend_Auth::getInstance()->getIdentity();

		Zend_Auth::getInstance()->clearIdentity();

		if (Zend_Registry::isRegistered('Unwired_Event_Broker')) {
			$broker = Zend_Registry::get('Unwired_Event_Broker');

			$data = array('user' => $user);

			$broker->dispatch(new Unwired_Event_Message('logout', $data));
		}

		return true;
	}

	/* (non-PHPdoc)
	 * @see Zend_Acl_Assert_Interface::assert()
	 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		// TODO Auto-generated method stub

	}

	/**
	 * Create temporary new password for admin
	 *
	 * @param Users_Model_Admin $admin
	 * @param string $template
	 * @return boolean
	 */
	public function createTempPassword(Users_Model_Admin $admin, $template = null)
	{
        if (!$template) {
            $template = "Your temporary password is: %%password%%\n";
        }

        $tempPassword = substr(sha1(date() . $admin->getEmail()),0,8);


        $template = str_replace('%%password%%', $tempPassword, $template);

        $originalAdmin = clone $admin;

        $admin->setPassword($tempPassword);

        $mapperAdmin = new Users_Model_Mapper_Admin();

        /**
         * Don't fire save event
         */
        $mapperAdmin->setEventsDisabled(true);

        try {
            $mapperAdmin->save($admin);
        } catch (Exception $e) {
            return false;
        }

        try {
             $mailer = new Zend_Mail();
             $mailer->addTo($admin->getEmail(), $admin->getFirstname() . ' ' . $admin->getLastname())
                    ->setSubject("Password reset")
                    ->setBodyHtml($template);

             $mailer->send();
        } catch (Exception $e) {
            $mapperAdmin->save($originalAdmin);
            return false;
        }

        /**
         * @todo Fire reset password event
         */
        return true;
	}

}