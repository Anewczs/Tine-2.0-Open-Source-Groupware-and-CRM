<?php
/**
 * Tinebase xls generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Ods.php 10912 2009-10-12 14:40:25Z p.schuele@metaways.de $
 * 
 * @todo        make it work
 * @todo        allow templates
 */

// set include path for phpexcel
set_include_path(dirname(dirname(dirname(__FILE__))) . '/library/PHPExcel' . PATH_SEPARATOR . get_include_path() );

/**
 * Tinebase xls generation class
 * 
 * @package     Tinebase
 * @subpackage  Export
 * 
 */
class Tinebase_Export_Xls extends PHPExcel
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * export records to Xls file
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return void
     */
    public function generate(Tinebase_Model_Filter_FilterGroup $_filter)
    {
    }
}
