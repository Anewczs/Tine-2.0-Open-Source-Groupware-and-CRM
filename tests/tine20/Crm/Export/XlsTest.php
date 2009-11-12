<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: XlsTest.php 10905 2009-10-12 13:39:57Z p.schuele@metaways.de $
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Export_XlsTest::main');
}

/**
 * Test class for Crm_Export_Xls
 */
class Crm_Export_XlsTest extends Crm_Export_AbstractTest
{
    /**
     * csv export class
     *
     * @var Crm_Export_Xls
     */
    protected $_instance;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm_Export_XlsTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = new Crm_Export_Xls();
        parent::setUp();
    }

    /**
     * test ods export
     * 
     * @return void
     * 
     * @todo add assertions
     */
    public function testExportXls()
    {
        $this->_instance->generate(new Crm_Model_LeadFilter($this->_getLeadFilter()));
        
        // output as csv
        $xlswriter = new PHPExcel_Writer_CSV($this->_instance);
        $xlswriter->save('php://output');
        //$xlswriter->save('test.csv');
        
        /*
        $odsFilename = $this->_instance->generate(new Crm_Model_LeadFilter($this->_getLeadFilter()));
        
        $this->assertTrue(file_exists($odsFilename));
        
        $xmlBody = $this->_instance->getBody()->generateXML();    
        //echo  $xmlBody;
        $this->assertEquals(1, preg_match("/PHPUnit/", $xmlBody), 'no name'); 
        $this->assertEquals(1, preg_match("/Description/", $xmlBody), 'no description');
        
        unlink($odsFilename);
        */
    }
}       

if (PHPUnit_MAIN_METHOD == 'Crm_Export_XlsTest::main') {
    Addressbook_ControllerTest::main();
}
