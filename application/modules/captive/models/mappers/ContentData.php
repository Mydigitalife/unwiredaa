<?php

class Captive_Model_Mapper_ContentData extends Unwired_Model_Mapper
{
	protected $_modelClass = 'Captive_Model_ContentData';

	protected $_dbTableClass = 'Captive_Model_DbTable_ContentData';

	public function rowToModel(Zend_Db_Table_Row $row, $updateRepo = true)
	{
	    return parent::rowToModel($row, true);
	}
}
