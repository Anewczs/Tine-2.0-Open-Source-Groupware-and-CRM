/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         perhaps we should load the settings only if settings tab is clicked
 * TODO         remove deprecated code
 * TODO         don't use json stores for lines/rights
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Phone Edit Dialog
 */
Tine.Voipmanager.SnomPhoneEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'SnomPhoneEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomPhone,
    recordProxy: Tine.Voipmanager.SnomPhoneBackend,
    evalGrants: false,
    
    /**
     * @property {Ext.data.JsonStore}
     */
    rightsStore: null,
    
    /**
     * @property {Ext.data.JsonStore}
     */
    linesStore: null,

    /**
     * @property {Tine.Voipmanager.LineGridPanel}
     */
    linesGrid: null,
    
    /**
     * max lines (depending on phone model)
     * 
     * @type Number
     */
    maxLines: 4,
    
    /**
     * writeable fields (from phone settings)
     * 
     * @type Object
     */
    writeableFields: null,
    
    /**
     * @private
     * 
     */
    initComponent: function() {
        
        // why the hack is this a jsonStore???
        this.rightsStore =  new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.SnomPhoneRight
        });
        
        // why the hack is this a jsonStore???
        this.linesStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.SnomLine
        });
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * record load (get rights and put them into the store)
     */
    onRecordLoad: function() {
    	
        var rights = this.record.get('rights') || [];
        this.rightsStore.loadData({results: rights});
        
        var lines = this.record.get('lines') || [];
        this.linesStore.loadData({results: lines});

        /* @deprecated
        if (this.record.get('current_model')) {
        	this.addEmptyLines(this.getMaxLines(this.record.get('current_model')));
        }
        */
        
        if (this.record.get('setting_id')) {
            this.getWriteableFields(this.record.get('setting_id'));
        }
        
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * record update (push rights and lines into record property)
     */
    onRecordUpdate: function() {
        Tine.Voipmanager.SnomPhoneEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('rights', '');
        this.record.set('lines', '');
        
        var rights = [];
        this.rightsStore.each(function(_record){
            rights.push(_record.data);
        });
        this.record.set('rights', rights);
        
        var lines = [];
        this.linesStore.each(function(_record){
            lines.push(_record.data);
        });
        this.record.set('lines', lines);
    },
    
    /**
     * max lines
     * 
     * @todo this data is already in some data array in voipmanager.js
     * @param {} _val
     * @return {}
     * @deprecated
     */
    /*
    getMaxLines: function(_val) {      
        var _data = new Object();
        _data.snom300 = '4';
        _data.snom320 = '12';
        _data.snom360 = '12';
        _data.snom370 = '12';   
         
        if(!_val) {
            return _data;
        }        
        return _data[_val];
    },
    */    
    
    /**
     * 
     * @param {} maxLines
     * @deprecated
     */
    /*
    addEmptyLines: function(maxLines) {
        while (this.linesStore.getCount() < maxLines) {
            _snomRecord = new Tine.Voipmanager.Model.SnomLine({
                'asteriskline_id':'',
                'id':'',
                'idletext':'',
                'lineactive':0,
                'linenumber':this.linesStore.getCount()+1,
                'snomphone_id':'',
                'name': ''
            });         
            this.linesStore.add(_snomRecord);
        }                                            	
    },
    */
    
    /**
     * 
     * @param {} setting_id
     */
    getWriteableFields: function(setting_id) {
    	
        Ext.Ajax.request({
            params: {
                method: 'Voipmanager.getSnomSetting', 
                id: setting_id
            },
            success: function(_result, _request) {
                _data = Ext.util.JSON.decode(_result.responseText);
                _writableFields = new Array('web_language','language','display_method','mwi_notification','mwi_dialtone','headset_device','message_led_other','global_missed_counter','scroll_outgoing','show_local_line','show_call_status','call_waiting');
                this.writeableFields = new Object();
                var _settingsData = new Object();

                Ext.each(_writableFields, function(_item, _index, _all) {
                    _rwField = _item.toString() + '_writable';

                    // update record
                    if(_data[_rwField] == '0' || !this.record.get(_item)) {
                        this.record.set(_item, _data[_item]);                                                        
                    }                                                       

                    // set writeable fields
                    this.getForm().findField(_item).setDisabled(_data[_rwField] == '0');
                    this.getForm().findField(_item).setValue(this.record.get(_item));
                }, this);                                     
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert('Failed', 'No settings data found.'); 
            },
            scope: this 
        });                                   
    	
    },
    
    /**
     * update settings on template change
     * 
     * @param {} _combo
     * @param {} _record
     * @param {} _index
     */
    onTemplateChange: function(_combo, _record, _index) {

    	var setting_id = false;
    	if (_record.data && _record.data.setting_id) {
    		setting_id = _record.data.setting_id;
    	}
    	
    	if (! setting_id && this.getForm().findField('template_id').store.getById(this.getForm().findField('template_id').getValue())) {
    	    setting_id = this.getForm().findField('template_id').store.getById(this.getForm().findField('template_id').getValue()).data.setting_id;
    	}
    	
    	if (setting_id) {
            this.getWriteableFields(setting_id);
    	}
    },
    
    /**
     * on model change
     * 
     * @param {} _combo
     * @param {} _record
     * @param {} _index
     * 
     * TODO remove lines?
     * @deprecated ?
     */
    /*
    onModelChange: function(_combo, _record, _index) {

    	while (this.linesStore.getCount() > this.getMaxLines(_record.data.id) ) {
            var _id = this.linesStore.getCount();
            this.linesStore.remove(this.linesStore.getAt((_id-1)));
        }
  
        // add empty rows to grid
        this.addEmptyLines(this.getMaxLines(_record.data.id));
    },
    */
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        this.linesGrid = new Tine.Voipmanager.LineGridPanel({
            title: this.app.i18n._('Lines'),
            store: this.linesStore,
            app: this.app
        });
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            deferredRender: false,
            items:[
                this.getPhonePanel(),
                this.linesGrid,
                //this.getLinesPanel(),
                this.getSettingsPanel(),
                this.getRightsPanel()
            ]
        };
    },
    
    /**
     * returns general phone panel (first panel)
     * 
     * @return {Object}
     */
    getPhonePanel: function() {
        return {
            title: this.app.i18n._('Phone'),
            layout: 'hfit',
            frame: true,
            border: false,
            items: [{
                xtype: 'columnform',
                formDefaults: {
                    columnWidth: 0.5,
                    anchor: '100%',
                    labelSeparator: ''
                },
                items: [
                    [{
                        xtype: 'textfield',
                        name: 'description',
                        fieldLabel: this.app.i18n._('Name'),
                        allowBlank: false
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n._('Phone Model'),
                        name: 'current_model',
                        mode: 'local',
                        displayField: 'model',
                        valueField: 'id',
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        /*
                        listeners: {
                        	scope: this,
                            select: this.onModelChange
                        },
                        */
                        store: Tine.Voipmanager.Data.loadPhoneModelData()
                    }], [{
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('MAC Address'),
                        name: 'macaddress',
                        maxLength: 12,
                        allowBlank: false
                    }, {
                        xtype:'reccombo',
                        name: 'template_id',
                        fieldLabel: this.app.i18n.n_('Template', 'Templates', 1),
                        displayField: 'name',
                        store: new Ext.data.Store({
                            fields: Tine.Voipmanager.Model.SnomTemplate,
                            proxy: Tine.Voipmanager.SnomTemplateBackend,
                            reader: Tine.Voipmanager.SnomTemplateBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'name', dir: 'ASC'}
                        }),
                        listeners: {
                            scope: this,
                            select: this.onTemplateChange
                        }
                    }], [{
                        xtype:'reccombo',
                        name: 'location_id',
                        fieldLabel: this.app.i18n.n_('Location', 'Locations', 1),
                        displayField: 'name',
                        store: new Ext.data.Store({
                        	fields: Tine.Voipmanager.Model.SnomLocation,
                            proxy: Tine.Voipmanager.SnomLocationBackend,
                            reader: Tine.Voipmanager.SnomLocationBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'name', dir: 'ASC'}
                        })
                    }]
                ]}, {
                    title: this.app.i18n._('infos'),
                    autoHeight: true,
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    items: [{
                        xtype: 'columnform',
                        border: false,
                        formDefaults: {
                            columnWidth: 0.5,
                            anchor: '100%',
                            labelSeparator: ''
                        },
                        items: [
                            [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Current IP Address'),
                                name: 'ipaddress',
                                maxLength: 20,
                                anchor: '98%',
                                readOnly: true
                            }, {
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Firmware last checked at'),
                                name: 'firmware_checked_at',
                                anchor: '100%',
                                emptyText: 'never',
                                hideTrigger: true,
                                readOnly: true
                            }], [{
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('Current Software Version'),
                                name: 'current_software',
                                maxLength: 20,
                                anchor: '98%',
                                readOnly: true
                            }, {
                                xtype: 'datetimefield',
                                fieldLabel: this.app.i18n._('Settings Loaded at'),
                                name: 'settings_loaded_at',
                                anchor: '100%',
                                emptyText: 'never',
                                hideTrigger: true,
                                readOnly: true
                            }]
                        ]
                    }]
                }/*, {
                    title: this.app.i18n._('redirection'),
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    autoHeight: true,
                    items: [{
                        xtype: 'columnform',
                        border: false,
                        formDefaults: {
                            columnWidth: 0.333,
                            anchor: '100%',
                            labelSeparator: ''
                        },
                        items: [
                            [{ 
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('redirect_event'),
                                name: 'redirect_event',
                                mode: 'local',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                value: 'all',
                                listeners: {
                                    select: function(_combo, _record, _index) {
                                        if (_record.data.id == 'time') {
                                            Ext.getCmp('redirect_time').enable();
                                        }
                                        
                                        if(_record.data.id != 'time') {                                                   
                                            Ext.getCmp('redirect_time').disable();
                                        }
                                    }
                                },
                                store: [
                                    ['all', this.app.i18n._('all')],
                                    ['busy', this.app.i18n._('busy')],
                                    ['none', this.app.i18n._('none')],
                                    ['time', this.app.i18n._('time')]
                                ]
                            }, {
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('redirect_number'),
                                name: 'redirect_number'
                            }, {
                                xtype: 'numberfield',
                                fieldLabel: this.app.i18n._('redirect_time'),
                                name: 'redirect_time',
                                id: 'redirect_time',
                                anchor: '100%'                                                                     
                           }]
                        ]  
                    }]
                }*/]
            };
    },
    
    /**
     * returns settings panel (second panel)
     * 
     * @return {Object}
     */
    getSettingsPanel: function() {
        return {
            title: this.app.i18n._('Settings'),
            id: 'settingsBorderLayout',
            frame: true,
            border: false,
            xtype: 'columnform',
            formDefaults: {
                xtype:'combo',
                anchor: '100%',
                labelSeparator: '',
                columnWidth: .333,
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                value: null
            },
            items: [
                [{
                    fieldLabel: this.app.i18n._('web_language'),
                    name: 'web_language',
                    disabled: (this.writeableFields) ? this.writeableFields.web_language : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['English', Locale.getTranslationData('Language', 'en')],
                        ['Deutsch', Locale.getTranslationData('Language', 'de')],
                        ['Espanol', Locale.getTranslationData('Language', 'es')],
                        ['Francais', Locale.getTranslationData('Language', 'fr')],
                        ['Italiano', Locale.getTranslationData('Language', 'it')],
                        ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                        ['Portugues', Locale.getTranslationData('Language', 'pt')],
                        ['Suomi', Locale.getTranslationData('Language', 'fi')],
                        ['Svenska', Locale.getTranslationData('Language', 'sv')],
                        ['Dansk', Locale.getTranslationData('Language', 'da')],
                        ['Norsk', Locale.getTranslationData('Language', 'no')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('language'),
                    name: 'language',
                    disabled: (this.writeableFields) ? this.writeableFields.language : true,                                    
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                                                                               
                        ['English', Locale.getTranslationData('Language', 'en')],
                        ['English(UK)', Locale.getTranslationData('Language', 'en_GB')],
                        ['Deutsch', Locale.getTranslationData('Language', 'de')],
                        ['Espanol', Locale.getTranslationData('Language', 'es')],
                        ['Francais', Locale.getTranslationData('Language', 'fr')],
                        ['Italiano', Locale.getTranslationData('Language', 'it')],
                        ['Cestina', Locale.getTranslationData('Language', 'cs')],
                        ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                        ['Polski', Locale.getTranslationData('Language', 'pl')],
                        ['Portugues', Locale.getTranslationData('Language', 'pt')],
                        ['Slovencina', Locale.getTranslationData('Language', 'sl')],
                        ['Suomi', Locale.getTranslationData('Language', 'fi')],
                        ['Svenska', Locale.getTranslationData('Language', 'sv')],
                        ['Dansk', Locale.getTranslationData('Language', 'da')],
                        ['Norsk', Locale.getTranslationData('Language', 'no')],                                            
                        ['Japanese', Locale.getTranslationData('Language', 'ja')],
                        ['Chinese', Locale.getTranslationData('Language', 'zh')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('display_method'),
                    name: 'display_method',
                    disabled: (this.writeableFields) ? this.writeableFields.display_method : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                                                                
                        ['full_contact', this.app.i18n._('whole url')],
                        ['display_name', this.app.i18n._('name')],
                        ['display_number', this.app.i18n._('number')],
                        ['display_name_number', this.app.i18n._('name + number')],
                        ['display_number_name', this.app.i18n._('number + name')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('call_waiting'),
                    name: 'call_waiting',
                    disabled: (this.writeableFields) ? this.writeableFields.call_waiting : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['on', this.app.i18n._('on')],
                        ['visual', this.app.i18n._('visual')],
                        ['ringer', this.app.i18n._('ringer')],
                        ['off', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('mwi_notification'),
                    name: 'mwi_notification',
                    disabled: (this.writeableFields) ? this.writeableFields.mwi_notification : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['silent', this.app.i18n._('silent')],
                        ['beep', this.app.i18n._('beep')],
                        ['reminder', this.app.i18n._('reminder')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('mwi_dialtone'),
                    name: 'mwi_dialtone',
                    disabled: (this.writeableFields) ? this.writeableFields.mwi_dialtone : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['normal', this.app.i18n._('normal')],
                        ['stutter', this.app.i18n._('stutter')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('headset_device'),
                    name: 'headset_device',
                    disabled: (this.writeableFields) ? this.writeableFields.headset_device : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],                                        
                        ['none', this.app.i18n._('none')],
                        ['headset_rj', this.app.i18n._('headset_rj')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('message_led_other'),
                    name: 'message_led_other',
                    disabled: (this.writeableFields) ? this.writeableFields.message_led_other : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('global_missed_counter'),
                    name: 'global_missed_counter',
                    disabled: (this.writeableFields) ? this.writeableFields.global_missed_counter : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')], 
                        ['0', this.app.i18n._('off')]
                    ]
                }], [{
                    fieldLabel: this.app.i18n._('scroll_outgoing'),
                    name: 'scroll_outgoing',
                    disabled: (this.writeableFields) ? this.writeableFields.scroll_outgoing : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_local_line'),
                    name: 'show_local_line',
                    disabled: (this.writeableFields) ? this.writeableFields.show_local_line : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('show_call_status'),
                    name: 'show_call_status',
                    disabled: (this.writeableFields) ? this.writeableFields.show_call_status : true,
                    store: [
                        [ null,  this.app.i18n._('- factory default -')],
                        ['1', this.app.i18n._('on')],
                        ['0', this.app.i18n._('off')]
                    ]
                }]
            ]
        };
    },
    
    /**
     * returns the lines panel (thrid panel)
     * 
     * @return {Object}
     * @deprecated
     */
    /*
    getLinesPanel: function() {
        var linesText = [];
        var linesSIPCombo = [];
        var linesIdleText = [];
        var linesActive = [];
        
        var checkColumn = new Ext.ux.grid.CheckColumn({
           header: this.app.i18n._('lineactive'),
           dataIndex: 'lineactive',
           width: 25
        });         
        
        var combo = new Ext.form.ComboBox({
            typeAhead: true,
            triggerAction: 'all',
            lazyRender:true,
            displayField:'name',
            valueField:'id',
            anchor:'98%',                    
            triggerAction: 'all',
            allowBlank: false,
            editable: false,
            store: new Ext.data.Store({
                fields: Tine.Voipmanager.Model.AsteriskSipPeer,
                proxy: Tine.Voipmanager.AsteriskSipPeerBackend,
                remoteSort: true,
                sortInfo: {field: 'name', dir: 'ASC'}
            })
        });

        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: 'line', dataIndex: 'id', width: 20, hidden: true },
            {
                resizable: true,
                id: 'sipCombo',
                header: this.app.i18n._('sipCombo'),
                dataIndex: 'asteriskline_id',
                width: 80,
                editor: combo,
                renderer: function (value, b, record) {
                    if (record.data && record.data.name) {
                    	return record.data.name;
                    } else {
                    	if(combo.store.getById(value)) {
                            return combo.store.getById(value).get('name');
                    	} else {
                    		return '';
                    	}
                    }
                }
            },
            {
                resizable: true,
                id: 'idletext',
                header: this.app.i18n._('Idle Text'),
                dataIndex: 'idletext',
                width: 40,
                editor: new Ext.form.TextField({
                   allowBlank: false,
                   allowNegative: false,
                   maxLength: 60
               })  
            },
            checkColumn
        ]); 
        
        var gridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            id: 'Voipmanager_PhoneLines_Grid',
            store: this.linesStore,
            cm: columnModel,
            autoSizeColumns: false,
            plugins:checkColumn,
            clicksToEdit:1,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'idleText',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No software to display'
            })            
        });

    	return {
            title: this.app.i18n._('Lines'),
            layout: 'fit',
            items: [
                gridPanel
            ]
        };
    },
    */
    
    /**
     * returns right panel (fourth panel)
     * 
     * @return {Object}
     */
    getRightsPanel: function() {
        return {
            title: this.app.i18n._('Users'),
            layout: 'fit',
            items: new Tine.widgets.account.PickerGridPanel({
                accountPickerType: 'both',
                store: this.rightsStore,
                hasAccountPrefix: true
            })
        };
    }
});


/**
 * Snom Phone Edit Popup
 */
Tine.Voipmanager.SnomPhoneEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 450,
        name: Tine.Voipmanager.SnomPhoneEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomPhoneEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
