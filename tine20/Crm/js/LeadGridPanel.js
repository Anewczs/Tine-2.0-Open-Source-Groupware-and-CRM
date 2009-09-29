/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * Lead grid panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.GridPanel
 * @extends     Tine.Tinebase.widgets.app.GridPanel
 * 
 * <p>Lead Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.GridPanel
 */
Tine.Crm.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Crm.Model.Lead} recordClass
     */
    recordClass: Tine.Crm.Model.Lead,
    
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: true,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'lead_name', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'title'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Crm.recordBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.cm = this.getColumnModel();
        //this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        //this.plugins.push(this.filterToolbar);
        
        Tine.Crm.GridPanel.superclass.initComponent.call(this);
        
        //this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Crm', 'records'));
        //this.action_editInNewWindow.requiredGrant = 'editGrant';
        
    },
    
    /**
     * initialises filter toolbar
     *  @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                // @todo add filtes
                /*
                {label: this.app.i18n._('Lead'),    field: 'query',       operators: ['contains']},
                {label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
                new Tine.Crm.TimeAccountStatusGridFilter({
                    field: 'status'
                }),
                */
                new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'query',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO    add more columns
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [/*{
                id: 'number',
                header: this.app.i18n._("Number"),
                width: 100,
                sortable: true,
                dataIndex: 'number'
            },{
                id: 'title',
                header: this.app.i18n._("Title"),
                width: 350,
                sortable: true,
                dataIndex: 'title'
            },{
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 150,
                sortable: true,
                dataIndex: 'status',
                renderer: this.statusRenderer.createDelegate(this)
            },{
                id: 'budget',
                header: this.app.i18n._("Budget"),
                width: 100,
                sortable: true,
                dataIndex: 'budget'
            }*/]
        });
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * return additional tb items
     * @private
     */
    getToolbarItems: function(){
        /*
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        */
        
        return [
            /*
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
            */
        ];
    }    
});
