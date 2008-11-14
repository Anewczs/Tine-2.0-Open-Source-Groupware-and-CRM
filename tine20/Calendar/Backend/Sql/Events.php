<?php

/**
 * this classes provides access to the sql table <prefix>_cal
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * this classes provides access to the sql table <prefix>_cal
 * 
 * @package     Calendar
 */
class Calendar_Backend_Sql_Events extends Zend_Db_Table_Abstract
{
    protected $_primary = 'cal_id';
    
    protected $_dependentTables = array(
        'Calendar_Backend_Sql_Dates',
        'Calendar_Backend_Sql_Repeats',
        'Calendar_Backend_Sql_User',
        'Calendar_Backend_Sql_Extra'
    );    
}
