<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Tinebase_Relation_Backend_Sql
 * 
 * Tinebase_Relation_Backend_Sql enables records to define cross application relations to other records.
 * It acts as a gneralised storage backend for the records relation property of these records.
 * 
 * Relations between records have a certain degree (PARENT, CHILD and SIBLING). This degrees are defined
 * in Tinebase_Model_Relation. Moreover Relations are of a type which is defined by the application defining 
 * the relation. In case of users manually created relations this type is 'MANUAL'. This manually created
 * relatiions can also hold a free-form remark.
 * 
 * NOTE: Relations are viewed as time dependend properties of records. As such, relations could
 * be broken, but never become deleted.
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Relation_Backend_Sql
{

	/**
	 * Holds instance for SQL_TABLE_PREFIX . 'record_relations' table
	 * 
	 * @var Tinebase_Db_Table
	 */
	protected $_db;
	
	/**
	 * constructor
	 */
    public function __construct()
    {
    	// temporary on the fly creation of table
    	$this->_db = new Tinebase_Db_Table(array(
    	    'name' => SQL_TABLE_PREFIX . 'relations',
    	    'primary' => 'id'
    	));
    }
    
    /**
     * adds a new relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the new relation
     */
    public function addRelation( $_relation )
    {
    	if ($_relation->getId()) {
    		throw new Tinebase_Record_Exception_NotAllowed('Could not add existing relation');
    	}
    	
    	$id = $_relation->generateUID();
    	$_relation->setId($id);
    	
        // check if relation is already set (with is_deleted=1)  
        if ($deletedId = $this->_checkExistance($_relation)) {
            $where = array(
                $this->_db->getAdapter()->quoteInto('id IN (?)', $deletedId)
            );          
            $this->_db->delete($where);
        }
                
		$data = $_relation->toArray();
		unset($data['related_record']);

		$this->_db->insert($data);
		$this->_db->insert($this->_swapRoles($data));
		
		return $this->getRelation($id, $_relation['own_model'], $_relation['own_backend'], $_relation['own_id']);
    		
    } // end of member function addRelation
    
    /**
     * update an existing relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the updated relation
     */
    public function updateRelation( $_relation )
    {
        $id = $_relation->getId();
        
        $data = $_relation->toArray();
        unset($data['related_record']);
        
        foreach (array($data, $this->_swapRoles($data)) as $toUpdate) {
            $where = array(
                'id          = ' . $this->_db->getAdapter()->quote($id),
                'own_model   = ' . $this->_db->getAdapter()->quote($toUpdate['own_model']),
                'own_backend = ' . $this->_db->getAdapter()->quote($toUpdate['own_backend']),
                'own_id      = ' . $this->_db->getAdapter()->quote($toUpdate['own_id']),
            );
            $this->_db->update($toUpdate, $where);
        }
        
        return $this->getRelation($id, $_relation['own_model'], $_relation['own_backend'], $_relation['own_id']);
            
    } // end of member function updateRelation
    
    /**
     * breaks a relation
     * 
     * @param Tinebase_Model_Relation $_relation 
     * @return void 
     */
    public function breakRelation( $_id )
    {
    	$where = array(
    	    'id = ' . $this->_db->getAdapter()->quote($_id)
    	);
    	
    	$this->_db->update(array(
    	    'is_deleted'   => true,
    	    'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
    	    'deleted_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
    	), $where);
    } // end of member function breakRelation
    
    /**
     * breaks all relations, optionally only of given role
     * 
     * @param  string $_model    own model to break all relations for
     * @param  string $_backend  own backend to break all relations for
     * @param  string $_id       own id to break all relations for
     * @param  string $_degree   only breaks relations of given degree
     * @param  string $_type     only breaks relations of given type
     * @return void
     */
    public function breakAllRelations( $_model, $_backend, $_id, $_degree = NULL, $_type = NULL )
    {
        $relationIds = $this->getAllRelations($_model, $_backend, $_id, $_degree, $_type)->getArrayOfIds();
        if (!empty($relationIds)) {
            $where = array(
                $this->_db->getAdapter()->quoteInto('id IN (?)', $relationIds)
            );
        
            $this->_db->update(array(
                'is_deleted'   => true,
                'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
                'deleted_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ), $where);
        }
    } // end of member function breakAllRelations
    
    /**
     * returns all relations of a given record and optionally only of given role
     * 
     * @param  string       $_model    own model to get all relations for
     * @param  string       $_backend  own backend to get all relations for
     * @param  string|array $_id       own id to get all relations for 
     * @param  string       $_degree   only breaks relations of given degree
     * @param  string       $_type     only breaks relations of given type
     * @param  boolean      $_returnAll gets all relations (default: only get not deleted/broken relations)
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getAllRelations($_model, $_backend, $_id, $_degree = NULL, $_type = NULL, $_returnAll = false)
    {
        $_id = $_id ? (array)$_id : array('');
        $where = array(
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_model') .' = ?', $_model),
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_backend') .' = ?',$_backend),
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_id') .' IN (?)' , $_id),
            //'is_deleted  = '  . $this->_db->quote((bool)$_returnBroken)
        );
        
        if (!$_returnAll) {
            $where[] = $this->_db->getAdapter()->quoteIdentifier('is_deleted') . ' = FALSE';
        }
        if ($_degree) {
            $where[] = $this->_db->getAdapter()->quoteInto($this->_db->quoteIdentifier('own_degree') . ' = ?', $_degree);
        }
        if ($_type) {
            $where[] = $this->_db->getAdapter()->quoteInto($this->_db->quoteIdentifier('type') . ' = ?', $_type);
        }
        
       // Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($where, true));
        
        $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array(), true);
        foreach ($this->_db->fetchAll($where) as $relation) {
            $relations->addRecord(new Tinebase_Model_Relation($relation->toArray(), true));
        }
        return $relations; 
    } // end of member function getAllRelations
    
    /**
     * returns on side of a relation
     *
     * @param  string $_id
     * @param  string $_ownModel 
     * @param  string $_ownBackend
     * @param  string $_ownId
     * @param  bool   $_returnBroken
     * @return Tinebase_Model_Relation
     */
    public function getRelation($_id, $_ownModel, $_ownBackend, $_ownId, $_returnBroken = false)
    {
        $where = array(
            $this->_db->getAdapter()->quoteInto('id = ?', $_id),
            $this->_db->getAdapter()->quoteInto('own_model = ?', $_ownModel),
            $this->_db->getAdapter()->quoteInto('own_backend = ?', $_ownBackend),
            $this->_db->getAdapter()->quoteInto('own_id = ?', $_ownId),
        );
        if ($_returnBroken !== true) {
            $where[] = 'is_deleted = FALSE';
        }
    	$relationRow = $this->_db->fetchRow($where);
    	
    	if($relationRow) {
    		return new Tinebase_Model_Relation($relationRow->toArray(), true);
    	} else {
    		throw new Tinebase_Record_Exception_NotDefined("No relation found.");
    	}
    	
    } // end of member function getRelationById
    
    /**
     * purges(removes from table) all relations
     * 
     * @param  string $_ownModel 
     * @param  string $_ownBackend
     * @param  string $_ownId
     * @return void
     * 
     * @todo should this function only purge deleted/broken relations?
     */
    public function purgeAllRelations($_ownModel, $_ownBackend, $_ownId)
    {
        $relationIds = $this->getAllRelations($_ownModel, $_ownBackend, $_ownId, NULL, NULL, true)->getArrayOfIds();
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($relationIds, true));
        
        if (!empty($relationIds)) {
            $where = array(
                $this->_db->getAdapter()->quoteInto('id IN (?)', $relationIds)
            );
        
            $this->_db->delete($where);
        }
    }
    /**
     * swaps roles own/related
     * 
     * @param  array data of a relation
     * @return array data with swaped roles
     */
    protected function _swapRoles($_data)
    {
        $data = $_data;
        $data['own_model']       = $_data['related_model'];
        $data['own_backend']     = $_data['related_backend'];
        $data['own_id']          = $_data['related_id'];
        $data['related_model']   = $_data['own_model'];
        $data['related_backend'] = $_data['own_backend'];
        $data['related_id']      = $_data['own_id'];
        switch ($_data['own_degree']) {
            case Tinebase_Model_Relation::DEGREE_PARENT:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_CHILD;
                break;
            case Tinebase_Model_Relation::DEGREE_CHILD:
                $data['own_degree'] = Tinebase_Model_Relation::DEGREE_PARENT;
                break;
        }
        return $data;
    }
    
    /**
     * check if relation already exists but is_deleted
     *
     * @param Tinebase_Model_Relation $_relation
     * @return string relation id
     */
    protected function _checkExistance($_relation)
    {

        $where = array(
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_model') . ' = ?', $_relation->own_model),
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_backend') . ' = ?', $_relation->own_backend),
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('own_id') . ' = ?', $_relation->own_id),
            $this->_db->getAdapter()->quoteInto($this->_db->getAdapter()->quoteIdentifier('related_id') . ' = ?', $_relation->related_id),
            $this->_db->getAdapter()->quoteIdentifier('is_deleted') . ' = 1'
        );
        
        $relationRow = $this->_db->fetchRow($where);
        
        if($relationRow) {
            return $relationRow->id;
        } else {
            return FALSE;
        }
    }    
    
} // end of Tinebase_Relation_Backend_Sql
