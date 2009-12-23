<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

error_reporting( E_ALL | E_STRICT );

set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__),
    dirname(__FILE__). '/../../tine20',
    dirname(__FILE__). '/../../tine20/library',
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/*
// get config
if(file_exists(dirname(__FILE__) . '/config.inc.php')) {
    $config = new Zend_Config(require dirname(__FILE__) . '/config.inc.php');
} else {
    throw new Exception("Couldn't find config.inc.php! \n");
}
*/

// setup tine20 session
$connection = new SessionTestCase();
$connection->setBrowser('*firefox');
$connection->setBrowserUrl('http://localhost/tt/tine20/');

$connection->start();
$connection->open('http://localhost/tt/tine20/');

$connection->getEval("window.moveBy(-1 * window.screenX, 0); window.resizeTo(screen.width,screen.height);");

$connection->waitForElementPresent('username');

$connection->type('username', 'tine20admin');
$connection->type('password', 'lars');

$loginButtonId = $connection->getEval("window.Tine.loginPanel.getLoginPanel().getForm().getEl().query('button')[0].id");
$connection->click($loginButtonId);
