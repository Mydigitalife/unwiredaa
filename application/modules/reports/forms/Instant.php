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
 * Instant report form
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Reports_Form_Instant extends Reports_Form_Group {

    public function init()
	{
		parent::init();

		$this->removeElement('title');
		$this->removeElement('description');
		$this->removeElement('report_type');
		$this->removeElement('report_interval');

		$this->getElement('form_element_submit')->setLabel('report_group_edit_form_generate');
	}

}