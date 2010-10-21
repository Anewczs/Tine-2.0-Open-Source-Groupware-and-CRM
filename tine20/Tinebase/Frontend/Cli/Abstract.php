<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * abstract cli server
 *
 * This class handles cli requests
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array();
    
    /**
     * echos usage information
     *
     */
    public function getHelp()
    {
        foreach ($this->_help as $functionHelp) {
            echo $functionHelp['description']."\n";
            echo "parameters:\n";
            foreach ($functionHelp['params'] as $param => $description) {
                echo "$param \t $description \n";
            }
        }
    }
    
    /**
     * update or create import/export definition
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function updateImportExportDefinition(Zend_Console_Getopt $_opts)
    {
        $defs = $_opts->getRemainingArgs();
        if (empty($defs)) {
            echo "No definition given.\n";
            return FALSE;
        }
        
        if (! $this->_checkAdminRight()) {
            return FALSE; 
        }
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        foreach ($defs as $definitionFilename) {
            Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFilename, $application);
            echo "Imported " . $definitionFilename . " successfully.\n";
        }
        
        return TRUE;
    }

    /**
     * set container grants
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function setContainerGrants(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE; 
        }
        
        $data = $this->_parseArgs($_opts, array('accountId', 'containerId', 'grants'));
        
        $container = Tinebase_Container::getInstance()->getContainerById($data['containerId']);
        
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        if ($application->getId() !== $container->application_id) {
            echo "Container does not belong this Application!\n";
            return FALSE;
        }
        
        if ($data['accountId'] == '0') {
            $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
        } else {
            $accountType = (array_key_exists('accountType', $data)) ? $data['accountType'] : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        }
        Tinebase_Container::getInstance()->addGrants($data['containerId'], $accountType, $data['accountId'], (array) $data['grants'], TRUE);
        
        echo "Added grants to container.\n";
        
        return TRUE;
    }
    
    /**
     * parses arguments (key1=value1 key2=value2 key3=subvalue1,subvalue2 ...)
     * 
     * @param Zend_Console_Getopt $_opts
     * @param array $_requiredKeys
     * @throws Tinebase_Exception_InvalidArgument
     * @return array
     */
    protected function _parseArgs(Zend_Console_Getopt $_opts, $_requiredKeys = array())
    {
        $args = $_opts->getRemainingArgs();
        
        $result = array();
        foreach ($args as $idx => $arg) {
            list($key, $value) = explode('=', $arg);
            if (strpos($value, ',') !== false) {
                $value = explode(',', $value);
            }
            $result[$key] = $value;
        }
        
        if (! empty($_requiredKeys)) {
            foreach ($_requiredKeys as $requiredKey) {
                if (! array_key_exists($requiredKey, $result)) {
                    throw new Tinebase_Exception_InvalidArgument('Required parameter not found: ' . $requiredKey);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * check admin right of application
     * 
     * @return boolean
     */
    protected function _checkAdminRight()
    {
        // check if admin for tinebase
        if (! Tinebase_Core::getUser()->hasRight($this->_applicationName, Tinebase_Acl_Rights::ADMIN)) {
            echo "No permission.\n";
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * import records
     *
     * @param Zend_Console_Getopt $_opts
     * @param Tinebase_Controller_Record_Interface $_controller
     */
    protected function _import($_opts, $_controller)
    {
        $args = $_opts->getRemainingArgs();
            
        // get csv importer
        $definitionName = array_pop($args);
        
        if (empty($definitionName)) {
            echo "No definition name/file given.\n";
            exit;
        }
        
        if (preg_match("/\.xml/", $definitionName)) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                $definitionName,
                Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
            ); 
        } else {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($definitionName);
        }
        
        $importer = new $definition->plugin($definition, $_controller, ($_opts->d) ? array('dryrun' => 1) : array());
        
        // loop files in argv
        foreach ($args as $filename) {
            // read file
            if ($_opts->v) {
                echo "reading file $filename ...";
            }
            try {
                $result = $importer->import($filename);
                if ($_opts->v) {
                    echo "done.\n";
                }
            } catch (Exception $e) {
                if ($_opts->v) {
                    echo "failed (". $e->getMessage() . ").\n";
                } else {
                    echo $e->getMessage() . "\n";
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                continue;
            }
            
            echo "Imported " . $result['totalcount'] . " records. Import failed for " . $result['failcount'] . " records. \n";
                        
            // import (check if dry run)
            if ($_opts->d) {
                print_r($result['results']->toArray());
            } 
        }
    }

    /**
     * search for duplicates
     * 
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param  Tinebase_Model_Filter_FilterGroup
     * @param string $_field
     * @return array with ids / field
     * 
     * @todo add more options (like soundex, what do do with duplicates/delete/merge them, ...)
     */
    protected function _searchDuplicates(Tinebase_Controller_Record_Abstract $_controller, $_filter, $_field)
    {
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 100,
        ));
        $results = array();
        $allRecords = array();
        $totalCount = $_controller->searchCount($_filter);
        echo 'Searching ' . $totalCount . " record(s) for duplicates\n";
        while ($pagination->start < $totalCount) {
            $records = $_controller->search($_filter, $pagination);
            foreach ($records as $record) {
                if (in_array($record->{$_field}, $allRecords)) {
                    $allRecordsFlipped = array_flip($allRecords);
                    $duplicateId = $allRecordsFlipped[$record->{$_field}];
                    $results[] = array('id' => $duplicateId, 'value' => $record->{$_field});
                    $results[] = array('id' => $record->getId(), 'value' => $record->{$_field});
                }
                
                $allRecords[$record->getId()] = $record->{$_field};
            }
            $pagination->start += 100;
        }
        
        return $results;
    }
}
