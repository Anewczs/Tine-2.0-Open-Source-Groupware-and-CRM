/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.widgets', 'Tine.widgets.tree');

/**
 * returns generic tree context menu with
 * - create/add
 * - rename
 * - delete
 * - edit grants
 * 
 * ctxNode class var is required in calling class
 */
Tine.widgets.tree.ContextMenu = {
	
    /**
     * create new Ext.menu.Menu with actions
     * 
     * @param {} config has the node name, actions, etc.
     * @return {}
     */
	getMenu: function(config) {
        
        /***************** define action handlers *****************/
        var handler = {
            /**
             * create
             */
            addNode: function() {
                Ext.MessageBox.prompt(String.format(_('New {0}'), config.nodeName), String.format(_('Please enter the name of the new {0}:'), config.nodeName), function(_btn, _text) {
                    if( this.ctxNode && _btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(_('No {0} added'), config.nodeName), String.format(_('You have to supply a {0} name!'), config.nodeName));
                            return;
                        }
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Creating {0}...' ), config.nodeName));
                        var parentNode = this.ctxNode;
                        
                        var params = {
                            method: config.backend + '.add' + config.backendModel,
                            name: _text
                        };
                        
                        // TODO try to generalize this
                        if (config.backendModel == 'Container') {
                            params.application = this.appName;
                            params.containerType = parentNode.attributes.containerType;
                        } else if (config.backendModel == 'Folder') {
                            params.parent = parentNode.attributes.globalname;
                            params.accountId = parentNode.attributes.account_id;
                        }
                        
                        Ext.Ajax.request({
                            params: params,
                            scope: this,
                            success: function(_result, _request){
                                var nodeData = Ext.util.JSON.decode(_result.responseText);
                                var newNode = this.loader.createNode(nodeData);
                                parentNode.appendChild(newNode);
                                if (config.backendModel == 'Container') {
                                    this.fireEvent('containeradd', nodeData);
                                }
                                Ext.MessageBox.hide();
                            }
                        });
                        
                    }
                }, this);
            },
            
            /**
             * delete
             */
            deleteNode: function() {
                if (this.ctxNode) {
                    var node = this.ctxNode;
                    Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the {0} "{1}"?'), config.nodeName, node.text), function(_btn){
                        if ( _btn == 'yes') {
                            Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting {0} "{1}"' ), config.nodeName , node.text));
                            
                            var params = {
                                method: config.backend + '.delete' + config.backendModel
                            }
                            
                            if (config.backendModel == 'Container') {
                                params.containerId = node.attributes.container.id
                            } else if (config.backendModel == 'Folder') {
                                params.folder = node.attributes.globalname;
                                params.accountId = node.attributes.account_id;
                            } else {
                                // use default json api style
                                params.ids = [node.id];
                                params.method = params.method + 's';
                            }
                            
                            Ext.Ajax.request({
                                params: params,
                                scope: this,
                                success: function(_result, _request){
                                    if(node.isSelected()) {
                                        this.getSelectionModel().select(node.parentNode);
                                        this.fireEvent('click', node.parentNode);
                                    }
                                    node.remove();
                                    if (config.backendModel == 'Container') {
                                        this.fireEvent('containerdelete', node.attributes.container);
                                    }
                                    Ext.MessageBox.hide();
                                }
                            });
                        }
                    }, this);
                }
            },
            
            /**
             * rename
             */
            renameNode: function() {
                if (this.ctxNode) {
                    var node = this.ctxNode;
                    Ext.MessageBox.show({
                        title: 'Rename ' + config.nodeName,
                        msg: String.format(_('Please enter the new name of the {0}:'), config.nodeName),
                        buttons: Ext.MessageBox.OKCANCEL,
                        value: node.text,
                        fn: function(_btn, _text){
                            if (_btn == 'ok') {
                                if (! _text) {
                                    Ext.Msg.alert(String.format(_('Not renamed {0}'), config.nodeName), String.format(_('You have to supply a {0} name!'), config.nodeName));
                                    return;
                                }
                                Ext.MessageBox.wait(_('Please wait'), String.format(_('Updating {0} "{1}"'), config.nodeName, node.text));
                                
                                var params = {
                                    method: config.backend + '.rename' + config.backendModel,
                                    newName: _text
                                };
                                
                                // TODO try to generalize this
                                if (config.backendModel == 'Container') {
                                    params.containerId = node.attributes.container.id;
                                } else if (config.backendModel == 'Folder') {
                                    params.oldGlobalName = node.attributes.globalname;
                                    params.accountId = node.attributes.account_id;
                                }
                                
                                Ext.Ajax.request({
                                    params: params,
                                    scope: this,
                                    success: function(_result, _request){
                                        var container = Ext.util.JSON.decode(_result.responseText);
                                        node.setText(_text);
                                        if (config.backendModel == 'Container') {
                                            this.fireEvent('containerrename', container);
                                        }
                                        Ext.MessageBox.hide();
                                    }
                                });
                            }
                        },
                        scope: this,
                        prompt: true,
                        icon: Ext.MessageBox.QUESTION
                    });
                }
            },
            
            /**
             * manage permissions
             * 
             */
            managePermissions: function() {
                if (this.ctxNode) {
                    var node = this.ctxNode;
                    var window = Tine.widgets.container.GrantsDialog.openWindow({
                        title: String.format(_('Manage Permissions for {0} "{1}"'), config.nodeName, Ext.util.Format.htmlEncode(node.attributes.container.name)),
                        containerName: config.nodeName,
                        grantContainer: node.attributes.container
                    });
                }
            },
            
            /**
             * reload node
             */
            reloadNode: function() {
                if (this.ctxNode) {
                    var tree = this;
                    this.ctxNode.reload(function(node) {
                        node.expand();
                        node.select();
                        // update grid
                        tree.filterPlugin.onFilterChange();
                    });                    
                }
            }
        }
        
        /****************** create ITEMS array ****************/
        
        var items = [];
        for (var i=0; i < config.actions.length; i++) {
            switch(config.actions[i]) {
                case 'add':
                    items.push(new Ext.Action({
                        text: String.format(_('Add {0}'), config.nodeName),
                        iconCls: 'action_add',
                        handler: handler.addNode,
                        scope: config.scope
                    }));
                    break;
                case 'delete':
                    var i18n = new Locale.Gettext();
                    i18n.textdomain('Tinebase');
                    items.push(new Ext.Action({
                        text: String.format(i18n.n_('Delete {0}', 'Delete {0}', 1), config.nodeName),
                        iconCls: 'action_delete',
                        handler: handler.deleteNode,
                        scope: config.scope
                    }));
                    break;
                case 'rename':
                    items.push(new Ext.Action({
                        text: String.format(_('Rename {0}'), config.nodeName),
                        iconCls: 'action_rename',
                        handler: handler.renameNode,
                        scope: config.scope
                    }));
                    break;
                case 'grants':
                    items.push(new Ext.Action({
                        text: _('Manage permissions'),
                        iconCls: 'action_managePermissions',
                        handler: handler.managePermissions,
                        scope: config.scope
                    }));
                    break;
                case 'reload':
                    items.push(new Ext.Action({
                        text: String.format(_('Reload {0}'), config.nodeName),
                        iconCls: 'x-tbar-loading',
                        handler: handler.reloadNode,
                        scope: config.scope
                    }));
                    break;
                default:
                    // add custom actions
                    items.push(new Ext.Action(config.actions[i]));
            }
        }

        /******************* return menu **********************/
        
        return new Ext.menu.Menu({
		    items: items
		});
	}
};
