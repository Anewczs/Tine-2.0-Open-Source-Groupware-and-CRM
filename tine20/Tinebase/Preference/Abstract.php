<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 *
 * @todo        make this a real controller + singleton (create extra sql backend)
 * @todo        add getAllprefsForApp (similar to config) to get all prefs for the registry in one request
 * @todo        add getPreference function that returns the complete record
 * @todo        allow free-form preferences
 */


/**
 * abstract backend for preferences
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
abstract class Tinebase_Preference_Abstract extends Tinebase_Backend_Sql_Abstract
{
    /**
     * yes no options
     *
     * @staticvar string
     */
    const YES_NO_OPTIONS = 'yesnoopt';

    /**************************** backend settings *********************************/

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'preferences';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Preference';

    /**************************** public abstract functions *********************************/

    /**
     * get all possible application prefs
     * - every app should overwrite this
     *
     * @return  array   all application prefs
     */
    abstract public function getAllApplicationPreferences();

    /**
     * get translated right descriptions
     *
     * @return  array with translated descriptions for this applications preferences
     */
    abstract public function getTranslatedPreferences();

    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @param integer $_accountId
     * @param string $_accountType
     * @return Tinebase_Model_Preference
     */
    abstract public function getPreferenceDefaults($_preferenceName, $_accountId=NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);

    /**************************** public interceptior functions *********************************/

    /**
     * get interceptor (alias for getValue())
     *
     * @param string $_preferenceName
     * @return string
     */
    public function __get($_preferenceName)
    {
        return $this->getValue($_preferenceName);
    }

    /**
     * set interceptor (alias for setValue())
     *
     * @param string $_preferenceName
     * @param string $_value
     */
    public function __set($_preferenceName, $_value) {
        if (in_array($_preferenceName, $this->getAllApplicationPreferences())) {
            $this->setValue($_preferenceName, $_value);
        }
    }

    /**************************** public functions *********************************/

    /**
     * search for preferences
     * 
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  boolean                              $_onlyIds
     * @return Tinebase_Record_RecordSet of preferences
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        // make sure account is set in filter
        $userId = Tinebase_Core::getUser()->getId();
        if (! $_filter->isFilterSet('account')) {
            $accountFilter = $_filter->createFilter('account', 'equals', array(
                'accountId' => $userId, 
                'accountType' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
            ));
            $_filter->addFilter($accountFilter);
        } else {
            // only admins can search for other users prefs
            $accountFilter = $_filter->getAccountFilter();
            $accountFilterValue = $accountFilter->getValue(); 
            if ($accountFilterValue['accountId'] != $userId && $accountFilterValue['accountType'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                if (!Tinebase_Acl_Roles::getInstance()->hasRight($applicationName, Tinebase_Core::getUser()->getId(), Tinebase_Acl_Rights_Abstract::ADMIN)) {
                    return new Tinebase_Record_RecordSet('Tinebase_Model_Preference');
                }
            }
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_filter->toArray(), true));
        
        $paging = new Tinebase_Model_Pagination(array(
            'dir'       => 'ASC',
            'sort'      => array('name')
        ));
        $allPrefs = parent::search($_filter, $_pagination, $_onlyIds);
        
        // get single matching preferences for each different pref
        $records = $this->getMatchingPreferences($allPrefs);
        
        $allAppPrefs = $this->getAllApplicationPreferences();
        // add default prefs if not already in array (only if no name or type filters are set)
        if (! $_filter->isFilterSet('name') && ! $_filter->isFilterSet('type')) {
            $missingDefaultPrefs = array_diff($allAppPrefs, $records->name);
            foreach ($missingDefaultPrefs as $prefName) {
                $records->addRecord($this->getPreferenceDefaults($prefName));
            }
        }
        // remove all prefs that are not defined
        $undefinedPrefs = array_diff($records->name, $allAppPrefs);
        if (count($undefinedPrefs) > 0) {
            $records->addIndices(array('name'));
            foreach ($undefinedPrefs as $undefinedPrefName) {
                $record = $records->find('name', $undefinedPrefName);
                $records->removeRecord($record);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Removed undefined preference from result: ' . $undefinedPrefName);
            }
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($records->toArray(), true));
        
        return $records;
    }
    
    /**
     * do some call json functions if preferences name match
     * - every app should define its own special handlers
     *
     * @param Tinebase_Frontend_Json_Abstract $_jsonFrontend
     * @param string $name
     * @param string $value
     * @param string $appName
     */
    public function doSpecialJsonFrontendActions(Tinebase_Frontend_Json_Abstract $_jsonFrontend, $name, $value, $appName)
    {
    }

    /**
     * get value of preference
     *
     * @param string $_preferenceName
     * @param string $_default return this if no preference found and default given
     * @return string
     * @throws Tinebase_Exception_NotFound if no default given and no pref found
     */
    public function getValue($_preferenceName, $_default = NULL)
    {
        $accountId = (Tinebase_Core::getUser()) ? Tinebase_Core::getUser()->getId() : '0';

        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' get user preference "' . $_preferenceName . '" for account id ' . $accountId);

        try {
            $result = $this->getValueForUser(
            $_preferenceName, $accountId,
            ($accountId === '0')
            ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE
            : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
            );
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($_default !== NULL) {
                $result = $_default;
            } else {
                throw $tenf;
            }
        }

        return $result;
    }

    /**
     * get value of preference for a user/group
     *
     * @param string $_preferenceName
     * @param integer $_accountId
     * @param string $_accountType
     * @return string
     * @throws Tinebase_Exception_NotFound
     *
     * @todo add param for getting default value ?
     */
    public function getValueForUser($_preferenceName, $_accountId, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $select = $this->_getSelect('*');

        // build query: ... WHERE (user OR group OR anyone) AND name AND application_id
        $filter = new Tinebase_Model_PreferenceFilter(array(
        array('field'     => 'account',         'operator'  => 'equals', 'value'     => array(
                'accountId' => $_accountId, 'accountType' => $_accountType)
        ),
        array('field'     => 'name',            'operator'  => 'equals', 'value'     => $_preferenceName),
        ));
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $filter, $this);

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        if (!$queryResult) {
            //throw new Tinebase_Exception_NotFound("No matching preference for '$_preferenceName' found!");
            // try to get default value
            $pref = $this->getPreferenceDefaults($_preferenceName, $_accountId, $_accountType);

        } else {
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));

            // get the correct result
            $pref = $this->_getMatchingPreference($this->_rawDataToRecordSet($queryResult));
        }

        $result = $pref->value;

        return $result;
    }

    /**
     * get all users who have the preference $_preferenceName = $_value
     *
     * @param string $_preferenceName
     * @param string $_value
     * @param array $_limitToUserIds [optional]
     * @return array of user ids
     *
     * @todo support group preferences
     */
    public function getUsersWithPref($_preferenceName, $_value, $_limitToUserIds = array())
    {
        $result = array();

        // check if value is default or forced setting
        $select = $this->_getSelect();
        $filter = new Tinebase_Model_PreferenceFilter(array(
        array('field'     => 'account',         'operator'  => 'equals', 'value'     => array(
                'accountId' => '0', 'accountType' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE)
        ),
        array('field'     => 'name',            'operator'  => 'equals', 'value'     => $_preferenceName),
        ));
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $filter, $this);
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        if (empty($queryResult)) {
            // get default pref
            $pref = $this->getPreferenceDefaults($_preferenceName);
        } else {
            // found
            $pref = new Tinebase_Model_Preference($queryResult[0]);
        }

        if ($pref->value == $_value) {

            if (! empty($_limitToUserIds)) {
                $result = Tinebase_User::getInstance()->getMultiple($_limitToUserIds)->getArrayOfIds();
            } else {
                $result = Tinebase_User::getInstance()->getUsers()->getArrayOfIds();
            }

            if ($pref->type == Tinebase_Model_Preference::TYPE_FORCED) {
                // forced: get all users -> do nothing here

            } else if ($pref->type == Tinebase_Model_Preference::TYPE_DEFAULT) {
                // default: remove all users/groups who don't have default
                $filter = new Tinebase_Model_PreferenceFilter(array(
                array('field'   => 'account_type',    'operator'  => 'equals', 'value' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER),
                array('field'   => 'name',            'operator'  => 'equals', 'value' => $_preferenceName),
                array('field'   => 'value',           'operator'  => 'not',    'value' => $_value),
                ));
                $accountsWithOtherValues = $this->search($filter)->account_id;
                $result = array_diff($result, $accountsWithOtherValues);

            } else {
                throw new Tinebase_Exception_UnexpectedValue('Preference should be of type "forced" or "default".');
            }

        } else {
            // not default or forced: get all users/groups who have the setting
            $filter = new Tinebase_Model_PreferenceFilter(array(
            array('field'   => 'account_type',    'operator'  => 'equals', 'value' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER),
            array('field'   => 'name',            'operator'  => 'equals', 'value' => $_preferenceName),
            array('field'   => 'value',           'operator'  => 'equals', 'value' => $_value),
            ));
            $result = $this->search($filter)->account_id;
        }

        return $result;
    }

    /**
     * set value of preference
     *
     * @param string $_preferenceName
     * @param string $_value
     */
    public function setValue($_preferenceName, $_value)
    {
        $accountId = (Tinebase_Core::isRegistered(Tinebase_Core::USER)) ? Tinebase_Core::getUser()->getId() : '0';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set ' . $_preferenceName . ' for user ' . $accountId . ':' . $_value);

        return $this->setValueForUser($_preferenceName, $_value, $accountId);
    }

    /**
     * get value of preference for a user/group
     *
     * @param string $_preferenceName
     * @param string $_value
     * @param integer $_userId
     * @param boolean $_ignoreAcl
     * @return string
     */
    public function setValueForUser($_preferenceName, $_value, $_accountId, $_ignoreAcl = FALSE)
    {
        // check acl first
        if(!$_ignoreAcl){
            $userId = Tinebase_Core::getUser()->getId();
            if (
            $_accountId !== $userId
            && !Tinebase_Acl_Roles::getInstance()->hasRight($this->_application, $userId, Tinebase_Acl_Rights_Abstract::ADMIN)
            ) {
                throw new Tinebase_Exception_AccessDenied('You are not allowed to change the preferences.');
            }
        }
        // check if already there -> update
        $select = $this->_getSelect('*');
        $select
        ->where($this->_db->quoteIdentifier($this->_tableName . '.account_id')      . ' = ?', $_accountId)
        ->where($this->_db->quoteIdentifier($this->_tableName . '.account_type')    . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        ->where($this->_db->quoteIdentifier($this->_tableName . '.name')            . ' = ?', $_preferenceName);

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        if (!$queryResult) {
            // no preference yet -> create
            $preference = new Tinebase_Model_Preference(array(
                'application_id'    => $appId = Tinebase_Application::getInstance()->getApplicationByName($this->_application)->getId(),
                'name'              => $_preferenceName,
                'value'             => $_value,
                'account_id'        => $_accountId,
                'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'type'              => Tinebase_Model_Preference::TYPE_NORMAL
            ));
            $this->create($preference);

        } else {
            $preference = $this->_rawDataToRecord($queryResult);
            $preference->value = $_value;
            $this->update($preference);
        }
    }

    /**
     * get matching preferences from recordset with multiple prefs)
     *
     * @param Tinebase_Record_RecordSet $_preferences
     */
    public function getMatchingPreferences(Tinebase_Record_RecordSet $_preferences)
    {
        $_preferences->addIndices(array('name'));

        // get unique names, the matching preference and add it to result set
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Preference');
        $uniqueNames = array_unique($_preferences->name);
        foreach ($uniqueNames as $name) {
            $singlePrefSet = $_preferences->filter('name', $name);
            $result->addRecord($this->_getMatchingPreference($singlePrefSet));
        }

        return $result;
    }

    /**
     * resolve preference options and add 'use default'
     * 
     * @param Tinebase_Model_Preference $_preference
     * 
     * @todo add 'use default' here
     */
    public function resolveOptions(Tinebase_Model_Preference $_preference)
    {
        if (! empty($_preference->options)) {
            $_preference->options = $this->_convertXmlOptionsToArray($_preference->options);
        }
    }
    
    /**
     * convert options xml string to array
     *
     * @param string $_xmlOptions
     * @return array
     */
    protected function _convertXmlOptionsToArray($_xmlOptions)
    {
        $result = array();
        $optionsXml = new SimpleXMLElement($_xmlOptions);

        if ($optionsXml->special) {
           $result = $this->_getSpecialOptions($optionsXml->special);
        } else {
            foreach($optionsXml->option as $option) {
                $result[] = array((string)$option->value, (string)$option->label);
            }
        }

        return $result;
    }

    /**
     * delete user preference by name
     *
     * @param string $_preferenceName
     * @return void
     */
    public function deleteUserPref($_preferenceName)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting pref ' . $_preferenceName);

        $where = array(
        $this->_db->quoteInto($this->_db->quoteIdentifier('name')           . ' = ?', $_preferenceName),
        $this->_db->quoteInto($this->_db->quoteIdentifier('account_id')     . ' = ?', Tinebase_Core::getUser()->getId()),
        $this->_db->quoteInto($this->_db->quoteIdentifier('account_type')   . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        );

        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // check if personal only and account type=anyone -> throw exception
        if ($_record->personal_only && $_record->account_type == Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE) {
            $message = 'It is not allowed to set this preference for anyone.';
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $message);
            throw new Tinebase_Exception_UnexpectedValue($message);
        }

        return parent::create($_record);
    }


    /**************************** protected functions *********************************/

    /**
     * get matching preference from result set
     * - order: forced > user > group > default
     * - get options xml from default pref if available
     *
     * @param Tinebase_Record_RecordSet $_preferences
     * @return Tinebase_Model_Preference
     */
    protected function _getMatchingPreference(Tinebase_Record_RecordSet $_preferences)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_preferences->toArray(), TRUE));
        $_preferences->addIndices(array('type', 'account_type'));

        if (count($_preferences) == 1) {
            $result = $_preferences->getFirstRecord();
        } else {
            // check forced
            $forced = $_preferences->filter('type', Tinebase_Model_Preference::TYPE_FORCED);
            if (count($forced) > 0) {
                $_preferences = $forced;
            }

            // check user
            $user = $_preferences->filter('account_type', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);
            if (count($user) > 0) {
                $result = $user->getFirstRecord();
            } else {
                // check group
                $group = $_preferences->filter('account_type', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP);
                if (count($group) > 0) {
                    $result = $group->getFirstRecord();
                } else {
                    // get first record of the remaining result set (defaults/anyone)
                    $result = $_preferences->getFirstRecord();
                }
            }
        }

        // add options from default preference
        if ($result->type !== Tinebase_Model_Preference::TYPE_DEFAULT) {
            $defaults = $_preferences->filter('type', Tinebase_Model_Preference::TYPE_DEFAULT);
            try {
                if (count($defaults) > 0) {
                    $defaultPref = $defaults->getFirstRecord();
                } else {
                    $defaultPref = $this->getPreferenceDefaults($result->name);
                }
                $result->options = $defaultPref->options;
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Preference not found: ' . $result->name);
            }
        }

        return $result;
    }

    /**
     * return base default preference
     *
     * @param string $_preferenceName
     * @return Tinebase_Model_Preference
     */
    protected function _getDefaultBasePreference($_preferenceName)
    {
        if (empty($this->_application)) {
            throw new Tinebase_Exception_UnexpectedValue('No application name set in preference class.');
        }

        return new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_application)->getId(),
            'name'              => $_preferenceName,
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <special>' . $_preferenceName . '</special>
                </options>',
            'id'                => 'default' . Tinebase_Record_Abstract::generateUID(33)
        ), TRUE);
    }

    /**
     * get the basic select object to fetch records from the database
     * - overwritten to add application id
     *
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        if (empty($this->_application)) {
            throw new Tinebase_Exception_UnexpectedValue('No application name set in preference class.');
        }

        $select = parent::_getSelect($_cols, $_getDeleted);

        $appId = Tinebase_Application::getInstance()->getApplicationByName($this->_application)->getId();
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.application_id') . ' = ?', $appId);

        return $select;
    }

    /**
     * overwrite this to add more special options for other apps
     *
     * - result array has to have the following format:
     *  array(
     *      array('value1', 'label1'),
     *      array('value2', 'label2'),
     *      ...
     *  )
     *
     * @param  string $_value
     * @return array
     */
    protected function _getSpecialOptions($_value)
    {
        $result = array();

        switch ($_value) {

                /****************** yes / no *******************************/
            case Tinebase_Preference_Abstract::YES_NO_OPTIONS:
                $locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
                $question = Zend_Locale::getTranslationList('Question', $locale);

                list($yes, $dummy) = explode(':', $question['yes']);
                list($no, $dummy) = explode(':', $question['no']);

                $result[] = array(0, $no);
                $result[] = array(1, $yes);
                break;

                /****************** default *********************************/
            default:
                throw new Tinebase_Exception_NotFound('Special option not found.');
        }

        return $result;
    }
}
