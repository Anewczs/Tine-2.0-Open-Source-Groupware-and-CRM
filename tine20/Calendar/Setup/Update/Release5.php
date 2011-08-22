<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>transp</name>
                <type>text</type>
                <length>40</length>
                <default>OPAQUE</default>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration);
        $this->setTableVersion('cal_events', 4);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>user_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>role</name>
                <type>text</type>
                <length>32</length>
                <default>REQ</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>NEEDS-ACTION</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('cal_attendee', $declaration);
        $this->setTableVersion('cal_attendee', 2);
        
        $this->setApplicationVersion('Calendar', '5.1');
    }
    
    /**
     * update to 5.2
     * - move attendee roles + status records in config
     */
    public function update_1()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $attendeeRolesConfig = array(
            'name'    => Calendar_Config::ATTENDEE_ROLES,
            'records' => array(
                array('id' => 'REQ', 'value' => 'Requierd', 'system' => true), //_('Requierd')
                array('id' => 'OPT', 'value' => 'Optional', 'system' => true), //_('Optional')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_ROLES,
            'value'             => json_encode($attendeeRolesConfig),
        )));
        
        $attendeeStatusConfig = array(
            'name'    => Calendar_Config::ATTENDEE_STATUS,
            'records' => array(
                array('id' => 'NEEDS-ACTION', 'value' => 'No response',  'system' => true), //_('No response')
                array('id' => 'ACCEPTED',     'value' => 'Accepted',     'system' => true), //_('Accepted')
                array('id' => 'DECLINED',     'value' => 'Declined',     'system' => true), //_('Declined')
                array('id' => 'TENTATIVE',    'value' => 'Tentative',    'system' => true), //_('Tentative')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_STATUS,
            'value'             => json_encode($attendeeStatusConfig),
        )));
        
        $this->setApplicationVersion('Calendar', '5.2');
    }
}
