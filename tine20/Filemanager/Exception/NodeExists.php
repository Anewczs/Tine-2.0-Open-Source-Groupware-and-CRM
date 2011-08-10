<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Filemanager exception
 * 
 * @package     Filemanager
 * @subpackage  Exception
 */
class Filemanager_Exception_NodeExists extends Filemanager_Exception
{
    /**
     * existing nodes info
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_existingNodes = NULL;
    
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'file exists', $_code = 901) {
        parent::__construct($_message, $_code);
    }
    
    /**
     * set existing nodes info
     * 
     * @param Tinebase_Record_RecordSet $_fbInfo
     */
    public function addExistingNodeInfo(Tinebase_Model_Tree_Node $_existingNode)
    {
       $this->getExistingNodesInfo->addRecord($_existingNode);
    }
    
    /**
     * get existing nodes info
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function getExistingNodesInfo()
    {
        return $this->_existingNodes ? $this->_existingNodes : new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
    }
    
    /**
     * returns existing nodes info as array
     * 
     * @return array
     */
    public function toArray()
    {
        $this->getExistingNodesInfo()->setTimezone(Tinebase_Core::get('userTimeZone'));
        return array(
            'existingnodesinfo' => $this->getExistingNodesInfo()->toArray()
        );
    }
}
