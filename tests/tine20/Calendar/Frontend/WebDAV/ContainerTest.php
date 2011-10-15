<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Frontend_WebDAV_ContainerTest::main');
}

/**
 * Test class for Calendar_Frontend_WebDAV_Container
 */
class Calendar_Frontend_WebDAV_ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Container Tests');
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
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $this->objects['containerToDelete'][] = $this->objects['initialContainer'];
        
        $this->objects['eventsToDelete'] = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['eventsToDelete'] as $contact) {
            $contact->delete();
        }
        
        foreach ($this->objects['containerToDelete'] as $containerId) {
            $containerId = $containerId instanceof Tinebase_Model_Container ? $containerId->getId() : $containerId;
            
            try {
                Tinebase_Container::getInstance()->deleteContainer($containerId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->name, $result);
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetIdAsName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer'], true);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $result);
    }
    
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $GLOBALS['_SERVER']['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], $vcalendarStream);
        
        $this->objects['eventsToDelete'][] = $event;
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $children = $container->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertInstanceOf('Calendar_Frontend_WebDAV_Event', $children[0]);
    }
    
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $requestedProperties = array(
        	'{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
       
        $this->assertNotEmpty($result['{http://calendarserver.org/ns/}getctag']);
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
}
