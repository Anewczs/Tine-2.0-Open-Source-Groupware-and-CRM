﻿/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ContactGrid.js 6638 2009-02-09 11:56:32Z c.weiss@metaways.de $
 *
 */

// appName translation: _('Addressbook')
Ext.namespace('Tine.Addressbook');


/******************************* main screen **********************************/
Tine.Addressbook.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    activeContentType: 'Contact',
    
    setContentPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        
        if (! this[type + 'GridPanel']) {
            this[type + 'GridPanel'] = new Tine[this.app.appName][type + 'GridPanel']({
                app: this.app,
                plugins: [this.treePanel.getFilterPlugin()]
            });
            
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[type + 'GridPanel'], true);
        this[type + 'GridPanel'].store.load();
    },
    
    getContentPanel: function() {
        // which content panel?
        var type = this.activeContentType;
        return this[type + 'GridPanel'];
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        var type = this.activeContentType;
        
        if (! this[type + 'ActionToolbar']) {
            this[type + 'ActionToolbar'] = this[type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[type + 'ActionToolbar'], true);
    }
});



/******************************* tree panel ***********************************/
Tine.Addressbook.TreePanel = function(config) {
    Ext.apply(this, config);
    
    //var accountBackend = Tine.Tinebase.registry.get('accountBackend');
    //if (accountBackend == 'Sql') {
       this.extraItems = [{
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._("Internal Contacts"),
            cls: "file",
            containerType: 'internal',
            id: "internal",
            children: [],
            leaf: false,
            expanded: true
        }];
    //}
    
    this.id = 'Addressbook_Tree';
    this.recordClass = Tine.Addressbook.Model.Contact;
    Tine.Addressbook.TreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Addressbook.TreePanel , Tine.widgets.container.TreePanel);

Tine.Addressbook.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Addressbook.FilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.FilterPanel, Tine.widgets.grid.PersistentFilterPicker, {
    filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ContactFilter'}]
});


/**
 * get salutation store
 * if available, load data from initial data
 * 
 * @return Ext.data.JsonStore with salutations
 */
Tine.Addressbook.getSalutationStore = function() {
    
    var store = Ext.StoreMgr.get('AddressbookSalutationStore');
    if (!store) {

        store = new Ext.data.JsonStore({
            fields: Tine.Addressbook.Model.Salutation,
            baseParams: {
                method: 'Addressbook.getSalutations'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if (Tine.Addressbook.registry.get('Salutations')) {
            store.loadData(Tine.Addressbook.registry.get('Salutations'));
        }
        
            
        Ext.StoreMgr.add('AddressbookSalutationStore', store);
    }
    
    return store;
};

/**************************** models ***************************************/

Ext.namespace('Tine.Addressbook.Model');

Tine.Addressbook.Model.ContactArray = [
    {name: 'id'},
    {name: 'tid'},
    {name: 'container_id'},
    {name: 'private'},
    {name: 'cat_id'},
    {name: 'n_family'},
    {name: 'n_given'},
    {name: 'n_middle'},
    {name: 'n_prefix'},
    {name: 'n_suffix'},
    {name: 'n_fn'},
    {name: 'n_fileas'},
    {name: 'bday', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    {name: 'org_name'},
    {name: 'org_unit'},
    {name: 'salutation_id'},
    {name: 'title'},
    {name: 'role'},
    {name: 'assistent'},
    {name: 'room'},
    {name: 'adr_one_street'},
    {name: 'adr_one_street2'},
    {name: 'adr_one_locality'},
    {name: 'adr_one_region'},
    {name: 'adr_one_postalcode'},
    {name: 'adr_one_countryname'},
    {name: 'label'},
    {name: 'adr_two_street'},
    {name: 'adr_two_street2'},
    {name: 'adr_two_locality'},
    {name: 'adr_two_region'},
    {name: 'adr_two_postalcode'},
    {name: 'adr_two_countryname'},
    {name: 'tel_work'},
    {name: 'tel_cell'},
    {name: 'tel_fax'},
    {name: 'tel_assistent'},
    {name: 'tel_car'},
    {name: 'tel_pager'},
    {name: 'tel_home'},
    {name: 'tel_fax_home'},
    {name: 'tel_cell_private'},
    {name: 'tel_other'},
    {name: 'tel_prefer'},
    {name: 'email'},
    {name: 'email_home'},
    {name: 'url'},
    {name: 'url_home'},
    {name: 'freebusy_uri'},
    {name: 'calendar_uri'},
    {name: 'note'},
    {name: 'tz'},
    {name: 'lon'},
    {name: 'lat'},
    {name: 'pubkey'},
    {name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'created_by',         type: 'int'                  },
    {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'last_modified_by',   type: 'int'                  },
    {name: 'is_deleted',         type: 'boolean'              },
    {name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'deleted_by',         type: 'int'                  },
    {name: 'jpegphoto'},
    {name: 'account_id'},
    {name: 'tags'},
    {name: 'notes'},
    {name: 'relations'},
    {name: 'customfields'},
    {name: 'type'}
];

/**
 * @type {Tine.Tinebase.data.Record}
 * Contact record definition
 */
Tine.Addressbook.Model.Contact = Tine.Tinebase.data.Record.create(Tine.Addressbook.Model.ContactArray, {
    appName: 'Addressbook',
    modelName: 'Contact',
    idProperty: 'id',
    titleProperty: 'n_fn',
    // ngettext('Contact', 'Contacts', n); gettext('Contacts');
    recordName: 'Contact',
    recordsName: 'Contacts',
    containerProperty: 'container_id',
    // ngettext('addressbook', 'addressbooks', n); gettext('addressbooks');
    containerName: 'addressbook',
    containersName: 'addressbooks'
});

/* not usefull as long we don't use full records in the gird
Tine.Addressbook.Model.Contact.getDefaultData = function() { 
    var data = {};
    
    
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    //var treeNode = app.getMainScreen().getTreePanel().getSelectionModel().getSelectedNode();
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
        data.container_id = treeNode.attributes.container;
    } else {
        data.container_id = Tine.Addressbook.registry.get('defaultAddressbook');
    }
    
    return data;
};*/

Tine.Addressbook.Model.Contact.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['contact', app.i18n._('Contact')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: _('Quick search'),                                                      field: 'query',              operators: ['contains']},
        {label: app.i18n._('First Name'),                                               field: 'n_given' },
        {label: app.i18n._('Last Name'),                                                field: 'n_family'},
        {label: app.i18n._('Company'),                                                  field: 'org_name'},
        {label: app.i18n._('Phone'),                                                    field: 'telephone',          operators: ['contains']},
        {label: app.i18n._('Job Title'),                                                field: 'title'},
        {label: app.i18n._('Job Role'),                                                 field: 'role'},
        {filtertype: 'tinebase.tag', app: app},
        //{label: app.i18n._('Birthday'),    field: 'bday', valueType: 'date'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Company Address') + ')',      field: 'adr_one_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Company Address') + ')', field: 'adr_one_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + '  (' + app.i18n._('Company Address') + ')',       field: 'adr_one_locality'},
        {label: app.i18n._('Street') + ' (' + app.i18n._('Private Address') + ')',      field: 'adr_two_street',     defaultOperator: 'equals'},
        {label: app.i18n._('Postal Code') + ' (' + app.i18n._('Private Address') + ')', field: 'adr_two_postalcode', defaultOperator: 'equals'},
        {label: app.i18n._('City') + ' (' + app.i18n._('Private Address') + ')',        field: 'adr_two_locality'},
        {label: app.i18n._('Type'), defaultValue: 'contact', valueType: 'combo',        field: 'type',               store: typeStore}
    ];
};
    
/**
 * default timesheets backend
 */
Tine.Addressbook.contactBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Addressbook',
    modelName: 'Contact',
    recordClass: Tine.Addressbook.Model.Contact
});

/**
 * salutation model
 */
Tine.Addressbook.Model.Salutation = Ext.data.Record.create([
   {name: 'id'},
   {name: 'name'},
   {name: 'gender'}
]);

