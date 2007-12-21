<?php

/**
 * this classes provides access to the sql table egw_metacrm_project
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 221 2007-11-12 12:35:08Z twadewitz  $
 *
 */

class Crm_Backend_Sql_Projects extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_metacrm_project';
    protected $_owner = 'pj_owner';
    
    /**
     * fetch all entries matching where parameter.
     *
     * @param string/array $_where where filter
     * @param string $_order order by 
     * @param int $_count maximum rows to return
     * @param int $_offset how many rows to skio
     * @param array $_readACL read ACL; return row only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = NULL, $_count = NULL, $_offset = NULL, $_readACL = NULL)
    {
        if($_dir !== NULL && ($_dir != 'ASC' && $_dir != 'DESC')) {
            throw new InvalidArgumentException('$_dir can only be DESC or ASC or NULL');
        }

        if($_limit !== NULL && !is_int($_limit)) {
            throw new InvalidArgumentException('$_limit must be integer or NULL');
        }

        if($_start !== NULL && !is_int($_start)) {
            throw new InvalidArgumentException('$_start must be integer or NULL');
        }
        
        $result = parent::fetchAll($_where, "$_order $_dir", $_count, $_offset);
        
        return $result;
    }
    
    /**
     * get total count of rows matching acl
     *
     * @param array $_readACL read ACL; count only rows machting owner in array
     * @return int number of rows matching acl
     */
    public function getCountByAcl($_readACL)
    {
        $where = $this->getAdapter()->quoteInto($this->_owner . ' IN (?)', $_readACL);
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE ' . $where);
    }
}
       