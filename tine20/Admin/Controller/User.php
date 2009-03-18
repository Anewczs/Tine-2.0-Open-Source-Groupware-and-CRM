<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        extend Tinebase_Application_Controller_Record_Abstract
 */

/**
 * User Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_User extends Tinebase_Application_Controller_Abstract
{
	/**
	 * @var Tinebase_User_Ldap
	 */
	protected $_userBackend = NULL;
	
	/**
	 * @var bool
	 */
	protected $_manageSAM = false;
	
	/**
	 * @var Tinebase_SambaSAM_Ldap
	 */
	protected $_samBackend = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
		
		$this->_userBackend = Tinebase_User::getInstance();
		
		// manage samba sam?
		if(isset(Tinebase_Core::getConfig()->samba)) {
			$this->_manageSAM = Tinebase_Core::getConfig()->samba->get('manageSAM', false); 
			if ($this->_manageSAM) {
				$this->_samBackend = Tinebase_SambaSAM::getInstance();
			}
		}
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller_User
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_User
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_User;
        }
        
        return self::$_instance;
    }

    /**
     * get list of full accounts -> renamed to search full users
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function searchFullUsers($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        $result = $this->_userBackend->getUsers($_filter, $_sort, $_dir, $_start, $_limit);
        
        return $result;
    }
    
    /**
     * get account -> renamed to get user
     *
     * @param   int $_accountId account id to get
     * @return  Tinebase_Model_FullUser
     */
    public function get($_accountId)
    {        
        $this->checkRight('VIEW_ACCOUNTS');
        
        $user = $this->_userBackend->getUserById($_accountId, 'Tinebase_Model_FullUser');
        
        if ($this->_manageSAM) {
            $samUser = $this->_samBackend->getUserById($_accountId);
            $user->sambaSAM = $samUser;
        }
        
        return $user;
    }
    
    /**
     * set account status
     *
     * @param   string $_accountId  account id
     * @param   string $_status     status to set
     * @return  array with success flag
     */
    public function setAccountStatus($_accountId, $_status)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $result = $this->_userBackend->setStatus($_accountId, $_status);
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->setStatus($_accountId, $_status);
        }

        return $result;
    }
    
    /**
     * set the password for a given account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return unknown
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        }
        
        $result = $this->_userBackend->setPassword($_account->accountLoginName, $_password);
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->setPassword($_account, $_password);
        }
                
        return $result;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function update(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = $this->_userBackend->updateUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($_account, $_password, $_passwordRepeat);
        }
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->updateUser($_account, $_account->sambaSAM);
            $account->sambaSAM = $samResult;
        }
           
        return $account;
    }
    
    /**
     * save or update account
     *
     * @param Tinebase_Model_FullUser $_account the account
     * @param string $_password the new password
     * @param string $_passwordRepeat the new password again
     * @return Tinebase_Model_FullUser
     */
    public function create(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $account = $this->_userBackend->addUser($_account);
        Tinebase_Group::getInstance()->addGroupMember($account->accountPrimaryGroup, $account);
        
        $event = new Admin_Event_AddAccount;
        $event->account = $account;
        Tinebase_Events::fireEvent($event);
        
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($_account, $_password, $_passwordRepeat);
        }

        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->addUser($_account, $_account->sambaSAM);
            $account->sambaSAM = $samResult;
        }
 
        return $account;
    }
    
    /**
     * delete accounts
     *
     * @param   array $_accountIds  array of account ids
     * @return  array with success flag
     */
    public function delete(array $_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $this->_userBackend->deleteUsers($_accountIds);
        
        if ($this->_manageSAM) {
            $samResult = $this->_samBackend->deleteUsers($_accountIds);
        }
    }
}
