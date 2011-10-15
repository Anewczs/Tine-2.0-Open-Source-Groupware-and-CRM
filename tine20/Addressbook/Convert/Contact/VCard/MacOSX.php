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
 * class to convert a SOGO vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_MacOSX extends Addressbook_Convert_Contact_VCard_Abstract
{
    protected $_emptyArray = array(
        'adr_one_countryname'   => null,
        'adr_one_locality'      => null,
        'adr_one_postalcode'    => null,
        #'adr_one_region'        => null,
        'adr_one_street'        => null,
        #'adr_one_street2'       => null,
        'adr_two_countryname'   => null,
        'adr_two_locality'      => null,
        'adr_two_postalcode'    => null,
        #'adr_two_region'        => null,
        'adr_two_street'        => null,
        #'adr_two_street2'       => null,
        #'assistent'             => null,
        'bday'                  => null,
        #'calendar_uri'          => null,
        'email'                 => null,
        'email_home'            => null,
        'jpegphoto'             => null,
        #'freebusy_uri'          => null,
        'note'                  => null,
        #'role'                  => null,
        #'salutation_id'         => null,
        #'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        #'n_fn'                  => null,
        'n_given'               => null,
        #'n_middle'              => null,
        #'n_prefix'              => null,
        #'n_suffix'              => null,
        'org_name'              => null,
        #'org_unit'              => null,
        #'pubkey'                => null,
        #'room'                  => null,
        #'tel_assistent'         => null,
        #'tel_car'               => null,
        'tel_cell'              => null,
        #'tel_cell_private'      => null,
        'tel_fax'               => null,
        'tel_fax_home'          => null,
        'tel_home'              => null,
        'tel_pager'             => null,
        'tel_work'              => null,
        #'tel_other'             => null,
        #'tel_prefer'            => null,
        #'tz'                    => null,
        #'geo'                   => null,
        #'lon'                   => null,
        #'lat'                   => null,
        'tags'                  => null,
        'notes'                 => null,
    );
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_model
     * @return string
     */
    public function fromTine20Model(Addressbook_Model_Contact $_model)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_model->toArray(), true));
        
        $card = new Sabre_VObject_Component('VCARD');
        
        // required vcard fields
        $card->add(new Sabre_VObject_Property('VERSION', '3.0'));
        $card->add(new Sabre_VObject_Property('FN', $_model->n_fileas));
        $card->add(new Sabre_VObject_Property('N', $_model->n_family . ';' . $_model->n_given));
        
        $card->add(new Sabre_VObject_Property('PRODID', '-//tine20.org//Tine 2.0//EN'));
        $card->add(new Sabre_VObject_Property('UID', $_model->getId()));

        // optional fields
        $card->add(new Sabre_VObject_Property('ORG', Sabre_VObject_Property::concatCompoundValues(array($_model->org_name, $_model->org_unit))));
        $card->add(new Sabre_VObject_Property('TITLE', $_model->title));
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_work);
        $tel->add('TYPE', 'WORK');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_home);
        $tel->add('TYPE', 'HOME');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_cell);
        $tel->add('TYPE', 'CELL');
        $card->add($tel);
        
        #$tel = new Sabre_VObject_Property('TEL', $_model->tel_cell_private);
        #$tel->add('TYPE', 'CELL');
        #$tel->add('TYPE', 'HOME');
        #$card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_fax);
        $tel->add('TYPE', 'FAX');
        $tel->add('TYPE', 'WORK');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_fax_home);
        $tel->add('TYPE', 'FAX');
        $tel->add('TYPE', 'HOME');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_pager);
        $tel->add('TYPE', 'PAGER');
        $card->add($tel);
        
        $adr = new Sabre_VObject_Property('ADR', Sabre_VObject_Property::concatCompoundValues(array(null, null, $_model->adr_one_street, $_model->adr_one_locality, $_model->adr_one_region, $_model->adr_one_postalcode, $_model->adr_one_countryname)));
        $adr->add('TYPE', 'WORK');
        $card->add($adr);

        $adr = new Sabre_VObject_Property('ADR', Sabre_VObject_Property::concatCompoundValues(array(null, null, $_model->adr_two_street, $_model->adr_two_locality, $_model->adr_two_region, $_model->adr_two_postalcode, $_model->adr_two_countryname)));
        $adr->add('TYPE', 'HOME');
        $card->add($adr);
        
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=work', $_model->email));
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=home', $_model->email_home));
        
        $card->add(new Sabre_VObject_Property('URL;TYPE=work', $_model->url));
        $card->add(new Sabre_VObject_Property('URL;TYPE=home', $_model->url_home));
        
        $card->add(new Sabre_VObject_Property('NOTE', $_model->note));
        
        if(! empty($_model->jpegphoto)) {
            try {
                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $_model->getId());
                $jpegData = $image->getBlob('image/jpeg');
                $card->add(new Sabre_VObject_Property('PHOTO;ENCODING=b;TYPE=JPEG', base64_encode($jpegData)));
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$_model->getId()} not found or invalid");
            }
        
        
        }        
        if(isset($_model->tags) && count($_model->tags) > 0) {
            $card->add(new Sabre_VObject_Property('CATEGORIES', Sabre_VObject_Property::concatCompoundValues((array) $_model->tags->name, ',')));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card->serialize();
    }    
}
