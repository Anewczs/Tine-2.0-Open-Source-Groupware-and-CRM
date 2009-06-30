<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * controller tasks class
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller_Tasks extends ActiveSync_Controller_Abstract 
{
    protected $_mapping = array(
        #'Body'              => 'body',
        #'BodySize'          => 'bodysize',
        #'BodyTruncated'     => 'bodytruncated',
        #'Categories'        => 'categories',
        #'Category'          => 'category',
        'Complete'          => 'completed',
        #'DateCompleted'     => 'datecompleted',
        #'DueDate'           => 'duedate',
        'UtcDueDate'        => 'due',
        'Importance'        => 'priority',
        #'Recurrence'        => 'recurrence',
        #'Type'              => 'type',
        #'Start'             => 'start',
        #'Until'             => 'until',
        #'Occurrences'       => 'occurrences',
        #'Interval'          => 'interval',
        #'DayOfWeek'         => 'dayofweek',
        #'DayOfMonth'        => 'dayofmonth',
        #'WeekOfMonth'       => 'weekofmonth',
        #'MonthOfYear'       => 'monthofyear',
        #'Regenerate'        => 'regenerate',
        #'DeadOccur'         => 'deadoccur',
        #'ReminderSet'       => 'reminderset',
        #'ReminderTime'      => 'remindertime',
        #'Sensitivity'       => 'sensitivity',
        #'StartDate'         => 'startdate',
        #'UtcStartDate'      => 'utcstartdate',
        'Subject'           => 'summary',
        #'Rtf'               => 'rtf'
    );
    
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Tasks';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Task';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = ActiveSync_Command_FolderSync::FOLDERTYPE_TASK;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = ActiveSync_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED;
    
    public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_folderId, $_serverId)
    {
        $data = $this->_contentController->get($_serverId);
        
        foreach($this->_mapping as $key => $value) {
            if(!empty($data->$value)) {
                switch($value) {
                    case 'completed':
                        continue 2;
                        break;
                        
                    case 'due':
                        if($data->$value instanceof Zend_Date) {
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', $key, $data->$value->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z'));
                            #$_xmlNode->appendChild($_xmlDocument->createElementNS('POOMTASKS', $key, '2008-12-30T23:00:00.000Z'));
                            $data->$value->setTimezone(Tinebase_Core::get('userTimeZone'));
                            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', 'DueDate', $data->$value->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z'));
                        }
                        break;
                        
                    case 'priority':
                        $priority = ($data->$value <= 2) ? $data->$value : 2;
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', $key, $priority));
                        break;
                        
                    default:
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', $key, $data->$value));
                        break;
                }
            }
        }        
        
        // Completed is required
        if($data->completed instanceof Zend_Date) {
            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', 'Complete', 1));
            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', 'DateCompleted', $data->completed->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z'));
        } else {
            $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Tasks', 'Complete', 0));
        }
        
    }
        
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Tasks_Model_Task) {
            $task = $_entry;
        } else {
            $task = new Tasks_Model_Task(null, true);
        }
        
        $xmlData = $_data->children('uri:Tasks');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'completed':
                    if((int)$_data->$fieldName === 1) {
                        $task->status_id = 2;
                        $task->completed = (string)$_data->DateCompleted;
                    } else {
                        $task->status_id = 3; 
                        $task->completed = NULL;
                    }
                    break;
                case 'due':
                    if(isset($xmlData->$fieldName)) {
                        $timeStamp = $this->_convertISOToTs((string)$xmlData->$fieldName);
                        $task->$value = new Zend_Date($timeStamp, NULL);
                    } else {
                        $task->$value = null;
                    }
                    break;
                default:
                    if(isset($xmlData->$fieldName)) {
                        $task->$value = (string)$xmlData->$fieldName;
                    } else {
                        $task->$value = null;
                    }
                    break;
            }
        }
        
        // contact should be valid now
        $task->isValid();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " taskData " . print_r($task->toArray(), true));
        
        return $task;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTineFilter(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('Tasks');
                
        $filterArray[] = array(
            'field'     => 'containerType',
            'operator'  => 'equals',
            'value'     => 'all'
        ); 
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($xmlData->$fieldName)) {
                $filterArray[] = array(
                    'field'     => $value,
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        
        $filter = new Tasks_Model_TaskFilter($filterArray); 
    
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filter, true));
        
        return $filter;
    }
    
    /**
     * converts an iso formated date into a timestamp
     *
     * @param  string Zend_Date::ISO8601 representation of a datetime filed
     * @return int    UNIX Timestamp
     */
    protected function _convertISOToTs($_ISO)
    {
        $matches = array();
        
        preg_match("/^(\d{4})-(\d{2})-(\d{2})[T ]{1}(\d{2}):(\d{2}):(\d{2})/", $_ISO, $matches);

        if (count($matches) !== 7) {
            throw new Tinebase_Exception_UnexpectedValue("invalid date format $_ISO");
        }
        
        list($match, $year, $month, $day, $hour, $minute, $second) = $matches;
        return  mktime($hour, $minute, $second, $month, $day, $year);
    }
}
