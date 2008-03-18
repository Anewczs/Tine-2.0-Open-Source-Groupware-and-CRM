<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id$
 */
/**
 * Test helper
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 All Tests');
        $suite->addTestSuite('Tinebase_AllTests');
        //  $suite->addTestSuite('Crm_ControllerTest');
        //  $suite->addTest(Asterisk_AllTests::suite());
        //  $suite->addTest(Admin_AllTests::suite());
        //  $suite->addTest(Addressbook_AllTests::suite());
        //  $suite->addTest(Calendar_AllTests::suite());
        //  $suite->addTestSuite('Tasks_ControllerTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}