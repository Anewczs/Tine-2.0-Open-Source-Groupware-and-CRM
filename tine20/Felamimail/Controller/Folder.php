<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use other/shared namespaces
 * @todo        translate standard folder names
 */

/**
 * folder controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Folder extends Tinebase_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * last search count (2 dim array: userId => accountId => count)
     *
     * @var array
     */
    protected $_lastSearchCount = array();
    
    /**
     * default system folder names
     *
     * @var array
     */
    protected $_systemFolders = array('inbox', 'drafts', 'sent', 'templates', 'junk', 'trash');
    
    /**
     * folder delimiter/separator
     * 
     * @var string
     */
    protected $_delimiter = '/';
    
    /**
     * folder backend
     *
     * @var Felamimail_Backend_Folder
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Folder
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_backend = new Felamimail_Backend_Folder();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Folder
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Folder();
        }
        
        return self::$_instance;
    }

    /************************************* public functions *************************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $filterValues = $this->_extractFilter($_filter);
        
        // get folders from db
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $filterValues['globalname']),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $filterValues['account_id'])
        ));
        $result = $this->_backend->search($filter);
        if (count($result) == 0) {
            // try to get folders from imap server
            $result = Felamimail_Controller_Cache_Folder::getInstance()->update($filterValues['account_id'], $filterValues['globalname']);
        }             
        $result->cache_status = Felamimail_Model_Folder::CACHE_STATUS_PENDING;
        
        $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['account_id']] = count($result);
        
        // sort folders
        $account = Felamimail_Controller_Account::getInstance()->get($filterValues['account_id']);
        if ($account->sort_folders) {
            $result = $this->_sortFolders($result, $filterValues['globalname']);
        }
        
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $filterValues = $this->_extractFilter($_filter);
        
        return $this->_lastSearchCount[$this->_currentAccount->getId()][$filterValues['account_id']];
    }
    
    /**
     * get single folder
     * 
     * @param string $_id
     * @return Felamimail_Model_Folder
     */
    public function get($_id)
    {
        return $this->_backend->get($_id);
    }
    
    /**
     * create folder
     *
     * @param string $_folderName to create
     * @param string $_parentFolder
     * @param string $_accountId [optional]
     * @return Felamimail_Model_Folder
     */
    public function create($_accountId, $_folderName, $_parentFolder = '')
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to create new folder: ' . $_parentFolder . $this->_delimiter . $_folderName );
        
        $imap = Felamimail_Backend_ImapFactory::factory($account);
        $imap->createFolder($_folderName, (empty($_parentFolder)) ? NULL : $_parentFolder , $this->_delimiter);
        
        $globalname = (empty($_parentFolder)) ? $_folderName : $_parentFolder . $this->_delimiter . $_folderName;
        
        // create new folder
        $folder = new Felamimail_Model_Folder(array(
            'localname'     => $_folderName,
            'globalname'    => $globalname,
            'account_id'    => $_accountId,
            'parent'        => $_parentFolder
        ));           
        
        $folder = $this->_backend->create($folder);
        return $folder;
    }
    
    /**
     * update single folder
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    public function update(Felamimail_Model_Folder $_folder)
    {
        return $this->_backend->update($_folder);
    }
    
    /**
     * remove folder
     *
     * @param string $_accountId
     * @param string $_folderName globalName (complete path) of folder to delete
     * 
     * @todo check if folders has subfolders
     */
    public function delete($_accountId, $_folderName)
    {
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($_accountId);
            $imap->removeFolder($_folderName);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' IMAP Error: ' . $zmse->getMessage() 
                . '. Folder name: ' . $_folderName . ' - Did you delete the subfolders first?'
            );
            
            //-- check if folders has subfolders and throw exception if that is the case
        }
        
        try {
            $folder = $this->_backend->getByBackendAndGlobalName($_accountId, $_folderName);
            $this->_backend->delete($folder->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Trying to delete non-existant folder ' . $_folderName);
        }
    }
    
    /**
     * rename folder
     *
     * @param string $_accountId
     * @param string $_oldFolderName local (complete path) of folder to rename
     * @param string $_newFolderName new globalName of folder
     * @return Felamimail_Model_Folder
     */
    public function rename($_accountId, $_newLocalName, $_oldGlobalName)
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;
        
        // use delimiter to old globalname, remove old localname and build new globalname
        $globalNameParts = explode($this->_delimiter, $_oldGlobalName);
        array_pop($globalNameParts);
        $newGlobalName = implode($this->_delimiter, $globalNameParts) . $this->_delimiter . $_newLocalName;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Renaming ... ' . $_oldGlobalName . ' -> ' . $newGlobalName);
        
        $imap = Felamimail_Backend_ImapFactory::factory($_accountId);
        $imap->renameFolder($_oldGlobalName, $newGlobalName);
        
        // rename folder in db
        try {
            $folder = $this->_backend->getByBackendAndGlobalName($_accountId, $_oldGlobalName);
            $folder->globalname = $newGlobalName;
            $folder->localname = $_newLocalName;
            $folder = $this->update($folder);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Trying to rename non-existant folder.');
            throw $tenf;
        }
        
        // loop subfolders (recursive) and replace new localname in globalname path
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'startswith',  'value' => $_oldGlobalName . $account->delimiter),
            array('field' => 'account_id', 'operator' => 'equals',      'value' => $_accountId),
        ));
        $subfolders = $this->_backend->search($filter);
        foreach ($subfolders as $subfolder) {
            if ($newGlobalName != $subfolder->globalname) {
                $newSubfolderGlobalname = str_replace($_oldGlobalName, $newGlobalName, $subfolder->globalname);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Renaming ... ' . $subfolder->globalname . ' -> ' . $newSubfolderGlobalname);
                $subfolder->globalname = $newSubfolderGlobalname;
                $this->update($subfolder);
            }
        }
        
        return $folder;
    }

    /**
     * delete all messages in one folder -> be careful, they are completly removed and not moved to trash
     *
     * @param string $_folderId
     * @return void
     */
    public function emptyFolder($_folderId)
    {
        $folder = $this->_backend->get($_folderId);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        Felamimail_Controller_Cache_Message::getInstance()->clear($_folderId);
        
        try {
            // try to delete messages in imap folder
            $imap = Felamimail_Backend_ImapFactory::factory($account);    
            
            $imap->selectFolder($folder->globalname);
            $messageUids = $imap->getUniqueId();
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to delete ' 
                . count($messageUids) . ' messages from folder ' . $folder->globalname . '.'
                //. print_r($messageUids, TRUE)
            );
            
            foreach ($messageUids as $uid) {
                $imap->removeMessage($uid);
            }

        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
        }
    }
    
    /**
     * get system folders
     * 
     * @param string $_accountId
     * @return array
     * 
     * @todo    update these from account settings
     */
    public function getSystemFolders($_accountId)
    {
        return $this->_systemFolders;
    }
    
    /************************************* protected functions *************************************/
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_FolderFilter $_filter
     * @return array (assoc) with filter values
     * 
     * @todo add AND/OR conditions for multiple filters of the same field?
     */
    protected function _extractFilter(Felamimail_Model_FolderFilter $_filter)
    {
        $result = array(
            'account_id' => Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 
            'globalname' => ''
        );
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            switch($filter->getField()) {
                case 'account_id':
                    $result['account_id'] = $filter->getValue();
                    break;
                case 'globalname':
                    $result['globalname'] = $filter->getValue();
                    break;
            }
        }
        
        return $result;
    }

    /**
     * sort folder record set
     * - begin with INBOX + other standard/system folders, add other folders
     *
     * @param Tinebase_Record_RecordSet $_folders
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet
     */
    protected function _sortFolders(Tinebase_Record_RecordSet $_folders, $_parentFolder)
    {
        $sortedFolders = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        
        $_folders->sort('localname', 'ASC', 'natcasesort');
        $_folders->addIndices(array('globalname'));
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Sorting subfolders of "' . $_parentFolder . '".');

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folders->globalname, TRUE));
        
        foreach ($this->_systemFolders as $systemFolderName) {
            $folders = $_folders->filter('globalname', '@^' . $systemFolderName . '$@i', TRUE);
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $systemFolderName . ' => ' . print_r($folders->toArray(), TRUE));
            if (count($folders) > 0) {
                $sortedFolders->addRecord($folders->getFirstRecord());
            }
        }
        
        foreach ($_folders as $folder) {
            if (! in_array(strtolower($folder->globalname), $this->_systemFolders)) {
                $sortedFolders->addRecord($folder);
            }
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($sortedFolders->globalname, TRUE));
        
        return $sortedFolders;
    }
}
