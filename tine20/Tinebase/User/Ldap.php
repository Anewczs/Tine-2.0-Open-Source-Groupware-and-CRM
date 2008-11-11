<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * User ldap backend
 * 
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_Ldap extends Tinebase_User_Abstract
{
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) 
    {
        $this->_backend = new Tinebase_Ldap($_options);
        $this->_backend->bind();
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
     * @var Tinebase_User_Ldap
     */
    private static $_instance = NULL;
    
    /**
     * Enter description here...
     *
     * @var Tinebase_Ldap
     */
    protected $_backend = NULL;
    
    protected $_rowNameMapping = array(
        'accountId'             => 'uidnumber',
        'accountDisplayName'    => 'displayname',
        'accountFullName'       => 'cn',
        'accountFirstName'      => 'givenname',
        'accountLastName'       => 'sn',
        'accountLoginName'      => 'uid',
        //'accountLastLogin'      => 'last_login',
        //'accountLastLoginfrom'  => 'last_login_from',
        'accountLastPasswordChange' => 'shadowlastchange',
        'accountStatus'         => 'shadowinactive',
        'accountExpires'        => 'shadowexpire',
        'accountPrimaryGroup'   => 'gidnumber',
        'accountEmailAddress'   => 'mail'
    );
    
    
    /**
     * the singleton pattern
     *
     * @param  array $options Options used in connecting, binding, etc.
     * @return Tinebase_User_Ldap
     */
    public static function getInstance(array $_options = array()) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_User_Ldap($_options);
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, 
        $_limit = NULL, $_accountClass = 'Tinebase_Model_User')
    {        
        if (!empty($_filter)) {
            $searchString = "*" . Tinebase_Ldap::filterEscape($_filter) . "*";
            $filter = "(&(objectclass=posixaccount)(|(uid=$searchString)(cn=$searchString)(sn=$searchString)(givenName=$searchString)))";
        } else {
            $filter = 'objectclass=posixaccount';
        }
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' search filter: ' . $filter);
        $accounts = $this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->userDn, $filter, array_values($this->_rowNameMapping));
        
        $result = new Tinebase_Record_RecordSet($_accountClass);
        
        foreach ($accounts as $account) {
            $accountArray = array(
                'accountStatus' => 'enabled'
            );
            
            foreach ($account as $key => $value) {
                if (is_int($key)) {
                    continue;
                }
                $keyMapping = array_search($key, $this->_rowNameMapping);
                if ($keyMapping !== FALSE) {
                    switch($keyMapping) {
                        case 'accountLastPasswordChange':
                        case 'accountExpires':
                            $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                            break;
                        case 'accountStatus':
                            break;
                        default: 
                            $accountArray[$keyMapping] = $value[0];
                            break;
                    }
                }
            }
            
            $accountObject = new $_accountClass($accountArray);
            
            $result->addRecord($accountObject);
        }
        
        return $result;
    }
    
    /**
     * get user by login name
     *
     * @param string $_loginName the loginname of the user
     * @return Tinebase_Model_User the user object
     *
     * @throws Tinebase_Exception_Record_NotDefined when row is empty
     */
    public function getUserByLoginName($_loginName, $_accountClass = 'Tinebase_Model_User')
    {
        $loginName = Zend_Ldap::filterEscape($_loginName);
        $account = $this->_backend->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->userDn, 'uid=' . $loginName);
                
        // throw exception if data is empty (if the row is no array, the setFromArray function throws a fatal error 
        // because of the wrong type that is not catched by the block below)
/*        if ( $row === false ) {
             throw new Exception('user not found');
        } */        

        $accountArray = array(
            'accountStatus' => 'enabled'
        );
        
        foreach ($account as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_rowNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'accountLastPasswordChange':
                    case 'accountExpires':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                        break;
                    case 'accountStatus':
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        $account = new $_accountClass($accountArray);
                
        return $account;
    }
    
    /**
     * get user by userId
     *
     * @param int $_accountId the account id
     * @return Tinebase_Model_User the user object
     */
    public function getUserById($_accountId, $_accountClass = 'Tinebase_Model_User')
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $account = $this->_backend->fetch(Zend_Registry::get('configFile')->accounts->get('ldap')->userDn, 'uidnumber=' . $accountId);
                
        // throw exception if data is empty (if the row is no array, the setFromArray function throws a fatal error 
        // because of the wrong type that is not catched by the block below)
/*        if ( $row === false ) {
             throw new Exception('user not found');
        } */        

        $accountArray = array(
            'accountStatus' => 'enabled'
        );
        
        foreach ($account as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_rowNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'accountLastPasswordChange':
                    case 'accountExpires':
                        $accountArray[$keyMapping] = new Zend_Date($value[0], Zend_Date::TIMESTAMP);
                        break;
                    case 'accountStatus':
                        break;
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        $account = new $_accountClass($accountArray);
                
        return $account;
    }
    
    /**
     * update the lastlogin time of user
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['last_login_from'] = $_ipAddress;
        $accountData['last_login']      = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    protected function _getUserSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 
                array(
                    'accountId'             => $this->_rowNameMapping['accountId'],
                    'accountLoginName'      => $this->_rowNameMapping['accountLoginName'],
                    'accountLastLogin'      => $this->_rowNameMapping['accountLastLogin'],
                    'accountLastLoginfrom'  => $this->_rowNameMapping['accountLastLoginfrom'],
                    'accountLastPasswordChange' => $this->_rowNameMapping['accountLastPasswordChange'],
                    'accountStatus'         => $this->_rowNameMapping['accountStatus'],
                    'accountExpires'        => $this->_rowNameMapping['accountExpires'],
                    'accountPrimaryGroup'   => $this->_rowNameMapping['accountPrimaryGroup']
            ))
            ->join(SQL_TABLE_PREFIX . 'addressbook',
                SQL_TABLE_PREFIX . 'accounts.id = ' . SQL_TABLE_PREFIX . 'addressbook.account_id',
                array(
                    'accountDisplayName'    => $this->_rowNameMapping['accountDisplayName'],
                    'accountFullName'       => $this->_rowNameMapping['accountFullName'],
                    'accountFirstName'      => $this->_rowNameMapping['accountFirstName'],
                    'accountLastName'       => $this->_rowNameMapping['accountLastName'],
                    'accountEmailAddress'   => $this->_rowNameMapping['accountEmailAddress']
            ));
                
        return $select;
    }
    
    /**
     * update user status
     *
     * @param   int         $_accountId
     * @param   string      $_status
    */
    public function setStatus($_accountId, $_status) 
    {
        throw new Exception('not yet implemented');
    }

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param   int         $_accountId
     * @param   Zend_Date   $_expiryDate
    */
    public function setExpiryDate($_accountId, $_expiryDate) 
    {
        throw new Exception('not yet implemented');
    }

    /**
     * blocks/unblocks the user (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    public function setBlockedDate($_accountId, $_blockedUntilDate) 
    {
        throw new Exception('not yet implemented');
    }
        
    /**
     * updates an existing user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateUser(Tinebase_Model_FullUser $_account) 
    {
        throw new Exception('not yet implemented');
    }

    /**
     * adds a new user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function addUser(Tinebase_Model_FullUser $_account) 
    {
        throw new Exception('not yet implemented');
    }
    
    /**
     * delete an user
     *
     * @param int $_accountId
     */
    public function deleteUser($_accountId) 
    {
        throw new Exception('not yet implemented');
    }

    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    public function deleteUsers(array $_accountIds) 
    {
        throw new Exception('not yet implemented');
    }

    /**
     * Get multiple users
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet
     * @todo implement
     */
    public function getMultiple($_id) 
    {
        throw new Tinebase_Exception('Not implemented yet.');
    }
}