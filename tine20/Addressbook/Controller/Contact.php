<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_Contact extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Addressbook';
        $this->_modelName = 'Addressbook_Model_Contact';
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_Contact
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Contact
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Contact();
        }
        
        return self::$_instance;
    }
    
    /**
     * gets binary contactImage
     *
     * @param int $_contactId
     * @return blob
     */
    public function getImage($_contactId) {
        // ensure user has rights to see image
        $this->get($_contactId);
        
        $image = $this->_backend->getImage($_contactId);
        return $image;
    }
    
    /**
     * returns the default addressbook
     * 
     * @return Tinebase_Model_Container
     * 
     * @todo replace this with Tinebase_Container::getInstance()->getDefaultContainer
     */
    public function getDefaultAddressbook()
    {
        $defaultAddressbookId = Tinebase_Core::getPreference('Addressbook')->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
        try {
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // default may be gone -> remove default adb pref
            Tinebase_Core::getPreference('Addressbook')->deleteUserPref(Addressbook_Preference::DEFAULTADDRESSBOOK);
            
            // generate a new one
            $defaultAddressbookId = Tinebase_Core::getPreference('Addressbook')->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        }
        
        return $defaultAddressbook;
    }
    
    /**
     * fetch one contact identified by $_userId
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact
     * @throws  Addressbook_Exception_AccessDenied if user has no read grant
     */
    public function getContactByUserId($_userId)
    {
        $contact = $this->_backend->getByUserId($_userId);
        if (!$this->_currentAccount->hasGrant($contact->container_id, Tinebase_Model_Container::GRANT_READ)) {
            throw new Addressbook_Exception_AccessDenied('read access to contact denied');
        }            
        return $contact;            
    }

    /**
     * delete one record
     * - don't delete if it belongs to an user account
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        if (!empty($_record->account_id)) {
            throw new Addressbook_Exception_AccessDenied('It is not allowed to delete a contact linked to an user account!');
        }
        
        parent::_deleteRecord($_record);
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        $this->_setGeoData($_record);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo    check if address changes before setting new geodata
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        $this->_setGeoData($_record);
    }
    
    /**
     * set geodata of record
     * 
     * @param $_record
     * @return void
     */
    protected function _setGeoData($_record)
    {
        if (! Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::MAPPANEL, NULL, TRUE)->value) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Mappanel/geoext/nominatim disabled with config option.');
            return;
        }
        
        if(! empty($_record->adr_one_locality)) {
            $nomination = new Zend_Service_Nominatim();
            
            $nomination->setVillage($_record->adr_one_locality);
            
            if(!empty($_record->adr_one_postalcode)) {
                $nomination->setPostcode($_record->adr_one_postalcode);
            }
            
            if(!empty($_record->adr_one_street)) {
                $nomination->setStreet($_record->adr_one_street);
            }
            
            if(!empty($_record->adr_one_countryname)) {
                $nomination->setCountry($_record->adr_one_countryname);
            }
            
            try {            
                $places = $nomination->search();
                
                if(count($places) > 0) {
                    $_record->lon = $places->current()->lon;
                    $_record->lat = $places->current()->lat;
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Place found: lon/lat ' . $_record->lon . ' / ' . $_record->lat);
                } else {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find place.');
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_record->adr_one_street . ', ' 
                        . $_record->adr_one_postalcode . ', ' . $_record->adr_one_locality . ', ' . $_record->adr_one_countryname
                    );
                }
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            }
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No locality given: Do not search for geodata.');
        }
    }
}
