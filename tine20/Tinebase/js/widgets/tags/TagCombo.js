/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.tags');

Tine.widgets.tags.TagCombo = Ext.extend(Ext.ux.form.ClearableComboBox, {
    /**
     * @cfg {String} app Application which uses this panel
     */
    app: '',
    /**
     * @cfg {Bool} findGlobalTags true to find global tags during search (default: true)
     */
    findGlobalTags: true,
    
    id: 'TagCombo',
    emptyText: null,
    typeAhead: true,
    //editable: false,
    mode: 'remote',
    triggerAction: 'all',
    displayField:'name',
    valueField:'id',
    width: 100,
    
    /**
     * @private
     */
    initComponent: function() {
        this.emptyText = this.emptyText ? this.emptyText : _('tag name');
        
        this.store = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.Tinebase.Model.Tag,
            baseParams: {
                method: 'Tinebase.searchTags',
                filter: Ext.util.JSON.encode({
                    application: this.app
                }),
                paging : Ext.util.JSON.encode({})
            }
        });
        Tine.widgets.tags.TagCombo.superclass.initComponent.call(this);
        
        this.on('select', function(){
            var v = this.getValue();
            if(String(v) !== String(this.startValue)){
                this.fireEvent('change', this, v, this.startValue);
            }
        }, this);
    }
});