/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets');

/**
 * Widget for country selection
 */
Tine.widgets.CountryCombo = Ext.extend(Ext.form.ComboBox, {
    fieldLabel: 'Country',
    displayField:'translatedName',
    valueField:'shortName',
    typeAhead: true,
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    emptyText:'Select a country...',
    selectOnFocus:true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.store = this.getCountryStore();
        Tine.widgets.CountryCombo.superclass.initComponent.call(this);
        
        this.on('focus', function(searchField){
            // initial load
            if (this.getCountryStore().getCount() == 0) {
                searchField.hasFocus = false;
                this.getCountryStore().load({
                    scope: this,
                    callback: function() {
                        searchField.hasFocus = true;
                    }
                });
            }
        }, this);
        
        this.on('select', function(searchField){
            searchField.selectText();
        },this);
    },
    /**
     * @private store has static content
     */
    getCountryStore: function(){
        var store = Ext.StoreMgr.get('Countries');
        if(!store) {
            store = new Ext.data.JsonStore({
                baseParams: {
                    method:'Tinebase.getCountryList'
                },
                root: 'results',
                id: 'shortName',
                fields: ['shortName', 'translatedName'],
                remoteSort: false
            });
            Ext.StoreMgr.add('Countries', store);
        }
        if (Tine.Tinebase.Registry.get('CountryList')) {
            store.loadData(Tine.Tinebase.Registry.get('CountryList'));
        }
        
        return store;
    },
    /**
     * @private
     * expand after store load, as this is ommited by the initial load hack
     */
    onTriggerClick: function(){
        if (this.getCountryStore().getCount() == 0) {
            this.getCountryStore().load({
                scope: this,
                callback: function() {
                    Tine.widgets.CountryCombo.superclass.onTriggerClick.call(this);
                }
            });
        } else {
            Tine.widgets.CountryCombo.superclass.onTriggerClick.call(this);
        }
        
    }
});

Ext.reg('widget-countrycombo', Tine.widgets.CountryCombo);