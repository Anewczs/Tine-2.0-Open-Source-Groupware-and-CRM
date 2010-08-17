/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RuleEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing a filter rule.</p>
 * <p>
 * TODO         add more form fields (action)
 * TODO         add title
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleEditDialog
 */
Tine.Felamimail.RuleEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'RuleEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Rule,
    //recordProxy: Tine.Felamimail.vacationBackend,
    mode: 'local',
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        //Tine.log.debug(this.record);
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Felamimail.RuleEditDialog.superclass.onRecordUpdate.call(this);
        
        var form = this.getForm();
        
        // set conditions
        var conditions = [];
        var conditionFields = ['from_contains', 'to_contains', 'subject_contains'];
        var field, i, condition, parts;
        for (i = 0; i < conditionFields.length; i++) {
            field = form.findField(conditionFields[i]);
            if (field.getValue() != '') {
                parts = conditionFields[i].split('_');
                condition = {
                    // add key/values (split fieldname)
                    test: 'address',
                    header: parts[0],
                    comperator: parts[1],
                    key: field.getValue()
                };
                conditions.push(condition);
            }
        }
        this.record.set('conditions', conditions);
        
        // TODO set action
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        return [{
            title: this.app.i18n._('Conditions'),
            xtype: 'fieldset',
            autoHeight: true,
            layout: 'form',
            anchor: '90%',
            defaults: {
                xtype: 'textfield',
                anchor: '90%'
            },
            items: [{
                name: 'from_contains',
                fieldLabel: this.app.i18n._('If "from" contains')
            }, {
                name: 'to_contains',
                fieldLabel: this.app.i18n._('If "to" contains')
            }, {
                name: 'subject_contains',
                fieldLabel: this.app.i18n._('If "subject" contains')
            }]
        }, {
            title: this.app.i18n._('Action'),
            xtype: 'fieldset',
            autoHeight: true,
            layout: 'form',
            anchor: '90%',
            defaults: {
                xtype: 'textfield',
                anchor: '90%'
            },
            items: [{
                name: 'action_argument',
                fieldLabel: this.app.i18n._('Move to folder')
            }]
        }]
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.RuleEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 640,
        height: 480,
        name: Tine.Felamimail.RuleEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RuleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
