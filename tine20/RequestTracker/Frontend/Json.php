<?php
/**
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Phone.css 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 */

/**
 * RequestTracker HTTP Frontend
 *
 * @package RequestTracker
 */
class RequestTracker_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'RequestTracker';
    
    /**
     * RT REST Backend
     *
     * @var RequestTracker_Backend_Rest
     */
    protected $_backend = NULL;
    
    /**
     * returns all queus current user has access to
     *
     * @return string JSON
     */
    public function searchQueues()
    {
        $response = array();
        $queues = $this->_getBackend()->getQueues();
        foreach ($queues as $queue) {
            $response[] = array(
                'id'    => $queue,
                'text'  => $queue,
            );
        }
        
        echo Zend_Json::encode($response);
        die();
    }
    
    /**
     * search tickets
     *
     * @param string $filter
     * @param string $paging
     * @return array
     */
    public function searchTickets($filter, $paging)
    {
        $filter = new RequestTracker_Model_TicketFilter(Zend_Json::decode($filter));
        $paging = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        return array(
            'results'    => $this->_getBackend()->search($filter, $paging)->toArray(),
            'totalCount' => $this->_getBackend()->searchCount($filter)
        );
    }
    
    public function getTicket($id)
    {
        return $this->_getBackend()->get($id)->toArray();
    }
    
    protected function _getBackend()
    {
        if (! $this->_backend) {
            $this->_backend = new RequestTracker_Backend_Rest();
        }
        
        return $this->_backend;
    }
}
