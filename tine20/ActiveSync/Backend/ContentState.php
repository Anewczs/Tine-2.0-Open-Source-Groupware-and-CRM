<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.html AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:ContentState.php 6978 2009-02-24 12:40:40Z p.schuele@metaways.de $
 * 
 */

/**
 * sql backend class for the content state
 *
 * @package     ActiveSync
 */
class ActiveSync_Backend_ContentState extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     *
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'acsync_content', 'ActiveSync_Model_ContentState');
    }    
    
    /**
     * delete all stored contentId's for given device and class
     *
     * @param ActiveSync_Model_Device $_deviceId
     * @param string $_class
     */
    public function resetState(ActiveSync_Model_Device $_deviceId, $_class)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?', $_deviceId->getId()),
            $this->_db->quoteInto($this->_db->quoteIdentifier('class') . ' = ?', $_class)
        );
        
        $this->_db->delete($this->_tableName, $where);
    }
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param ActiveSync_Model_Device $_deviceId
     * @param string $_class
     * @return array
     */
    public function getClientState(ActiveSync_Model_Device $_deviceId, $_class)
    {
        # contentid
        $select = $this->_getSelect('contentid');
        $select->where($this->_db->quoteIdentifier('device_id') . ' = ?', $_deviceId->getId())
            ->where($this->_db->quoteIdentifier('class') . ' = ?', $_class);
        
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);

        return $result;
    }
}
