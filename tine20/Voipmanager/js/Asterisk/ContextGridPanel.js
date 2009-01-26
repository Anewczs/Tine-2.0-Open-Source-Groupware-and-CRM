/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
 *
 */
 
Ext.namespace('Tine.Voipmanager.Asterisk');

/**
 * Context grid panel
 */
Tine.Voipmanager.Asterisk.ContextGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.Asterisk.Context,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.Asterisk.contextBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Voipmanager.Asterisk.ContextGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                //{label: this.app.i18n._('Context'),    field: 'query',    operators: ['contains']}, // query only searches description
                new Tine.Voipmanager.Asterisk.ContextGridFilter(),
                {label: this.app.i18n._('Name'),      field: 'name' },
                {label: this.app.i18n._('Description'),  field: 'description' },
                new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'description',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("id"),
            width: 10,
            sortable: true,
            hidden: true,
            dataIndex: 'id'
        }, {
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 100,
            sortable: true,
            dataIndex: 'name',
            renderer: function(name) {
            	return Ext.util.Format.htmlEncode(name);
            }
        }, {
            id: 'description',
            header: this.app.i18n._("Description"),
            width: 350,
            sortable: true,
            dataIndex: 'description',
            renderer: function(description) {
            	return Ext.util.Format.htmlEncode(description);
            }
        }];
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
       
        return [

        ];
    } 
});