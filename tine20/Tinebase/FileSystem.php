<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * filesystem controller
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 */
class Tinebase_FileSystem
{
    /**
     * @var Tinebase_Tree_FileObject
     */
    protected $_fileObjectBackend;
    
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend;
    
    /**
     * path where physical files gets stored
     * 
     * @var string
     */
    protected $_basePath;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_FileSystem
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct() 
    {
        $this->_currentAccount     = Tinebase_Core::getUser();
        $this->_fileObjectBackend  = new Tinebase_Tree_FileObject();
        $this->_treeNodeBackend    = new Tinebase_Tree_Node();
        
        if (empty(Tinebase_Core::getConfig()->filesdir) || !is_writeable(Tinebase_Core::getConfig()->filesdir)) {
            throw new Tinebase_Exception_InvalidArgument('no base path(filesdir) configured or path not writeable');
        }
        
        $this->_basePath = Tinebase_Core::getConfig()->filesdir;
        
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_FileSystem;
        }
        
        return self::$_instance;
    }
    
    /**
     * init application base paths
     * 
     * @param Tinebase_Model_Application|string $_application
     */
    public function initializeApplication($_application)
    {
        // create app root node
        $appPath = $this->getApplicationBasePath($_application);
        if (!$this->fileExists($appPath)) {
            $this->mkDir($appPath);
        }
        
        $sharedBasePath = $this->getApplicationBasePath($_application, Tinebase_Model_Container::TYPE_SHARED);
        if (!$this->fileExists($sharedBasePath)) {
            $this->mkDir($sharedBasePath);
        }
        
        $personalBasePath = $this->getApplicationBasePath($_application, Tinebase_Model_Container::TYPE_PERSONAL);
        if (!$this->fileExists($personalBasePath)) {
            $this->mkDir($personalBasePath);
        }
    }
    
    /**
     * get application base path
     * 
     * @param Tinebase_Model_Application|string $_application
     * @param string $_type
     * @return string
     */
    public function getApplicationBasePath($_application, $_type = NULL)
    {
        $application = $_application instanceof Tinebase_Model_Application ? $_application : Tinebase_Application::getInstance()->getApplicationById($_application);
        
        $result = '/' . $application->getId();
        if ($_type !== NULL) {
            if (! in_array($_type, array(Tinebase_Model_Container::TYPE_SHARED, Tinebase_Model_Container::TYPE_PERSONAL))) {
                throw new Timetracker_Exception_UnexpectedValue('Type can only be shared or personal.');
            }
            
            $result .= '/' . $_type;
        }
        
        return $result;
    } 
    
    /**
     * create container node
     * 
     * @param Tinebase_Model_Container $_container
     * 
     * FIXME is the account login name missing from the (personal) path? if not, we have to fix the path in Filemanager_Controller_Node
     */
    public function createContainerNode(Tinebase_Model_Container $_container)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_container->application_id);
        $path = '/' . $application->getId() . '/' . $_container->type . '/' . $_container->getId();
        if (!$this->fileExists($path)) {
            $this->mkDir($path);
        }
    }
    
    /**
     * clear stat cache
     */
    public function clearStatCache()
    {
        $this->_statCache = array();
    }
    
    public function getMTime($_path)
    {
        $node = $this->stat($_path);
        
        $timestamp = $node->last_modified_time instanceof Tinebase_DateTime ? $node->last_modified_time->getTimestamp() : $node->creation_time->getTimestamp();
        
        return $timestamp;        
    }
    
    public function fileExists($_path) 
    {
        return $this->_treeNodeBackend->pathExists($_path);
    }
    
    public function fclose($_handle)
    {
        if (!is_resource($_handle)) {
            return false;
        }
        
        $options = stream_context_get_options($_handle);
        
        switch ($options['tine20']['mode']) {
            case 'x':
                rewind($_handle);
                
                $ctx = hash_init('sha1');
                hash_update_stream($ctx, $_handle);
                $hash = hash_final($ctx);
                
                $hashDirectory = $this->_basePath . '/' . substr($hash, 0, 3);
                $hashFile      = $hashDirectory . '/' . substr($hash, 3);
                
                if (!file_exists($hashDirectory)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash directory: ' . $hashDirectory);
                    if(mkdir($hashDirectory, 0700) === false) {
                        throw new Tinebase_Exception_UnexpectedValue('failed to create directory');
                    } 
                }
                
                if (!file_exists($hashFile)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash file: ' . $hashFile);
                    rewind($_handle);
                    $hashHandle = fopen($hashFile, 'x');
                    stream_copy_to_stream($_handle, $hashHandle);
                    fclose($hashHandle);
                }
                
                $fileObject = $this->_fileObjectBackend->get($options['tine20']['node']->object_id);
                $fileObject->hash = $hash;
                $fileObject->size = filesize($hashFile);
                if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $hashFile);
                    if ($mimeType !== false) {
                        $fileObject->contenttype = $mimeType;
                    }
                    finfo_close($finfo);
                }
                
                $this->_fileObjectBackend->update($fileObject);
                
                break;
        }
        
        fclose($_handle);
        
        return true;
    }
    
    public function fopen($_path, $_mode)
    {
        switch ($_mode) {
            case 'x':
                $dirName  = dirname($_path);
                $fileName = basename($_path);
                
                if (!$this->isDir($dirName) || $this->fileExists($_path)) {
                    return false;
                }
                $parent = $this->stat($dirName);
                $node = $this->_createFileTreeNode($parent, $fileName);
                
                $handle = tmpfile();
                break;
                
            case 'r':
                $dirName = dirname($_path);
                $fileName = basename($_path);
                
                if ($this->isDir($_path) || !$this->fileExists($_path)) {
                    return false;
                }
                
                $node = $this->stat($_path);
                $hashFile = $this->_basePath . '/' . substr($node->hash, 0, 3) . '/' . substr($node->hash, 3);
                
                $handle = fopen($hashFile, 'r');
                break;
                
            default:
                return false;
        }
        
        stream_context_set_option ($handle, 'tine20', 'path', $_path);
        stream_context_set_option ($handle, 'tine20', 'mode', $_mode);
        stream_context_set_option ($handle, 'tine20', 'node', $node);
        
        return $handle;
    }
    
    public function fwrite($_handle, $_data, $_length = null)
    {
        if (!is_resource($_handle)) {
            return false;
        }
        
        $written = fwrite($_handle, $_data);
        
        return $written;
    }
    
    public function getContentType($_path)
    {
        $node = $this->stat($_path);
        
        return $node->contenttype;        
    }
    
    public function getETag($_path)
    {
        $node = $this->stat($_path);
        
        return $node->hash;        
    }
    
    /**
     * return if path is directory
     * 
     * @param  string  $_path
     * @return boolean
     */
    public function isDir($_path)
    {
        try {
            $node = $this->stat($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return false;
        }
        
        return true;
    }
    
    /**
     * create directory
     * 
     * @param string $_path
     */
    public function mkDir($_path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Creating directory ' . $_path);
        
        $path = '/';
        $parentNode = null;
        $pathParts = $this->_splitPath($_path);
        
        foreach ($pathParts as $part) {
            $path .= '/' . $part;
            if (!$this->fileExists($path)) {
                $parentNode = $this->_createDirectoryTreeNode($parentNode, trim($part));
            } else {
                $parentNode = $this->_getTreeNode($parentNode, trim($part));
            }
        }
    }
    
    /**
     * remove directory
     * 
     * @param string $_path
     * @param boolean $_recursive
     * @return boolean
     */
    public function rmDir($_path, $_recursive = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Removing directory ' . $_path);
        
        $node = $this->stat($_path);
        
        $children = $this->_getTreeNodeChildren($node);
        
        // delete object only, if no one uses it anymore
        if (count($children) > 0) {
            if ($_recursive !== true) {
                throw new Tinebase_Exception_InvalidArgument('directory not empty');
            } else {
                foreach ($children as $child) {
                    if ($this->isDir($_path . '/' . $child->name)) {
                        $this->rmdir($_path . '/' . $child->name, true);
                    } else {
                        $this->unlink($_path . '/' . $child->name);
                    }
                }
            }
        }
        
        $this->_treeNodeBackend->delete($node->getId());
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'object_id',
                'operator'  => 'equals',
                'value'     => $node->object_id
            )
        ));
        $result = $this->_treeNodeBackend->search($searchFilter);

        // delete object only, if no one uses it anymore
        if ($result->count() == 0) {
            $this->_fileObjectBackend->delete($node->object_id);
        }
        
        return true;
    }
    
    /**
     * scan dir
     * 
     * @param string $_path
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function scanDir($_path)
    {
        $node = $this->stat($_path);
        
        $children = $this->_getTreeNodeChildren($node);
        
        foreach ($children as $child) {
            $this->_statCache[$_path . '/' . $child->name] = $child;
        }
        
        return $children;
    }
    
    /**
     * @param  string  $_path
     * @return Tinebase_Model_Tree_Node
     */
    public function stat($_path)
    {
        if (isset($this->_statCache[$_path])) {
            $node = $this->_statCache[$_path];
        } else {
            $node = $this->_treeNodeBackend->getLastPathNode($_path); 
            $this->_statCache[$_path] = $node;
        }
        
        return $node;
    }
    
    /**
     * get filesize
     * 
     * @param string $_path
     * @return integer
     */
    public function filesize($_path)
    {
        $node = $this->stat($_path);
        
        return $node->size;
    }
    
    /**
     * delete file
     * 
     * @param string $_path
     * @return boolean
     */
    public function unlink($_path)
    {
        $node = $this->stat($_path);
        
        if ($node->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            throw new Tinebase_Exception_InvalidArgument('can not unlink directories');
        }
        
        $this->_treeNodeBackend->delete($node->getId());
        
        unset($this->_statCache[$_path]);
        
        // delete object only, if no one uses it anymore
        if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
            $this->_fileObjectBackend->delete($node->object_id);
        }
        
        return true;
    }
    
    /**
     * create directory
     * 
     * @param string $_parentId
     * @param string $_name
     * @return Tinebase_Model_Tree_Node
     */
    protected function _createDirectoryTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $directoryObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
            'contentytype'  => null,
            'creation_time' => Tinebase_DateTime::now() 
        ));
        $directoryObject = $this->_fileObjectBackend->create($directoryObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $directoryObject->getId(),
            'parent_id'     => $parentId
        ));
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }

    /**
     * @param unknown_type $_parentId
     * @param unknown_type $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    protected function _createFileTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $fileObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'contentytype'  => null,
            'creation_time' => Tinebase_DateTime::now() 
        ));
        $fileObject = $this->_fileObjectBackend->create($fileObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $fileObject->getId(),
            'parent_id'     => $parentId
        ));
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }
    
    /**
     * get tree node children
     * 
     * @param string $_nodeId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getTreeNodeChildren($_nodeId)
    {
        $nodeId = $_nodeId instanceof Tinebase_Model_Tree_Node ? $_nodeId->getId() : $_nodeId;
        $children = array();
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'equals',
                'value'     => $nodeId
            )
        ));
        
        $children = $this->searchNodes($searchFilter);
        
        return $children;
    }
    
    /**
     * search tree nodes
     * 
     * @param Tinebase_Model_Tree_Node_Filter $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function searchNodes(Tinebase_Model_Tree_Node_Filter $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL)
    {
        $result = $this->_treeNodeBackend->search($_filter, $_pagination);
        return $result;
    }
    
    /**
     * get tree node specified by parent node (or id) and name
     * 
     * @param string|Tinebase_Model_Tree_Node $_parentId
     * @param string $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Getting tree node ' . $parentId . '/'. $_name);
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $_name
            ),
            array(
                'field'     => 'parent_id',
                'operator'  => $parentId === null ? 'isnull' : 'equals',
                'value'     => $parentId
            )
        ));
        
        $result = $this->_treeNodeBackend->search($searchFilter);
        
        if ($result->count() == 0) {
            throw new Tinebase_Exception_InvalidArgument('directory node not found');
        }
        
        return $result->getFirstRecord();
    }
    
    /**
     * split path
     * 
     * @param  string  $_path
     * @return array
     */
    protected function _splitPath($_path)
    {
        return explode('/', trim($_path, '/'));
    }
}
