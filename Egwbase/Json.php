<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Egwbase
 * 
 * @package     Egwbase
 * @subpackage  Server
 */
class Egwbase_Json
{
	
	/**
	 * register dependend classes
	 */
	public static function setJsonServers($_server)
	{
	    $_server->setClass('Egwbase_Container_Json', 'Egwbase_Container');
	}
	
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    public function getCountryList()
    {
        $locale = Zend_Registry::get('locale');

        $countries = $locale->getCountryTranslationList();
        asort($countries);
        foreach($countries as $shortName => $translatedName) {
            $results[] = array(
				'shortName'         => $shortName, 
				'translatedName'    => $translatedName
            );
        }

        $result = array(
			'results'	=> $results
        );

        return $result;
    }
    
    public function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Egwbase_Account::getInstance()->getAccounts($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }

    /**
     * change password of user 
     *
     * @param string $oldPw the old password
     * @param string $newPw the new password
     * @return array
     */
    public function changePassword($oldPassword, $newPassword)
    {
        $response = array(
            'success'      => TRUE
        );
        
        try {
            Egwbase_Controller::getInstance()->changePassword($oldPassword, $newPassword, $newPassword);
        } catch (Exception $e) {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "new password could not be set!"
            );   
        }
        
        return $response;
        
/*        
        $auth = Zend_Auth::getInstance();        
              
        $oldIsValid = Egwbase_Controller::getInstance()->isValidPassword($auth->getIdentity(), $oldPw);              

        if ($oldIsValid === true) {
            $_account   = Egwbase_Account::getInstance();
            $result     = $_account->setPassword(Zend_Registry::get('currentAccount')->accountId, $newPw);
            
            if($result == 1) {
                $res = array(
    				'success'      => TRUE);                
            } else {
                 $res = array(
    				'success'      => FALSE,
	    			'errorMessage' => "new password could'nt be set!");   
            }
        } else {
            $res = array(
				'success'      => FALSE,
				'errorMessage' => "old password is wrong!");
        }
        
        return $res;*/
    }    
    
    
    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    public function login($username, $password)
    {
        if (Egwbase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === true) {
            $response = array(
				'success'        => TRUE,
                'welcomeMessage' => "Some welcome message!"
			);
        } else {
            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or passord!"
			);
        }

        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        Egwbase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
