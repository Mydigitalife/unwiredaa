<?php

class Captive_Model_Mapper_Content extends Unwired_Model_Mapper
{
	protected $_modelClass = 'Captive_Model_Content';

	protected $_dbTableClass = 'Captive_Model_DbTable_Content';

	public function rowToModel(Zend_Db_Table_Row $row, $updateRepo = false)
	{
	    $model = parent::rowToModel($row, $updateRepo);

	    if (!$model) {
	        return null;
	    }

	    $mapperData = new Captive_Model_Mapper_ContentData();

	    $data = $mapperData->findBy(array('content_id' => $model->getContentId()));

	    $model->setData($data);

	    return $model;
	}

	public function save(Unwired_Model_Generic $model)
	{
        $model = parent::save($model);

        $mapperData = new Captive_Model_Mapper_ContentData();

        $mapperData->setEventsDisabled();

        foreach ($model->getData() as $contentData) {
            $contentData = $mapperData->save($contentData);
        }

        return $model;
	}
}
