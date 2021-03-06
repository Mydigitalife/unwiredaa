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

class Unwired_Validate_Zip extends Zend_Validate_Regex
{
	const NOT_ZIP = "zipNotValid";

	/**
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_ZIP  => 'Invalid ZIP code',
    );

	public function __construct()
	{
		parent::__construct('/^[0-9A-Z\s]{3,10}$/i');
	}

	public function isValid($value)
	{
		$valid = parent::isValid($value);

		if (!$valid) {
			$this->_errors = array();
			$this->_messages = array();
			$this->_error(self::NOT_ZIP);
		}

		return $valid;
	}
}