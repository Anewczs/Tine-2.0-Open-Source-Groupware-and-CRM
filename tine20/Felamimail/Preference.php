<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        add default account settings ?
 * @todo        make UPDATEINTERVAL a free form preference
 */


/**
 * backend for Felamimail preferences
 *
 * @package     Felamimail
 * @subpackage  Backend
 */
class Felamimail_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * use tine user credentials for imap connection
     *
     */
    const USERACCOUNT = 'userEmailAccount';

    /**
     * default email account to use
     *
     */
    const DEFAULTACCOUNT = 'defaultEmailAccount';

    /**
     * email folder update interval
     *
     */
    const UPDATEINTERVAL = 'updateInterval';

    /**
     * use felamimail in addressbook
     *
     */
    const USEINADB = 'useInAdb';

    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Felamimail';    
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::USERACCOUNT,
            self::DEFAULTACCOUNT,
            self::UPDATEINTERVAL,
            self::USEINADB,
        );
            
        return $allPrefs;
    }
    
    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications preferences
     */
    public function getTranslatedPreferences()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $prefDescriptions = array(
            self::USERACCOUNT  => array(
                'label'         => $translate->_('User Email Account'),
                'description'   => $translate->_('Use user credentials for IMAP email account.'),
            ),
            self::DEFAULTACCOUNT  => array(
                'label'         => $translate->_('Default Email Account'),
                'description'   => $translate->_('The default email account to use when sending mails.'),
            ),
            self::UPDATEINTERVAL  => array(
                'label'         => $translate->_('Email Update Interval'),
                'description'   => $translate->_('How often should Felamimail check for new Emails (in minutes). "0" means never.'),
            ),
            self::USEINADB  => array(
                'label'         => $translate->_('Use in Addressbook'),
                'description'   => $translate->_('Compose Emails from the Addressbook with Felamimail.'),
            ),
        );
        
        return $prefDescriptions;
    }
    
    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @return Tinebase_Model_Preference
     */
    public function getPreferenceDefaults($_preferenceName, $_accountId=NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::USEINADB:
            case self::USERACCOUNT:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTACCOUNT:
                $preference->value      = 'default';
                break;
            case self::UPDATEINTERVAL:
                $preference->value      = 5;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>';
                for ($i = 0; $i < 21; $i++) {
                    $preference->options .= '<option>
                        <label>'. $i . '</label>
                        <value>'. $i . '</value>
                    </option>';
                }
                $preference->options    .= '</options>';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * get special options
     *
     * @param string $_value
     * @return array
     */
    protected function _getSpecialOptions($_value)
    {
        $result = array();
        switch($_value) {
            case self::DEFAULTACCOUNT:
                // get all user accounts
                $accounts = Felamimail_Controller_Account::getInstance()->search();
                foreach ($accounts as $account) {
                    $result[] = array($account->getId(), $account->name);
                }
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
