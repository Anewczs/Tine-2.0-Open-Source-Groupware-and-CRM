#!/usr/bin/env php
<?php
/**
 * tine cli script 
 *
 * @package     Cli
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

/**
 * path to tine 2.0 checkout
 */
$tine20path = dirname(__FILE__);

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
    /*
        'verbose|v'             => 'Output messages',
        'format|f'              => 'File format [csv]',
        'dry|d'                 => "Dry run - don't import data",
    */
        'help|h'                => 'Display this help Message',
        'verbose|v'             => 'Output messages',
        'dry|d'                 => "Dry run - don't change anything",    
        'info|i'                => 'Get usage description of method',
    
        'method=s'              => 'Method to call [required]',              
        'username=s'            => 'Username [required]',              
        'password=s'            => 'Password [required]',              
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   //echo $e->__toString();
   echo $e->getUsageMessage();
   exit;
}

if (count($opts->toArray()) === 0 || $opts->h || empty($opts->method) || empty($opts->username) || empty($opts->password)) {
    //print_r($opts->toArray());
    echo $opts->getUsageMessage();
    exit;
}

$tineBase = Tinebase_Controller::getInstance();
$tineBase->handleCli($opts);

/*
if (count($opts->toArray()) === 0 || $opts->h || empty($opts->filename)) {
    echo $opts->getUsageMessage();
    exit;
}

if ($opts->v) {
    echo "filename to import: " . $opts->filename ."\n";
}

// Set up basic tine 2.0 environment
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

$tinebaseController = Tinebase_Controller::getInstance();
$tinebaseController->initFramework();

// set default locale
$tinebaseController->setupUserLocale('de');

if (!$tinebaseController->login('tine20admin', 'lars', '127.0.0.1')){
    throw new Exception("Couldn't login, user session required for import! \n");
}

// get csv importer
$importer = Addressbook_Import_Factory::factory('Csv');

// set mapping
$mapping = array(
    'adr_one_locality'      => 'Ort',
    'adr_one_postalcode'    => 'Plz',
    'adr_one_street'        => 'Straße',
    'org_name'              => 'Name1',
    'org_unit'              => 'Name2',
    'note'                  => array(
        'Mitarbeiter'       => 'inLab Spezi',
        'Anzahl Mitarbeiter' => 'ANZMitarbeiter',
        'Bemerkung'         => 'Bemerkung',
    ),
    'tel_work'              => 'TelefonZentrale',
    'tel_cell'              => 'TelefonDurchwahl',
    'n_family'              => 'Nachname',
    'n_given'               => 'Vorname',
    'n_prefix'              => array('Anrede', 'Titel'),
);

// read file
if ($opts->v) {
    echo "reading file ...";
}
$records = $importer->read($opts->filename, $mapping);
if ($opts->v) {
    echo "done.\n";
}

// import (check if dry run)
if (!$opts->d) {
    if ($opts->v) {
        echo "importing ". count($records) ." records ...";
    }
    $importedRecords = $importer->import($records);
    if ($opts->v) {
        echo "done.\n";
    }
    if ($opts->v) {
        foreach ($importedRecords as $contact) {
            echo "Imported contact: " . $contact->n_full;
        }   
    }
} else {
    print_r($records->toArray());
}
*/
?>