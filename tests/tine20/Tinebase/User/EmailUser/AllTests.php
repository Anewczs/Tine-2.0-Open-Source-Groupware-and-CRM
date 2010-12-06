<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: AllTests.php 17625 2010-12-04 18:26:39Z l.kneschke@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_AllTests::main');
}

class Tinebase_User_EmailUser_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All EmailUser Tests');
#        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
#        if (isset($imapConfig['backend'])) {
#            switch (ucfirst($imapConfig['backend'])) {
#                case Tinebase_EmailUser::DBMAIL:
#                   $suite->addTestSuite('Tinebase_User_EmailUser_DbmailTest');
#                    break;
#           }
#       }

        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_DovecotTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_LdapDbmailSchemaTest');
        
        $suite->addTestSuite('Tinebase_User_EmailUser_Smtp_PostfixTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Smtp_LdapDbmailSchemaTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_User_EmailUser_AllTests::main') {
    Tinebase_User_AllTests::main();
}