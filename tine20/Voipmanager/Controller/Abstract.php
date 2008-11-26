<?php
/**
 * Abstract controller for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @deprecated
 * @todo        remove later
 */

/**
 * abstract controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
abstract class Voipmanager_Controller_Abstract extends Tinebase_Application_Controller_Abstract implements Voipmanager_Controller_Interface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
   /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Interface
     */
    protected $_backend;
    
    /**
     * the central caching object
     *
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet
     */
    public function get($_id)
    {
        $context = $this->_backend->get($_id);
        
        return $context;    
    }

    /**
     * get list of voipmanager records
     *
     * @param Tinebase_Record_Interface|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL)
    {
        $result = $this->_backend->search($_filter, $_pagination);
        
        return $result;    
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        $record = $this->_backend->create($_record);
      
        return $this->get($record);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $record = $this->_backend->update($_record);
        
        return $this->get($record);
    }    
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  void
     */
    public function delete($_identifiers)
    {
        $records = $this->_backend->getMultiple((array)$_identifiers);
        if (count((array)$_identifiers) != count($records)) {
            throw new Voipmanager_Exception_NotFound('Error, only ' . count($records) . ' of ' . count((array)$_identifiers) . ' records exist');
        }
                    
        try {        
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            foreach ($records as $record) {
                $this->_backend->delete($record);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Voipmanager_Exception($e->getMessage());
        }                
        
        //$this->_backend->delete($_identifiers);
    }    

    /**
     * initialize the database backend
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws  Voipmanager_Exception_UnexpectedValue
     */
    protected function _getDatabaseBackend() 
    {
        if(isset(Zend_Registry::get('configFile')->voipmanager) && isset(Zend_Registry::get('configFile')->voipmanager->database)) {
            $dbConfig = Zend_Registry::get('configFile')->voipmanager->database;
        
            $dbBackend = constant('Tinebase_Core::' . strtoupper($dbConfig->get('backend', Tinebase_Core::PDO_MYSQL)));
            
            switch($dbBackend) {
                case Tinebase_Core::PDO_MYSQL:
                    $db = Zend_Db::factory('Pdo_Mysql', $dbConfig->toArray());
                    break;
                case Tinebase_Core::PDO_OCI:
                    $db = Zend_Db::factory('Pdo_Oci', $dbConfig->toArray());
                    break;
                default:
                    throw new Voipmanager_Exception_UnexpectedValue('Invalid database backend type defined. Please set backend to ' . Tinebase_Core::PDO_MYSQL . ' or ' . Tinebase_Core::PDO_OCI . ' in config.ini.');
                    break;
            }
        } else {
            $db = Zend_Registry::get('dbAdapter');
        }
        
        return $db;
    }
}
