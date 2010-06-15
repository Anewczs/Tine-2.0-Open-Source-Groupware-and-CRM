<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        set timestamp field (add default to model?)
 */

/**
 * sql backend class for Felamimail folders
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Folder extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_folder';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Folder';

    /**
     * get saved folder record by backend and globalname
     *
     * @param string $_accountId
     * @param string $_globalName
     * @return Felamimail_Model_Folder
     */
    public function getByBackendAndGlobalName($_accountId, $_globalName)
    {
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $_accountId),
            array('field' => 'globalname', 'operator' => 'equals', 'value' => $_globalName),
        ));
        
        $folders = $this->search($filter);
        
        if (count($folders) > 0) {
            $result = $folders->getFirstRecord();
        } else {
            throw new Tinebase_Exception_NotFound("Folder $_globalName not found.");
        }
        
        return $result;
    }
    
    /**
     * get folder counter like total, unseen and recent count
     *  
     * @param  string  $_folderId  the folderid
     * @return array
     */
    public function getFolderCounter($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        // fetch total count
        $cols = array('cache_totalcount' => new Zend_Db_Expr('COUNT(id)'));
        $select = $this->_db->select()
            ->from(array('felamimail_cache_message' => $this->_tablePrefix . 'felamimail_cache_message'), $cols)
            ->where($this->_db->quoteIdentifier('felamimail_cache_message.folder_id') . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $totalCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        
        // get seen count
        $cols = array('cache_totalcount' => new Zend_Db_Expr('COUNT(id)'));
        $select = $this->_db->select()
            ->from(array('felamimail_cache_message' => $this->_tablePrefix . 'felamimail_cache_message'), $cols)
            ->where($this->_db->quoteIdentifier('felamimail_cache_message.folder_id') . ' = ?', $folderId);
        
        $stmt = $this->_db->query($select);
        $seenCount = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        
        
        return array(
            'cache_totalcount'  => $totalCount,
            'cache_unreadcount' => $totalCount - $seenCount
        );
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $result = parent::_recordToRawData($_record);

        // don't write this value as it requires a schema update
        // see: Felamimail_Controller_Cache_Folder::getIMAPFolderCounter
        unset($result['cache_uidvalidity']);
        
        return $result;
    }
}
