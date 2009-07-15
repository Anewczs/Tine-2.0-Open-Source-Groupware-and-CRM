/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  SambaMachine
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Admin.sambaMachine');

/**
 * Samba machine 'mainScreen'
 */
Tine.Admin.sambaMachine.show = function() {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.sambaMachine.gridPanel) {
        Tine.Admin.sambaMachine.gridPanel = new Tine.Admin.SambaMachineGridPanel({
            app: app
        });
    }
    
    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.sambaMachine.gridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.sambaMachine.gridPanel.actionToolbar, true);
    Tine.Admin.sambaMachine.gridPanel.store.load();
};

/**
 * SambaMachine grid panel
 */
Tine.Admin.SambaMachineGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Admin.Model.SambaMachine,
    recordProxy: Tine.Admin.sambaMachineBackend,
    defaultSortInfo: {field: 'accountLoginName', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'accountDisplayName'
    },
    
    initComponent: function() {
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Admin.SambaMachineGridPanel.superclass.initComponent.call(this);
        
        //this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Admin', 'sambaMachines'));
        //this.action_editInNewWindow.requiredGrant = 'editGrant';
        
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Computer Name'),    field: 'query',       operators: ['contains']}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
             ],
             defaultFilter: 'query',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [{
            id: 'accountId',
            header: this.app.i18n._("ID"),
            width: 100,
            sortable: true,
            dataIndex: 'accountId',
            hidden: true
        },{
            id: 'accountLoginName',
            header: this.app.i18n._("Name"),
            width: 350,
            sortable: true,
            dataIndex: 'accountLoginName'
        },{
            id: 'accountDisplayName',
            header: this.app.i18n._("Description"),
            width: 350,
            sortable: true,
            dataIndex: 'accountDisplayName'
        }];
    }
});
