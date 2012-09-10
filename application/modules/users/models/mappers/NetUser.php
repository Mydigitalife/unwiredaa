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
 * Mapper for Users_Model_NetUser
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Users_Model_Mapper_NetUser extends Unwired_Model_Mapper {

    protected $_modelClass = 'Users_Model_NetUser';
    protected $_dbTableClass = 'Users_Model_DbTable_NetworkUser';
    protected $_defaultOrder = array('radius_sync ASC', 'lastname ASC');

    public function save(Unwired_Model_Generic $model) {
        try {
            $model = parent::save($model);

            //Network User Data
            $tableUserData = new Users_Model_DbTable_NetworkUserData();
            // $tableUserData->delete(array('user_id = ? AND data_key = ?' => $model->getUserId()));
            //print_r($model->getData());
            foreach ($model->getData() as $dataKey => $dataValue) {
                $tableUserData->insert(array('user_id' => $model->getUserId(),
                    'data_key' => $dataKey, 'data_value' => $dataValue));
            }
            $tableUserData = null;
            //

            $tableUserPolicy = new Users_Model_DbTable_NetworkUserPolicy();

            $tableUserPolicy->delete(array('user_id = ?' => $model->getUserId()));

            foreach ($model->getPolicyIds() as $policy_id) {
                $tableUserPolicy->insert(array('user_id' => $model->getUserId(),
                    'policy_id' => $policy_id));
            }
            $adapter = $tableUserPolicy->getAdapter();
            $tableUserPolicy = null;

            $adapter->delete('radcheck', "`username`='{$model->getUsername()}' AND `attribute`='MD5-Password'");

            $password = $model->getPassword();
            if (!empty($password)) {
                /**
                 * @todo This shouldn't be possibe! (null password)
                 */
                $adapter->insert('radcheck', array('username' => $model->getUsername(),
                    'attribute' => 'MD5-Password',
                    'op' => ':=',
                    'value' => $model->getPassword()));
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $model;
    }

    public function rowToModel(Zend_Db_Table_Row $row) {
        $model = parent::rowToModel($row);

        $adapter = $this->getDbTable()->getAdapter();

        $select = $adapter->select();

        $select->from('radcheck', 'value')
                ->where('username = ?', $model->getUsername())
                ->where('attribute = ?', 'MD5-Password');

        $password = $adapter->fetchOne($select);

        $model->setPassword($password);

        $result = $row->findDependentRowset('Users_Model_DbTable_NetworkUserPolicy');

        $policyIds = array();

        foreach ($result as $policyRow) {
            $policyIds[] = $policyRow->policy_id;
        }

        $model->setPolicyIds($policyIds);
        
        //Network User Data 
        $result = $row->findDependentRowset('Users_Model_DbTable_NetworkUserData');

        $userData = array();

        foreach ($result as $dataRow) {
            $userData[$dataRow->data_key] = $dataRow->data_value;
        }

        $model->setData($userData);

        return $model;
    }

}

