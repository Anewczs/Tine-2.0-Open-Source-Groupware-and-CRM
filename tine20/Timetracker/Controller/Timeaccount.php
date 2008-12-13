<?php
/**
 * Timeaccount controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * Timeaccount controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timeaccount extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timeaccount();
        $this->_modelName = 'Timetracker_Model_Timeaccount';
        $this->_currentAccount = Tinebase_Core::getUser();   
        $this->_purgeRecords = FALSE;
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Timetracker_Controller_Timeaccount
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timeaccount
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timetracker_Controller_Timeaccount();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
    
    /**
     * add one record
     * - create new container as well
     *
     * @param   Timetracker_Model_Timeaccount $_record
     * @return  Timetracker_Model_Timeaccount
     * 
     * @todo    check if container name exists ?
     */
    public function create(Tinebase_Record_Interface $_record)
    {   
        $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS);
        
        // create container and add container_id to record
        $containerName = $_record->title;
        if (!empty($_record->number)) {
            $containerName = $_record->number . ' ' . $containerName;
        }
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => $containerName,
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => $this->_backend->getType(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId() 
        ));
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => $this->_currentAccount->getId(),
            'account_type'  => 'user',
            'book_own'      => TRUE,
            'view_all'      => TRUE,
            'book_all'      => TRUE,
            'manage_clearing' => TRUE,
            'manage_all'    => TRUE,
        )));
        
        // add container with grants (all grants for creator) and ignore ACL here
        $container = Tinebase_Container::getInstance()->addContainer(
            $newContainer, 
            Timetracker_Model_TimeaccountGrants::doMapping($grants), 
            TRUE
        );

        $_record->container_id = $container->getId();
        
        return parent::create($_record);       
    }    
    
    /**
     * delete linked objects / timesheets
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {    
        // delete linked timesheets
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($_record->getId());
        Timetracker_Controller_Timesheet::getInstance()->delete($timesheets->getArrayOfIds());
        
        // delete other linked objects
        parent::_deleteLinkedObjects($_record);
    }

    /**
     * check grant for action (CRUD)
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.')
    {
        if ($_action == 'create') {
            // no check here because the MANAGE_TIMEACCOUNTS right has been already checked before
            return TRUE;
        }
        
        $hasGrant = Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Timetracker_Model_TimeaccountGrants::MANAGE_ALL);
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    $hasGrant
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Timetracker_Model_TimeaccountGrants::VIEW_ALL)
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Timetracker_Model_TimeaccountGrants::BOOK_OWN)
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Timetracker_Model_TimeaccountGrants::BOOK_ALL) 
                    || Timetracker_Model_TimeaccountGrants::hasGrant($_record->getId(), Timetracker_Model_TimeaccountGrants::MANAGE_CLEARING) 
                    );
            case 'delete':
            case 'update':
                $hasGrant = (
                    $hasGrant
                    || $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
                );
                break;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }
}
