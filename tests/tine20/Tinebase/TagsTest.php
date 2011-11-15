<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        implement testTagsAcl test
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Tags
 */
class Tinebase_TagsTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Tags
     */
    protected $_instance;
    
    /**
    * tags that should be deleted in tearDown
    *
    * @var array
    */
    protected $_tagIdsToDelete = array();
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_TagsTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        $this->_instance = Tinebase_Tags::getInstance();
    }
    
    /**
    * Tears down the fixture
    * This method is called after a test is executed.
    *
    * @access protected
    */
    protected function tearDown()
    {
        if (! empty($this->_tagIdsToDelete)) {
            $this->_instance->deleteTags($this->_tagIdsToDelete);
        }
    }

    /**
     * create tags
     */
    public function testCreateTags()
    {
        $sharedTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tag::shared',
            'description' => 'this is a shared tag',
            'color' => '#009B31',
        ));
        $savedSharedTag = $this->_instance->createTag($sharedTag);
        
        $right = new Tinebase_Model_TagRight(array(
            'tag_id'        => $savedSharedTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'    => Setup_Core::getUser()->getId(),
            'view_right'    => true,
            'use_right'     => true,
        ));
        $this->_instance->setRights($right);        
        $this->_tagIdsToDelete[] = $savedSharedTag->getId();
        $this->assertEquals($sharedTag->name, $savedSharedTag->name);
        
        $personalTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'  => 'tag::personal',
            'description' => 'this is a personal tag of account 1',
            'color' => '#FF0000',
        ));
        $savedPersonalTag = $this->_instance->createTag($personalTag);
        $this->_tagIdsToDelete[] = $savedPersonalTag->getId();
        $this->assertEquals($personalTag->description, $savedPersonalTag->description);
    }
    
    /**
     * test tags acl
     * 
     * @todo implement
     */
    public function testTagsAcl()
    {
        // create tags out of scope for the test user!
    }
    
    /**
     * test search tags
     */
    public function testSearchTags()
    {
//         $filter = new Tinebase_Model_TagFilter(array(
//             'name' => 'tag::%'
//         ));
//         $paging = new Tinebase_Model_Pagination();
//         $tags = $this->_instance->searchTags($filter, $paging);
//         $this->_instance->getSearchTagsCount($filter);
        
        //print_r($tags->toArray());
    }

    /**
    * test search tags with 'attached' filter
    * 
    * @todo implement
    */
    public function testSearchAttachedTags()
    {
    }
    
    /**
     * attach tags to records
     */
    public function testAttachTagToMultipleRecords()
    {
        $personas = Zend_Registry::get('personas');
        $personasContactIds = array();
        foreach ($personas as $persona) {
            $personasContactIds[] = $persona->contact_id;
        }
        
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        foreach ($contacts as $contact) {
            $contact->tags = array();
            $this->_instance->setTagsOfRecord($contact);
        }
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $personasContactIds)
        ));
        
        $tagData = array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tag::testAttachTagToMultipleRecords',
            'description' => 'testAttachTagToMultipleRecords',
            'color' => '#009B31',
        );
        
        $this->_instance->attachTagToMultipleRecords($filter, $tagData);
        
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        
        $this->_instance->getMultipleTagsOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertEquals(1, count($contact->tags), 'Tag not found in contact ' . $contact->n_fn);
        }
    }
}
