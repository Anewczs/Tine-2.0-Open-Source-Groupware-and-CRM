/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.account');
Tine.widgets.account.ConfigGrid = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Int} accountPickerWidth
     */
    accountPickerWidth: 200,
    /**
     * @cfg {Ext.data.JsonStore} configStore
     */
    configStore: null,
    /**
     * @cfg {bool} have the record account properties an account prefix?
     */
    hasAccountPrefix: false,
    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: [],
    
    accountPicker: null,
    configGridPanel: null,
    
    layout: 'column',
    height: 200,
    border: true,

    /**
     * @private
     */
    initComponent: function(){
        this.recordPrefix = this.hasAccountPrefix ? 'account_' : '';
        
        this.action_removeAccount = new Ext.Action({
            text: 'remove account',
            disabled: true,
            scope: this,
            handler: this.removeAccount,
            iconCls: 'action_deleteContact'
        });
        
        /* account picker */
        this.accountPicker = new Tine.widgets.account.PickerPanel({
            enableBbar: true
        });
        this.accountPicker.on('accountdblclick', function(account){
            this.addAccount(account);   
        }, this);
        
        /* col model */
        var columnModel = new Ext.grid.ColumnModel([{
                resizable: true, 
                id: this.recordPrefix + 'name', 
                header: 'Name', 
                dataIndex: this.recordPrefix + 'name', 
                renderer: Tine.Tinebase.Common.accountRenderer,
                width: 70
            }].concat(this.configColumns)
        );
        columnModel.defaultSortable = true; // by default columns are sortable
        
        /* selection model */
        var rowSelectionModel = new Ext.grid.RowSelectionModel({
            multiSelect:true
        });
        
        rowSelectionModel.on('selectionchange', function(selectionModel) {
            this.action_removeAccount.setDisabled(selectionModel.getCount() < 1);
        }, this);
        
        /* grid panel */
        this.configGridPanel = new Ext.grid.EditorGridPanel({
            title: 'Permissions',
            store: this.configStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            plugins: this.configColumns,
            autoExpandColumn: this.recordPrefix + 'name',
            bbar: [this.action_removeAccount],
            border: false
        });
        
        this.items = this.getConfigGridLayout();
        
        Tine.widgets.account.ConfigGrid.superclass.initComponent.call(this);
        
        this.on('afterlayout', function(){
            var height = this.getEl().getSize().height;
            this.items.each(function(item){
                item.setHeight(height);
            });
        },this);
    },
    /**
     * @private Layout
     */
    getConfigGridLayout: function() {
        return [{
            layout: 'fit',
            width: this.accountPickerWidth,
            items: this.accountPicker
        },{
            layout: 'fit',
            columnWidth: 1,
            items: this.configGridPanel
        }];
    },
    /**
     * add given account to this.configStore
     * 
     * @param {Tine.model.account}
     */
    addAccount: function(account) {
        var recordIndex = this.getRecordIndex(account);
        if (recordIndex === false) {
            var newRecord = {};
            newRecord[this.recordPrefix + 'name'] = account.data.name;
            newRecord[this.recordPrefix + 'type'] = account.data.type;
            newRecord[this.recordPrefix + 'id'] = account.data.id;
            
            var newData = {};
            newData[this.configStore.root] = [newRecord];
            newData[this.configStore.totalProperty] = 1;
            
            this.configStore.loadData(newData,true);
        }
        this.configGridPanel.getSelectionModel().selectRow(this.getRecordIndex(account));
    },
    /**
     * removes currently in this.configGridPanel selected rows
     */
    removeAccount: function() {
        var selectedRows = this.configGridPanel.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.configStore.remove(selectedRows[i]);
        }
    },
    /**
     * returns index of given record in this.configStore
     * 
     * @param {Tine.model.account}
     */
    getRecordIndex: function(account) {
        var id = false;
        this.configStore.each(function(item){
            if ( item.data[this.recordPrefix + 'type'] == 'user' && account.data.type == 'user' &&
                    item.data[this.recordPrefix + 'id'] == account.data.id) {
                id = item.id;
            } else if (item.data[this.recordPrefix + 'type'] == 'group' && account.data.type == 'group' &&
                    item.data[this.recordPrefix + 'id'] == account.data.id) {
                id = item.id;
            }
        }, this);
        return id ? this.configStore.indexOfId(id) : false;
    }
    
});