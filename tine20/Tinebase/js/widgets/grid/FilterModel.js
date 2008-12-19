/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * Model of filter
 * 
 * @constructor
 */
Tine.widgets.grid.FilterModel = function(config) {
    Ext.apply(this, config);
    Tine.widgets.grid.FilterModel.superclass.constructor.call(this);
    
    this.addEvents(
      /**
       * @event filtertrigger
       * is fired when user request to update list by filter
       * @param {Tine.widgets.grid.FilterToolbar}
       */
      'filtertrigger'
    );
    
};

Ext.extend(Tine.widgets.grid.FilterModel, Ext.Component, {
    /**
     * @cfg {String} label for the filter
     */
    label: '',
    
    /**
     * @cfg {String} name of th field to filter
     */
    field: '',
    
    /**
     * @cfg {string} type of value
     */
    valueType: 'string',
    
    /**
     * @cfg {string} default value
     */
    defaultValue: null,
    
    /**
     * @cfg {Array} valid operators
     */
    operators: null,
    
    /**
     * @cfg {String} name of the default operator
     */
    defaultOperator: null,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.FilterModel.superclass.initComponent.call(this);
        this.isFilterModel = true;
        
        if (! this.operators) {
            this.operators = [];
        }
        
        
        if (this.defaultOperator === null) {
            switch (this.valueType) {
                
                case 'date':
                    this.defaultOperator = 'within';
                    break;
                case 'account':
                case 'group':
                case 'user':
                    this.defaultOperator = 'equals';
                    break;
                case 'string':
                default:
                    this.defaultOperator = 'contains';
                    break;
            }
        }
        
        if (this.defaultValue === null) {
            switch (this.valueType) {
                case 'string':
                    this.defaultValue = '';
                    break;
                case 'date':
                case 'account':
                case 'group':
                case 'user':
                default:
                    break;
            }
        }
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
        var operatorStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: [
                {operator: 'contains', label: _('contains')},
                {operator: 'equals',   label: _('is equal to')},
                {operator: 'greater',  label: _('is greater than')},
                {operator: 'less',     label: _('is less than')},
                {operator: 'not',      label: _('is not')},
                {operator: 'in',       label: _('is in')},
                {operator: 'before',   label: _('is before')},
                {operator: 'after',    label: _('is after')},
                {operator: 'within',   label: _('is within')}
            ]
        });

        // filter operators
        if (this.operators.length == 0) {
            switch (this.valueType) {
                case 'string':
                    this.operators.push('contains', 'equals', 'not');
                    break;
                case 'date':
                    this.operators.push('equals', 'before', 'after', 'within');
                    break;
                default:
                    this.operators.push(this.defaultOperator);
                    break;
            }
        }
        
        if (this.operators.length > 0) {
            operatorStore.each(function(operator) {
                if (this.operators.indexOf(operator.get('operator')) < 0 ) {
                    operatorStore.remove(operator);
                }
            }, this);
        }
        
        if (operatorStore.getCount() > 1) {
            var operator = new Ext.form.ComboBox({
                filter: filter,
                width: 80,
                id: 'tw-ftb-frow-operatorcombo-' + filter.id,
                mode: 'local',
                lazyInit: false,
                emptyText: _('select a operator'),
                forceSelection: true,
                typeAhead: true,
                triggerAction: 'all',
                store: operatorStore,
                displayField: 'label',
                valueField: 'operator',
                value: filter.get('operator') ? filter.get('operator') : this.defaultOperator,
                renderTo: el
            });
            operator.on('select', function(combo, newRecord, newKey) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
        } else {
            var operator = new Ext.form.Label({
                filter: filter,
                width: 100,
                style: {margin: '0px 10px'},
                getValue: function() { return operatorStore.getAt(0).get('operator'); },
                text : operatorStore.getAt(0).get('label'),
                //hideLabel: true,
                //readOnly: true,
                renderTo: el
            });
        }
        
        return operator;
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        
        // for date filters we need to rerender the value section
        if (this.valueType == 'date') {
            var valueType = newOperator == 'within' ? 'withinCombo' : 'datePicker';
            
            if (valueType == 'withinCombo') {
                this.datePicker.hide();
                this.withinCombo.show();
                filter.formFields.value = this.withinCombo;
            } else {
                this.withinCombo.hide();
                this.datePicker.show();
                filter.formFields.value = this.datePicker;
            }
        }
        //console.log('operator change');
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value;
        
        switch (this.valueType) {
            case 'date':
                value = this.dateValueRenderer(filter, el);
                break;
            case 'user':
                value = new Tine.widgets.AccountpickerField({
                    filter: filter,
                    width: 200,
                    id: 'tw-ftb-frow-valuefield-' + filter.id,
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    renderTo: el
                });
                break;
            case 'string':
            default:
                value = new Ext.form.TextField({
                    //hideTrigger: true,
                    //triggerClass: 'x-form-clear-trigger',
                    filter: filter,
                    width: 200,
                    id: 'tw-ftb-frow-valuefield-' + filter.id,
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    renderTo: el,
                    listeners: {
                        scope: this,
                        specialkey: function(field, e){
                            if(e.getKey() == e.ENTER){
                                //field.trigger.setVisible(field.getValue().length > 0);
                                this.onFiltertrigger();
                            }
                        }/*,
                        change: function() {
                            //console.log('change');
                        }*/
                    }/*,
                    onTriggerClick: function() {
                        value.setValue(null);
                        //value.trigger.hide();
                        this.fireEvent('change');
                    }*/
                });
                /*
                value.on('specialkey', function(field, e){
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                }, this);
                */
                break;
        }
        
        return value;
    },
    
    /**
     * called on value change of a filter row
     * @private
     */
    onValueChange: function(filter, newValue) {
        filter.set('value', newValue);
        //console.log('value change');
    },
    
    /**
     * render a date value
     * 
     * we place a picker and a combo in the dom element and hide the one we don't need yet
     */
    dateValueRenderer: function(filter, el) {
        var operator = filter.get('operator') ? filter.filter.get('operator') : this.defaultOperator;
        var valueType = operator == 'within' ? 'withinCombo' : 'datePicker';
        
        this.withinCombo = new Ext.form.ComboBox({
            hidden: valueType != 'withinCombo',
            filter: filter,
            width: 200,
            value: filter.data.value ? filter.data.value : ( this.defaultValue ? this.defaultValue : 'weekThis'),
            renderTo: el,
            mode: 'local',
            lazyInit: false,
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: [
                ['weekThis',        _('this week')], 
                ['weekLast',        _('last week')],
                ['weekBeforeLast',  _('the week before last')],
                ['monthThis',       _('this month')],
                ['monthLast',       _('last month')],
                ['quarterThis',     _('this quarter')],
                ['quarterLast',     _('last quarter')],
                ['yearThis',        _('this year')],
                ['yearLast',        _('last year')]
            ]
        });

        this.datePicker = new Ext.form.DateField({
            hidden: valueType != 'datePicker',
            filter: filter,
            width: 200,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        
        // upps, how to get a var i only know the name of???
        return this[valueType]
    },
    
    /**
     * @private
     */
    onFiltertrigger: function() {
        this.fireEvent('filtertrigger', this);
    }
});