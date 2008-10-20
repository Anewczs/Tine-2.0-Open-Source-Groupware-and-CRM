/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Admin.Tags');
Tine.Admin.Tags.Main = {
    
    actions: {
        addTag: null,
        editTag: null,
        deleteTag: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addTag: function(_button, _event) {
            Tine.Admin.Tags.EditDialog.openWindow({tag: null});
        },

        /**
         * onclick handler for editBtn
         */
        editTag: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
            Tine.Admin.Tags.EditDialog.openWindow({tag: selectedRows[0]});
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteTag: function(_button, _event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected tags?'), function(_button){
                if (_button == 'yes') {
                
                    var tagIds = new Array();
                    var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        tagIds.push(selectedRows[i].id);
                    }
                    
                    tagIds = Ext.util.JSON.encode(tagIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteTags',
                            tagIds: tagIds
                        },
                        text: this.translation.gettext('Deleting tag(s)...'),
                        success: function(_result, _request){
                            Ext.getCmp('AdminTagsGrid').getStore().reload();
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.actions.addTag = new Ext.Action({
            text: this.translation.gettext('add tag'),
            handler: this.handlers.addTag,
            iconCls: 'action_tag',
            scope: this,
            disabled: !(Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags'))
        });
        
        this.actions.editTag = new Ext.Action({
            text: this.translation.gettext('edit tag'),
            disabled: true,
            handler: this.handlers.editTag,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTag = new Ext.Action({
            text: this.translation.gettext('delete tag'),
            disabled: true,
            handler: this.handlers.deleteTag,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayTagsToolbar: function()
    {
        var TagsQuickSearchField = new Ext.ux.SearchField({
            id: 'TagsQuickSearchField',
            width:240,
            emptyText: this.translation.gettext('enter searchfilter')
        }); 
        TagsQuickSearchField.on('change', function(){
            Ext.getCmp('AdminTagsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var tagsToolbar = new Ext.Toolbar({
            id: 'AdminTagsToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addTag, 
                this.actions.editTag,
                this.actions.deleteTag,
                '->', 
                this.translation.gettext('Search:'), 
                ' ',
                TagsQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(tagsToolbar);
    },

    displayTagsGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getTags'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Tag,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('name', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('TagsQuickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying tags {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No tags to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'color', header: this.translation.gettext('Color'), dataIndex: 'color', width: 25, renderer: function(color){return '<div style="width: 8px; height: 8px; background-color:' + color + '; border: 1px solid black;">&#160;</div>';} },
            { resizable: true, id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 200 },
            { resizable: true, id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description', width: 500}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags') ) {
                if(rowCount < 1) {
                    // no row selected
                    this.actions.deleteTag.setDisabled(true);
                    this.actions.editTag.setDisabled(true);
                } else if(rowCount > 1) {
                    // more than one row selected
                    this.actions.deleteTag.setDisabled(false);
                    this.actions.editTag.setDisabled(true);
                } else {
                    // only one row selected
                    this.actions.deleteTag.setDisabled(false);
                    this.actions.editTag.setDisabled(false);
                }
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'AdminTagsGrid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation.gettext('No tags to display')
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuTags', 
                items: [
                    this.actions.editTag,
                    this.actions.deleteTag,
                    '-',
                    this.actions.addTag 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            Tine.Admin.Tags.EditDialog.openWindow({tag: record});
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function()
    {
        var dataStore = Ext.getCmp('AdminTagsGrid').getStore();
            
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function() 
    {
        this.initComponent();
        
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'AdminTagsToolbar') {
            this.displayTagsToolbar();
            this.displayTagsGrid();
        }
        this.loadData();
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('AdminTagsGrid')) {
            setTimeout ("Ext.getCmp('AdminTagsGrid').getStore().reload()", 200);
        }
    }
};

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Tags.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    
    /**
     * var tag
     */
    tag: null,
    

    windowNamePrefix: 'AdminTagEditDialog_',
    id : 'tagDialog',
    layout: 'hfit',
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();
        
        if(form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
            var tag = this.tag;
            
            // fetch rights
            tag.data.rights = [];
            this.rightsStore.each(function(item){
                tag.data.rights.push(item.data);
            });
            
            // fetch contexts
            tag.data.contexts = [];
            var anycontext = true;
            var confinePanel = Ext.getCmp('adminSharedTagsConfinePanel');
            confinePanel.getRootNode().eachChild(function(node){
                if (node.attributes.checked) {
                    tag.data.contexts.push(node.id);
                } else {
                    anycontext = false;
                }
            });
            if (anycontext) {
                tag.data.contexts = ['any'];
            }
            
            form.updateRecord(tag);
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveTag', 
                    tagData: Ext.util.JSON.encode(tag.data)
                },
                success: function(response) {
                    if(window.opener.Tine.Admin.Tags) {
                        window.opener.Tine.Admin.Tags.Main.reload();
                    }
                    if(_closeWindow === true) {
                        window.close();
                    } else {
                        this.onRecordLoad(response);
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save tag.')); 
                },
                scope: this 
            });
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },

    handlerDeleteTag: function(_button, _event) {
        var tagIds = Ext.util.JSON.encode([this.tag.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteTags', 
                tagIds: tagIds
            },
            text: this.translation.gettext('Deleting tag...'),
            success: function(_result, _request) {
                if(window.opener.Tine.Admin.Tags) {
                    window.opener.Tine.Admin.Tags.Main.reload();
                }
                window.close();
            }/*,
            failure: function ( result, request) { 
                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the tag.'); 
            }*/
        });                           
    },
        
     
    /**
     * function updateRecord
     */
    updateRecord: function(_tagData) {
        // if tagData is empty (=array), set to empty object because array wont work!
        if (_tagData.length === 0) {
            _tagData = {};
        }
        this.tag = new Tine.Tinebase.Model.Tag(_tagData, _tagData.id ? _tagData.id : 0);
        
        if (!_tagData.rights) {
            _tagData.rights = [{
                tag_id: '', //todo!
                account_name: 'Anyone',
                account_id: 0,
                account_type: 'anyone',
                view_right: true,
                use_right: true
            }];
        }
        
        this.rightsStore.loadData({
            results:    _tagData.rights,
            totalcount: _tagData.rights.length
        });
        
        this.anyContext = !_tagData.contexts || _tagData.contexts.indexOf('any') > -1;
        this.createTreeNodes(_tagData.appList);
        this.getForm().loadRecord(this.tag);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights) {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }
       */

    },
    
    createTreeNodes: function(_appList) {
        // clear old childs
        var toRemove = [];
        this.rootNode.eachChild(function(node){
            toRemove.push(node);
        });
        
        for(var i=0, j=_appList.length; i<j; i++){
            // don't duplicate tree nodes on 'apply changes'
            toRemove[i] ? toRemove[i].remove() : null;
            
            var app = _appList[i];
            if (app.name == 'Tinebase' /*|| app.status == 'disabled'*/) {
                continue;
            }
            //console.log(app);
            
            this.rootNode.appendChild(new Ext.tree.TreeNode({
                text: app.name,
                id: app.id,
                checked: this.anyContext || this.tag.get('contexts').indexOf(app.id) > -1,
                leaf: true,
                icon: "s.gif"
            }));
        }
    },
    /**
     * function display
     */
    getFormContents: function() {

        /******* THE contexts box ********/
        this.rootNode = new Ext.tree.TreeNode({
            text: this.translation.gettext('Allowed Contexts'),
            expanded: true,
            draggable:false,
            allowDrop:false
        });
        var confinePanel = new Ext.tree.TreePanel({
            id: 'adminSharedTagsConfinePanel',
            rootVisible: true,
            border: false,
            root: this.rootNode
        });

        var rightsPanel = new Tine.widgets.account.ConfigGrid({
            //height: 300,
            accountPickerType: 'both',
            accountListTitle: this.translation.gettext('Account Rights'),
            configStore: this.rightsStore,
            hasAccountPrefix: true,
            configColumns: [
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('View'),
                    dataIndex: 'view_right',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('Use'),
                    dataIndex: 'use_right',
                    width: 55
                })
            ]
        });
        
        /******* THE edit dialog ********/

        /** quick hack for a color chooser **/
        var colorPicker = new Ext.form.ComboBox({
            listWidth: 150,
            readOnly:true,
            editable: false,
            name: 'color',
            fieldLabel: this.translation.gettext('Color'),
            columnWidth: .1,
            store: new Ext.data.Store({})
        });
        
        colorPicker.colorPalette = new Ext.ColorPalette({
            border: true
        });
        colorPicker.colorPalette.on('select', function(cp, color) {
            color = '#' + color;
            colorPicker.setValue(color);
            colorPicker.onTriggerClick();
        }, this);
        
        colorPicker.onTriggerClick = function() {
            if(this.disabled){
                return;
            }
            if(this.isExpanded()){
                this.collapse();
                this.el.focus();
            }else {
                colorPicker.initList();
                colorPicker.list.alignTo(colorPicker.wrap, colorPicker.listAlign);
                colorPicker.list.show();
                //if (typeof(colorPicker.colorPalette.render) == 'function') {
                    colorPicker.colorPalette.render(colorPicker.list);
                //}
            }
        };
        
        colorPicker.setValue = function(color) {
            colorPicker.el.setStyle('background', color);
            colorPicker.color = color;
        };
        colorPicker.getValue = function() {
            return colorPicker.color;
        };
        /** end of color chooser **/
        
        var editTagDialog = {
            layout:'hfit',
            border:false,
            width: 600,
            height: 350,
            items:[{
                    xtype: 'columnform',
                    border: false,
                    autoHeight: true,
                    items:[
                        [{
                            columnWidth: .3,
                            fieldLabel: this.translation.gettext('Tag Name'), 
                            name:'name',
                            allowBlank: false
                        }, {
                            columnWidth: .6,
                            name: 'description',
                            fieldLabel: this.translation.gettext('Description'),
                            anchor:'100%'
                        },
                        colorPicker
                        ]        
                    ]
                },{
                    xtype: 'tabpanel',
                    //autoHeight: true,
                    height: 300,
                    activeTab: 0,
                    deferredRender: false,
                    defaults:{autoScroll:true},
                    border: true,
                    plain: true,                    
                    items: [{
                        title: this.translation.gettext('Rights'),
                        items: [rightsPanel]
                    },{
                        title: this.translation.gettext('Context'),
                        items: [confinePanel]
                    }]
                }
            ]
        };
        
        return editTagDialog;
    },
    
    initComponent: function() {
        this.tag = this.tag ? this.tag : new Tine.Tinebase.Model.Tag({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        //this.title = title: 'Edit Tag ' + ,
        
        Ext.MessageBox.wait(this.translation._('Loading Tag...'), this.translation._('Please Wait'));
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getTag',
                tagId: this.tag.id
            }
        });
        
        this.rightsStore = new Ext.data.JsonStore({
            storeId: 'adminSharedTagsRights',
            baseParams: {
                method: 'Admin.getTagRights',
                containerId: this.tag.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            fields: [ 'account_name', 'account_id', 'account_type', 'view_right', 'use_right' ]
        });
        
        this.items = this.getFormContents();
        Tine.Admin.Tags.EditDialog.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.tag.id) {
            window.document.title = this.translation.gettext('Add New Tag');
        } else {
            window.document.title = sprintf(this.translation._('Edit Tag "%s"'), this.tag.get('name'));
        }
        
        Ext.MessageBox.hide();
    }    
    
});

/**
 * Admin Tag Edit Popup
 */
Tine.Admin.Tags.EditDialog.openWindow = function (config) {
    config.tag = config.tag ? config.tag : new Tine.Tinebase.Model.Tag({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 400,
        name: Tine.Admin.Tags.EditDialog.prototype.windowNamePrefix + config.tag.id,
        layout: Tine.Admin.Tags.EditDialog.prototype.windowLayout,
        itemsConstructor: 'Tine.Admin.Tags.EditDialog',
        itemsConstructorConfig: config
    });
    return window;
};
