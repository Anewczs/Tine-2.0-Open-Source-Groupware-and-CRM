<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * addressbook vcard convert factory class
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Factory
{
    const CLIENT_SOGO       = 'sogo';
    const CLIENT_MACOSX     = 'macosx';
    const CLIENT_GENERIC    = 'generic';
    
    /**
	 * factory function to return a selected phone backend class
	 *
	 * @param   string $_backend
	 * @param   string $_version
	 * @return  Addressbook_Convert_Contact_VCard_Interface
	 */
	static public function factory($_backend, $_version = null)
	{
	    switch ($_backend) {
	        case Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC:
	            return new Addressbook_Convert_Contact_VCard_Generic($_version);
	            
	            break;
	            
	        case Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX:
	            return new Addressbook_Convert_Contact_VCard_MacOSX($_version);
	            
	            break;
	            
            case Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO:
                return new Addressbook_Convert_Contact_VCard_Sogo($_version);
                 
                break;
	                 
	    }
	}
	
	/**
	 * parse iseragent and return backend and version
	 * 
	 * @return array
	 */
	static public function parseUserAgent($_userAgent)
	{
	    // MacOS X
        if (preg_match(Addressbook_Convert_Contact_VCard_MacOSX::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX;
            $version = $matches['version'];
        
        // Thunderbird with Sogo Connector
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_Sogo::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO;
            $version = $matches['version'];
        
            // generic client
        } else {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC;
            $version = null;
        }
        
        return array($backend, $version);
	}
}
