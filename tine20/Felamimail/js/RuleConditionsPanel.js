/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RuleConditionsPanel
 * @extends     Tine.widgets.grid.FilterToolbar
 * 
 * <p>Sieve Filter Conditions Panel</p>
 * <p>
 * mapping when getting filter values:
 *  field       -> test_header or 'size'
 *  operator    -> comperator
 *  value       -> key
 * </p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleConditionsPanel
 */
Tine.Felamimail.RuleConditionsPanel = Ext.extend(Tine.widgets.grid.FilterToolbar, {
    
    defaultFilter: 'from',
    allowSaving: false,
    showSearchButton: false,
    
    // unused fn
    onFiltertrigger: Ext.emptyFn,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.rowPrefix = '';
        
        this.filterModels = Tine.Felamimail.RuleConditionsPanel.getFilterModel(this.app);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * gets filter data (use getValue() if we don't have a store/plugins)
     * 
     * @return {Array} of filter records
     */
    getAllFilterData: function() {
        return this.getValue();
    }
});

/**
 * get rule conditions for filter model and condition renderer
 * 
 * @param {} app
 * @return {Array}
 */
Tine.Felamimail.RuleConditionsPanel.getFilterModel = function(app) {
    return [
        {label: app.i18n._('From'),     field: 'from',     operators: ['contains']},
        {label: app.i18n._('To'),       field: 'to',       operators: ['contains']},
        {label: app.i18n._('Subject'),  field: 'subject',  operators: ['contains']},
        {label: app.i18n._('Size'),     field: 'size',     operators: ['greater', 'less'], valueType: 'number', defaultOperator: 'greater'}
    ];
};
