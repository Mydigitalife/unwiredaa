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

class Default_Service_Htaccess implements Unwired_Event_Handler_Interface
{
	/* (non-PHPdoc)
     * @see Unwired_Event_Handler_Interface::handle()
     */
    public function handle(Unwired_Event_Message $message)
    {
        $data = $message->getData();


        if ($message->getMessageId() !== 'edit' || !isset($data->entity) || (!$data->entity instanceof Default_Model_Settings)) {
            return;
        }

        if (!$data->entity->getKey() !== 'uwaa_allowed_ip') {
            return;
        }

        $htaccess = @file_get_contents(PUBLIC_PATH . '/.htaccess');

        if (empty($htaccess)) {
            return;
        }

        $allowedHosts = $data->entity->getValue();

        $allowedHosts = str_ireplace(',', ' ', trim($allowedHosts));


        if (preg_match('/#start ip restriction(.*)#end ip restriction/ius', $htaccess)) {
            $htaccess = preg_replace('/#start ip restriction(.*)#end ip restriction/ius', '', $htaccess);
        }

        if (!empty($allowedHosts)) {
        $htaccess = <<<EOF
#start ip restriction
order deny, allow
deny from all
allow from $allowedHosts
#end ip restriction
$htaccess
EOF;
        }

        @file_put_contents(PUBLIC_PATH . '/.htaccess', $htaccess);
    }
}