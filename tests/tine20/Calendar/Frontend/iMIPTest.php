<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo		move files/*.eml to Felamimail tests or remove them?
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Frontend_iMIP
 */
class Calendar_Frontend_iMIPTest extends PHPUnit_Framework_TestCase
{
    /**
     * iMIP frontent to be tested
     * 
     * @var Calendar_Frontend_iMIP
     */
    protected $_iMIPFrontend = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar iMIP Tests');
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
        $this->_iMIPFrontend = new Calendar_Frontend_iMIP();
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
     * testExternalInvitationRequest
     */
    public function testExternalInvitationRequestAutoProcess()
    {
        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
        	'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ));
        
        $event = $iMIP->getEvent();
        $this->assertEquals(3, count($event->attendee));
        $this->assertEquals('test mit extern', $event->summary);
        
        $this->_iMIPFrontend->autoProcess($iMIP);
        return $iMIP;
    }
    
    /**
     * testExternalInvitationRequestProcess
     * 
     * @todo implement
     */
    public function testExternalInvitationRequestProcess()
    {
        // -- handle message with fmail (add to cache)
        // -- get $iMIP from message
        // -- test this->_iMIPFrontend->process($iMIP, $status);
    }

    /**
     * testInternalInvitationRequest
     * 
     * @todo implement
     */
    public function testInternalInvitationRequest()
    {
        // -- create event
        // -- get iMIP invitation for event
        // -- autoProcess
    }

    /**
     * testInvitationReplyAccepted
     * 
     * @todo implement
     */
    public function testInvitationReplyAccepted()
    {
        
    }

    /**
     * testInvitationReplyDeclined
     * 
     * @todo implement
     */
    public function testInvitationReplyDeclined()
    {
        
    }

    /**
     * testInvitationCancel
     * 
     * @todo implement
     */
    public function testInvitationCancel()
    {
        
    }
}
