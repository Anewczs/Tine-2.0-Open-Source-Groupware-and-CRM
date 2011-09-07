<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add transactions to move/create/delete/copy 
 */

/**
 * Node controller for Filemanager
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller_Node extends Tinebase_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * Filesystem backend
     *
     * @var Tinebase_FileSystem
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Filemanager_Controller_Node
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_backend = Tinebase_FileSystem::getInstance();
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
     * @return Filemanager_Controller_Node
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller_Node();
        }
        
        return self::$_instance;
    }
    
    /**
     * search tree nodes
     * 
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string|optional $_action
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $path = $this->_checkFilterACL($_filter, 'get');
        
        if ($path->containerType === Tinebase_Model_Tree_Node_Path::TYPE_ROOT) {
            $result = $this->_getRootNodes();
        } else if ($path->containerType === Tinebase_Model_Container::TYPE_PERSONAL && ! $path->containerOwner) {
            if (! file_exists($path->statpath)) {
                mkdir($path->streamwrapperpath);
            }
            $result = $this->_getOtherUserNodes();
        } else {
            try {
                $result = $this->_backend->searchNodes($_filter, $_pagination);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create basic nodes like personal|shared|user root
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ' ' . $path->statpath);
                if ($path->name === Tinebase_Model_Container::TYPE_SHARED || 
                    $path->statpath === $this->_backend->getApplicationBasePath(
                        Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName), 
                        Tinebase_Model_Container::TYPE_PERSONAL
                    ) . '/' . $this->_currentAccount->getId()
                ) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ' Creating new path ' . $path->statpath);
                    mkdir($path->streamwrapperpath);
                    $result = $this->_backend->searchNodes($_filter, $_pagination);
                } else {
                    throw $tenf;
                }
            }
            $this->resolveContainerAndAddPath($result, $path);
        }
        return $result;
    }
    
    /**
     * checks filter acl and adds base path
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($_filter === NULL) {
            $_filter = new Tinebase_Model_Tree_Node_Filter();
        }
        
        $pathFilters = $_filter->getFilter('path', TRUE);
        
        if (count($pathFilters) !== 1) {
            throw new Tinebase_Exception_AccessDenied('Exactly one path filter required.');
        }
        
        // add base path and check grants
        $pathFilter = $pathFilters[0];
        $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($pathFilter->getValue()));
        $pathFilter->setValue($path);
        
        $this->_checkPathACL($path, 'get');
        
        return $path;
    }
    
    /**
     * get the three root nodes
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getRootNodes()
    {
        $translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(
            array(
                'name' => $translate->_('My folders'),
                'path' => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName,
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER,
    	        'id' => Tinebase_Model_Container::TYPE_PERSONAL,

            ),
            array(
                'name' => $translate->_('Shared folders'),
                'path' => '/' . Tinebase_Model_Container::TYPE_SHARED,
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER,
                'id' => Tinebase_Model_Container::TYPE_SHARED,
            
            ),
            array(
                'name' => $translate->_('Other users folders'),
                'path' => '/' . Tinebase_Model_Container::TYPE_PERSONAL,
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER,
            	'id' => Tinebase_Model_Container::TYPE_OTHERUSERS,
            ),
        ), TRUE); // bypass validation
        
        return $result;
    }

    /**
     * get other users nodes
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getOtherUserNodes()
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $users = Tinebase_Container::getInstance()->getOtherUsers($this->_currentAccount, $this->_applicationName, Tinebase_Model_Grants::GRANT_READ);
        foreach ($users as $user) {
            $fullUser = Tinebase_User::getInstance()->getFullUserById($user);
            $record = new Tinebase_Model_Tree_Node(array(
                'name' => $fullUser->accountDisplayName,
                'path' => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . $fullUser->accountLoginName,
                'type' => Tinebase_Model_Tree_Node::TYPE_FOLDER,
            ), TRUE);
            $result->addRecord($record);
        }
        
        return $result;
    }
    
    /**
     * get file node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node
     */
    public function getFileNode(Tinebase_Model_Tree_Node_Path $_path)
    {
        $this->_checkPathACL($_path, 'get');
        
        if (! file_exists($_path->streamwrapperpath)) {
            throw new Filemanager_Exception('File does not exist,');
        }
        
        if (! is_file($_path->streamwrapperpath)) {
            throw new Filemanager_Exception('Is a directory');
        }
        
        return $this->_backend->stat($_path->statpath);
    }
    
    /**
     * add base path
     * 
     * @param Tinebase_Model_Tree_Node_PathFilter $_pathFilter
     * @return string
     */
    public function addBasePath($_path)
    {
        $basePath = $this->_backend->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
        $basePath .= '/folders';
        
        $path = (strpos($_path, '/') === 0) ? $_path : '/' . $_path;
                
        return $basePath . $path;
    }
    
    /**
     * check if user has the permissions for the container
     * 
     * @param Tinebase_Model_Container $_container
     * @param string $_action get|update|...
     * @return boolean
     */
    protected function _checkACLContainer($_container, $_action = 'get')
    {
        if (Tinebase_Container::getInstance()->hasGrant($this->_currentAccount, $_container, Tinebase_Model_Grants::GRANT_ADMIN)) {
            return TRUE;
        }
        
        switch ($_action) {
            case 'get':
                $requiredGrant = Tinebase_Model_Grants::GRANT_READ;
                break;
            case 'update':
                $requiredGrant = Tinebase_Model_Grants::GRANT_EDIT;
                break;
            case 'delete':
                $requiredGrant = Tinebase_Model_Grants::GRANT_DELETE;
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
        
        return Tinebase_Container::getInstance()->hasGrant($this->_currentAccount, $_container, $requiredGrant);
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string|optional $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        throw new Tinebase_Exception_NotImplemented('searchCount not implemented yet');
    }

    /**
     * create node(s)
     * 
     * @param array $_filenames
     * @param string $_type directory or file
     * @param array $_tempFileIds
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function createNodes($_filenames, $_type, $_tempFileIds = array(), $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        
        foreach ($_filenames as $idx => $filename) {
            $tempFileId = (isset($_tempFileIds[$idx])) ? $_tempFileIds[$idx] : NULL;
            $node = $this->_createNode($filename, $_type, $tempFileId, $_forceOverwrite);
            $result->addRecord($node);
        }
        
        return $result;
    }
    
    /**
     * create new node
     * 
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @param string $_type
     * @param string $_tempFileId
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _createNode($_path, $_type, $_tempFileId = NULL, $_forceOverwrite = FALSE)
    {
        if (! in_array($_type, array(Tinebase_Model_Tree_Node::TYPE_FILE, Tinebase_Model_Tree_Node::TYPE_FOLDER))) {
            throw new Tinebase_Exception_InvalidArgument('Type ' . $_type . 'not supported.');
        } 

        $path = ($_path instanceof Tinebase_Model_Tree_Node_Path) 
            ? $_path : Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($_path));
        $parentPathRecord = $path->getParent();
        
        $this->_checkPathACL($parentPathRecord, 'update');
        
        try {
            $this->_checkIfExists($path);
        } catch (Filemanager_Exception_NodeExists $fene) {
            if ($_forceOverwrite && ! $_tempFileId) {
                // just return the exisiting node and do not overwrite existing file if no tempfile id was given
                $existingNode = $this->_backend->stat($path->statpath);
                $this->resolveContainerAndAddPath($existingNode, $parentPathRecord);
                return $existingNode;
            } else if (! $_forceOverwrite) {
                throw $fene;
            }
        }

        if (! $parentPathRecord->container && $_type === Tinebase_Model_Tree_Node::TYPE_FOLDER) {
            $container = $this->_createContainer($path->name, $parentPathRecord->containerType);
            $newNodePath = $parentPathRecord->statpath . '/' . $container->getId();
        } else {
            $container = NULL;
            $newNodePath = $parentPathRecord->statpath . '/' . $path->name;
        }
        
        $newNode = $this->_createNodeInBackend($newNodePath, $_type, $_tempFileId);
        $this->resolveContainerAndAddPath($newNode, $parentPathRecord, $container);
        return $newNode;
    }
    
    /**
     * delete node in backend
     * 
     * @param string $_statpath
     * @param type
     * @param string $_tempFileId
     * @return Tinebase_Model_Tree_Node
     * @throws Filemanager_Exception
     */
    protected function _createNodeInBackend($_statpath, $_type, $_tempFileId = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Creating new path ' . $_statpath . ' of type ' . $_type);
        
        $streamwrapperpath = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX . $_statpath;
        
        switch ($_type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                if (!$handle = fopen($streamwrapperpath, 'w')) {
                    throw new Tinebase_Exception_AccessDenied('Permission denied to create file (filename ' . $_statpath . ')');
                }
                
                if ($_tempFileId !== NULL) {
                    $tempFile = Tinebase_TempFile::getInstance()->getTempFile($_tempFileId);
                    $tempData = fopen($tempFile->path, 'r');
                    if ($tempData) {
                        stream_copy_to_stream($tempData, $handle);
                        fclose($tempData);
                    } else {
                        throw new Filemanager_Exception('Could not read tempfile ' . $tempFile->path);
                    }
                }
                fclose($handle);                
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                mkdir($streamwrapperpath);
                break;
        }
        
        return $this->_backend->stat($_statpath);
    }
    
    /**
     * check file existance
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param Tinebase_Model_Tree_Node $_node
     * @throws Filemanager_Exception_NodeExists
     */
    protected function _checkIfExists(Tinebase_Model_Tree_Node_Path $_path, $_node = NULL)
    {
        if (file_exists($_path->streamwrapperpath)) {
            $existsException = new Filemanager_Exception_NodeExists();
            $existsException->addExistingNodeInfo(($_node !== NULL) ? $_node : $this->_backend->stat($_path->statpath));
            throw $existsException;
        }        
    }
        
    /**
     * check acl of path
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_action
     * @param boolean $_topLevelAllowed
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkPathACL(Tinebase_Model_Tree_Node_Path $_path, $_action = 'get', $_topLevelAllowed = TRUE)
    {
        $hasPermission = FALSE;
        
        if ($_path->container) {
            $hasPermission = $this->_checkACLContainer($_path->container, $_action);
        } else if ($_topLevelAllowed) {
            switch ($_path->containerType) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    if ($_path->containerOwner) {
                        $hasPermission = ($_path->containerOwner === $this->_currentAccount->accountLoginName || $_action === 'get');
                    } else {
                        $hasPermission = ($_action === 'get');
                    }
                    break;
                case Tinebase_Model_Container::TYPE_SHARED:
                    $hasPermission = ($_action !== 'get') ? $this->checkRight(Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS, FALSE) : TRUE;
                    break;
                case Tinebase_Model_Tree_Node_Path::TYPE_ROOT:
                    $hasPermission = ($_action === 'get');
                    break;
            }
        }
        
        if (! $hasPermission) {
            throw new Tinebase_Exception_AccessDenied('No permission to ' . $_action . ' nodes in path ' . $_path->flatpath);
        }
    }
    
    /**
     * create new container
     * 
     * @param string $_name
     * @param string $_type
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _createContainer($_name, $_type)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => $app->getId(),
            'name'           => $_name,
            'type'           => $_type,
        )));
        if (count($search) > 0) {
            throw new Tinebase_Exception_Record_NotAllowed('Container ' . $_name . ' of type ' . $_type . ' already exists.');
        }
        
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $_name,
            'type'           => $_type,
            'backend'        => 'sql',
            'application_id' => $app->getId(),
        )));
        
        return $container;
    }

    /**
     * resolve node container and path
     * 
     * (1) add path to records 
     * (2) replace name with container record, if node name is a container id 
     *     / path is toplevel (shared/personal with useraccount
     * (3) add account grants of acl container to node
     * 
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param Tinebase_Model_Container $_container
     */
    public function resolveContainerAndAddPath($_records, Tinebase_Model_Tree_Node_Path $_path, Tinebase_Model_Container $_container = NULL)
    {
        $records = ($_records instanceof Tinebase_Model_Tree_Node) 
            ? new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($_records)) : $_records;
        
        if (! $_path->container) {
            // fetch top level container nodes
            if ($_container === NULL) {
                $containerIds = $_records->name;
                $containers = Tinebase_Container::getInstance()->getMultiple($containerIds);
            } else {
                $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($_container));
            }
        }
        
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $flatpathWithoutBasepath = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_path->flatpath, $app);
        
        foreach ($records as $record) {
            // path
            $record->path = $flatpathWithoutBasepath . '/' . $record->name;
            
            $aclContainer = NULL;
            if (! $_path->container) {
                // resolve container
                if (is_string($record->name)) {
                    $idx = $containers->getIndexById($record->name);
                    if ($idx !== FALSE) {
                        $aclContainer = $containers[$idx];
                        $record->name = $aclContainer;
                        $record->path = $flatpathWithoutBasepath . '/' . $record->name->name;
                    }
                }
            } else {
                $aclContainer = $_path->container;
            }
            
            // account grants
            if ($aclContainer) {
                $record->account_grants = Tinebase_Container::getInstance()->getGrantsOfAccount(
                    Tinebase_Core::getUser(), 
                    $aclContainer
                )->toArray();
            }
        }
    }
    
    /**
     * copy nodes
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function copyNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'copy', $_forceOverwrite);
    }
    
    /**
     * copy or move an array of nodes identified by their path
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param string $_action copy|move
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, $_action, $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        
        foreach ($_sourceFilenames as $idx => $source) {
            $sourcePathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($source));
            $destinationPathRecord = $this->_getDestinationPath($_destinationFilenames, $idx, $sourcePathRecord);

            try {
                if ($_action === 'move') {
                    $node = $this->_moveNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                } else if ($_action === 'copy') {
                    $node = $this->_copyNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                }
                $result->addRecord($node);
            } catch (Filemanager_Exception_NodeExists $fene) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Node exists: ' . $destinationPathRecord->statpath);
                
                // collect all nodes that already exist and add them to exception info
                if (! isset($nodeExistsException)) {
                    $nodeExistsException = new Filemanager_Exception_NodeExists();
                }
                
                $nodesInfo = $fene->getExistingNodesInfo();
                if (count($nodesInfo) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Adding node info to exception.');
                    $nodeExistsException->addExistingNodeInfo($nodesInfo->getFirstRecord());
                }
            }
        }
        
        $this->resolveContainerAndAddPath($result, $destinationPathRecord->getParent());
        
        if (isset($nodeExistsException)) {
            // @todo add correctly moved/copied files here?
            throw $nodeExistsException;
        }
        
        return $result;
    }
    
    /**
     * get single destination from an array of destinations and an index + $_sourcePathRecord
     * 
     * @param string|array $_destinationFilenames
     * @param int $_idx
     * @param Tinebase_Model_Tree_Node_Path $_sourcePathRecord
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Filemanager_Exception
     * 
     * @todo add Tinebase_FileSystem::isDir() check?
     */
    protected function _getDestinationPath($_destinationFilenames, $_idx, $_sourcePathRecord)
    {
        if (is_array($_destinationFilenames)) {
            $isdir = FALSE;
            if (isset($_destinationFilenames[$_idx])) {
                $destination = $_destinationFilenames[$_idx];
            } else {
                throw new Filemanager_Exception('No destination path found.');
            }
        } else {
            $isdir = TRUE;
            $destination = $_destinationFilenames;
        }
        
        if ($isdir) {
            $destination = $destination . '/' . $_sourcePathRecord->name;
        }
        
        return Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($destination));
    }
    
    /**
     * copy single node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _copyNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_forceOverwrite = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Copy Node ' . $_source->flatpath . ' to ' . $_destination->flatpath);
                
        $newNode = NULL;
        
        $this->_checkPathACL($_source, 'get', FALSE);
        
        $sourceNode = $this->_backend->stat($_source->statpath);
        
        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $newNode = $this->_copyOrMoveFileNode($_source, $_destination, 'copy', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $newNode = $this->_copyFolderNode($_source, $_destination);
                break;
        }
        
        return $newNode;        
    }
    
    /**
     * copy file node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param string $_action
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _copyOrMoveFileNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_action, $_forceOverwrite = FALSE)
    {
        $this->_checkPathACL($_destination->getParent(), 'update', FALSE);
        
        try {
            $this->_checkIfExists($_destination);
        } catch (Filemanager_Exception_NodeExists $fene) {
            if ($_forceOverwrite) {
                // delete old node
                unlink($_destination->streamwrapperpath);
            } else {
                throw $fene;
            }
        }
            
        switch ($_action) {
            case 'copy':
                copy($_source->streamwrapperpath, $_destination->streamwrapperpath);
                break;
            case 'move':
                rename($_source->streamwrapperpath, $_destination->streamwrapperpath);
                break;
        }

        $newNode = $this->_backend->stat($_destination->statpath);
        
        return $newNode;
    }
    
    /**
     * copy folder node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @return Tinebase_Model_Tree_Node
     * @throws Filemanager_Exception_NodeExists
     * 
     * @todo add $_forceOverwrite?
     */
    protected function _copyFolderNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination)
    {
        $newNode = $this->_createNode($_destination, Tinebase_Model_Tree_Node::TYPE_FOLDER);
        
        // recursive copy for (sub-)folders/files
        $filter = new Tinebase_Model_Tree_Node_Filter(array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => Tinebase_Model_Tree_Node_Path::removeAppIdFromPath(
                $_source->flatpath, 
                Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)
            ),
        )));
        $result = $this->search($filter);
        if (count($result) > 0) {
            $this->copyNodes($result->path, $newNode->path);
        }
        
        return $newNode;
    }
    
    /**
     * move nodes
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function moveNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'move', $_forceOverwrite);
    }
    
    /**
     * move single node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _moveNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_forceOverwrite = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Move Node ' . $_source->flatpath . ' to ' . $_destination->flatpath);
        
        $sourceNode = $this->_backend->stat($_source->statpath);
                
        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $movedNode = $this->_copyOrMoveFileNode($_source, $_destination, 'move', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $movedNode = $this->_moveFolderNode($_source, $sourceNode, $_destination);
                break;
        }
        
        return $movedNode;
    }
    
    /**
     * move folder node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node $_sourceNode
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @return Tinebase_Model_Tree_Node
     * 
     * @todo create new container if it did not exist before
     */
    protected function _moveFolderNode($_source, $_sourceNode, $_destination)
    {
        $this->_checkPathACL($_source, 'get', FALSE);
        
        $destinationParentPathRecord = $_destination->getParent();
        
        if (! $destinationParentPathRecord->container) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Moving container ' . $_source->container->name . ' to ' . $_destination->flatpath);
                
            $this->_checkACLContainer($_source->container, 'update');
            $_sourceNode->name = $_source->container->getId();
            
            if ($_source->container->name !== $_destination->name) {
                $_source->container->name = $_destination->name;
                $_source->container = Tinebase_Container::getInstance()->update($_source->container);
            }
            
            if (! $_source->getParent()->container) {
                // just renamed the container
                return $_sourceNode;
            } else {
                throw new Tinebase_Exception_NotImplemented('TODO: create new container');
            }
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' rename ' . $_source->streamwrapperpath . ' -> ' . $_destination->streamwrapperpath);
            
            $this->_checkPathACL($destinationParentPathRecord, 'update');
            rename($_source->streamwrapperpath, $_destination->streamwrapperpath);
        }
        
        $movedNode = $this->_backend->stat($_destination->statpath);
        
        return $movedNode;
    }
    
    /**
     * delete nodes
     * 
     * @param array $_filenames string->single file, array->multiple
     * @return int delete count
     * 
     * @todo add recursive param?
     */
    public function deleteNodes($_filenames)
    {
        $deleteCount = 0;
        foreach ($_filenames as $filename) {
            if ($this->_deleteNode($filename)) {
                $deleteCount++;
            }
        }
        
        return $deleteCount;
    }

    /**
     * delete node
     * 
     * @param string $_flatpath
     * @return boolean
     */
    protected function _deleteNode($_flatpath)
    {
        $flatpathWithBasepath = $this->addBasePath($_flatpath);
        list($parentPathRecord, $nodeName) = Tinebase_Model_Tree_Node_Path::getParentAndChild($flatpathWithBasepath);
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($flatpathWithBasepath);
        
        $this->_checkPathACL($parentPathRecord, 'delete');
        
        if (! $parentPathRecord->container) {
            // check acl for deleting toplevel container
            $this->_checkPathACL($pathRecord, 'delete');
        }
        
        $success = $this->_deleteNodeInBackend($pathRecord);
        
        if ($success && ! $parentPathRecord->container) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Delete container ' . $pathRecord->container->name);
            Tinebase_Container::getInstance()->delete($pathRecord->container->getId());
        }
        
        return $success;
    }
    
    /**
     * delete node in backend
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @return boolean
     */
    protected function _deleteNodeInBackend(Tinebase_Model_Tree_Node_Path $_path)
    {
        $success = FALSE;
        
        $node = $this->_backend->stat($_path->statpath);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Removing path ' . $_path->flatpath . ' of type ' . $node->type);
        
        switch ($node->type) {
            case Tinebase_Model_Tree_Node::TYPE_FILE:
                $success = unlink($_path->streamwrapperpath);
                break;
            case Tinebase_Model_Tree_Node::TYPE_FOLDER:
                $success = $this->_backend->rmDir($_path->statpath, TRUE);
                break;
        }
        
        return $success;
    }
}
