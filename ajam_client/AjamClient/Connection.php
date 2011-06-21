<?php
/**
 * AJAM PHP client
 *
 * @package     AJAM
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class AjamClient_Connection extends Zend_Http_Client
{
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $debugEnabled = false;
    
    /**
     * contructor
     *
     * @param string $_uri uri to connect to
     * @param array $_config config options
     */
    public function __construct($_uri, array $_config = array())
    {
        $_config['useragent'] = 'Ajam remote client (rv: 0.1)';
        $_config['keepalive'] = TRUE;
        
        parent::__construct($_uri, $_config);
        
        $this->setCookieJar();
    }
    
    /**
     * login to asterisk server
     *
     * see /etc/asterisk/mananger.conf for username and password
     * 
     * @param string $_username the username
     * @param string $_secret the password
     */
    public function login($_username, $_secret)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'login',
            'username'  => $_username,
            'secret'    => $_secret
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * disconnect selected channel
     *
     * @param string $_channel the name of the channel to disconnect
     */
    public function hangup($_channel)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'hangup',
            'channel'  	=> $_channel
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * redirect call to another extension
     * 
     * this function did not work a expected to far. needs more testing
     *
     * @param string $_channel the channel to redirect
     * @param string $_exten the extension to redirect to
     */
    public function redirect($_channel, $_exten)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'redirect',
            'priority'	=> 1,
            'channel'  	=> $_channel,
            'exten'	=> $_exten
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * get status all channels or channel matching $_channel
     *
     * @param string $_channel to channel to match against
     * @return array list of active channels
     */
    public function status($_channel = NULL)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'status'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Exception($xml->response[0]->generic['message']);
        }
        
        $result = array();
        
        foreach($xml->response as $statusRow) {
          if($statusRow->generic['event'] == 'Status' and ($_channel === NULL or stripos($statusRow->generic['channel'], $_channel) === 0) )  {
            $status = new stdClass;
            foreach($statusRow->generic->attributes() as $key => $value) {
              $status->$key = (string)$value;
            }
            $result[] = $status;
          }
        }
        
        return $result;
    }
    
    /**
     * logout from asterisk server
     *
     */
    public function logout()
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'logoff'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Goodbye') {
            throw new Exception($xml->response->generic['message']);
        }
    }

    /**
     * get list of all sip peers
     *
     * @return array list of sip peers
     */
    public function sippeers()
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'sippeers'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Exception($xml->response->generic['message']);
        }

        $result = array();
        foreach($xml->response as $statusRow) {
          if($statusRow->generic['event'] == 'PeerEntry')  {
            $status = new stdClass;
            foreach($statusRow->generic->attributes() as $key => $value) {
              $status->$key = (string)$value;
            }
            $result[] = $status;
          }
        }
        
        return $result;
    }

    /**
     * initiate new call
     *
     * @param string $_channel
     * @param string $_context
     * @param string $_exten
     * @param string $_priority
     * @param string $_callerId
     */
    public function originate($_channel, $_context, $_exten, $_priority, $_callerId="Ajam Service")
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'originate',
            'channel'	=> $_channel,
            'context'	=> $_context,
            'exten'	=> $_exten,
            'priority'	=> $_priority,
            'callerid'	=> $_callerId,
            'async'	=> 1
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('logout failed');
        }

        $dom = new DomDocument();
        $dom->loadXML( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($dom);
        }
    }
    
    /**
     * execute command on asterisk server
     *
     * @param string $_command the command to execute
     */
    public function command($_command)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'command',
            'command'	=> $_command
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('logout failed');
        }

        $dom = new DomDocument();
        $dom->loadXML( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($dom);
        }
    }
    
    /**
     * enabled debugging
     *
     * @param bool $_status set to true to enable debugging
     */
    public function setDebugEnabled($_status)
    {
        $this->debugEnabled = (bool)$_status;
    }
}