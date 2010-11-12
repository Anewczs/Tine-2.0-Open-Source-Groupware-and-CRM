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
 * @class       Tine.Felamimail.RecipientPickerDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for searching contacts in the addressbook and adding them to the recipient list in the email compose dialog.</p>
 * <p>
 * TODO         add toolbar (add as to/cc/bcc)
 * TODO         update context menu
 * TODO         make doubleclick work
 * TODO         add favorites? 
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RecipientPickerDialog
 */
 Tine.Felamimail.RecipientPickerDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'RecipientPickerWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    mode: 'local',
    
    bodyStyle:'padding:0px',
    
    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * @private
     */
    onRecordLoad: function() {
        Tine.Felamimail.RecipientPickerDialog.superclass.onRecordLoad.call(this);
        
        var subject = (this.record.get('subject') != '') ? this.record.get('subject') : this.app.i18n._('(new message)');
        this.window.setTitle(String.format(this.app.i18n._('Select recipients for "{0}"'), subject));
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        return {
            border: false,
            frame: true,
            layout: {
                align: 'stretch',
                type: 'hbox'
            },
            items: [{
                xtype: 'felamimailcontactgrid',
                title: this.app.i18n._('Contacts'),
                frame: true,
                app: Tine.Tinebase.appMgr.get('Addressbook'),
                flex: 3,
                ref: '../contactgrid'
            }, {
                xtype: 'felamimailrecipientgrid',
                record: this.record,
                i18n: this.app.i18n,
                title: this.app.i18n._('Recipients'),
                flex: 2,
                header: true,
                ref: '../recipientgrid'
            }]
        };
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.RecipientPickerDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 1000,
        height: 600,
        name: Tine.Felamimail.RecipientPickerDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RecipientPickerDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
