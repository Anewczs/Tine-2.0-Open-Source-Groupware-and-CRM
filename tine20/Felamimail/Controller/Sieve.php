<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        move sieve backend to Felamimail/Backend ?
 */

/**
 * Sieve controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Sieve extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * Sieve Script backend
     *
     * @var Felamimail_Sieve_Script
     */
    protected $_scriptBackend = NULL;
    
    /**
     * Sieve backend
     *
     * @var Felamimail_Backend_Sieve
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Sieve
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_scriptBackend = new Felamimail_Sieve_Script();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Sieve
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Sieve();
        }
        
        return self::$_instance;
    }
    
    /**
     * get vacation for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return Felamimail_Model_Sieve_Vacation
     * 
     * @todo finish implementation
     */
    public function getVacation($_accountId)
    {
        //$this->_setSieveBackendAndAuthenticate($_accountId);
        //$scripts = $this->_backend->listScripts();

        //print_r($scripts);
        
        //$script->parseScript($vacationScript);
        //$vacation = $script->getVacation();
        $result = new Felamimail_Model_Sieve_Vacation(array(
            'account_id'    => $_accountId
        ));
        //$result->setFromFSV($vacation);
        
        return $result;
    }
    
    /**
     * init and connect to sieve backend + authenticate with imap user of account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    protected function _setSieveBackendAndAuthenticate($_accountId)
    {
        $this->_backend = Felamimail_Backend_SieveFactory::factory($_accountId);
    }
    
    /**
     * set vacation for account
     * 
     * @param string $_accountId
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     * @return Felamimail_Model_Sieve_Vacation
     * 
     * @todo finish implementation
     */
    public function setVacation($_accountId, Felamimail_Model_Sieve_Vacation $_vacation)
    {
        //$this->_setSieveBackendAndAuthenticate($_accountId);
        
        return $this->getVacation($_accountId);
    }

    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Sieve_Rule
     * 
     * @todo implement
     */
    public function getRules($_accountId)
    {
    }
    
    /**
     * set rules for account
     * 
     * @param string $_accountId
     * @param Tinebase_Record_RecordSet $_rules (Felamimail_Model_Sieve_Rule)
     * @return Tinebase_Record_RecordSet
     * 
     * @todo implement
     */
    public function setRules($_accountId, Tinebase_Record_RecordSet $_rules)
    {
    }
}
