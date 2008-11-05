<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        simplify relations tests: create related_records with relations class
 * @todo        create new ids for tasks/contacts each time
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Crm_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Crm_Frontend_Json
     */
    protected $_backend;
    
    /**
     * @var bool allow the use of GLOBALS to exchange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * container to use for the tests
     *
     * @var Tinebase_Model_Container
     */
    protected $container;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Json Tests');
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
        $this->_backend = new Crm_Frontend_Json();
        
        // initialise global for this test suite
        $GLOBALS['Crm_JsonTest'] = array_key_exists('Crm_JsonTest', $GLOBALS) ? $GLOBALS['Crm_JsonTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->objects['initialLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        )); 
        
        $this->objects['updatedLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description updated',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        ));

        $addressbookPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        $addressbookContainer = $addressbookPersonalContainer[0];
        
        $this->objects['contact'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 120,
            'note'                  => 'Bla Bla Bla',
            'container_id'                 => $addressbookContainer->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        )); 
        
        $tasksPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Tasks', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->objects['task'] = new Tasks_Model_Task(array(
            'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
        
        // define filter
        $this->objects['filter'] = array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'lead_name',
            'dir' => 'ASC',
            'containerType' => 'all',
            'query' => $this->objects['initialLead']->lead_name     
        );

        $this->objects['productLink'] = array(
            'product_id'        => 1001,
            'product_desc'      => 'test product',
            'product_price'     => 4000.44
        );
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
     * try to add a lead and link a contact
     *
     * @todo move creation of task & contact to relations class (via related_record)
     */
    public function testAddLead()
    {
        // create test contact
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->get($this->objects['contact']->getId());
        } catch ( Exception $e ) {
            $contact = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact']);
        }

        // create test task
        $task = Tasks_Controller_Task::getInstance()->createTask($this->objects['task']);
        $GLOBALS['Crm_JsonTest']['taskId'] = $task->getId();

        $leadData = $this->objects['initialLead']->toArray();
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',            
        );
        $leadData['notes'] = Zend_Json::encode(array($note));        
        
        $leadData['relations'] = array(
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => Crm_Backend_Factory::SQL,
                'own_id'                 => $this->objects['initialLead']->getId(),
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Tasks_Model_Task',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $GLOBALS['Crm_JsonTest']['taskId'],
                'type'                   => 'TASK',
                //'related_record'         => $this->objects['task']->toArray()
            ),
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => Crm_Backend_Factory::SQL,
                'own_id'                 => $this->objects['initialLead']->getId(),
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Addressbook_Model_Contact',
                'related_backend'        => Addressbook_Backend_Factory::SQL,
                'related_id'             => $this->objects['contact']->getId(),
                'type'                   => 'RESPONSIBLE',
                //'related_record'         => $this->objects['contact']->toArray()
            )        
        );
        $leadData['tags'] = Zend_Json::encode(array());
        $leadData['products'] = array($this->objects['productLink']);
        
        $encodedData = Zend_Json::encode($leadData);
        
        $result = $this->_backend->saveLead($encodedData);

        //print_r ( $result );
        
        $this->assertEquals($this->objects['initialLead']->description, $result['description']);

        // check linked contacts / tasks
        $this->assertGreaterThan(0, count($result['relations']));
        $this->assertEquals($this->objects['contact']->getId(), $result['relations'][0]['related_id']);
        $this->assertEquals($GLOBALS['Crm_JsonTest']['taskId'], $result['relations'][1]['related_id']);

        // check linked products
        $this->assertGreaterThan(0, count($result['products']));
        $this->assertEquals($this->objects['productLink']['product_desc'], $result['products'][0]['product_desc']);
        
        // check notes
        $createdNoteType = Tinebase_Notes::getInstance()->getNoteTypeByName('created');
        foreach ($result['notes'] as $leadNote) {
            if ($leadNote['note_type_id'] !== $createdNoteType->getId()) {
                $this->assertEquals($note['note'], $leadNote['note']);
            }
        }                       
    }

    /**
     * try to get an empty lead
     *
     */
    public function testGetEmptyLead()    
    {
        $emptyLead = $this->_backend->getLead(NULL);

        $this->assertEquals(0, $emptyLead['probability']);
        $this->assertEquals(Zend_Registry::get('currentAccount')->accountFullName, $emptyLead['relations'][0]['related_record']['n_fn']);
    }
        
    /**
     * try to get a lead (test searchLeads as well)
     *
     */
    public function testGetLead()    
    {
        $result = $this->_backend->searchLeads(Zend_Json::encode($this->objects['filter']));
        $leads = $result['results'];
        $initialLead = $leads[0];
        
        $lead = $this->_backend->getLead($initialLead['id']);
        
        //print_r($lead);
        
        $this->assertEquals($lead['description'], $this->objects['initialLead']->description);        
        $this->assertEquals($lead['relations'][0]['related_record']['assistent'], $this->objects['contact']->assistent);                
        $this->assertEquals($lead['products'][0]['product_desc'], $this->objects['productLink']['product_desc']);
    }

    /**
     * try to get all leads
     *
     */
    public function testGetLeads()    
    {
        $result = $this->_backend->searchLeads(Zend_Json::encode($this->objects['filter']));
        $leads = $result['results'];
        $initialLead = $leads[0];

        $this->assertEquals($this->objects['initialLead']->description, $initialLead['description']);        
        $this->assertEquals($this->objects['contact']->assistent, $initialLead['relations'][0]['related_record']['assistent']);
    }
    
    /**
     * try to update a lead and remove linked contact 
     *
     */
    public function testUpdateLead()
    {   
        $result = $this->_backend->searchLeads(Zend_Json::encode($this->objects['filter']));        
        $initialLead = $result['results'][0];
        
        $updatedLead = $this->objects['updatedLead'];
        $updatedLead->id = $initialLead['id'];
        // unset contact
        unset($initialLead['relations'][0]);
        $updatedLead->relations = new Tinebase_Record_Recordset('Tinebase_Model_Relation', $initialLead['relations']);
        
        $encodedData = Zend_Json::encode($updatedLead->toArray());
        
        $result = $this->_backend->saveLead($encodedData);
        
        $this->assertEquals($this->objects['updatedLead']->description, $result['description']);

        // check if contact is no longer linked
        $lead = Crm_Controller_Lead::getInstance()->getLead($initialLead['id']);
        $this->assertEquals(1, count($lead->relations));
        
        // delete contact
        Addressbook_Controller_Contact::getInstance()->deleteContact($this->objects['contact']->getId());

    }

    /**
     * try to delete a lead (and if task is deleted as well)
     *
     */
    public function testDeleteLead()
    {        
        $result = $this->_backend->searchLeads(Zend_Json::encode($this->objects['filter']));        

        $deleteIds = array();
        
        $backend = new Tinebase_Relation_Backend_Sql();        
        foreach ($result['results'] as $lead) {
            $deleteIds[] = $lead['id'];
        }
        
        //print_r($deleteIds);
        
        $encodedLeadIds = Zend_Json::encode($deleteIds);
        
        $this->_backend->deleteLeads($encodedLeadIds);        
                
        $result = $this->_backend->searchLeads(Zend_Json::encode($this->objects['filter']));
        $this->assertEquals(0, $result['totalcount']);   

        // check if linked task got removed as well
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $task = Tasks_Controller_Task::getInstance()->getTask($GLOBALS['Crm_JsonTest']['taskId']);
        
        // purge relations
        foreach ($deleteIds as $id) {
            $backend->purgeAllRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $id);            
        }
    }    
    
    /**
     * test leadsources
     */
    public function testLeadSources()
    {
        // test getLeadsources
        $leadsources = $this->_backend->getLeadsources('id', 'ASC');
        $this->assertEquals(4, $leadsources['totalcount']);

        // test saveLeadsources
        $this->_backend->saveLeadsources(Zend_Json::encode($leadsources['results']));

        $leadsourcesUpdated = $this->_backend->getLeadsources('id', 'ASC');
        $this->assertEquals(4, $leadsourcesUpdated['totalcount']);
    }

    /**
     * test leadstates
     */
    public function testLeadStates()
    {
        // test getLeadstates
        $leadstates = $this->_backend->getLeadstates('id', 'ASC');
        $this->assertEquals(6, $leadstates['totalcount']);

        // test saveLeadstates
        $this->_backend->saveLeadstates(Zend_Json::encode($leadstates['results']));

        $leadstatesUpdated = $this->_backend->getLeadstates('id', 'ASC');
        $this->assertEquals(6, $leadstatesUpdated['totalcount']);
    }

    /**
     * test leadtypes
     */
    public function testLeadTypes()
    {
        // test getLeadtypes
        $leadtypes = $this->_backend->getLeadtypes('id', 'ASC');
        $this->assertEquals(3, $leadtypes['totalcount']);

        // test saveLeadtypes
        $this->_backend->saveLeadtypes(Zend_Json::encode($leadtypes['results']));

        $leadtypesUpdated = $this->_backend->getLeadtypes('id', 'ASC');
        $this->assertEquals(3, $leadtypesUpdated['totalcount']);
    }

    /**
     * test products
     */
    public function testProducts()
    {
        // test getProducts
        $products = $this->_backend->getProducts('id', 'ASC');

        // test saveProducts
        $this->_backend->saveProducts(Zend_Json::encode($products['results']));

        $productsUpdated = $this->_backend->getProducts('id', 'ASC');
        $this->assertEquals($products['totalcount'], $productsUpdated['totalcount']);
    }
    
}		
	