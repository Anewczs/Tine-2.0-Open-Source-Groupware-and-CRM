<?php
/**
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * RequestTracker HTTP Frontend
 *
 * @package RequestTracker
 */
class RequestTracker_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'RequestTracker';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'RequestTracker/js/RequestTracker.js',
            'RequestTracker/js/Model.js',
            'RequestTracker/js/TreePanel.js',
            'RequestTracker/js/StatusCombo.js',
            'RequestTracker/js/TicketGridPanel.js',
        );
    }
}
