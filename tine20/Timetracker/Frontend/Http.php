<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Http.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */

/**
 * This class handles all Http requests for the Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Http extends Tinebase_Application_Frontend_Http_Abstract
{
    protected $_applicationName = 'Timetracker';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Timetracker/js/DurationSpinner.js',
            'Timetracker/js/Models.js',
            'Timetracker/js/Timetracker.js',
            'Timetracker/js/MainScreen.js',
            'Timetracker/js/TimeaccountSelect.js',
            'Timetracker/js/TimeaccountGridPanel.js',
            'Timetracker/js/TimeaccountEditDialog.js',
            'Timetracker/js/TimesheetGridPanel.js',
            'Timetracker/js/TimesheetEditDialog.js',
        );
    }
}
