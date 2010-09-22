<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Tinebase_Setup_Update_Release3 extends Setup_Update_Abstract
{    
    /**
     * update to 3.1
     * - add value_search option field to customfield_config
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>value_search</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('customfield_config', $declaration);
        
        $this->setTableVersion('customfield_config', '4');
        $this->setApplicationVersion('Tinebase', '3.1');
    }    

    /**
     * update to 3.2
     * - add personal_only field to preference
     * - remove all admin/default prefs with this setting
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>personal_only</name>
                <type>boolean</type>
            </field>');
        try {
            $this->_backend->addCol('preferences', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // field already exists
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
        }
        
        $this->setTableVersion('preferences', '5');
        
        // remove all personal only prefs for anyone
        $this->_db->query("DELETE FROM " . SQL_TABLE_PREFIX . "preferences WHERE account_type LIKE 'anyone' AND name IN ('defaultCalendar', 'defaultAddressbook')");
        
        $this->setApplicationVersion('Tinebase', '3.2');
    }    
    
    /**
     * update to 3.3
     * - change key of import export definitions table
     */
    public function update_2()
    {
        // we need to drop the foreign key and the index first
        try {
            $this->_backend->dropForeignKey('importexport_definition', 'importexport_definitions::app_id--applications::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            try {
                // try it again with table prefix
                $this->_backend->dropForeignKey('importexport_definition', SQL_TABLE_PREFIX . 'importexport_definitions::app_id--applications::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
                // already dropped
            }
        }
        $this->_backend->dropIndex('importexport_definition', 'application_id-name-type');
        
        // add index and foreign key again
        $this->_backend->addIndex('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>model-name-type</name>
                <unique>true</unique>
                <field>
                    <name>model</name>
                </field>
                <field>
                    <name>name</name>
                </field>
                <field>
                    <name>type</name>
                </field>
            </index>')
        ); 
        $this->_backend->addForeignKey('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>importexport_definitions::app_id--applications::id</name>
                <field>
                    <name>application_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>applications</table>
                    <field>id</field>
                </reference>
            </index>')
        );
        
        // increase versions
        $this->setTableVersion('importexport_definition', '3');
        $this->setApplicationVersion('Tinebase', '3.3');
    }
    
    /**
     * update to 3.4
     * - add filename field to import/export definitions
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                    <name>filename</name>
                    <type>text</type>
                    <length>40</length>
                </field>');
        $this->_backend->addCol('importexport_definition', $declaration);
        
        $this->setTableVersion('importexport_definition', '4');
        $this->setApplicationVersion('Tinebase', '3.4');
    }    

    /**
     * update to 3.5
     * - set filename field in export definitions (name + .xml)
     */
    public function update_4()
    {
        $this->_db->query("UPDATE " . SQL_TABLE_PREFIX . "importexport_definition SET filename=CONCAT(name,'.xml') WHERE type = 'export'");
        $this->setApplicationVersion('Tinebase', '3.5');
    }
    
    /**
     * update to 3.6
     * - container_acl -> int to string
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
         <field>
            <name>account_grant</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>');
        
        $this->_backend->alterCol('container_acl', $declaration);
        
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='readGrant' WHERE `account_grant` = '1'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='addGrant' WHERE `account_grant` = '2'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='editGrant' WHERE `account_grant` = '4'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='deleteGrant' WHERE `account_grant` = '8'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='adminGrant' WHERE `account_grant` = '16'");
        
        $this->setTableVersion('container_acl', '2');
        $this->setApplicationVersion('Tinebase', '3.6');
    }
    
    /**
     * update to 3.7
     * - container_acl -> add EXPORT/SYNC grants
     */
    public function update_6()
    {
        // get timetracker app id
        try {
            $tt = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
            $select = $this->_db->select()
                ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array('container_acl.container_id', 'container_acl.account_type', 'container_acl.account_id'))
                ->join(array('container' => SQL_TABLE_PREFIX . 'container'), 'container.id = container_acl.container_id', '')
                ->where('account_grant = ?', 'readGrant')
                ->where('application_id <> ?', $tt->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $select = $this->_db->select()
                ->from(array('container_acl' => SQL_TABLE_PREFIX . 'container_acl'), array('container_acl.container_id', 'container_acl.id', 'container_acl.account_type', 'container_acl.account_id'))
                ->where('account_grant = ?', 'readGrant');
        }
        
        $result = $this->_db->fetchAll($select);
        foreach ($result as $row) {
            // insert new grants
            foreach (array(Tinebase_Model_Grants::GRANT_EXPORT, Tinebase_Model_Grants::GRANT_SYNC) as $grant) {
                $row['account_grant'] = $grant;
                $row['id'] = Tinebase_Record_Abstract::generateUID();
                $this->_db->insert(SQL_TABLE_PREFIX . 'container_acl', $row);
            }
        }
        
        $this->setApplicationVersion('Tinebase', '3.7');
    } 

    /**
     * update to 3.8
     * - schedulers
     */
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
         <table>
            <name>scheduler</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>integer</type>
                    <length>11</length>
                    <autoincrement>true</autoincrement>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>data</name>
                    <type>text</type>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>');
        
        $this->_backend->createTable($declaration);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'scheduler', 
            1
        );
        
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = new Tinebase_Scheduler_Task();
        $task->setMonths("Jan-Dec");
        $task->setWeekdays("Sun-Sat");
        $task->setDays("1-31");
        $task->setHours("0-23");
        $task->setMinutes("0/1");
        $task->setRequest($request);
        
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->addTask('Tinebase_Alarm', $task);
        $scheduler->saveTask();
        
        $this->setApplicationVersion('Tinebase', '3.8');
    }    
    
    /**
     * update to 3.9
     * - manage shared favorites
     */
    public function update_8()
    {
        $appsWithFavorites = array(
            'Addressbook',
            'Calendar',
            'Crm',
            'Tasks',
            'Timetracker',
        );
        
        try {
            $roles = Tinebase_Acl_Roles::getInstance();
            $adminRole = $roles->getRoleByName('admin role');
            
            foreach($appsWithFavorites as $appName) {
                try {
                    $app = Tinebase_Application::getInstance()->getApplicationByName($appName);
                    $roles->addSingleRight(
                        $adminRole->getId(), 
                        $app->getId(), 
                        Tinebase_Acl_Rights::MANAGE_SHARED_FAVORITES
                    );
                } catch (Exception $nfe) {
                    // app is not installed
                }
            }
        } catch (Exception $nfe) {
            Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . " default admin role not found -> MANAGE_SHARED_FAVORITES right is not assigned");
        }
        
        $this->setApplicationVersion('Tinebase', '3.9');
    }
    
    /**
     * update to 3.10
     * - add missing indexes for notes table
     */
    public function update_9()
    {
        // add index and foreign key again
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_id</name>
                <field>
                    <name>record_id</name>
                </field>
            </index>
        '));
         
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_model</name>
                <field>
                    <name>record_model</name>
                </field>
            </index>
        '));
         
        $this->_backend->addIndex('notes', new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>record_backend</name>
                <field>
                    <name>record_backend</name>
                </field>
            </index>
        ')); 
        $this->setApplicationVersion('Tinebase', '3.10');
    }
    
    /**
     * update to 3.11
     * - change length of last_login_from
     */
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_login_from</name>
                <type>text</type>
                <length>39</length>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'last_login_from');
        $this->setApplicationVersion('Tinebase', '3.11');
    }
    
    /**
     * update to 3.12
     * - add department table
     */
    public function update_11()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>departments</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>name</name>
                        <type>text</type>
                        <length>128</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>254</length>
                        <notnull>false</notnull>
                    </field>
                    <field>
                        <name>created_by</name>
                        <type>text</type>
                        <length>40</length>
                    </field>
                    <field>
                        <name>creation_time</name>
                        <type>datetime</type>
                    </field>
                    <field>
                        <name>last_modified_by</name>
                        <type>text</type>
                        <length>40</length>
                    </field>
                    <field>
                        <name>last_modified_time</name>
                        <type>datetime</type>
                    </field>
                    <field>
                        <name>is_deleted</name>
                        <type>boolean</type>
                        <default>false</default>
                    </field>
                    <field>
                        <name>deleted_by</name>
                        <type>text</type>
                        <length>40</length>
                    </field>
                    <field>
                        <name>deleted_time</name>
                        <type>datetime</type>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>name</name>
                        <unique>true</unique>
                        <length>40</length>
                        <field>
                            <name>name</name>
                        </field>
                    </index>
                </declaration>
            </table>'
        );
        $this->createTable('departments', $declaration);
                
        $this->setApplicationVersion('Tinebase', '3.12');
    }
    
    /**
     * update to 3.13
     * - add color to container table
     */
    public function update_12()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>color</name>
                <type>text</type>
                <length>7</length>
                <default>NULL</default>
            </field>');
        $this->_backend->addCol('container', $declaration, 3);
        
        $this->setTableVersion('container', '3');
        $this->setApplicationVersion('Tinebase', '3.13');
    }

    /**
     * update to 3.14
     * - change type field in preferences table
     * - change type from normal -> user / default -> admin
     */
    public function update_13()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>type</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('preferences', $declaration);
        $this->setTableVersion('preferences', '6');
        
        $this->_db->update(SQL_TABLE_PREFIX . 'preferences', array('type' => Tinebase_Model_Preference::TYPE_USER), array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?', 'normal')
        ));
        $this->_db->update(SQL_TABLE_PREFIX . 'preferences', array('type' => Tinebase_Model_Preference::TYPE_ADMIN), array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('type') . ' = ?', 'default')
        ));
        
        $this->setApplicationVersion('Tinebase', '3.14');
    }
    
/**
     * update to 3.15
     * - add client type to access log
     */
    public function update_14()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>clienttype</name>
                <type>text</type>
                <length>128</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('access_log', $declaration);
        
        $this->setTableVersion('access_log', '2');
        $this->setApplicationVersion('Tinebase', '3.15');
    }
    
    /**
     * update to 3.16
     * - add customfield_acl table
     */
    public function update_15()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml(
        '<table>
            <name>customfield_acl</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>customfield_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_type</name>
                    <type>enum</type>
                    <value>anyone</value>
                    <value>user</value>
                    <value>group</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_grant</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>customfield_id-account-type-account_id-account_grant</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>account_grant</name>
                    </field>
                </index>
                <index>
                    <name>id-account_type-account_id</name>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
                <index>
                    <name>customfield_acl::customfield_id--customfield_config::id</name>
                    <field>
                        <name>customfield_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>customfield_config</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
        $this->createTable('customfield_acl', $declaration);
        
        // add grants to existing customfields
        $configBackend = new Tinebase_CustomField_Config();
        $allCfConfigs = $configBackend->search();
        foreach ($allCfConfigs as $cfConfig) {
            Tinebase_CustomField::getInstance()->setGrants($cfConfig, Tinebase_Model_CustomField_Grant::getAllGrants());
        }
                
        $this->setApplicationVersion('Tinebase', '3.16');
    }
}
