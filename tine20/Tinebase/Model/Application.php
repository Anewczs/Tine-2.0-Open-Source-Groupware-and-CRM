<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one application
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Application extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
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
    protected $_application = 'Tinebase';
    
	/**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'      => 'StringTrim',
        'version'   => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array();

    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array(
            'id'        => array('Alnum', 'allowEmpty' => true),
            'name'      => array('presence' => 'required'),
            'status'    => array(new Zend_Validate_InArray(array('enabled', 'disabled'))),
            'order'     => array('Digits', 'presence' => 'required'),
            'tables'    => array('allowEmpty' => true),
            'version'   => array('presence' => 'required')
        );
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Tinebase_Model_Application to an accountid
     *
     * @param int|string|Tinebase_Model_Application $_accountId the accountid to convert
     * @return int
     */
    static public function convertApplicationIdToInt($_applicationId)
    {
        if($_applicationId instanceof Tinebase_Model_Application) {
            if(empty($_applicationId->id)) {
                throw new Exception('no application id set');
            }
            $applicationId = $_applicationId->id;
        } else {
            $applicationId = $_applicationId;
        }
        
        if($applicationId === NULL) {
            throw new Exception('applicationId can not be NULL');
        }
        
        return $applicationId;
    }
        
    /**
     * returns applicationname
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }    
    
    /**
     * return the major version of the appliaction
     *
     * @return int the major version
     */
    public function getMajorVersion()
    {
        if(empty($this->version)) {
            throw new Exception('no version set');
        }
        
        list($majorVersion, $minorVersion) = explode('.', $this->version);
        
        return $majorVersion;
    }
}