<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Tasks';
    
    /**
     * @var Tasks_Controller
     */
    protected $_controller;
    
    protected $_userTimezone;
    protected $_serverTimezone;
    
    public function __construct()
    {
        try{
            $this->_controller = Tasks_Controller::getInstance();
        } catch (Exception $e) {
            //error_log($e);
        }
        $this->_userTimezone = Zend_Registry::get('userTimeZone');
        $this->_serverTimezone = date_default_timezone_get();
    }

    /**
     * Search for tasks matching given arguments
     *
     * @param array $filter
     * @return array
     */
    public function searchTasks($filter)
    {
        $paginationFilter = Zend_Json::decode($filter);
        $filter = new Tasks_Model_Filter($paginationFilter);
        $pagination = new Tasks_Model_Pagination($paginationFilter);
        //Zend_Registry::get('logger')->debug(print_r($pagination->toArray(),true));
        
        $tasks = $this->_controller->searchTasks($filter, $pagination);
        $tasks->setTimezone($this->_userTimezone);
        $tasks->convertDates = true;
        
        return array(
            'results' => $tasks->toArray(),
            'totalcount' => $this->_controller->searchTasksCount($filter)
        );
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Model_Task task
     */
    public function getTask($uid)
    {
        $task = $this->_controller->getTask($uid);
        return $this->taskToJson($task);
    }
    
    /**
     * Upate an existing Task
     *
     * @param  $task
     * @return array the updated task
     */
    public function updateTask($task)
    {
        $inTask = $this->jsonToTask($task);
        
        //error_log(print_r($newTask->toArray(),true));
        $outTask = $this->_controller->updateTask($inTask);
        return $this->taskToJson($outTask);
    }
    
    /**
     * creates/updates a Task
     *
     * @param  $task
     * @return array created/updated task
     */
    public function saveTask($task)
    {
        $inTask = $this->jsonToTask($task);
        //Zend_Registry::get('logger')->debug(print_r($inTask->toArray(),true));
        
        $outTask = strlen($inTask->getId()) > 10 ? 
            $this->_controller->updateTask($inTask): 
            $this->_controller->createTask($inTask);

        return $this->taskToJson($outTask);
    }
    
    /**
     * returns instance of Tasks_Model_Task from json encoded data
     * 
     * @param string JSON encoded task
     * @return Tasks_Model_Task task
     * 
     * @todo replace with Tasks_Model_Task::setFromJson() -> how do we handle the timezone setting?
     */
    public function jsonToTask($json)
    {
        date_default_timezone_set($this->_userTimezone);
        //$inTask = new Tasks_Model_Task(Zend_Json::decode($json));
        $inTask = Tasks_Model_Task::setFromJson($json);
        $inTask->setTimezone($this->_serverTimezone);
        date_default_timezone_set($this->_serverTimezone);
        
        return $inTask;
    }
    
    /**
     * returns task prepared for json transport
     *
     * @param Tasks_Model_Task $_task
     * @return array task data
     */
    public function taskToJson($_task)
    {
        $_task->setTimezone(Zend_Registry::get('userTimeZone'));
        $_task->bypassFilters = true;
        $_task->container_id = Zend_Json::encode(Tinebase_Container::getInstance()->getContainerById($_task->container_id)->toArray());
        return $_task->toArray();
    }
    
    /**
     * Deletes an existing Task
     *
     * @throws Exception
     * @param int $identifier
     * @return string
     */
    public function deleteTask($identifier)
    {
        if (strlen($identifier) > 40) {
            $identifier = Zend_Json::decode($identifier);
        }
        $this->_controller->deleteTask($identifier);
        return 'success';
    }
    
    /**
     * temporaray function to get a default container
     * 
     * @return array container
     */
    public function getDefaultContainer()
    {
        $container = $this->_controller->getDefaultContainer();
        $container->setTimezone($this->_userTimezone);
        return $container->toArray();
    }
    
    /**
     * retruns all possible task stati
     * 
     * @return Tinebase_Record_RecordSet of Tasks_Model_Status
     */
    public function getStati() {
        return $this->_controller->getStati()->toArray();
    }
}
