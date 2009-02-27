/**
 * Tine 2.0
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Erp');

/**
 * Erp Edit Dialog
 */
Tine.Erp.ContractGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Erp.Contract,
    
    // grid specific
    defaultSortInfo: {field: 'title', dir: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'title'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Erp.JsonBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins.push(this.filterToolbar);
        
        Tine.Erp.ContractGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n.n_('Contract', 'Contract', 1),    field: 'query',    operators: ['contains']}
                //{label: this.app.i18n._('Summary'), field: 'summary' }
             ],
             defaultFilter: 'query',
             filters: []
        });
    },    
    
    /**
     * open contract edit dialog
     */
    onEditInNewWindow: function(_button, _event) {
        if (_button.actionType == 'edit') {
            var selectedRows = this.grid.getSelectionModel().getSelections();
            var record = selectedRows[0];
        } else {
        	var record = {};
        }
        var containerId = Tine.Erp.registry.get('containerId'); 
        
        var popupWindow = Tine.Erp.ContractEditDialog.openWindow({
            record: record,
            containerId: containerId,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.store.load({});
                }
            }
        });    	
    },
    
    /**
     * returns cm
     * @private
     * 
     * @todo    add more columns
     */
    getColumns: function(){
        return [{
            id: 'number',
            header: this.app.i18n._("Contract number"),
            width: 100,
            sortable: true,
            dataIndex: 'number'
        },{
            id: 'title',
            header: this.app.i18n._("Title"),
            width: 200,
            sortable: true,
            dataIndex: 'title'
        },{
            id: 'status',
            header: this.app.i18n._("Status"),
            width: 100,
            sortable: true,
            dataIndex: 'status'
        }];
    }  
});