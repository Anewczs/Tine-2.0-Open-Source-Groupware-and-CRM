<?php
/**
 * class to hold Sieve Vacation data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to hold Vacation data
 * 
 * @property  string  trash_folder
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_Vacation extends Tinebase_Record_Abstract
{  
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';    
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), // account id
        'addresses'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'days'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 7),
        'enabled'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'mime'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'reason'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'vacationObject'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
    );
    
    /**
     * set from sieve vacation object
     * 
     * @param Felamimail_Sieve_Vacation $fsv
     */
    public function setFromFSV(Felamimail_Sieve_Vacation $fsv)
    {
        $this->setFromArray($fsv->toArray());
        $this->vacationObject = $fsv;
    }
    
    /**
     * get sieve vacation object
     * 
     * @return Felamimail_Sieve_Vacation
     */
    public function getFSV()
    {
        $fsv = new Felamimail_Sieve_Vacation();
        
        $fsv->setEnabled($this->enabled)
            ->setDays($this->days)
            ->setSubject($this->subject)
            ->setFrom($this->from)
            ->setMime($this->mime)
            ->setReason($this->reason);
            
        if (is_array($this->addresses)) {
            foreach ($this->addresses as $address) {
                $fsv->addAddress($address);
            }
        }
        
        $this->vacationObject = $fsv;
        return $fsv;
    }
}
