<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * @package     Calendar
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the calendar application
 *
 * @package     Calendar
 * @subpackage  Server
 */
class Calendar_Frontend_Http extends Tinebase_Application_Frontend_Http_Abstract
{
    protected $_appname = 'Calendar';
    
    public function getJsFilesToInclude()
    {
        return array(
            'Calendar/js/Calendar.js',
            //'Calendar/js/GridView_Days.js'
        );
    }
    
    public function getCssFilesToInclude()
    {
        return array(
            'Calendar/css/Calendar.css'
        );
    }
}