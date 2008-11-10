<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * SQL Backend for Tasks 2.0
 * 
 * The Tasks 2.0 Sql backend consists of various tables. Properties with single
 * appearance are stored in the egw_tasks table. Properties which could appear
 * more than one time are stored in corresponding tables.
 * 
 * @package     Tasks
 * @subpackage  Backend
 * 
 * @todo    remove current account from sql backend
 * @todo    add function for complete removal of tasks?
 * @todo    split backend (status/tasks)?
 */
class Tasks_Backend_Sql extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * For some said reason, Zend_Db doesn't support table prefixes. Thus each 
     * table calss needs to implement it its own.
     * 
     * @see http://framework.zend.com/issues/browse/ZF-827
     * @todo solve table prefix in Tinebase_Db (quite a bit of work)
     * @var array
     */
    protected $_tableNames = array(
        'tasks'     => 'tasks',
        'contact'   => 'tasks_contact',
        'status'    => 'tasks_status',
    );
    
    /**
     * Holds the table instances for the different tables
     *
     * @var array
     */
    protected $_tables = array();
    
    /**
     * Holds instance of current account
     *
     * @var Tinebase_Model_User
     */
    protected $_currentAccount;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        // fix table prefixes
        foreach ($this->_tableNames as $basename => $name) {
            $this->_tableNames[$basename] = SQL_TABLE_PREFIX . $name;
        }
        
        try {
            $this->_currentAccount = Zend_Registry::get('currentAccount');
        } catch (Zend_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . 'no account available: ' . $e->getMessage());
        }
        
        // set identifier with table name because we join tables in _getSelect()
        $this->_identifier = 'tasks.id';
        
        parent::__construct($this->_tableNames['tasks'], 'Tasks_Model_Task');
    }
    
    /**
     * Create a new Task
     *
     * @param   Tasks_Model_Task $_task
     * @return  Tasks_Model_Task
     * @throws  Tasks_Exception_Backend
     */
    public function create(Tinebase_Record_Interface $_task)
    {
        if ( empty($_task->id) ) {
        	$newId = $_task->generateUID();
        	$_task->setId($newId);
        }
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_task, 'create');
        $taskParts = $this->seperateTaskData($_task);
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            $tasksTable = $this->getTableInstance('tasks');
            $tasksTable->insert($taskParts['tasks']);
            $this->insertDependentRows($taskParts);
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            return $this->get($_task->getId());
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tasks_Exception_Backend($e->getMessage);
        }
    }
    
    
    /**
     * Upate an existing Task
     *
     * @param   Tasks_Model_Task $_task
     * @return  Tasks_Model_Task
     * @throws  Exception
     */ 
    public function update(Tinebase_Record_Interface $_task)
    {
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // database update
            $taskParts = $this->seperateTaskData($_task);
            $tasksTable = $this->getTableInstance('tasks');
            $numAffectedRows = $tasksTable->update($taskParts['tasks'], array(
                $this->_db->quoteInto('id = ?', $_task->id),
            ));
            $this->deleteDependentRows($_task->id);
            $this->insertDependentRows($taskParts);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            return $this->get($_task->id);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            //throw new Tasks_Exception_Backend($e->getMessage());
            throw $e;            
        }
    }
    
    /**
     * Deletes a Task
     *
     * @param   string|array $_id
     * @return  void
     * @throws  Tasks_Exception_Backend
     */
    public function delete($_id)
    {
        $id = $this->_convertId($_id);
        
        $tasksTable = $this->getTableInstance('tasks');
        $data = array(
            'is_deleted'   => true, 
            'deleted_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'deleted_by'   => $this->_currentAccount->getId()
        );
        
        //$tasksTable->delete($tasksTable->getAdapter()->quoteInto('id = ?', $_uid));
        $tasksTable->update($data, array(
            $this->_db->quoteInto('id = ?', $id)
        ));
    }
    
    /**
     * Returns a record as it was at a given point in history
     * 
     * @param string _id 
     * @param Zend_Date _at 
     * @return Tinebase_Record
     * @access public
     */
    public function getRecord($_id,  Zend_Date $_at)
    {
        
    }
    
    /**
     * Returns a set of records as they where at a given point in history
     * 
     * @param array _ids array of strings
     * @param Zend_Date _at 
     * @return Tinebase_Record_RecordSet
     * @access public
     */
    public function getRecords(array $_ids,  Zend_Date $_at)
    {
        
    }
    
    /**
     * Deletes all depended rows from a given parent task
     *
     * @param string $_parentTaskId
     * @return int number of deleted rows
     */
    protected function deleteDependentRows($_parentTaskId)
    {
        $deletedRows = 0;
        foreach (array('contact') as $table) {
            $TableObject = $this->getTableInstance($table);
            $deletedRows += $TableObject->delete(
                $this->_db->quoteInto('task_id = ?', $_parentTaskId)
            );
        }
        return $deletedRows;
    }
    
    /**
     * Inserts rows in dependent tables
     *
     * @param array $_taskparts
     */
    protected function insertDependentRows($_taskParts)
    {
        foreach (array('contact') as $table) {
            if (!empty($_taskParts[$table])) {
                $items = explode(',', $_taskParts[$table]);
                $TableObject = $this->getTableInstance($table);
                foreach ($items as $itemId) {
                    $TableObject->insert(array(
                        'task_id'    => $taskId,
                        $table . '_id' => $itemId
                    ));
                }
            }
        }
    }
    
    /**
     * Seperates tasks data into the different tables
     *
     * @param Tasks_Model_Task $_task
     * @return array array of arrays
     */
    protected function seperateTaskData($_task)
    {
    	$_task->convertDates = true;
        $taskArray = $_task->toArray();
        $TableDescr = $this->getTableInstance('tasks')->info();
        $taskparts['tasks'] = array_intersect_key($taskArray, array_flip($TableDescr['cols']));
        
        foreach (array('contact') as $table) {
            if (!empty($taskArray[$table])) {
                $taksparts[$table] = $taskArray[$table];
            }
        }
        
        return $taskparts;
    }
    
    /**
     * Returns instance of given table-class
     *
     * @todo Move Migration to setup class once we have one!
     * @param string $_tablename
     * @return Tinebase_Db_Table
     */
    protected function getTableInstance($_tablename)
    {
        if (!isset($this->_tables[$_tablename])) {
            $this->_tables[$_tablename] = new Tinebase_Db_Table(array('name' => $this->_tableNames[$_tablename]));
        }
        return $this->_tables[$_tablename];
    }
    
    /********************************** protected funcs **********************************/
    
    /**
     * Returns a common select Object
     * 
     * @return Zend_Db_Select
     */
    protected function _getSelect($_getCount = FALSE)
    {
        $select = $this->_db->select()
            ->where('tasks.is_deleted = FALSE');
        
        $tablename = array('tasks' => $this->_tableNames['tasks']);
        
        if ($_getCount) {
            $fields = array('count' => 'COUNT(tasks.id)');
            $select->from($tablename, $fields) 
                ->joinLeft(array('status'  => $this->_tableNames['status']), 'tasks.status_id = status.id', array());
        } else {
            $fields = array(
                'tasks.*', 
                'contact' => 'GROUP_CONCAT(DISTINCT contact.contact_id)',
                'is_due'  => 'LENGTH(tasks.due)',
                //'is_open' => 'status.status_is_open',
            );
            $select->from($tablename, $fields)
                ->joinLeft(array('contact' => $this->_tableNames['contact']), 'tasks.id = contact.task_id', array())
                ->joinLeft(array('status'  => $this->_tableNames['status']), 'tasks.status_id = status.id', array())
                ->group('tasks.id');
        }

        return $select;
    }

    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Crm_Model_LeadFilter $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Tasks_Model_Filter $_filter)
    {
        $_select->where($this->_db->quoteInto('tasks.container_id IN (?)', $_filter->container));
                                
        if(!empty($_filter->query)){
            $_select->where($this->_db->quoteInto('(tasks.summary LIKE ? OR tasks.description LIKE ?)', '%' . $_filter->query . '%'));
        }
        if(!empty($_filter->status)){
            $_select->where($this->_db->quoteInto('tasks.status_id = ?',$_filter->status));
        }
        if(!empty($_filter->organizer)){
            $_select->where($this->_db->quoteInto('tasks.organizer = ?', (int)$_filter->organizer));
        }
        if(isset($_filter->showClosed) && $_filter->showClosed){
            // nothing to filter
        } else {
            $_select->where('status.status_is_open = TRUE OR tasks.status_id IS NULL');
        }
    }        
    
}
