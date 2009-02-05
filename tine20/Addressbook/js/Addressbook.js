﻿Ext.namespace('Tine.Addressbook');

/**************************** panel ****************************************/

Tine.Addressbook = {

    getPanel: function()
    {
        var translation = new Locale.Gettext();
        translation.textdomain('Addressbook');
    	
        var accountBackend = Tine.Tinebase.registry.get('accountBackend');
        if (accountBackend == 'Sql') {
            var internalContactsleaf = {
                text: translation._("Internal Contacts"),
                cls: "file",
                containerType: 'internal',
                id: "internal",
                children: [],
                leaf: false,
                expanded: true
            };
        }
        
        var treePanel =  new Tine.widgets.container.TreePanel({
            id: 'Addressbook_Tree',
            iconCls: 'AddressbookIconCls',
            title: translation._('Addressbook'),
            containersName: translation._('contacts'),
            containerName: 'addressbook',
            appName: 'Addressbook',
            border: false,
            extraItems: internalContactsleaf ? internalContactsleaf : [] 
        });
        
        treePanel.on('click', function(_node, _event) {
            Tine.Addressbook.Main.show(_node);
        }, this);
        
        // executed when adb gets activated
        treePanel.on('beforeexpand', function(_panel) {
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
        
        return treePanel;       
    }
};

/**************************** main dialog **********************************/

Tine.Addressbook.Main = {
	actions: {
	    addContact: null,
	    editContact: null,
	    deleteContact: null,
	    exportContact: null,
	    callContact: null
	},
	
    /**
     * holds underlaying store
     */
    store: null,
    
    /**
     * @cfg {Object} paging defaults
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'n_family',
        dir: 'ASC'
    },
    
    filterToolbar: null,
    
    /**
     * @cfg {Array} default filters
     * @todo container filters not in filter logic yet!
     * @see store.on(beforeload)
     */
    //filter: [],
    	
	handlers: {
	    /**
	     * onclick handler for addBtn
	     */
	    addContact: function(_button, _event) {
            var selectedNode = Ext.getCmp('Addressbook_Tree').getSelectedContainer();
            Tine.Addressbook.ContactEditDialog.openWindow({
                forceContainer: selectedNode,
                listeners: {
                    scope: this,
                    'update': this.onRecordUpdate
                }
            });
        },

        /**
         * onclick handler for editBtn
         */
        editContact: function(_button, _event) {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
            Tine.Addressbook.ContactEditDialog.openWindow({
                contact: selectedRows[0],
                listeners: {
                    scope: this,
                    'update': this.onRecordUpdate
                }
            });
        },

        /**
         * onclick handler for exportBtn
         */
        exportContact: function(_button, _event) {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();            
            var toExportIds = [];
        
            for (var i = 0; i < selectedRows.length; ++i) {
                toExportIds.push(selectedRows[i].data.id);
            }
            
            var contactIds = Ext.util.JSON.encode(toExportIds);

            Tine.Tinebase.common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContacts&_format=pdf&_filter=' + contactIds, 768, 1024);
        },

        /**
         * onclick handler for exportBtn
         */
        callContact: function(_button, _event) {
            var number;

            var contact = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelected();

            switch(_button.getId()) {
                case 'Addressbook_Contacts_CallContact_Work':
                    number = contact.data.tel_work;
                    break;
                case 'Addressbook_Contacts_CallContact_Home':
                    number = contact.data.tel_home;
                    break;
                case 'Addressbook_Contacts_CallContact_Cell':
                    number = contact.data.tel_cell;
                    break;
                case 'Addressbook_Contacts_CallContact_CellPrivate':
                    number = contact.data.tel_cell_private;
                    break;
                default:
                    if(!Ext.isEmpty(contact.data.tel_work)) {
                    	number = contact.data.tel_work;
                    } else if (!Ext.isEmpty(contact.data.tel_cell)) {
                        number = contact.data.tel_cell;
                    } else if (!Ext.isEmpty(contact.data.tel_cell_private)) {
                        number = contact.data.tel_cell_private;
                    } else if (!Ext.isEmpty(contact.data.tel_home)) {
                    	number = contact.data.tel_work;
                    }
                    break;
            }

            Tine.Phone.dialNumber(number);
            /*
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Phone.dialNumber',
                    number: number
                },
                success: function(_result, _request){
                    //Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
                },
                failure: function(result, request){
                    //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
                }
            });
            */
        },
        
        
	    /**
	     * onclick handler for deleteBtn
	     */
	    deleteContact: function(btn, e) {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
	        Ext.MessageBox.confirm(
                this.translation._('Are you Sure?'), 
                this.translation.ngettext('Do you really want to delete the selected contact?',
                                          'Do you really want to delete the selected contacts?', 
                                          selectedRows.length),
                function(_button){
	            if (_button == 'yes') {
	            
	                var contactIds = [];
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    contactIds.push(selectedRows[i].data.id);
	                }
	                
	                contactIds = Ext.util.JSON.encode(contactIds);
	                
	                Ext.Ajax.request({
	                    url: 'index.php',
                        scope: this,
	                    params: {
	                        method: 'Addressbook.deleteContacts',
	                        _contactIds: contactIds
	                    },
	                    text: this.translation.ngettext('Deleting contact...', 'Deleting contacts...', selectedRows.length),
	                    success: function(_result, _request){
	                        Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert(
                                this.translation._('Failed'),
                                this.translation.ngettext('Some error occured while trying to delete the contact.',
                                                          'Some error occured while trying to delete the contacts.',
                                                          selectedRows.length
                              ));
	                    }
	                });
	            }
	        }, this);
	    }    
	},
	
	renderer: {
        contactTid: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            //console.log(_record.get('container_id').type);
            switch(_record.get('container_id').type) {
                case 'internal':
                    return "<img src='images/oxygen/16x16/actions/user-female.png' width='12' height='12' alt='contact' ext:qtip='" + Tine.Addressbook.Main.translation._("Internal Contacts") + "'/>";
                default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
            }
	    }		
	},

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        this.translation = this.app.i18n;
    
        this.actions.addContact = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.translation._('add contact'),
            handler: this.handlers.addContact,
            iconCls: 'action_addContact',
            scope: this
        });
        
        this.actions.editContact = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.translation._('edit contact'),
            disabled: true,
            handler: this.handlers.editContact,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteContact = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: 'delete contact',
            pluralText: 'delete contacts',
            translationObject: this.translation,
            text: this.translation.ngettext('delete contact', 'delete contacts', 1),
            disabled: true,
            handler: this.handlers.deleteContact,
            iconCls: 'action_delete',
            scope: this
        });

        this.actions.exportContact = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            text: this.translation._('export as pdf'),
            disabled: true,
            handler: this.handlers.exportContact,
            iconCls: 'action_exportAsPdf',
            scope: this
        });

        this.actions.callContact = new Ext.Action({
            requiredGrant: 'readGrant',
        	id: 'Addressbook_Contacts_CallContact',
            text: this.translation._('call contact'),
            disabled: true,
            handler: this.handlers.callContact,
            iconCls: 'PhoneIconCls',
            menu: new Ext.menu.Menu({
                id: 'Addressbook_Contacts_CallContact_Menu'
            }),
            scope: this
        });
                
        // init grid store
        this.initStore();
        this.initContactsGrid();

        this.action_exportCsv = new Tine.widgets.grid.ExportButton({
            text: this.translation._('Csv Export All'),
            format: 'csv',
            exportFunction: 'Addressbook.exportContacts',
            sm: Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel()
        });

        this.initToolbar();
    },

    updateMainToolbar : function() {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        /*menu.add(
            {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
        );*/

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Addressbook.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('AddressbookTreePanel');
        preferencesButton.setDisabled(true);
    },
	
    /**
     * execuded when a record gets updated
     */
    onRecordUpdate: function() {
        this.reload();
    },
    
    initToolbar: function() {
        this.contactToolbar = new Ext.Toolbar({
            id: 'Addressbook_Contacts_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addContact, 
                this.actions.editContact,
                this.actions.deleteContact,
                '-',
                //this.action_exportCsv,
                this.actions.exportContact,
                (Tine.Phone && Tine.Tinebase.common.hasRight('run', 'Phone')) ? new Ext.Toolbar.MenuButton(this.actions.callContact) : ''
            ]
        });
    },

    initContactsGrid: function() {
        // the filter toolbar
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            id : 'addressbookFilterToolbar',
            filterModels: [
                {label: this.translation._('Contact'),    field: 'query',    operators: ['contains']},
                {label: this.translation._('First Name'), field: 'n_given' },
                {label: this.translation._('Last Name'),  field: 'n_family'},
                {label: this.translation._('Company'),    field: 'org_name'},
                {label: this.translation._('Job Title'),    field: 'title'},
                {label: this.translation._('Job Role'),    field: 'role'},
                new Tine.widgets.tags.TagFilter({app: this.app}),
                //{label: this.translation._('Birthday'),    field: 'bday', valueType: 'date'},
                {label: this.translation._('Street') + ' (' + this.translation._('Company Address') + ')',      field: 'adr_one_street', defaultOperator: 'equals'},
                {label: this.translation._('Postal Code') + ' (' + this.translation._('Company Address') + ')', field: 'adr_one_postalcode', defaultOperator: 'equals'},
                {label: this.translation._('City') + '  (' + this.translation._('Company Address') + ')',       field: 'adr_one_locality'},
                {label: this.translation._('Street') + ' (' + this.translation._('Private Address') + ')',      field: 'adr_two_street', defaultOperator: 'equals'},
                {label: this.translation._('Postal Code') + ' (' + this.translation._('Private Address') + ')', field: 'adr_two_postalcode', defaultOperator: 'equals'},
                {label: this.translation._('City') + '  (' + this.translation._('Private Address') + ')',       field: 'adr_two_locality'}
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        this.filterToolbar.on('change', function() {
            this.store.load({});
        }, this);
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying contacts {0} - {1} of {2}'),
            emptyMsg: this.translation._("No contacts to display")
        });
        // mark next grid refresh as paging-refresh
        pagingToolbar.on('beforechange', function() {
            Ext.getCmp('Addressbook_Contacts_Grid').getView().isPagingRefresh = true;
        }, this);
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'tid', header: this.translation._('Type'), dataIndex: 'tid', width: 30, renderer: this.renderer.contactTid },
            { resizable: true, id: 'n_family', header: this.translation._('Last Name'), dataIndex: 'n_family', hidden: true },
            { resizable: true, id: 'n_given', header: this.translation._('First Name'), dataIndex: 'n_given', width: 80, hidden: true },
            { resizable: true, id: 'n_fn', header: this.translation._('Full Name'), dataIndex: 'n_fn', hidden: true },
            { resizable: true, id: 'n_fileas', header: this.translation._('Display Name'), dataIndex: 'n_fileas'},
            { resizable: true, id: 'org_name', header: this.translation._('Company'), dataIndex: 'org_name', width: 200 },
            { resizable: true, id: 'org_unit', header: this.translation._('Unit'), dataIndex: 'org_unit' , hidden: true },
            { resizable: true, id: 'title', header: this.translation._('Job Title'), dataIndex: 'title', hidden: true },
            { resizable: true, id: 'role', header: this.translation._('Job Role'), dataIndex: 'role', hidden: true },
            { resizable: true, id: 'room', header: this.translation._('Room'), dataIndex: 'room', hidden: true },
            { resizable: true, id: 'adr_one_street', header: this.translation._('Street'), dataIndex: 'adr_one_street', hidden: true },
            { resizable: true, id: 'adr_one_locality', header: this.translation._('City'), dataIndex: 'adr_one_locality', width: 150, hidden: false },
            { resizable: true, id: 'adr_one_region', header: this.translation._('Region'), dataIndex: 'adr_one_region', hidden: true },
            { resizable: true, id: 'adr_one_postalcode', header: this.translation._('Postalcode'), dataIndex: 'adr_one_postalcode', hidden: true },
            { resizable: true, id: 'adr_one_countryname', header: this.translation._('Country'), dataIndex: 'adr_one_countryname', hidden: true },
            { resizable: true, id: 'adr_two_street', header: this.translation._('Street (private)'), dataIndex: 'adr_two_street', hidden: true },
            { resizable: true, id: 'adr_two_locality', header: this.translation._('City (private)'), dataIndex: 'adr_two_locality', hidden: true },
            { resizable: true, id: 'adr_two_region', header: this.translation._('Region (private)'), dataIndex: 'adr_two_region', hidden: true },
            { resizable: true, id: 'adr_two_postalcode', header: this.translation._('Postalcode (private)'), dataIndex: 'adr_two_postalcode', hidden: true },
            { resizable: true, id: 'adr_two_countryname', header: this.translation._('Country (private)'), dataIndex: 'adr_two_countryname', hidden: true },
            { resizable: true, id: 'email', header: this.translation._('Email'), dataIndex: 'email', width: 150},
            { resizable: true, id: 'tel_work', header: this.translation._('Phone'), dataIndex: 'tel_work', hidden: false },
            { resizable: true, id: 'tel_cell', header: this.translation._('Mobile'), dataIndex: 'tel_cell', hidden: false },
            { resizable: true, id: 'tel_fax', header: this.translation._('Fax'), dataIndex: 'tel_fax', hidden: true },
            { resizable: true, id: 'tel_car', header: this.translation._('Car phone'), dataIndex: 'tel_car', hidden: true },
            { resizable: true, id: 'tel_pager', header: this.translation._('Pager'), dataIndex: 'tel_pager', hidden: true },
            { resizable: true, id: 'tel_home', header: this.translation._('Phone (private)'), dataIndex: 'tel_home', hidden: true },
            { resizable: true, id: 'tel_fax_home', header: this.translation._('Fax (private)'), dataIndex: 'tel_fax_home', hidden: true },
            { resizable: true, id: 'tel_cell_private', header: this.translation._('Mobile (private)'), dataIndex: 'tel_cell_private', hidden: true },
            { resizable: true, id: 'email_home', header: this.translation._('Email (private)'), dataIndex: 'email_home', hidden: true },
            { resizable: true, id: 'url', header: this.translation._('Web'), dataIndex: 'url', hidden: true },
            { resizable: true, id: 'url_home', header: this.translation._('URL (private)'), dataIndex: 'url_home', hidden: true },
            { resizable: true, id: 'note', header: this.translation._('Note'), dataIndex: 'note', hidden: true },
            { resizable: true, id: 'tz', header: this.translation._('Timezone'), dataIndex: 'tz', hidden: true },
            { resizable: true, id: 'geo', header: this.translation._('Geo'), dataIndex: 'geo', hidden: true },
            { resizable: true, id: 'bday', header: this.translation._('Birthday'), dataIndex: 'bday', hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable

		// define clear preview tpl
		var clearTpl = new Ext.Template(	
			'<div class="preview-panel-timesheet-nobreak">',	
	            '<!-- Preview contacts -->',
				'<div class="preview-panel preview-panel-timesheet-left">',
					'<div class="bordercorner_1"></div>',
					'<div class="bordercorner_2"></div>',
					'<div class="bordercorner_3"></div>',
					'<div class="bordercorner_4"></div>',
					'<div class="preview-panel-declaration">contacts</div>',
					'<div class="preview-panel-timesheet-leftside preview-panel-left">',
						'<span class="preview-panel-bold">',
							this.translation._('Select contact') + '<br/>',
							'<br/>',
							'<br/>',
							'<br/>',
						'</span>',
					'</div>',
					'<div class="preview-panel-timesheet-rightside preview-panel-left">',
						'<span class="preview-panel-nonbold">',
							'<br/>',
							'<br/>',
							'<br/>',
							'<br/>',
						'</span>',
					'</div>',
				'</div>',
				'<!-- Preview xxx -->',
				'<div class="preview-panel-timesheet-right">',
					'<div class="bordercorner_gray_1"></div>',
					'<div class="bordercorner_gray_2"></div>',
					'<div class="bordercorner_gray_3"></div>',
					'<div class="bordercorner_gray_4"></div>',
					'<div class="preview-panel-declaration"></div>',
					'<div class="preview-panel-timesheet-leftside preview-panel-left">',
						'<span class="preview-panel-bold">',
							'<br/>',
							'<br/>',
							'<br/>',
							'<br/>',
						'</span>',
					'</div>',
					'<div class="preview-panel-timesheet-rightside preview-panel-left">',
						'<span class="preview-panel-nonbold">',
							'<br/>',
							'<br/>',
							'<br/>',
							'<br/>',
						'</span>',
					'</div>',
				'</div>',
			'</div>'		
		);
		
        // the rowselection model
        var rowSelectionModel = new Tine.Tinebase.widgets.grid.FilterSelectionModel({
            store: this.store
        });

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            // update toolbars
            Tine.widgets.actionUpdater(_selectionModel, this.actions, 'container_id');
            
            var rowCount = _selectionModel.getCount();
            if(rowCount < 1) {
                clearTpl.overwrite(Ext.getCmp('adr-preview-panel').body);    
            }  else if (rowCount == 1) {
                // only one row selected
                if((Tine.Phone && Tine.Tinebase.common.hasRight('run', 'Phone'))) {
	                var callMenu = Ext.menu.MenuMgr.get('Addressbook_Contacts_CallContact_Menu');
	                callMenu.removeAll();
	                var contact = _selectionModel.getSelected();
	                if(!Ext.isEmpty(contact.data.tel_work)) {
		                callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Work', 
		                   text: this.translation._('Work') + ' ' + contact.data.tel_work + '',
		                   handler: this.handlers.callContact
		                });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_home)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Home', 
	                       text: this.translation._('Home') + ' ' + contact.data.tel_home + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_cell)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Cell', 
	                       text: this.translation._('Cell') + ' ' + contact.data.tel_cell + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_cell_private)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_CellPrivate', 
	                       text: this.translation._('Cell private') + ' ' + contact.data.tel_cell_private + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
                }
            }
        }, this);

        // define a template to use for the detail view
        // @todo add tags?
        var detailTpl = new Ext.XTemplate(
            '<tpl for=".">',
				'<div class="preview-panel-adressbook-nobreak">',
                '<div class="preview-panel-left">',                
                    '<!-- Preview image -->',
                    '<div class="preview-panel preview-panel-left preview-panel-image">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<img src="{jpegphoto}"/>',
                    '</div>',
                
                    '<!-- Preview office -->',
                    '<div class="preview-panel preview-panel-office preview-panel-left">',                
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">buero</div>',
                        '<div class="preview-panel-address preview-panel-left">',
                            '<span class="preview-panel-bold">{[this.encode(values.org_name, "mediumtext")]}{[this.encode(values.org_unit, "prefix", " / ")]}</span><br/>',
                            '{[this.encode(values.adr_one_street)]}<br/>',
                            '{[this.encode(values.adr_one_postalcode, " ")]}{[this.encode(values.adr_one_locality)]}<br/>',
                            '{[this.encode(values.adr_one_region, " / ")]}{[this.encode(values.adr_one_countryname, "country")]}<br/>',
                        '</div>',
                        '<div class="preview-panel-contact preview-panel-right">',
                            '<span class="preview-panel-symbolcompare">' + this.translation._('Phone') + '</span>{[this.encode(values.tel_work)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.translation._('Mobile') + '</span>{[this.encode(values.tel_cell)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.translation._('Fax') + '</span>{[this.encode(values.tel_fax)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.translation._('E-Mail') + '</span><a href="mailto:{[this.encode(values.email)]}">{[this.encode(values.email, "shorttext")]}</a><br/>',
                            '<span class="preview-panel-symbolcompare">' + this.translation._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url, "shorttext")]}</a><br/>',
                        /*
                            '<img src="images/oxygen/16x16/apps/kcall.png"/> 
                            '<img src="images/oxygen/16x16/apps/phone.png"/> 
                            '<img src="images/oxygen/16x16/apps/printer.png"/>
                            '<img src="images/oxygen/16x16/apps/kontact-mail.png"/>
                            '<img src="images/oxygen/16x16/apps/network.png"/>
                        */
                        '</div>',
                    '</div>',
                '</div>',

                '<!-- Preview privat -->',
                '<div class="preview-panel preview-panel-privat preview-panel-left">',                
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">privat</div>',
                    '<div class="preview-panel-address preview-panel-left">',
                        '<span class="preview-panel-bold">{[this.encode(values.n_fn)]}</span><br/>',
                        '{[this.encode(values.adr_two_street)]}<br/>',
                        '{[this.encode(values.adr_two_postalcode, " ")]}{[this.encode(values.adr_two_locality)]}<br/>',
                        '{[this.encode(values.adr_two_region, " / ")]}{[this.encode(values.adr_two_countryname, "country")]}<br/>',
                    '</div>',
                    '<div class="preview-panel-contact preview-panel-right">',
                        '<span class="preview-panel-symbolcompare">' + this.translation._('Phone') + '</span>{[this.encode(values.tel_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.translation._('Mobile') + '</span>{[this.encode(values.tel_cell_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.translation._('Fax') + '</span>{[this.encode(values.tel_fax_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.translation._('E-Mail') + '</span><a href="mailto:{[this.encode(values.email)]}">{[this.encode(values.email_home, "shorttext")]}</a><br/>',
                        '<span class="preview-panel-symbolcompare">' + this.translation._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url_home, "shorttext")]}</a><br/>',
                        /*
                        '<!-- <img src="images/oxygen/16x16/apps/kcall.png"/>--> <span class="preview-panel-symbolcompare">phone</span>0404040<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/phone.png"/>--> <span class="preview-panel-symbolcompare">mobil</span>040404<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/printer.png"/>--> <span class="preview-panel-symbolcompare">fax</span>04040<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/kontact-mail.png"/>--> <span class="preview-panel-symbolcompare">mail</span><a href="mailto:mai@me.dd">mai@me.dd</a><br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/network.png"/>--> <span class="preview-panel-symbolcompare">web</span><a href="{[this.encode(values.url_home)]}" target="_blank">{[this.encode(values.url_home, "shorttext")]}</a><br/>',
                        */
                    '</div>',                
                '</div>',
                
                '<!-- Preview info -->',
                '<div class="preview-panel-description preview-panel-left">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">info</div>',
                    '{[this.encode(values.note, "longtext")]}',
                '</div>',
                '</div>',
                //  '{[this.getTags(values.tags)]}',
            '</tpl>',
        	{
                encode: function(value, type, prefix) {
                	//var metrics = Ext.util.TextMetrics.createInstance('previewPanel');
                	if (value) {
                		if (type) {
                			switch (type) {
                				case 'country':
                				    value = Locale.getTranslationData('Territory', value);
                				    break;
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 135);
                                    break;
                                case 'mediumtext':
                                    value = Ext.util.Format.ellipsis(value, 30);
                                    break;
                                case 'shorttext':
                                    //console.log(metrics.getWidth(value));
                                    value = Ext.util.Format.ellipsis(value, 18);
                                    break;
                                case 'prefix':
                                    if (prefix) {
                                        value = prefix + value;
                                    }
                                    break;
                                default:
                                    value += type;
                			}                			
                		}
                		value = Ext.util.Format.htmlEncode(value);
                        return Ext.util.Format.nl2br(value);
                	} else {
                		return '';
                	}
                },
                getTags: function(value) {
                	var result = '';
                	for (var i=0; i<value.length; i++) {
                		result += value[i].name + ' ';
                	}
                	return result;
                }
            }
        );		
        
        rowSelectionModel.on('rowselect', function(sm, rowIdx, r) {
            var detailPanel = Ext.getCmp('adr-preview-panel');
            detailTpl.overwrite(detailPanel.body, r.data);
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Addressbook_Contacts_Grid',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'n_family',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation._('No contacts to display'),
                onLoad: Ext.emptyFn,
                listeners: {
                    beforerefresh: function(v) {
                        v.scrollTop = v.scroller.dom.scrollTop;
                    },
                    refresh: function(v) {
                        // on paging-refreshes (prev/last...) we don't preserv the scroller state
                        if (v.isPagingRefresh) {
                            v.scrollToTop();
                            v.isPagingRefresh = false;
                        } else {
                            v.scroller.dom.scrollTop = v.scrollTop;
                        }
                    }
                }
            })              
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
		        id:'ctxMenuContacts', 
		        items: [
		            this.actions.editContact,
		            this.actions.deleteContact,
		            this.actions.exportContact,
		            '-',
		            this.actions.addContact 
		        ]
		    });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            Tine.Addressbook.ContactEditDialog.openWindow({
                contact: record,
                listeners: {
                    scope: this,
                    'update': this.onRecordUpdate
                }
            });
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteContact.call(this);
             }
        }, this);

        // temporary resizeing
        this.filterToolbar.on('bodyresize', function(ftb, w, h) {
            /*
            var layout = Ext.getCmp('adr-filtertoolbar-panel');
            layout.setHeight(h);
            if (layout.rendered) {
                layout.el.setSize(w,h);
                console.log(layout);
                layout.doLayout();
                //layout.layout();
            }
            */
            var c = Ext.getCmp('center-panel');
            var p = Ext.getCmp('adr-preview-panel');
            if (c.rendered && p.rendered) {
                var availableGridHeight = Ext.getCmp('center-panel').getSize().height - Ext.getCmp('adr-preview-panel').getSize().height - h;
                gridPanel.setHeight(availableGridHeight);
            }
            
        }, this);
        this.gridPanel = new Ext.Panel({
            layout: 'border',
            border: false,
            items: [/*{
                id: 'adr-filtertoolbar-panel',
                region: 'north',
                layout: 'fit',
                border: false,
                collapsible:true,
                collapseMode: 'mini',
                //collapsed: true,
                split: true,
                height: 27,
                items: filterToolbar
            }, */{
                id: 'adr-center-panel',
                region: 'center',
                border: false,
                layout: 'fit',              
                tbar: this.filterToolbar,
                items: gridPanel
            },{
                // the new preview panel
                id: 'adr-preview-panel',
                region: 'south',
                collapsible:true,
                collapseMode: 'mini',
                //collapsed: true,
                split: true,
                layout: 'fit',
                height: 125,
				html: clearTpl.applyTemplate({})
            }]
        });
        this.gridPanel.on('resize', function(panel) {
            //panel.syncSize();
            /*
            panel.items.each(function(item) {
                console.log(item);
                if (item.rendered){
                item.syncSize();
                }
            }, this);
            */
        }, this);
    },
    
    /**
     * init the contacts json grid store
     */
    initStore: function() {

        this.store = new Ext.data.JsonStore({
        	id: 'id',
            autoLoad: false,
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Addressbook.Model.Contact,
            remoteSort: true,
            baseParams: {
                method: 'Addressbook.searchContacts'
            },
            sortInfo: {
                field: this.paging.sort,
                direction: this.paging.dir
            }
        });
        
        // register store
        Ext.StoreMgr.add('ContactsGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            if (!options.params) {
                options.params = {};
            }
            
            // paging toolbar only works with this properties in the options!
            options.params.sort  = store.getSortState() ? store.getSortState().field : this.paging.sort;
            options.params.dir   = store.getSortState() ? store.getSortState().direction : this.paging.dir;
            options.params.start = options.params.start ? options.params.start : this.paging.start;
            options.params.limit = options.params.limit ? options.params.limit : this.paging.limit;
            
            options.params.paging = Ext.util.JSON.encode(options.params);
            
            var filterToolbar = Ext.getCmp('addressbookFilterToolbar');
            var filter = filterToolbar ? filterToolbar.getValue() : [];
            
            // add container to filter
            var nodeAttributes = Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode().attributes || {};
            filter.push(
                {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
            );
            
            options.params.filter = Ext.util.JSON.encode(filter);
        }, this);
        
        // save used filter
        this.store.on('load', function(store, records, options) {
            this.store.lastFilter = Ext.util.JSON.decode(options.params.filter);
        }, this)
    },
    
    show: function(_node) {
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Addressbook_Contacts_Toolbar') {
            if (! this.gridPanel) {
               this.initComponent();
            }
            Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
            this.store.load({});
            Tine.Tinebase.MainScreen.setActiveToolbar(this.contactToolbar, true);
            this.updateMainToolbar();
            
        } else {
            // note: if node is clicked, it is not selected!
            _node.getOwnerTree().selectPath(_node.getPath());
            this.store.load({});  
        }
    },
    
    reload: function() {
        if(Ext.ComponentMgr.all.containsKey('Addressbook_Contacts_Grid')) {
            setTimeout ("Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload()", 200);
        }
    }
};

/**************************** edit dialog **********************************/

/**
 * The edit dialog
 * @constructor
 * @class Tine.Addressbook.ContactEditDialog
 */  
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * @cfg {Tine.Addressbook.Model.Contact}
     */
    contact: null,
    /**
     * @cfg {Object} container
     */
    forceContainer: null,    
    /**
     * @private
     */
    windowNamePrefix: 'AddressbookEditWindow_',
    
    /**
     * @private!
     */
    id: 'contactDialog',
    layout: 'hfit',
    appName: 'Addressbook',
    containerProperty: 'container_id',
    showContainerSelector: true,
    
    initComponent: function() {
        if (! this.contact) {
            this.contact = new Tine.Addressbook.Model.Contact({}, 0);
        }
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onContactLoad,
            params: {
                method: 'Addressbook.getContact',
                contactId: this.contact.id
            }
        });
        
        //this.containerItemName = this.translation._('contacts');
        this.containerName = this.translation._('addressbook');
        this.containersName = this.translation._('contacts');
        
        // export lead handler for edit contact dialog
        var exportContactButton = new Ext.Action({
            id: 'exportButton',
            text: this.translation.gettext('export as pdf'),
            handler: this.handlerExport,
            iconCls: 'action_exportAsPdf',
            disabled: false,
            scope: this
        });
        
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});  

        this.tbarItems = [exportContactButton, addNoteButton];
        this.items = Tine.Addressbook.ContactEditDialog.getEditForm(this.contact);
        
        Tine.Addressbook.ContactEditDialog.superclass.initComponent.call(this);
    },
    
	onRender: function(ct, position) {
        Tine.Addressbook.ContactEditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Contact...'), _('Please Wait'));
    },
    
    onContactLoad: function(response) {
        this.getForm().findField('n_prefix').focus(false, 250);
        var contactData = Ext.util.JSON.decode(response.responseText);
        if (this.forceContainer) {
            contactData.container = this.forceContainer;
            // only force initially!
            this.forceContainer = null;
        }
        this.updateContactRecord(contactData);
        
        if (! this.contact.id) {
            this.window.setTitle(this.translation.gettext('Add new contact'));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit Contact "{0}"'), this.contact.get('n_fn') + 
                (this.contact.get('org_name') ? ' (' + this.contact.get('org_name') + ')' : '')));
        }
        
        this.getForm().loadRecord(this.contact);
        this.updateToolbars(this.contact, 'container_id');
        Ext.getCmp('addressbookeditdialog-jpegimage').setValue(this.contact.get('jpegphoto'));

        Ext.MessageBox.hide();
    },
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        var form = this.getForm();

        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.) 
        //       and not required fields should be disabled (n_given, n_family, etc.) 
        if(form.isValid() 
            && (form.findField('n_family').getValue() !== ''
                || form.findField('org_name').getValue() !== '') ) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait a moment...'), this.translation.gettext('Saving Contact'));
            form.updateRecord(this.contact);
            this.contact.set('jpegphoto', Ext.getCmp('addressbookeditdialog-jpegimage').getValue());
    
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Addressbook.saveContact', 
                    contactData: Ext.util.JSON.encode(this.contact.data)
                },
                success: function(response) {
                    this.onContactLoad(response);

                    this.fireEvent('update', this.contact); 
                	
                    if(_closeWindow === true) {
                      	this.purgeListeners();
                        this.window.close();
                    } else {
                        this.updateToolbarButtons(this.contact);
                        
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save contact.')); 
                } 
            });
        } else {
            form.findField('n_family').markInvalid();
            form.findField('org_name').markInvalid();
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));        	
        }
    },

    handlerDelete: function(_button, _event) {
        var contactIds = Ext.util.JSON.encode([this.contact.data.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Addressbook.deleteContacts', 
                _contactIds: contactIds
            },
            text: this.translation.gettext('Deleting contact...'),
            success: function(_result, _request) {
                this.fireEvent('update', this.contact);
                this.window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the contact.')); 
            } 
        });                           
    },
    
    handlerExport: function(_button, _event) {
    	// we have to create an array (json encoded) as param here because exportContact expects one (for multiple contact export)
    	var contactIds = Ext.util.JSON.encode([this.contact.id]);

        Tine.Tinebase.common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContacts&_format=pdf&_filter=' + contactIds, 200, 150);                   
    },
    
    updateContactRecord: function(_contactData) {
        if(_contactData.bday && _contactData.bday !== null) {
            _contactData.bday = Date.parseDate(_contactData.bday, Date.patterns.ISO8601Long);
        }

        this.contact = new Tine.Addressbook.Model.Contact(_contactData, _contactData.id ? _contactData.id : 0);
    },
    
    updateToolbarButtons: function(contact) {
        this.updateToolbars.defer(10, this, [contact, 'container_id']);
        
        // add contact id to export button and enable it if id is set
        var contactId = contact.get('id');
        if (contactId) {
        	Ext.getCmp('exportButton').contactId = contactId;
            Ext.getCmp('exportButton').setDisabled(false);
        } else {
        	Ext.getCmp('exportButton').setDisabled(true);
        }
    }
});

/**
 * Addressbook Edit Popup
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    config.contact = config.contact ? config.contact : new Tine.Addressbook.Model.Contact({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        layout: Tine.Addressbook.ContactEditDialog.prototype.windowLayout,
        name: Tine.Addressbook.ContactEditDialog.prototype.windowNamePrefix + config.contact.id,
        contentPanelConstructor: 'Tine.Addressbook.ContactEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

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
    {name: 'geo'},
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
    {name: 'notes'}
];

/**
 * @type {Tine.Tinebase.Record}
 * Contact record definition
 */
Tine.Addressbook.Model.Contact = Tine.Tinebase.Record.create(Tine.Addressbook.Model.ContactArray, {
    appName: 'Addressbook',
    modelName: 'Contact',
    idProperty: 'id',
    titleProperty: 'n_fn',
    // ngettext('Contact', 'Contacts', n);
    recordName: 'Contact',
    recordsName: 'Contacts',
    containerProperty: 'container_id',
    // ngettext('addressbook', 'addressbooks', n);
    containerName: 'addressbook',
    containersName: 'addressbooks'
});
/* not possible yet as we don't have the container client side
Tine.Addressbook.Model.Contact.getDefaultData = function() { 

};*/

/**
 * default timesheets backend
 */
Tine.Addressbook.contactBackend = new Tine.Tinebase.widgets.app.JsonBackend({
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

