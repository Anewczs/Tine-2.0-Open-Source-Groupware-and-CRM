<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * main authentication class
 * 
 * @package     Tinebase
 * @subpackage  Auth 
 */

class Tinebase_Auth
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'Ldap';

    /**
     * General Failure
     */
    const FAILURE                       =  0;

    /**
     * Failure due to identity not being found.
     */
    const FAILURE_IDENTITY_NOT_FOUND    = -1;

    /**
     * Failure due to identity being ambiguous.
     */
    const FAILURE_IDENTITY_AMBIGUOUS    = -2;

    /**
     * Failure due to invalid credential being supplied.
     */
    const FAILURE_CREDENTIAL_INVALID    = -3;

    /**
     * Failure due to uncategorized reasons.
     */
    const FAILURE_UNCATEGORIZED         = -4;
    
    /**
     * Failure due the account is disabled
     */
    const FAILURE_DISABLED              = -100;

    /**
     * Failure due the account is expired
     */
    const FAILURE_EXPIRED               = -101;
    
    /**
     * Failure due the account is temporarly blocked
     */
    const FAILURE_BLOCKED               = -102;
        
    /**
     * Authentication success.
     */
    const SUCCESS                        =  1;

    /**
     * the name of the authenticationbackend
     *
     * @var string
     */
    protected $_backendType = Tinebase_Auth_Factory::SQL;
    
/**
     * the instance of the authenticationbackend
     *
     * @var Tinebase_Auth_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        try {
            $authConfig = Tinebase_Core::getConfig()->authentication;
            if (isset($authConfig)) {
                $this->_backendType = $authConfig->get('backend', Tinebase_Auth_Factory::SQL);
                $this->_backendType = ucfirst($this->_backendType);
            }            
        } catch (Zend_Config_Exception $e) {
            // do nothing
            // there is a default set for $this->_backendType
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' authentication backend: ' . $this->_backendType);
        
        $this->_backend = Tinebase_Auth_Factory::factory($this->_backendType);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Auth
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Auth
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Auth;
        }
        
        return self::$_instance;
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @return Zend_Auth_Result
     */
    public function authenticate($_username, $_password)
    {
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        $result = Zend_Auth::getInstance()->authenticate($this->_backend);
                
        return $result;
    }
    
    /**
     * check if password is valid
     *
     * @param string $_username
     * @param string $_password
     * @return boolean
     */
    public function isValidPassword($_username, $_password)
    {
        $this->_backend->setIdentity($_username);
        $this->_backend->setCredential($_password);
        
        $result = $this->_backend->authenticate();

        if ($result->isValid()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * sets the password
     *
     * @param   string $_loginName the loginname of the account
     * @param   string $_password1
     * @param   string $_password2
     * @param   bool $_encrypt encrypt password
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_loginName, $_password1, $_password2, $_encrypt = TRUE)
    {
        if($_password1 !== $_password2) {
            throw new Tinebase_Exception_InvalidArgument('Password 1 and Password 2 do not match!');
        }
        
        $this->_backend->setPassword($_loginName, $_password1, $_encrypt);
    }
}
