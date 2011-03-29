<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add test for contract <-> timeaccount relations
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_JsonTest::main');
}

/**
 * Test class for Timetracker_Frontent_Json
 */
class Timetracker_JsonTest extends Timetracker_AbstractTest
{
    /**
     * try to add a Timeaccount
     *
     */
    public function testAddTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
        $this->assertGreaterThan(0, count($timeaccountData['grants']));
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testGetTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        $timeaccountData = $this->_json->getTimeaccount($timeaccountData['id']);
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
                        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }

    /**
     * try to update a Timeaccount
     *
     */
    public function testUpdateTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // update Timeaccount
        $timeaccountData['description'] = "blubbblubb";
        $timeaccountUpdated = $this->_json->saveTimeaccount($timeaccountData);
        
        // check
        $this->assertEquals($timeaccountData['id'], $timeaccountUpdated['id']);
        $this->assertEquals($timeaccountData['description'], $timeaccountUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountUpdated['last_modified_by']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testSearchTimeaccounts()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // search & check
        $search = $this->_json->searchTimeaccounts($this->_getTimeaccountFilter(), $this->_getPaging());
        $this->assertEquals($timeaccount->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to add a Timeaccount with grants
     *
     */
    public function testAddTimeaccountWithGrants()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $timeaccount->toArray();
        $grants = $this->_getGrants();
        $timeaccountData['grants'] = $this->_getGrants();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccountData);
        
        // checks
        $this->assertGreaterThan(0, count($timeaccountData['grants']));
        $this->assertEquals($grants[0]['account_type'], $timeaccountData['grants'][0]['account_type']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals(Tinebase_DateTime::now()->toString('Y-m-d'),  $timesheetData['start_date']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
        
        // check if everything got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timesheet::getInstance()->get($timesheetData['id']);
    }

    /**
     * try to add a Timesheet with custom fields
     *
     */
    public function testAddTimesheetWithCustomFields()
    {
        $value = 'abcd';
        $cf = $this->_getCustomField();
                
        // create two timesheets with customfields
        $this->_addTsWithCf($cf, $value);
        $this->_addTsWithCf($cf, 'efgh');
        
        // search custom field values and check totalcount
        $tinebaseJson = new Tinebase_Frontend_Json();
        $cfValues = $tinebaseJson->searchCustomFieldValues(Zend_Json::encode($this->_getCfValueFilter($cf->getId())), '');
        $this->assertEquals($value, $cfValues['results'][0]['value'], 'value mismatch');
        $this->assertEquals(2, $cfValues['totalcount'], 'wrong totalcount');
    }

    /**
     * search Timesheet with empty custom fields
     */
    public function testSearchTimesheetWithEmptyCustomField()
    {
        $cf = $this->_getCustomField();
                
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];

        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(array(
            'field'     => 'customfield', 
            'operator'  => 'equals', 
            'value'     => array(
                'cfId'  => $cf->getId(),
                'value' => '',
            )
        )), $this->_getPaging());
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * try to add a Timesheet with custom fields (check grants)
     */
    public function testAddTimesheetWithCustomFieldGrants()
    {
        $value = 'test';
        $cf = $this->_getCustomField();
        
        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$cf->name] = $value;
        $ts = $this->_json->saveTimesheet($timesheetArray);
        
        // tearDown settings
        $this->_toDeleteIds['ta'][] = $ts['timeaccount_id']['id'];
        
        // test with default grants
        $this->assertTrue(array_key_exists($cf->name, $ts['customfields']), 'customfield should be readable');
        $this->assertEquals($value, $ts['customfields'][$cf->name]);
        
        // remove all grants
        Tinebase_CustomField::getInstance()->setGrants($cf, array());
        $ts = $this->_json->getTimesheet($ts['id']);
        
        $this->assertTrue(! array_key_exists('customfields', $ts), 'customfields should not be readable');
        $ts = $this->_updateCfOfTs($ts, $cf->name, 'try to update');
        
        // only read allowed
        Tinebase_CustomField::getInstance()->setGrants($cf, array(Tinebase_Model_CustomField_Grant::GRANT_READ));
        $ts = $this->_json->getTimesheet($ts['id']);
        $this->assertTrue(array_key_exists($cf->name, $ts['customfields']), 'customfield should be readable again');
        $this->assertEquals($value, $ts['customfields'][$cf->name], 'value should not have changed'); 
        $ts = $this->_updateCfOfTs($ts, $cf->name, 'try to update');
        $this->assertEquals($value, $ts['customfields'][$cf->name], 'value should still not have changed');
    }
    
    /**
     * update timesheet customfields and return saved ts
     * 
     * @param array $_ts
     * @param string $_cfName
     * @param string $_cfValue
     * @return array
     */
    protected function _updateCfOfTs($_ts, $_cfName, $_cfValue)
    {
        $_ts[$_cfName] = $_cfValue;
        $_ts['timeaccount_id'] = $_ts['timeaccount_id']['id'];
        $_ts['account_id'] = $_ts['account_id']['accountId'];
        unset($_ts['customfields']);
        $ts = $this->_json->saveTimesheet($_ts);
        
        return $ts;
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $timesheetData = $this->_json->getTimesheet($timesheetData['id']);
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals($timesheet['timeaccount_id'], $timesheetData['timeaccount_id']['id'], 'timeaccount is not resolved');
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /**
     * try to update a Timesheet (with relations)
     *
     */
    public function testUpdateTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // update Timesheet
        $timesheetData['description'] = "blubbblubb";
        //$timesheetData['container_id'] = $timesheetData['container_id']['id'];
        $timesheetData['account_id'] = $timesheetData['account_id']['accountId'];
        $timesheetData['timeaccount_id'] = $timesheetData['timeaccount_id']['id'];
        
        $timesheetUpdated = $this->_json->saveTimesheet($timesheetData);
        
        // check
        $this->assertEquals($timesheetData['id'], $timesheetUpdated['id']);
        $this->assertEquals($timesheetData['description'], $timesheetUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['last_modified_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals($timesheetData['timeaccount_id'], $timesheetUpdated['timeaccount_id']['id'], 'timeaccount is not resolved');
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']);
    }

    /**
     * try to update multiple Timesheets
     */
    public function testUpdateMultipleTimesheetsWithIds()
    {
        // create 2 timesheets
        $timesheet1 = $this->_getTimesheet();
        $timesheetData1 = $this->_json->saveTimesheet($timesheet1->toArray());
        $timesheet2 = $this->_getTimesheet($timesheetData1['timeaccount_id']['id']);
        $timesheetData2 = $this->_json->saveTimesheet($timesheet2->toArray());
        
        $this->assertEquals($timesheetData1['is_cleared'], 0);
        
        // update Timesheets
        $newValues = array('description' => 'argl', 'is_cleared' => 1);
        $filterData = array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($timesheetData1['id'], $timesheetData2['id']))
        );
        $result = $this->_json->updateMultipleTimesheets($filterData, $newValues);
        
        $changed1 = $this->_json->getTimesheet($timesheetData1['id']);
        $changed2 = $this->_json->getTimesheet($timesheetData2['id']);
                
        // check
        $this->assertEquals(2, $result['count']);
        $this->assertEquals($timesheetData1['id'], $changed1['id']);
        $this->assertEquals($changed1['description'], $newValues['description']);
        $this->assertEquals($changed2['description'], $newValues['description']);
        $this->assertEquals($changed1['is_cleared'], 1);
        $this->assertEquals($changed2['is_cleared'], 1);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData1['timeaccount_id']['id']);
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testDeleteTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // delete
        $this->_json->deleteTimesheets($timesheetData['id']);
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($timesheetData['timeaccount_id']['id']);
        
        // checks
        $this->assertEquals(0, count($timesheets));
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }
    
    
    /**
     * try to search for Timesheets
     *
     */
    public function testSearchTimesheets()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // search & check
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /**
     * try to search for Timesheets with date filtering (using 'weekThis' filter)
     *
     */
    public function testSearchTimesheetsWithDateFilterWeekThis()
    {
        $this->_dateFilterTest();
    }

    /**
     * try to search for Timesheets with date filtering (using inweek operator)
     *
     */
    public function testSearchTimesheetsWithDateFilterInWeek()
    {
        $this->_dateFilterTest('inweek');
    }
    
    /**
     * try to search for Timesheets with date filtering (using monthLast operator)
     */
    public function testSearchTimesheetsWithDateMonthLast()
    {
        $today = Tinebase_DateTime::now();
        $lastMonth = $today->setDate($today->get('Y'), $today->get('m') - 1, 1);
        $search = $this->_createTsAndSearch($lastMonth, 'monthLast');
        
        $this->assertEquals(1, $search['totalcount'], 'timesheet not found with last month filter');
    }
    
    /**
     * date filter test helper
     * 
     * @param string $_type weekThis|inweek|monthLast
     */
    protected function _dateFilterTest($_type = 'weekThis')
    {
        $oldLocale = Tinebase_Core::getLocale();
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('en_US'));
        
        // date is last/this sunday (1. day of week in the US)
        $today = Tinebase_DateTime::now();
        $dayOfWeek = $today->get('w');
        $lastSunday = $today->subDay($dayOfWeek);
        
        $search = $this->_createTsAndSearch($lastSunday, $_type);
        
        $this->assertEquals(1, $search['totalcount'], 'timesheet not found in english locale');
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        
        // change locale to de_DE -> timesheet should no longer be found because monday is the first day of the week
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('de_DE'));
        $search = $this->_json->searchTimesheets($this->_getTimesheetDateFilter($_type), $this->_getPaging());
        // if today is sunday -> ts should be found in german locale!
        $this->assertEquals(($dayOfWeek == 0) ? 1 : 0, $search['totalcount'], 'filter not working in german locale');
        
        Tinebase_Core::set(Tinebase_Core::LOCALE, $oldLocale);
    }
    
    /**
     * create timesheet and search with filter
     * 
     * @param Tinebase_DateTime $_startDate
     * @param string $_filterType
     * @return array
     */
    protected function _createTsAndSearch($_startDate, $_filterType)
    {
        $timesheet = $this->_getTimesheet(NULL, $_startDate);
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        $result = $this->_json->searchTimesheets($this->_getTimesheetDateFilter($_filterType), $this->_getPaging());
        
        return $result;
    }
    
    /**
     * try to search for Timesheets (with combined is_billable + cleared)
     *
     */
    public function testSearchTimesheetsWithCombinedIsBillableAndCleared()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // update timeaccount -> is_billable = false
        $ta = Timetracker_Controller_Timeaccount::getInstance()->get($timesheetData['timeaccount_id']['id']);
        $ta->is_billable = 0;
        Timetracker_Controller_Timeaccount::getInstance()->update($ta);
        
        // search & check
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertEquals(0, $search['results'][0]['is_billable_combined']);
        $this->assertEquals(0, $search['results'][0]['is_cleared_combined']);
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals(0, $search['totalsumbillable']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /******* persistent filter tests *****************/
    
    /**
     * try to save and search persistent filter
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testSavePersistentTimesheetFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $this->_getTimesheetFilter(), 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));
        
        // get
        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //print_r($persistentFilters);
        
        //check
        $this->assertEquals(1, $persistentFilters['totalcount']); 
        $this->assertEquals($filterName, $persistentFilters['results'][0]['name']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $persistentFilters['results'][0]['created_by']);
        $this->assertEquals($persistentFilters['results'][0]['filters'], $this->_getTimesheetFilter());

        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFilters['results'][0]['id']);
    }

    /**
     * try to save/update and search persistent filter
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testUpdatePersistentTimesheetFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        $tsFilter = $this->_getTimesheetFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $tsFilter, 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));

        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        
        // update
        $updatedFilter = $persistentFilters['results'][0];
        $updatedFilter[0]['value'] = 'blubb';
        $persistentFiltersJson->savePersistentFilter($updatedFilter);
        
        // get
        $persistentFiltersUpdated = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //print_r($persistentFiltersUpdated);
        
        //check
        $this->assertEquals(1, $persistentFiltersUpdated['totalcount']); 
        $this->assertEquals($filterName, $persistentFiltersUpdated['results'][0]['name']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $persistentFiltersUpdated['results'][0]['last_modified_by']);
        //$this->assertEquals($persistentFiltersUpdated['results'][0]['filters'], $updatedFilter);
        $this->assertEquals($persistentFilters['results'][0]['id'], $persistentFiltersUpdated['results'][0]['id']);

        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFiltersUpdated['results'][0]['id']);
    }

    /**
     * try to search timesheets with saved persistent filter id
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testSearchTimesheetsWithPersistentFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        $tsFilter = $this->_getTimesheetFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $tsFilter, 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        // search persistent filter
        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //check
        $search = $this->_json->searchTimesheets($persistentFilters['results'][0]['id'], $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals($tsFilter, $search['filter'], 'filters do not match');
        
        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFilters['results'][0]['id']);
    }
}
