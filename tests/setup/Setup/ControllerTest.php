<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        make this work again (when setup tests have been moved)
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Setup_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Setup_Frontend_Json
     */
    protected $_uit = null;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup Controller Tests');
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
        $this->_uit = Setup_Controller::getInstance();       
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {       
    }
       
    /**
     * test uninstall application
     *
     */
    public function testUninstallApplications()
    {
    	try {
    		$result = $this->_uit->uninstallApplications(array('ActiveSync'));
    	} catch (Tinebase_Exception_NotFound $e) {
    		$this->_uit->installApplications(array('ActiveSync'));
    		$result = $this->_uit->uninstallApplications(array('ActiveSync'));
    	}
    	        
        $apps = $this->_uit->searchApplications();
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertEquals('uninstalled', $activeSyncApp['install_status']);

	    $this->_uit->installApplications(array('ActiveSync')); //cleanup
    }
    
    /**
     * test uninstall application
     *
     */
    public function testUninstallTinebaseShouldThrowDependencyException()
    {
        $this->setExpectedException('Setup_Exception_Dependency');
    	$result = $this->_uit->uninstallApplications(array('Tinebase'));
    }
    
    /**
     * test search applications
     *
     */
    public function testSearchApplications()
    {
        $apps = $this->_uit->searchApplications();
        
        $this->assertGreaterThan(0, $apps['totalcount']);
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertTrue(isset($activeSyncApp['id']));
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
    }
    
    /**
     * test install application
     *
     */
    public function testInstallApplications()
    {
        try {
            $result = $this->_uit->installApplications(array('ActiveSync'));
        } catch (Exception $e) {
            $this->_uit->uninstallApplications(array('ActiveSync'));
            $result = $this->_uit->installApplications(array('ActiveSync'));
        }
                
        $apps = $this->_uit->searchApplications();
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        
        $applicationId = $activeSyncApp['id'];
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertTrue(isset($applicationId));
        $this->assertEquals('enabled', $activeSyncApp['status']);
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
        
        //check if user role has the right to run the recently installed app
        $roles = Tinebase_Acl_Roles::getInstance();
        $userRole = $roles->getRoleByName('user role');
        $rights = $roles->getRoleRights($userRole->getId());
        $hasRight = false;
        foreach ($rights as $right) {
            if ($right['application_id'] === $applicationId &&
                $right['right'] === 'run') {
            	$hasRight = true;
            }
        } 
        $this->assertTrue($hasRight, 'User role has run right for recently installed app?');
    }

    /**
     * test update application
     *
     * @todo test real update process; currently this test case only tests updating an already uptodate application 
     */
    public function testUpdateApplications()
    {
        $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        $applications->addRecord(Tinebase_Application::getInstance()->getApplicationByName('ActiveSync'));
        $result = $this->_uit->updateApplications($applications);
        $this->assertTrue(is_array($result)); //Setup_Controller::updateApplications just returns an array of messages and throws exceptions on failure
    }

    /**
     * test env check
     *
     */
    public function testEnvCheck()
    {
        $result = $this->_uit->checkRequirements();
        
        $this->assertTrue(isset($result['success']));
        $this->assertGreaterThan(16, count($result['results']));
    }
    
    public function testLoginWithWrongUsernameAndPassword()
    {
    	$result = $this->_uit->login('unknown_user_xxyz', 'wrong_password');
        $this->assertFalse($result);
    }

}
