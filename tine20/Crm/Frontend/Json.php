<?php
/**
 * Tine 2.0
 * @package     Crm
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        remove/replace @deprecated functions
 */

/**
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 * @subpackage  Frontend
 */
class Crm_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Crm_Controller_Lead
     */
    protected $_controller = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Crm';
        $this->_controller = Crm_Controller_Lead::getInstance();
    }
    
    /************************************** protected helper functions **************************************/
    
    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchLeads($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Crm_Model_LeadFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getLead($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveLead($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'Lead');        
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteLeads($ids)
    {
        $this->_delete($ids, $this->_controller);
    }    

    /**
     * Returns registry data of crm.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     * 
     * @todo get defaults from config
     * @todo add update script to move default config values to db
     */
    public function getRegistryData()
    {   
        $defaults = array(
            'leadstate_id'  => (isset(Tinebase_Core::getConfig()->crm->defaultstate)) ? Tinebase_Core::getConfig()->crm->defaultstate : 1,
            'leadtype_id'   => (isset(Tinebase_Core::getConfig()->crm->defaulttype)) ? Tinebase_Core::getConfig()->crm->defaulttype : 1,
            'leadsource_id' => (isset(Tinebase_Core::getConfig()->crm->defaultsource)) ? Tinebase_Core::getConfig()->crm->defaultsource : 1
        );
        
        $registryData = array(
            'leadtypes'     => $this->getLeadtypes('leadtype','ASC'),
            'leadstates'    => $this->getLeadStates('leadstate','ASC'),
            'leadsources'   => $this->getLeadSources('leadsource','ASC'),
            'products'      => $this->getProducts('productsource','ASC'),
            'defaults'      => $defaults,
        );
        
        return $registryData;
    }
    
    /**
     * get lead sources
     *
     * @param string $sort
     * @param string $dir
     * @return array
     * 
     * @deprecated -> move leadsources to config?
     */
    public function getLeadsources($sort, $dir)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadSources::getInstance()->getLeadSources($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    } 

    /**
     * get lead types
     *
     * @param string $sort
     * @param string $dir
     * @return array
     * 
     * @deprecated -> move lead types to config?
     */
   public function getLeadtypes($sort, $dir)
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadTypes::getInstance()->getLeadTypes($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }  
    
    /**
     * get lead states
     *
     * @param string $sort
     * @param string $dir
     * @return array
     * 
     * @deprecated -> move lead states to config?
     */   
    public function getLeadstates($sort, $dir)
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadStates::getInstance()->getLeadStates($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;   
    }  
    
    /**
     * get available products
     *
     * @param string $sort
     * @param string $dir
     * @return array
     * 
     * @deprecated -> move producets to sales management
     */
    public function getProducts($sort, $dir)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadProducts::getInstance()->getProducts($sort, $dir)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;  
    }
    
    /**
     * 
     * @param $optionsData
     * @return unknown_type
     * 
     * @deprecated obsolete code (only as reminder)
     */
    public function saveProducts($optionsData)
    {
        /*
        $products = Zend_Json::decode($optionsData);
         
        try {
            $products = new Tinebase_Record_RecordSet('Crm_Model_Product', $products);
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller_LeadProducts::getInstance()->saveProducts($products) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;
        */       
    }
}
