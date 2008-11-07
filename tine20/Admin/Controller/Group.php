<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Group Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_Group extends Tinebase_Application_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller_Group
     */
    private static $_instance = NULL;


    /**
     * Call parent constructor
     */
    private function __construct() 
    {
	    $this->_currentAccount = Zend_Registry::get('currentAccount');        
    }


    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Group
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Group;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Group
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        return Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);
    }
   
    /**
     * fetch one group identified by groupid
     *
     * @param int $_groupId
     * @return Tinebase_Model_Group
     */
    public function getGroup($_groupId)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->getGroupById($_groupId);

        return $group;            
    }  

   /**
     * add new group
     *
     * @param Tinebase_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Model_Group
     */
    public function addGroup(Tinebase_Model_Group $_group, array $_groupMembers = array ())
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->addGroup($_group);
        
        if ( !empty($_groupMembers) ) {
            Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);
        }

        return $group;            
    }  

   /**
     * update existing group
     *
     * @param Tinebase_Model_Group $_group
     * @param array $_groupMembers
     * 
     * @return Tinebase_Model_Group
     */
    public function updateGroup(Tinebase_Model_Group $_group, array $_groupMembers = array ())
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $group = Tinebase_Group::getInstance()->updateGroup($_group);
        
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $_groupMembers);

        return $group;            
    }  
    
    /**
     * delete multiple groups
     *
     * @param   array $_groupIds
     * @return  array with success flag
     */
    public function deleteGroups($_groupIds)
    {        
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_Group::getInstance()->deleteGroups($_groupIds);
    }    
    
    /**
     * get list of groupmembers
     *
     * @param int $_groupId
     * @return array with Tinebase_Model_User arrays
     */
    public function getGroupMembers($_groupId)
    {
        $result = Tinebase_Group::getInstance()->getGroupMembers($_groupId);
        
        return $result;
    }
    
}
