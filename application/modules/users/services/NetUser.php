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

class Users_Service_NetUser implements Unwired_Event_Handler_Interface
{
	const DISABLED_GROUP_ID = 3;

	protected $_logoutUri = 'http://chillivpn/portal/user_login.php?res=logoff&mac=';

	/* (non-PHPdoc)
	 * @see Unwired_Event_Handler_Interface::handle()
	 */
	public function handle(Unwired_Event_Message $message) {
		$data = $message->getData();

		if (!isset($data->entity) || (!$data->entity instanceof Users_Model_NetUser)) {
			return;
		}

		switch ($message->getMessageId()) {
			case 'edit':
				 $policyIds = $data->entity->getPolicyIds();

				 /**
				  * Do not kick enabled users ;)
				  */
				 /* if (!in_array(self::DISABLED_GROUP_ID, $policyIds)) {
				 	 break;

				 }*/
				 /**
				  * Changed! Kick everybody so new settings take effect
				  */
			case 'delete':
				$mac = $data->entity->getMac();
				$mac = trim($mac);

				if (empty($mac)) {
					$macValidator = new Unwired_Validate_Mac();
					if (!$macValidator->isValid($data->entity->getUsername())) {
						// we don't have any valid mac
						return;
					}

					$mac = $data->entity->getUsername();
				}

				$mac = str_replace(array(':','-'), '', $mac);
				/* $mac = str_split($mac, 2);
				$mac = implode('-', $mac); */

				$serviceChilli = new Default_Service_Chilli();

				try {
				    $serviceChilli->logoutUser($mac);
				} catch (Exception $e) {
					// @todo Maybe handle logoff error responses?
				}
			break;
		}
	}
}