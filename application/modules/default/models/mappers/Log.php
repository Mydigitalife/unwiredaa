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
 * Mapper for Default_Model_Log
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Default_Model_Mapper_Log extends Unwired_Model_Mapper
{
	protected $_eventsDisabled = true;

	protected $_modelClass = 'Default_Model_Log';

	protected $_dbTableClass = 'Default_Model_DbTable_Log';

}
