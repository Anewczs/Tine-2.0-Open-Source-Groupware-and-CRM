<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timesheet
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timesheet_AllTests::main');
}

class Timesheet_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Timesheet All Tests');
        $suite->addTestSuite('Timesheet_JsonTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Timesheet_AllTests::main') {
    Timesheet_AllTests::main();
}
