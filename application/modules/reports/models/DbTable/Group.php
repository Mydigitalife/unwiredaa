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
 * DB table gateway for report_group table
 * @author G. Sokolov <joro@web-teh.net>
 */

class Reports_Model_DbTable_Group extends Zend_Db_Table_Abstract {
	/**
	 * The default table name
	 */
	protected $_name = 'report_groups';

	protected $_dependentTables = array('Reports_Model_DbTable_Items',
										'Reports_Model_DbTable_Recepients',
	                                    'Reports_Model_DbTable_Node'
			);

	protected $_referenceMap = array(
            'Codetemplate'  => array(
                'columns'           => 'template_id',
                'refTableClass'     => 'Reports_Model_DbTable_Codetemplate',
                'refColumns'        => 'codetemplate_id'
                ),
			'Report' => array(
					'columns'           => 'group_id',
					'refTableClass'     => 'Groups_Model_DbTable_Group',
					'refColumns'        => 'group_id'
				)
             );
}