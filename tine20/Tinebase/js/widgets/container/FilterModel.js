/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.container');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.FilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.widgets.container.FilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    /**
     * @cfg {Array} operators allowed operators
     */
    operators: ['equals', 'in'],
    //operators: ['personalNode', 'specialNode', 'equals', 'in'],
    
    /**
     * @cfg {String} field container field (defaults to container_id)
     */
    field: 'container_id',
    
    /**
     * @cfg {String} defaultOperator default operator, one of <tt>{@link #operators} (defaults to equals)
     */
    defaultOperator: 'equals',
    
    /**
     * @cfg {String} defaultValue default value (defaults to all)
     */
    defaultValue: 'all',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.containerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
        this.containersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        
        this.label = this.containerName;
        
        /*
        // define custom operators
        this.customOperators = [
            {operator: 'specialNode',label: _('sub of')},
            {operator: 'personalNode',label: _('personal of')}
        ];
        */
    },
    
    /**
     * returns valueType of given filter
     * 
     * @param {Record} filter
     * @return {String}
     */
    getValueType: function(filter) {
        var operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator;
        
        var valueType = 'selectionComboBox';
        switch (operator) {
            case 'in':
                valueType = 'FilterModelMultipleValueField';
                break;
        }
        
        return valueType;
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        this.supr().onOperatorChange.call(this, filter, newOperator);
        
        var valueType = this.getValueType(filter);
        
        for (var valueField in filter.valueFields) {
            filter.valueFields[valueField][valueField == valueType ? 'show' : 'hide']();
        };
        
        filter.formFields.value = filter.valueFields[valueType];
        filter.formFields.value.setWidth(filter.formFields.value.width);
        filter.formFields.value.wrap.setWidth(filter.formFields.value.width);
        filter.formFields.value.syncSize();
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var valueType = this.getValueType(filter);
        
        filter.valueFields = {};
        filter.valueFields.selectionComboBox = new Tine.widgets.container.selectionComboBox({
            hidden: valueType != 'selectionComboBox',
            app: this.app,
            filter: filter,
            width: 200,
            listWidth: 200,
            //id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            allowNodeSelect: true,
            recordClass: this.recordClass,
            appName: this.recordClass.getMeta('appName'),
            containerName: this.containerName,
            containersName: this.containersName,
            getValue: function() {
                return this.selectedContainer ? this.selectedContainer.path : null;
            },
            setValue: function(value) {
                var operatorText = this.filter.data.operator === 'personalNode' ? _('is personal of') : _('is equal to');
                
                // use equals for node 'My containers'
                if (value.path && value.path === '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId) {
                    operatorText = _('is equal to')
                }
                
                //this.filter.formFields.operator.setText(operatorText);
                return Tine.widgets.container.selectionComboBox.prototype.setValue.call(this, value);
            },
            listeners: {
                scope: this, 
                select: this.onFiltertrigger,
                specialkey: function(field, e){
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                }
            }
        });
        
        filter.valueFields.FilterModelMultipleValueField = new Tine.widgets.container.FilterModelMultipleValueField({
            hidden: valueType != 'FilterModelMultipleValueField',
            app: this.app,
            recordClass: this.recordClass,
            containerName: this.containerName,
            containersName: this.containersName,
            filter: filter,
            width: 200,
            //id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        
        return filter.valueFields[valueType];
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.widget.container.filtermodel'] = Tine.widgets.container.FilterModel;


/**
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.FilterModelMultipleValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.widgets.container.FilterModelMultipleValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    layerAlign : 'tr-br?',
    minLayerWidth: 400,
    layerHeight: 300,
    
    lazyInit: true,
    
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    initComponent: function() {
        //this.fakeRecord = new Tine.widgets.container.Model.Event(Tine.widgets.container.Model.Event.getDefaultData());
        
        this.on('beforecollapse', this.onBeforeCollapse, this);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false
            },
            columns:  [
                {id: 'name', header: String.format(_('Selected  {0}'), this.containersName), dataIndex: 'name'}
            ]
        });
    },
    
    getFormValue: function() {
        //this.containerGridPanel.onRecordUpdate(this.fakeRecord);
        //return this.fakeRecord.get('attendee');
    },
    
    getItems: function() {
        var containerSelectionCombo = this.containerSelectionCombo = new Tine.widgets.container.selectionComboBox({
            allowNodeSelect: true,
            allowBlank: true,
            blurOnSelect: true,
            recordClass: this.recordClass,
            appName: this.recordClass.getMeta('appName'),
            containerName: this.containerName,
            containersName: this.containersName,
            listeners: {
                scope: this, 
                select: this.onContainerSelect
            }
        });
                
        this.containerGridPanel = new Tine.widgets.grid.PickerGridPanel({
            height: this.layerHeight || 'auto',
            recordClass: Tine.Tinebase.Model.Container,
            autoExpandColumn: 'name',
            getColumnModel: this.getColumnModel.createDelegate(this),
            initActionsAndToolbars: function() {
                Tine.widgets.grid.PickerGridPanel.prototype.initActionsAndToolbars.call(this);
                
                this.tbar = new Ext.Toolbar({
                    layout: 'fit',
                    items: [
                        containerSelectionCombo
                    ]
                });
            }
        });
        var items = [this.containerGridPanel];
        
        return items;
    },
    
    /**
     * cancel collapse if ctx menu is shown
     */
    onBeforeCollapse: function() {
        return (!this.containerGridPanel.contextMenu || this.containerGridPanel.contextMenu.hidden) &&
                !this.containerSelectionCombo.isExpanded() && !this.containerSelectionCombo.dlg;
    },
    
    onContainerSelect: function(field, containerRecord) {
        var existingRecord = this.containerGridPanel.store.getById(containerRecord.id);
        if (! existingRecord) {
            this.containerGridPanel.store.add(containerRecord);
        } else {
            var idx = this.containerGridPanel.store.indexOf(existingRecord);
            var row = this.containerGridPanel.getView().getRow(idx);
            Ext.fly(row).highlight();
        }
        
        this.containerSelectionCombo.clearValue();
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        //this.fakeRecord.set('attendee', '');
        //this.fakeRecord.set('attendee', value);
        this.currentValue = [];
        
        /*
        var attendeeStore = Tine.widgets.container.Model.Attender.getAttendeeStore(value);
        
        var a = [];
        attendeeStore.each(function(attender) {
            this.currentValue.push(attender.data);
            var name = Tine.widgets.container.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.widgets.container.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender);
            //var status = Tine.widgets.container.AttendeeGridPanel.prototype.renderAttenderStatus.call(Tine.widgets.container.AttendeeGridPanel.prototype, attender.get('status'), {}, attender);
            a.push(name);
        }, this);
        
        this.setRawValue(a.join(', '));
        */
        return this;
        
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        //this.containerGridPanel.onRecordLoad(this.fakeRecord);
    }
});

