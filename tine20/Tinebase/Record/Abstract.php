<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add toJson / setFromJson functions?
 */

/**
 * Abstract implemetation of  Tinebase_Record_Interface
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
abstract class Tinebase_Record_Abstract implements Tinebase_Record_Interface
{
    /**
     * ISO8601LONG datetime representation
     */
    const ISO8601LONG = 'yyyy-MM-dd HH:mm:ss';
    
	/**
     * should datas be validated on the fly(false) or only on demand(true)
     *
     * @var bool
     */
    public  $bypassFilters;
    
    /**
     * should datetimeFields be converted from iso8601 strings to ZendDate objects and back 
     *
     * @var bool
     */
    public  $convertDates;
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var string
     */
    protected $_identifier = NULL;
    
    /**
     * application the record belongs to
     * NOTE: _Must_ be set by the derived classes!
     *
     * @var string
     */
    protected $_application = NULL;
    
    /**
     * holds properties of record
     * 
     * @var array 
     */
    protected $_properties = array();
    
    /**
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend inputfilter
     */
    protected $_filters = array();
    
    /**
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var array list of zend validator
     */
    protected $_validators = array();
    
    /**
     * the validators place there validation errors in this variable
     * 
     * @var array list of validation errors
     */
    protected $_validationErrors = array();
    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array();
    
    /**
     * save state if data are validated
     *
     * @var bool
     */
    protected $_isValidated = false;
    
    /**
     * holds instance of Zend_Filter
     * 
     * @var Zend_Filter
     */
    protected $_Zend_Filter = NULL;
   
    /**
     * fields to translate when translate() function is called
     *
     * @var array
     */
    protected $_toTranslate = array();
    
    /******************************** functions ****************************************/
    
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     * 
     * @todo what happens if not all properties in the datas are set?
     * The default values must also be set, even if no filtering is done!
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     * @return void
     * @throws Tinebase_Record_Exception_DefinitionFailure
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        if ($this->_identifier === NULL) {
            throw new Tinebase_Record_Exception_DefinitionFailure('$_identifier is not declared');
        }
        
        $this->bypassFilters = (bool)$_bypassFilters;
        $this->convertDates = (bool)$_convertDates;

        if(is_array($_data)) {
            $this->setFromArray($_data);
        }
        
    }
    
    /**
     * sets identifier of record
     * 
     * @param int identifier
     * @return void
     */
    public function setId($_id)
    {
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        if ($this->bypassFilters === true) {
            $this->_properties[$this->_identifier] = $_id;
        } else {
        	$this->__set($this->_identifier, $_id);
        }
    }
    
    /**
     * gets identifier of record
     * 
     * @return int identifier
     */
    public function getId()
    {
    	if (! isset($this->_properties[$this->_identifier])) {
    		$this->setId(NULL);
    	}
		return $this->_properties[$this->_identifier];
    }
    
    /**
     * gets application the records belongs to
     * 
     * @return string application
     */
    public function getApplication()
    {
    	return $this->_application;
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Record_Exception_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        if($this->convertDates === true) {
            $this->_convertISO8601ToZendDate($_data);
        }
        
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        // make shure we run through the setters
        $bypassFilter = $this->bypassFilters;
        $this->bypassFilters = true;
        foreach ($_data as $key => $value) {
            if (array_key_exists ($key, $this->_validators)) {
                $this->$key = $value;
            }
        }
        $this->bypassFilters = $bypassFilter;
        
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * wrapper for setFromJason which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @todo move this to a generic __call interceptor setFrom<API>InUsersTimezone
     * 
     * @param  string $_data json encoded data
     * @throws Tinebase_Record_Exception_Validation when content contains invalid or missing data
     */
    public function setFromJsonInUsersTimezone($_data)
    {
        // change timezone of current php process to usertimezone to let new dates be in the users timezone
        // NOTE: this is neccessary as creating the dates in UTC and just adding/substracting the timeshift would
        //       lead to incorrect results on DST transistions 
        date_default_timezone_set(Zend_Registry::get('userTimeZone'));

        // NOTE: setFromArray creates new Zend_Dates of $this->datetimeFields
        $this->setFromJson($_data);
        
        // convert $this->_datetimeFields into the configured server's timezone (UTC)
        $this->setTimezone('UTC');
        
        // finally reset timzone of current php process to the configured server timezone (UTC)
        date_default_timezone_set('UTC');
    }
    
    /**
     * Sets timezone of $this->_datetimeFields
     * 
     * @see Zend_Date::setTimezone()
     * @param  string $_timezone
     * @param  bool   $_recursive
     * @return  void
     * @throws Tinebase_Record_Exception_Validation
     */
    public function setTimezone($_timezone, $_recursive = TRUE)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($this->_properties[$field])) continue;
            
            if(!is_array($this->_properties[$field])) {
                $toConvert = array($this->_properties[$field]);
            } else {
                $toConvert = $this->_properties[$field];
            }

            foreach ($toConvert as $field => &$value) {
                if (! $value instanceof Zend_Date) {
                    throw new Tinebase_Record_Exception_Validation($toConvert[$field] . 'must be an Zend_Date'); 
                }
                $value->setTimezone($_timezone);
            } 
        }
        
        if ($_recursive) {
            foreach ($this->_properties as $property => $value) {
                if (is_object($value) && 
                        (in_array('Tinebase_Record_Interface', class_implements($value)) || 
                        $value instanceof Tinebase_Record_Recordset) ) {
                    $value->setTimezone($_timezone, TRUE);
                }
            }
        }
        
    }
    
    /**
     * returns array of fields with validation errors 
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
    
    /**
     * returns array with record related properties 
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $recordArray = $this->_properties;
        if ($this->convertDates === true) {
            $this->_convertZendDateToISO8601($recordArray);
        }
        
        if ($_recursive) {
            foreach ($recordArray as $property => $value) {
                if (is_object($value) && 
                        (in_array('Tinebase_Record_Interface', class_implements($value)) || 
                        $value instanceof Tinebase_Record_Recordset) ) {
                    $recordArray[$property] = $value->toArray(FALSE);
                }
            }
        }
        
        return $recordArray;
    }
    
    /**
     * validate and filter the the internal data
     *
     * @param $_throwExceptionOnInvalidData
     * @return bool
     */
    public function isValid($_throwExceptionOnInvalidData=false)
    {
        if($this->_isValidated === false) {
            $inputFilter = $this->_getFilter();
            $inputFilter->setData($this->_properties);
            
            if ($inputFilter->isValid()) {
                // set $this->_properties with the filtered values
                $this->_properties = $inputFilter->getUnescaped();
                $this->_isValidated = true;
            } else {
                $this->_validationErrors = array();
                
                foreach($inputFilter->getMessages() as $fieldName => $errorMessage) {
                    //print_r($inputFilter->getMessages());
                    $this->_validationErrors[] = array(
                        'id'  => $fieldName,
                        'msg' => $errorMessage
                    );
                }
                if ($_throwExceptionOnInvalidData) {
                    $e = new Tinebase_Record_Exception_Validation('some fields ' . implode(',', array_keys($inputFilter->getMessages())) . ' have invalid content');
                    Zend_Registry::get('logger')->debug(__CLASS__ . ":\n" .
                        print_r($this->_validationErrors,true). $e);
                    throw $e;
                }
            }
        }
        
        return $this->_isValidated;
    }
    
    /**
     * apply filter
     *
     * @todo implement
     */
    public function applyFilter()
    {
        $this->isValid(true);
        
    }
    
    /**
     * sets record related properties
     * 
     * @param string _name of property
     * @param mixed _value of property
     * @throws Tinebase_Record_Exception_NotDefined
     * @return void
     */
    public function __set($_name, $_value)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Record_Exception_NotDefined($_name . ' is no property of $this->_properties');
        }
        
        $this->_properties[$_name] = $_value;
        $this->_isValidated = false;
        
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * unsets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Record_Exception_NotDefined
     * @return void
     */
    public function __unset($_name)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Record_Exception_NotDefined($_name . ' is no property of $this->_properties');
        }
        
        unset($this->_properties[$_name]);
        
        $this->_isValidated = false;
        
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * checkes if an propertiy is set
     * 
     * @param string _name name of property
     * @return bool property is set or not
     */
    public function __isset($_name)
    {
        return isset($this->_properties[$_name]);
    }
    
    /**
     * gets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Record_Exception_NotDefined
     * @return mixed value of property
     */
    public function __get($_name)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Record_Exception_NotDefined($_name . ' is no property of $this->_properties');
        }
        
        return array_key_exists($_name, $this->_properties) ? $this->_properties[$_name] : NULL;
    }
    
    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     * 
     * @return Zend_Filter
     */
    protected function _getFilter()
    {
        if ($this->_Zend_Filter == NULL) {
           $this->_Zend_Filter = new Zend_Filter_Input($this->_filters, $this->_validators);
        }
        return $this->_Zend_Filter;
    }
    
    /**
     * Converts Zend_Dates into ISO8601 representation
     *
     * @param array &$_toConvert
     * @return void
     */
    protected function _convertZendDateToISO8601(&$_toConvert)
    {
        foreach ($_toConvert as $field => &$value) {
            if ($value instanceof Zend_Date) {
                $_toConvert[$field] = $value->get(Tinebase_Record_Abstract::ISO8601LONG);
            } elseif (is_array($value)) {
                $this->_convertZendDateToISO8601($value);
            }
        }
    }
    
    /**
     * Converts dates into Zend_Date representation
     *
     * @param array &$_data
     * 
     * @return void
     */
    protected function _convertISO8601ToZendDate(array &$_data)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($_data[$field]) || $_data[$field] instanceof Zend_Date) continue;
            
            if(is_array($_data[$field])) {
                foreach($_data[$field] as $dataKey => $dataValue) {
                	if ($dataValue instanceof Zend_Date) continue;
                    $_data[$field][$dataKey] =  (int)$dataValue == 0 ? NULL : new Zend_Date($this->_convertISOToTs($dataValue), NULL);
                }
            } else {
                $_data[$field] = (int)$_data[$field] == 0 ? NULL : new Zend_Date($this->_convertISOToTs($_data[$field]), NULL);
            }
        }
    }
    
    /**
     * cut the timezone-offset from the iso representation in order to force 
     * Zend_Date to create dates in the user timezone. otherwise they will be 
     * created with Etc/GMT+<offset> as timezone which would lead to incorrect 
     * results in datetime computations!
     * 
     * @param  string Zend_Date::ISO8601 representation of a datetime filed
     * @return string ISO8601LONG representation ('YYYY-MM-dd HH:mm:ss')
     */
    protected function _convertISO8601ToISO8601LONG($_ISO)
    {
        $cutedISO = preg_replace('/[+\-]{1}\d{2}:\d{2}/', '', $_ISO);
        $cutedISO = str_replace('T', ' ', $cutedISO);
        
        return $cutedISO;
    }
    
    /**
     * converts an iso formated date into a timestamp
     *
     * @param  string Zend_Date::ISO8601 representation of a datetime filed
     * @return int    UNIX Timestamp
     */
    protected function _convertISOToTs($_ISO)
    {
        $matches = array();
        preg_match("/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})/", $_ISO, $matches);

        if (count($matches) == 7) {
            list($match, $year, $month, $day, $hour, $minute, $second) = $matches;
            return  mktime($hour, $minute, $second, $month, $day, $year);
        }
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetExists($_offset)
    {
        return isset($this->_properties[$_offset]);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetGet($_offset)
    {
        return $this->_properties[$_offset];
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetSet($_offset, $_value)
    {
        return $this->__set($_offset, $_value);
    }
    
    /**
     * required by ArrayAccess interface
     * @throws Tinebase_Record_Exception_NotAllowed
     */
    public function offsetUnset($_offset)
    {
        throw new Tinebase_Record_Exception_NotAllowed('Unsetting of properties is not allowed');
    }
    
    /**
     * required by IteratorAggregate interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_properties);    
    }
    
    /**
     * returns a random 40-character hexadecimal number to be used as 
     * universal identifier (UID)
     * 
     * @param int|optional $_length the length of the uid, defaults to 40
     * @return string 40-character hexadecimal number
     */
    public static function generateUID($_length = false)
    {
        $uid = sha1(mt_rand(). microtime());
        
        if($_length !== false) {
            $uid = substr($uid, 0, $_length);
        }
        
        return $uid;
    }
    
    /**
     * returns an array with differences to the given record
     * 
     * @param  Tinebase_Record_Interface $_record record for comparism
     * @return array with differences field => different value
     */
    public function diff($_record)
    {
        if(! $_record instanceof Tinebase_Record_Abstract) {
            return $_record;
        }
        
        //echo '---------------' ."n";
        //print_r($_record->toArray());
        $diff = array();
        foreach (array_keys($this->_validators) as $fieldName) {
            //echo $fieldName . "\n";
            if (in_array($fieldName, $this->_datetimeFields)) {
                if ($this->__get($fieldName) instanceof Zend_Date
                    && $_record->$fieldName instanceof Zend_Date
                    && $this->__get($fieldName)->compare($_record->$fieldName) === 0) {
                        continue;
                } elseif (!$_record->$fieldName instanceof Zend_Date
                          && $this->__get($fieldName) == $_record->$fieldName) {
                    continue;
                }
            } elseif($fieldName == $this->_identifier
                     && $this->getId() == $_record->getId()) {
                    continue;
            } /*elseif (is_array($_record->$fieldName)) {
                throw new Exception('Arrays are not allowed as values in records. use recordSets instead!');
            } */elseif ($_record->$fieldName instanceof Tinebase_Record_RecordSet 
                      || $_record->$fieldName instanceof Tinebase_Record_Abstract) {
                 $subdiv = $_record->$fieldName->diff($this->__get($fieldName));
                 if (!empty($subdiv)) {
                     $diff[$fieldName] = $subdiv;
                 }
                 continue;
            } elseif($this->__get($fieldName) == $_record->$fieldName) {
                continue;
            }
            
            $diff[$fieldName] = $_record->$fieldName;
        }
        return $diff;
    }
    
    /**
     * check if two records are equal
     * 
     * @param  Tinebase_Record_Interface $_record record for comparism
     * @param  array                     $_toOmit fields to omit
     * @return bool
     */
    public function isEqual($_record, array $_toOmmit = array())
    {
        $allDiffs = $this->diff($_record);
        $diff = array_diff(array_keys($allDiffs), $_toOmmit);
        
        return count($diff) == 0;
    }
    
    /**
     * translate this records' fields
     *
     */
    public function translate()
    {
        // get translation object
        if (!empty($this->_toTranslate)) {
            
            $locale = Zend_Registry::get('locale');
            $translate = Tinebase_Translation::getTranslation($this->_application);
            
            foreach ($this->_toTranslate as $field) {
                $this->$field = $translate->_($this->$field);
            }
        }
    }
    
    /**
     * check if the model has a specific field (container_id for example)
     *
     * @param string $_field
     * @return boolean
     */
    public function has($_field) 
    {
        return (array_key_exists ($_field, $this->_validators)); 
    }
}

