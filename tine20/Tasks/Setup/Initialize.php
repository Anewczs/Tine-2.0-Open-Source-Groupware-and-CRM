<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Initialize.php 9535 2010-05-10 10:30:05Z g.ciyiltepe@metaways.de $
 *
 */

/**
 * class for Tasks initialization
 * 
 * @package     Setup
 */
class Tasks_Setup_Initialize extends Setup_Initialize
{
    /**
     * initialize application
     *
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        parent::_initialize($_application, $_options);
        $this->_initializeFavorites(); 
    }
    
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All my tasks", // _("All my tasks")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
             )
        )));
    }
}