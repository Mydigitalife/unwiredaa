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
 * Mapper for Nodes_Model_Node
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Nodes_Model_Mapper_Node extends Unwired_Model_Mapper
{

	protected $_modelClass = 'Nodes_Model_Node';
	protected $_dbTableClass = 'Nodes_Model_DbTable_Node';

	protected $_locationTable = null;
	protected $_settingsTable = null;
	protected $_statusTable = null;

	public function getLocationTable()
	{
		if (null ===  $this->_locationTable) {
			$this->_locationTable = new Nodes_Model_DbTable_NodeLocation();
		}

		return $this->_locationTable;
	}

	public function getSettingsTable()
	{
		if (null ===  $this->_settingsTable) {
			$this->_settingsTable = new Nodes_Model_DbTable_NodeSettings();
		}

		return $this->_settingsTable;
	}

	public function getStatusTable()
	{
		if (null ===  $this->_statusTable) {
			$this->_statusTable = new Nodes_Model_DbTable_NodeStatus();
		}

		return $this->_statusTable;
	}

	public function find($id)
	{
		$result = parent::find($id);

		if ($result && $result->isDeleted()) {
			return null;
		}

		return $result;
	}

	/**
     * Find entities by criteria
     *
     * @param Zend_Db_Select|array $conditions
     * @param int|null $limit
     */
    public function findBy($conditions, $limit = null)
    {
    	if ($conditions instanceof Zend_Db_Select) {
    		$conditions->where('deleted = ?', 0);
    	} elseif (is_array($conditions)) {
    		$conditions['deleted'] = 0;
    	}

    	return parent::findBy($conditions, $limit);
    }

    /**
     * Get all entries
     * @param string $order
     * @return array
     */
    public function fetchAll($order = null)
    {
        $resultSet = $this->getDbTable()->fetchAll('deleted = 0', $order);

        return $this->rowsetToModels($resultSet);
    }


	public function save(Unwired_Model_Generic $model)
	{
		$nodeTable = $this->getDbTable();

		$nodeTable->getAdapter()->beginTransaction();

		try {

			if ($model->getNodeId()) {
				$event = 'edit';
			} else {
				$event = 'add';
			}

			/**
			 * Disable events fired in parent class
			 */
			$this->setEventsDisabled(true);

			parent::save($model);

			$this->setDbTable($this->getLocationTable());

			parent::save($model->getLocation());

			$this->setDbTable($this->getSettingsTable());

			parent::save($model->getSettings());

			$this->setDbTable($this->getStatusTable());

			parent::save($model->getStatusExtended());

			$this->setDbTable($nodeTable);

			$nodeTable->getAdapter()->commit();

			$data = $model->toArray();
			$data['settings'] = $model->getSettings()->toArray();
			$data['location'] = $model->getLocation()->toArray();
			$data['status_extended'] = $model->getStatusExtended()->toArray();

			/**
			 * Fire our own event for node add/edit
			 */
			$this->setEventsDisabled(false);
			$this->sendEvent($event, $model, $model->getNodeId(), $data);

		} catch (Exception $e) {
			$nodeTable->getAdapter()->rollBack();
			$this->setDbTable($nodeTable);
			throw $e;
		}

		return $model;
	}

	public function rowToModel(Zend_Db_Table_Row $row)
	{
		$model = parent::rowToModel($row);

		$locationRow = $row->findDependentRowset($this->getLocationTable())->current();

		if ($locationRow) {
			$model->setLocation($locationRow->toArray());
		}

		$settingsRow = $row->findDependentRowset($this->getSettingsTable())->current();

		if ($settingsRow) {
			$model->setSettings($settingsRow->toArray());
		}

		$statusRow = $row->findDependentRowset($this->getStatusTable())->current();

		if ($statusRow) {
			$model->setStatusExtended($statusRow->toArray());
		}

		if (!$model->getOnlineStatus()) {
			return $model;
		}

		/**
		 * @todo Fix wrong user count
		 */
		$select = $this->getDbTable()
							->getAdapter()
								 ->select()
								 	->from('acct_internet_interim', new Zend_Db_Expr('count(*) AS `online_users`'))
								 	->where('node_id = ?' . $model->getNodeId())
								 	->where('`time` > DATE_SUB(NOW(), INTERVAL 1 MINUTE)');

		$onlineUsers = $this->getDbTable()
								 ->getAdapter()
								 	  ->fetchOne($select);

		$model->setOnlineUsersCount($onlineUsers);

		return $model;
	}

    /**
     * Mark node entry in database as deleted
     * @param Unwired_Model_Generic $model
     * @return integer
     */
    public function delete(Unwired_Model_Generic $model)
    {
    	$rowSet = $this->getDbTable()->find($model->getNodeId());

    	if (!$rowSet || !$rowSet->count()) {
    		return 0;
    	}

    	$row = $rowSet->current();

    	$row->deleted = 1;

    	$row->save();

    	$this->sendEvent('delete', $model, $model->getNodeId());

    	return 1;
    }

	/* (non-PHPdoc)
	 * @see Zend_Paginator_AdapterAggregate::getPaginatorAdapter()
	 * @return Unwired_Paginator_Adapter_Mapper
	 */
	public function getPaginatorAdapter() {
		if (null === $this->_paginatorAdapter) {
			$this->_paginatorAdapter = new Nodes_Model_Mapper_NodePaginator($this, $this->_customSelect);
		}

		return $this->_paginatorAdapter;
	}
}

