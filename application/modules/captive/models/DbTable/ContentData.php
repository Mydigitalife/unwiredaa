<?php

class Captive_Model_DbTable_ContentData extends Zend_Db_Table_Abstract
{
    protected $_name = 'content_data';

    protected $_referenceMap = array(
                'Content' => array(
        			'columns'           => 'content_id',
                    'refTableClass'     => 'Captive_Model_DbTable_Content',
                    'refColumns'        => 'content_id'
                    ),
                'Language' => array(
        			'columns'           => 'language_id',
                    'refTableClass'     => 'Captive_Model_DbTable_Language',
                    'refColumns'        => 'language_id'
                    ),
            );

    public function init()
    {
        $moduleBoostraps = Zend_Controller_Front::getInstance()
                                ->getParam('bootstrap')
                                    ->getResource('modules');

        $dbAdapter = $moduleBoostraps->captive->getResource('db');

        $this->_setAdapter($dbAdapter);
    }
}