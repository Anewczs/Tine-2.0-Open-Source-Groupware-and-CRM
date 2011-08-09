<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class representing one node path
 * 
 * @package     Tinebase
 * @subpackage  Model
 * 
 * @property    string                      containerType
 * @property    string                      containerOwner
 * @property    string                      flatpath
 * @property    string                      statpath
 * @property    string                      streamwrapperpath
 * @property    Tinebase_Model_Application  application
 * @property    Tinebase_Model_Container    container
 * @property    Tinebase_Model_FullUser     user
 * @property    string                      name (last part of path)
 * @property    Tinebase_Model_Tree_Node_Path parentrecord
 * 
 * exploded flat path should look like this:
 * 
 * [0] => app id [required]
 * [1] => type [required] (personal|otherUsers|shared)
 * [2] => container | accountLoginName
 * [3] => container | directory
 * [4] => directory
 * [5] => directory
 * [...]
 */
class Tinebase_Model_Tree_Node_Path extends Tinebase_Record_Abstract
{
    /**
     * streamwrapper path prefix
     */
    const STREAMWRAPPERPREFIX = 'tine20://';
    
    /**
     * root type
     */
    const TYPE_ROOT = 'root';
    
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'flatpath';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array (
        'containerType'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'containerOwner'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'flatpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'statpath'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'streamwrapperpath' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'			    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'parentrecord'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract::__toString()
     */
    public function __toString()
    {
        return $this->flatpath;
    }
    
    /**
     * create new path record from given path string
     * 
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Model_Tree_Node_Path
     */
    public static function createFromPath($_path)
    {
        $pathRecord = ($_path instanceof Tinebase_Model_Tree_Node_Path) ? $_path : new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'  => $_path
        ));
        
        return $pathRecord;
    }
    
    /**
     * create new parent path record from given path string
     * 
     * @param string $_path
     * @return array with (Tinebase_Model_Tree_Node_Path, string)
     * 
     * @todo add child to model?
     */
    public static function getParentAndChild($_path)
    {
        $pathParts = $pathParts = explode('/', trim($_path, '/'));
        $child = array_pop($pathParts);
        
        $pathRecord = new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'  => '/' . implode('/', $pathParts)
        ));
        
        return array(
            $pathRecord,
            $child
        );
    }
    
    /**
     * remove app id from a path
     * 
     * @param string $_flatpath
     * @param Tinebase_Model_Application $_application
     * @return string
     */
    public static function removeAppIdFromPath($_flatpath, $_application)
    {
        $appId = $_application->getId();
        return preg_replace('@^/' . $appId . '@', '', $_flatpath);
    }
    
    /**
     * get parent path of this record
     * 
     * @return Tinebase_Model_Tree_Node_Path
     */
    public function getParent()
    {
        if (! $this->parentrecord) {
            list($this->parentrecord, $unused) = self::getParentAndChild($this->flatpath);
        }
        return $this->parentrecord;
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * if flatpath is set, parse it and set the fields accordingly
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        if (array_key_exists('flatpath', $_data)) {
            $this->_parsePath($_data['flatpath']);
        }
    }
    
    /**
     * parse given path: check validity, set container type, do replacements
     * 
     * @param string $_path
     */
    protected function _parsePath($_path)
    {
        $pathParts = $this->_getPathParts($_path);
        
        $this->name                 = $pathParts[count($pathParts) - 1];
        $this->containerType        = $this->_getContainerType($pathParts);
        $this->containerOwner       = $this->_getContainerOwner($pathParts);
        $this->application          = $this->_getApplication($pathParts);
        $this->container            = $this->_getContainer($pathParts);
        $this->statpath             = $this->_getStatPath($pathParts);
        $this->streamwrapperpath    = self::STREAMWRAPPERPREFIX . $this->statpath;
    }
    
    /**
     * get path parts
     * 
     * @param string $_path
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getPathParts($_path)
    {
        $pathParts = explode('/', trim($_path, '/'));
        if (count($pathParts) < 1) {
            throw new Tinebase_Exception_InvalidArgument('Invalid path: ' . $_path);
        }
        
        return $pathParts;
    }
    
    /**
     * get container type from path
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getContainerType($_pathParts)
    {
        $containerType = (isset($_pathParts[1])) ? $_pathParts[1] : self::TYPE_ROOT;
        
        if (! in_array($containerType, array(
            Tinebase_Model_Container::TYPE_PERSONAL,
            Tinebase_Model_Container::TYPE_SHARED,
            Tinebase_Model_Container::TYPE_OTHERUSERS,
            self::TYPE_ROOT
        ))) {
            throw new Tinebase_Exception_InvalidArgument('Invalid type: ' . $containerType);
        }
        
        return $containerType;
    }
    
    /**
     * get container owner from path
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _getContainerOwner($_pathParts)
    {
        $containerOwner = ($this->containerType !== Tinebase_Model_Container::TYPE_SHARED && isset($_pathParts[2])) ? $_pathParts[2] : NULL;
        
        return $containerOwner;
    }
    
    /**
     * get application from path
     * 
     * @param array $_pathParts
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getApplication($_pathParts)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($_pathParts[0]);
        
        return $application;
    }
    
    /**
     * get container from path
     * 
     * @param array $_pathParts
     * @return Tinebase_Model_Container
     */
    protected function _getContainer($_pathParts)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
            ' PATH PARTS: ' . print_r($_pathParts, true));
        
        $container = NULL;
        
        switch ($this->containerType) {
            case Tinebase_Model_Container::TYPE_SHARED:
                if (!empty($_pathParts[2])) {
                    $container = $this->_searchContainerByName($_pathParts[2], Tinebase_Model_Container::TYPE_SHARED);
                }
                break;
                
            case Tinebase_Model_Container::TYPE_PERSONAL:
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                if (!empty($_pathParts[2])) {
                    if ($this->containerType === Tinebase_Model_Container::TYPE_PERSONAL 
                        && $_pathParts[2] !== Tinebase_Core::getUser()->accountLoginName) 
                    {
                        throw new Tinebase_Exception_NotFound('Invalid user name: ' . $_pathParts[2] . '.');
                    }
                    
                    if (!empty($_pathParts[3])) {
                        $subPathParts = explode('/', $_pathParts[3], 2);
                        $container = $this->_searchContainerByName($subPathParts[0], Tinebase_Model_Container::TYPE_PERSONAL);
                    }
                }
                break;
        }
        
        return $container;
    }
    
    /**
     * search container by name and type
     * 
     * @param string $_name
     * @param string $_type
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_NotFound|NULL
     */
    protected function _searchContainerByName($_name, $_type)
    {
        $result = NULL;
        
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => $this->application->getId(),
            'name'           => $_name,
            'type'           => $_type,
        )));
        
        if (count($search) > 1) {
            throw new Tinebase_Exception_NotFound('Duplicate container found: ' . $_name);
        } else if (count($search) === 1) {
            $result = $search->getFirstRecord();
        }
        
        return $result;
    }    
        
    /**
     * do path replacements (container name => container id, otherUsers => personal, remove account name)
     * 
     * @param array $_pathParts
     * @return string
     */
    protected function _getStatPath($_pathParts)
    {
        $pathParts = $_pathParts;
        
        if ($this->containerType === Tinebase_Model_Container::TYPE_OTHERUSERS) {
            $pathParts[1] = Tinebase_Model_Container::TYPE_PERSONAL;
        }
        
        // remove account name in stat path
        if (count($pathParts) > 1 && $this->containerType !== Tinebase_Model_Container::TYPE_SHARED) {
            unset($pathParts[2]);
        }

        // replace container name with id
        if (count($pathParts) > 2) {
            $containerPartIdx = ($this->containerType === Tinebase_Model_Container::TYPE_SHARED) ? 2 : 3;
            if (isset($pathParts[$containerPartIdx]) && $this->container && $pathParts[$containerPartIdx] === $this->container->name) {
                $pathParts[$containerPartIdx] = $this->container->getId();
            }
        }
        
        $result = implode('/', $pathParts);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Path to stat: ' . $result);
        
        return $result;
    }
}
