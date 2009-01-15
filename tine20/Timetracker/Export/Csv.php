<?php
/**
 * Timetracker csv generation class
 *
 * @package     Timetracker
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Csv.php 6182 2009-01-09 11:51:41Z p.schuele@metaways.de $
 * 
 */

/**
 * Timetracker csv generation class
 * 
 * @package     Timetracker
 * @subpackage	Export
 * 
 */
class Timetracker_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * export timesheets to csv file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     * 
     * @todo add specific export values
     */
    public function exportTimesheets($_filter) {
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter);
        if (count($timesheets) < 1) {
            throw new Timetracker_Exception_NotFound('No Timesheets found.');
        }

        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        // resolve accounts
        $accountIds = $timesheets->account_id;
        $accounts = Tinebase_User::getInstance()->getMultiple(array_unique(array_values($accountIds)));
        
        foreach ($timesheets as $timesheet) {
            $timesheet->timeaccount_id = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->title;
            $timesheet->account_id = $accounts[$accounts->getIndexById($timesheet->account_id)]->accountDisplayName;
        }
                
        $filename = parent::exportRecords($timesheets);
        
        return $filename;
    }
}
