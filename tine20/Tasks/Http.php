<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @deprecated
 */

/**
 * backend class for Tinebase_Http_Server
 * This class handles all Http requests for the calendar application
 * 
 * @package Tasks
 */
class Tasks_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Tasks';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Tasks/js/Status.js',
            'Tasks/js/Tasks.js',
        );
    }
}
