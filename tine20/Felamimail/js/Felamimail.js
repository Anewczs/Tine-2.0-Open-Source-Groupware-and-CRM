/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.Application
 * @extends     Tine.Tinebase.Application
 * 
 * <p>Felamimail application obj</p>
 * <p>
 * TODO         make message caching flow work again
 * TODO         add credentials dialog on failure of folder store / updatefolderstatus
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * 
 * @constructor
 * Create a new  Tine.Felamimail.Application
 */
 Tine.Felamimail.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * refresh time in milliseconds
     * 
     * @property checkMailDelayTime
     * @type Number
     */
    checkMailDelayTime: 20000, // 20 seconds

    /**
     * @property checkMailsDelayedTask
     * @type Ext.util.DelayedTask
     */
    checkMailsDelayedTask: null,
    
    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,
    
    /**
     * returns title (Email)
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Email');
    },
    
    /**
     * start delayed task to init folder store / updateFolderStore
     */
    init: function() {
        this.checkMailsDelayedTask = new Ext.util.DelayedTask(this.checkMails, this);
        
        var delayTime = (Tine.Tinebase.appMgr.getActive() == this) ? /*1000*/ 0 : 15000;
        this.getFolderStore.defer(delayTime, this);
    },
    
    /**
     * check mails delayed task
     */
    checkMails: function() {
        this.getFolderStore();
        this.updateFolderStatus();
    },
    
    /**
     * get folder store
     * 
     * @return {Tine.Felamimail.FolderStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            this.folderStore = new Tine.Felamimail.FolderStore({
                listeners: {
                    scope: this,
                    update: this.onUpdateFolder
                }
            });
            
            var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
            if (defaultAccount != '') {
                this.folderStore.load({
                    path: '/' + defaultAccount,
                    params: {filter: [
                        {field: 'account_id', operator: 'equals', value: defaultAccount},
                        {field: 'globalname', operator: 'equals', value: ''}
                    ]},
                    callback: this.onStoreInitialLoad.createDelegate(this)
                });
            }
        }
        
        return this.folderStore;
    },
    
    /**
     * initial load of folder store
     *
     * @param {} record
     * @param {} options
     * @param {} success
     * 
     * TODO this could be obsolete, try to make it work without the initial load
     */
    onStoreInitialLoad: function(record, options, success) {
        var folderName = 'INBOX';
        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            var node = treePanel.getSelectionModel().getSelectedNode();
            if (node) {
                folderName = node.attributes.globalname;
            }
        } 
            
        this.updateFolderStatus(folderName);
    },
    
    /**
     * on update folder
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    onUpdateFolder: function(store, record, operation) {
        
        var changes = record.getChanges();
        
        if (record.isModified('cache_recentcount') && changes.cache_recentcount > 0) {
            //console.log('show notification');
            Ext.ux.Notification.show(
                this.i18n._('New mails'), 
                String.format(this.i18n._('You got {0} new mail(s) in Folder {1}.'), 
                    changes.cache_recentcount, record.get('localname'))
            );
        }
    },

    /**
     * set this.checkMailDelayTime
     * @param {} mode fast|slow
     */
    setCheckMailsRefreshTime: function(mode) {
        if (mode == 'slow') {
            // get folder update interval from preferences
            var updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval'));
            if (updateInterval > 0) {
                // convert to milliseconds
                this.checkMailDelayTime = 60000*updateInterval;
            } else {
                // TODO what shall we de if pref is set to 0?
                this.checkMailDelayTime = 1200000; // 20 minutes
            }
        } else {
            this.checkMailDelayTime = 20000; // 20 seconds
        }
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {String/Tine.Felamimail.Model.Folder} [folder]
     * 
     * TODO abort request if another folder has been clicked
     * TODO move request to record proxy
     */
    updateFolderStatus: function(folder) {
        
        if (Ext.isString(folder)) {
        //if (folder && folder.split) {
            var index = this.getFolderStore().find('globalname', folder);
            if (index >= 0) {
                folder = this.getFolderStore().getAt(index);
            }
        } 
        
        //console.log(folder);
        
        var folderIds, accountId;
        if (! folder || typeof folder.get !== 'function') {
            var treePanel = this.getMainScreen().getTreePanel();
            accountId = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
                
            if (treePanel && treePanel.rendered) {
                var account = treePanel.getActiveAccount();
                if (account !== null) {
                    accountId = account.id;
                }
            }
            folderIds = this.getFoldersForUpdateStatus(accountId);
            folder = null;
        } else {
            folderIds = [folder.id];
            accountId = folder.get('account_id');
        }
        
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.updateFolderStatus',
                folderIds: folderIds,
                accountId: accountId
            },
            scope: this,
            timeout: 60000, // 1 minute
            success: function(_result, _request) {
                var result = Tine.Felamimail.folderBackend.getReader().readRecords(Ext.util.JSON.decode(_result.responseText));
                //console.log(result);
                for (var i = 0; i < result.records.length; i++) {
                    this.updateFolderInStore(result.records[i]);
                }
                var result = this.updateMessageCache(folder);
            },
            failure: function() {
                // do nothing
            }
        });
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Tine.Felamimail.Model.Folder} [folder]
     * @return boolean true if caching is complete
     */
    updateMessageCache: function(folder) {

        /////////// select folder to update message cache for
        
        var refreshRate = 'fast';
        var folderId = null;
        var singleFolderUpdate = false;
        if (! folder && false /*this.getTreePanel()*/) {
            // get active node
            var node = this.getTreePanel().getSelectionModel().getSelectedNode();
            if (node && node.attributes.folder_id) {
                folder = this.folderStore.getById(node.id);
            }
        } else {
            singleFolderUpdate = true;
        }
        
        //console.log(folder);
        if (folder && (folder.get('cache_status') == 'incomplete' || folder.get('cache_status') == 'invalid')) {
            folderId = folder.id;
            
        } else if (! singleFolderUpdate) {
            folderId = this.getNextFolderToUpdate();
            if (folderId === null) {
                // nothing left to do for the moment! -> set refresh rate to 'slow'
                //console.log('finished for the moment');
                refreshRate = 'slow';
            }
        }
        
        //console.log('update folder:' + folderId);
        if (folderId !== null) {
            /////////// do request
            
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateMessageCache',
                    folderId: folderId,
                    time: 10
                },
                scope: this,
                success: function(result, request) {
                    var newRecord = Tine.Felamimail.folderBackend.recordReader(result);
                    //console.log(newRecord);
                    this.updateFolderInStore(newRecord);
                },
                failure: function(response, options) {
                    // TODO call handle failure and show credentials dialog / reload account afterwards
                    /*
                    if (node.parentNode) {
                        this.handleFailure(response, options, node.parentNode, false);
                    }
                    */
                }
            });           
        }
        
        // TODO add folder as arg
        var delayTime = this.setCheckMailsRefreshTime(refreshRate);
        //this.checkMailsDelayedTask.delay(delayTime/*, folder?*/);
    },
   
    /**
     * get all folders to update of account in store
     * 
     * @param {String} accountId
     */
    getFoldersForUpdateStatus: function(accountId) {
        var result = [];

        //console.log('# records: ' + this.folderStore.getCount());
        //console.log(this.folderStore);
        var accountFolders = this.getFolderStore().queryBy(function(record) {
            var timestamp = record.get('imap_timestamp');
            return (record.get('account_id') == accountId && (timestamp == '' || timestamp.getElapsed() > 300000)); // 5 minutes
        });
        //console.log(accountFolders);
        accountFolders.each(function(record) {
            result.push(record.id);
        });
        
        return result;
    },
    
    /**
     * update folder in store
     * 
     * @param {Tine.Felamimail.Model.Folder} folderData
     * @return {Tine.Felamimail.Model.Folder}
     * 
     * TODO iterate record fields
     */
    updateFolderInStore: function(newFolder) {
        
        var folder = this.getFolderStore().getById(newFolder.id);
        
        if (! folder) {
            return newFolder;
        }
        
        var fieldsToUpdate = ['imap_status','imap_timestamp','imap_uidnext','imap_uidvalidity','imap_totalcount',
            'cache_status','cache_uidnext','cache_totalcount', 'cache_recentcount','cache_unreadcount','cache_timestamp',
            'cache_job_actions_estimate','cache_job_actions_done'];

        // update folder store
        for (var j = 0; j < fieldsToUpdate.length; j++) {
            folder.set(fieldsToUpdate[j], newFolder.get(fieldsToUpdate[j]));
        }
        
        return folder;
    },
    
    /**
     * get next folder for update message cache
     * 
     * @return {String|null}
     */
    getNextFolderToUpdate: function() {
        var result = null;
        
        var account = this.getActiveAccount();
        // look for folder to update
        //console.log(account.id);
        var candidates = this.folderStore.queryBy(function(record) {
            //console.log(record);
            //console.log(record.id + ' ' + record.get('cache_status'));
            return (
                record.get('account_id') == account.id 
                && (record.get('cache_status') == 'incomplete' || record.get('cache_status') == 'invalid')
            );
        });
        //console.log(candidates);
        if (candidates.getCount() > 0) {
            folder = candidates.first();
            result = folder.id;
        }
        
        return result;
    },
    
    /**
     * handle failure to show credentials dialog if imap login failed
     * 
     * @param {String}  response
     * @param {Object}  options
     * @param {Node}    node optional account node
     * @param {Boolean} handleException
     * 
     * TODO implement
     */
    handleFailure: function(response, options, node, handleException) {
        /*
        var responseText = Ext.util.JSON.decode(response.responseText);
        var accountNode = (options.argument) ? options.argument.node : node;

        // cancel loading
        accountNode.loading = false;
        accountNode.ui.afterLoad(accountNode);
        
        if (responseText.data.code == 902) {
            
            // get account id and update username/password
            var accountId = accountNode.attributes.account_id;
            
            // remove intelligent folders
            accountNode.attributes.intelligent_folders = 0;
                        
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
            
        } else if (handleException !== false) {
            Ext.Msg.show({
               title:   this.app.i18n._('Error'),
               msg:     (responseText.data.message) ? responseText.data.message : this.app.i18n._('No connection to IMAP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });

            // TODO call default exception handler on specific exceptions?
            //var exception = responseText.data ? responseText.data : responseText;
            //Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
        }
        */
    }
});

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.MainScreen
 * @extends Tine.Tinebase.widgets.app.MainScreen
 * 
 * MainScreen Definition (use default)
 */ 
Tine.Felamimail.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

/**
 * get account store
 *
 * @param {Boolean} reload
 * @return {Ext.data.JsonStore}
 */
Tine.Felamimail.loadAccountStore = function(reload) {
    
    var store = Ext.StoreMgr.get('FelamimailAccountStore');
    
    if (!store) {
        
        //console.log(Tine.Felamimail.registry.get('accounts'));
        
        // create store (get from initial data)
        store = new Ext.data.JsonStore({
            fields: Tine.Felamimail.Model.Account,

            // initial data from http request
            data: Tine.Felamimail.registry.get('accounts'),
            autoLoad: true,
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount',
            proxy: Tine.Felamimail.accountBackend,
            reader: Tine.Felamimail.accountBackend.getReader()
        });
        
        Ext.StoreMgr.add('FelamimailAccountStore', store);
    } 

    return store;
};

/**
 * add signature (get it from default account settings)
 * 
 * @param {String} id
 * @return {Tine.Felamimail.Model.Account}
 */
Tine.Felamimail.getSignature = function(id) {
        
    var result = '';
    
    if (! id || id == 'default') {
        id = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    }
    
    var defaultAccount = Tine.Felamimail.loadAccountStore().getById(id);
    var signature = (defaultAccount) ? defaultAccount.get('signature') : '';
    if (signature && signature != '') {
        signature = Ext.util.Format.nl2br(signature);
        result = '<br><br><span class="felamimail-body-signature">--<br>' + signature + '</span>';
    }
    
    return result;
}

