/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: FelamimailTreePanel.js 11338 2009-11-06 17:30:48Z p.schuele@metaways.de $
 *
 */
 
/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.TreeLoader
 * @extends     Tine.widgets.tree.Loader
 * 
 * <p>Felamimail Account/Folder Tree Loader</p>
 * <p></p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.TreeLoader
 * 
 */
Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
	
    // private
    method: 'Felamimail.searchFolders',

    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback){
        
    	// add globalname to filter
    	this.filter = [
            {field: 'account_id', operator: 'equals', value: node.attributes.account_id},
            {field: 'globalname', operator: 'equals', value: node.attributes.globalname}
        ];
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * TODO     add qtip again (problem: it can't be changed later)?
     */
    createNode: function(attr) {
        
        var account = Tine.Felamimail.loadAccountStore().getById(attr.account_id);
        
        // check for account setting
        attr.has_children = (
            account 
            && account.get('has_children_support') 
            && account.get('has_children_support') == '1'
        ) ? attr.has_children : true;
        attr.has_children = (attr.has_children == '0') ? false : attr.has_children;
        
        //var qtiptext = this.app.i18n._('Totalcount') + ': ' + attr.totalcount 
        //    + ' / ' + this.app.i18n._('Cache') + ': ' + attr.cache_status;
        
        var node = {
    		id: attr.id,
    		leaf: false,
    		text: attr.localname,
            localname: attr.localname,
    		globalname: attr.globalname,
    		account_id: attr.account_id,
            folder_id: attr.id,
    		folderNode: true,
            allowDrop: true,
            //qtip: qtiptext,
            systemFolder: (attr.system_folder == '1'),
            unreadcount: attr.unreadcount,
            totalcount: attr.totalcount,
            cache_status: attr.cache_status,
            
            // if it has no children, it shouldn't have an expand icon 
            expandable: attr.has_children,
            expanded: ! attr.has_children
    	};
        
        // if it has no children, it shouldn't have an expand icon 
        if (! attr.has_children) {
            node.children = [];
            node.cls = 'x-tree-node-collapsed';
        }

        // show standard folders icons 
        if (account) {
            if (account.get('trash_folder') == attr.globalname) {
                if (attr.totalcount > 0) {
                    node.cls = 'felamimail-node-trash-full';
                } else {
                    node.cls = 'felamimail-node-trash';
                }
            }
            if (account.get('sent_folder') == attr.globalname) {
                node.cls = 'felamimail-node-sent';
            }
        }
        if ('INBOX' == attr.globalname) {
            node.cls = 'felamimail-node-inbox';
        }
        if ('Drafts' == attr.globalname) {
            node.cls = 'felamimail-node-drafts';
        }
        if ('Templates' == attr.globalname) {
            node.cls = 'felamimail-node-templates';
        }
        if ('Junk' == attr.globalname) {
            node.cls = 'felamimail-node-junk';
        }

        // add unread class to node
        if (attr.unreadcount > 0) {
            node.text = node.text + ' (' + attr.unreadcount + ')';
            node.cls = node.cls + ' felamimail-node-unread'; // x-tree-node-collapsed';
        }
        
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    },
    
    /**
     * handle failure to show credentials dialog if imap login failed
     * 
     * @param {String} response
     * @param {Object} options
     */
    handleFailure: function(response, options) {
        var responseText = Ext.util.JSON.decode(response.responseText);
        
        if (responseText.message == 'cannot login, user or password wrong' ||
            responseText.message == 'need at least user in params') {
            
            // get account id and update username/password
            var accountNode = options.argument.node;
            var accountId = accountNode.attributes.account_id;
            
            // remove intelligent folders
            accountNode.attributes.intelligent_folders = 0;
            
            // cancel loading
            accountNode.loading = false;
            accountNode.ui.afterLoad(accountNode);
                        
            var credentialsWindow = Tine.widgets.dialog.CredentialsDialog.openWindow({
                windowTitle: String.format(this.app.i18n._('IMAP Credentials for {0}'), accountNode.text),
                appName: 'Felamimail',
                credentialsId: accountId,
                i18nRecordName: this.app.i18n._('Credentials'),
                recordClass: Tine.Tinebase.Model.Credentials,
                listeners: {
                    scope: this,
                    'update': function(data) {
                        // update account node
                        var account = Tine.Felamimail.loadAccountStore().getById(accountId);
                        accountNode.attributes.intelligent_folders = account.get('intelligent_folders');
                        accountNode.reload(function(callback) {
                        }, this);
                    }
                }
            });
            
        } else {
            var exception = responseText.data ? responseText.data : responseText;
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
        }
    }
});
