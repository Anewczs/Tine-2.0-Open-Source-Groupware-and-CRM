<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle generic folders in WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Collection_Abstract extends Sabre_DAV_Collection implements Sabre_DAV_IProperties, Sabre_DAVACL_IACL
{
    /**
     * the current application object
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    protected $_applicationName;
    
    protected $_model;
    
    /**
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    /**
     * the current path
     * 
     * @var string
     */
    protected $_path;
    
    /**
     * @var array
     */
    protected $_pathParts;
    
    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_path         the current path
     */
    public function __construct($_path)
    {
        $this->_path = $_path;
        $this->_pathParts = $this->_parsePath($_path);

        #$this->_application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
    }
    
    protected function _parsePath($_path)
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        $this->_applicationName = $pathParts[0];
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        
        return $pathParts;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Collection::getChild()
     */
    public function getChild($_name)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name);
    
        switch(count($this->_pathParts)) {
            # path == ApplicationName
            # return personal and shared folder
            case 1:
                $className = $this->_applicationName . '_Frontend_WebDAV';
                return new $className($this->_path . '/' . $_name);
        
                break;
            
            # path == ApplicationName/{personal|shared}
            # list container
            case 2:
                if ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_SHARED) {
                    try {
                        $container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $_name, Tinebase_Model_Container::TYPE_SHARED);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new Sabre_DAV_Exception_FileNotFound('Directory not found');
                    }
                    
                    $objectClass = $this->_applicationName . '_Frontend_WebDAV_Container';
                    
                    return new $objectClass($container);
                } elseif ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_PERSONAL) {
                    if ($_name != Tinebase_Core::getUser()->accountLoginName) {
                        throw new Sabre_DAV_Exception_FileNotFound('Child not found');
                    }
                    
                    $className = $this->_applicationName . '_Frontend_WebDAV';
                    
                    return new $className($this->_path . '/' . $_name);
                }
                
                break;
                
            # path == Applicationname/personal/accountLoginName
            # return personal folders
            case 3:
                try {
                    $container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $_name, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
                } catch (Tinebase_Exception_NotFound $tenf) {
                    throw new Sabre_DAV_Exception_FileNotFound('Directory not found');
                }
                
                $objectClass = $this->_applicationName . '_Frontend_WebDAV_Container';
                
                return new $objectClass($container);
                
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound('Child not found');
            
                break;
        }
    
        
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $children = array();
        
        switch(count($this->_pathParts)) {
            # path == ApplicationName
            # return personal and shared folder
            case 1:
                $children[] = $this->getChild(Tinebase_Model_Container::TYPE_PERSONAL);
                $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);
        
                break;
            
            # path == Applicationname/{personal|shared}
            case 2:
                if ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_SHARED) {
                    $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_READ);
                    foreach ($containers as $container) {
                        $children[] = $this->getChild($container);
                    }
                } elseif ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_PERSONAL) {
                    $children[] = $this->getChild(Tinebase_Core::getUser()->accountLoginName);
                }
                
                break;
                
            # path == Applicationname/personal/accountLoginName
            # return personal folders
            case 3:
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container);
                }
                
                break;
        }
                
        return $children;
        
    }
    
    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup()
    {
        return null;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *      
     * @todo implement real logic
     * @return array
     */
    public function getACL() 
    {
        return null;
        
        return array(
            array(
                        'privilege' => '{DAV:}read',
                        'principal' => $this->addressBookInfo['principaluri'],
                        'protected' => true,
            ),
            array(
                        'privilege' => '{DAV:}write',
                        'principal' => $this->addressBookInfo['principaluri'],
                        'protected' => true,
            )
        );
    
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        list(,$name) = Sabre_DAV_URLUtil::splitPath($this->_path);
        
        return $name;
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo implement real logic
     * @return string|null
     */
    public function getOwner()
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        
        $children = array();
        
        list(, $basename) = Sabre_DAV_URLUtil::splitPath($this->_path);
        
        switch(count($pathParts)) {
            # path == /accountLoginName
            # list personal and shared folder
            case 1:
                $properties = array(
                    '{http://calendarserver.org/ns/}getctag' => 1,
                    'id'                => $basename,
                    'uri'               => $basename,
                    #'principaluri'      => $principalUri,
                    '{DAV:}displayname' => $basename
                );
                break;
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($requestedProperties, true));
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($properties, true));
        
        $response = array();
    
        foreach($requestedProperties as $prop) switch($prop) {
            case '{DAV:}owner' :
                $response[$prop] = new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id);
                break;
                
            default :
                if (isset($properties[$prop])) $response[$prop] = $properties[$prop];
                break;
    
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($response, true));
        
        return $response;
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl)
    {
        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existant property is always succesful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname. 
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param array $mutations 
     * @return bool|array 
     */
    public function updateProperties($mutations) 
    {
        return $this->carddavBackend->updateAddressBook($this->addressBookInfo['id'], $mutations); 
    }
}
