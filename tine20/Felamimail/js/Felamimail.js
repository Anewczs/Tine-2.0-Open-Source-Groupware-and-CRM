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
     * @property checkMailsDelayedTask
     * @type Ext.util.DelayedTask
     */
    checkMailsDelayedTask: null,
    
    /**
     * @property defaultAccount
     * @type Tine.Felamimail.Model.Account
     */
    defaultAccount: null,
    
    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,
    
    /**
     * @property updateInterval user defined update interval (milliseconds)
     * @type Number
     */
    updateInterval: null,
    
    /**
     * transaction id of current update message cache request
     * @type Number
     */
    updateMessageCacheTransactionId: null,
    
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
        Tine.log.info('initialising app');
        this.checkMailsDelayedTask = new Ext.util.DelayedTask(this.checkMails, this);
        
        this.updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval')) * 60000;
        Tine.log.debug('user defined update interval is "' + this.updateInterval/1000 + '" seconds');
        
        this.defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
        Tine.log.debug('default account is "' + this.defaultAccount);
        
        if (Tine.Tinebase.appMgr.getActive() != this && this.updateInterval) {
            var delayTime = this.updateInterval/20;
            
            Tine.log.debug('start preloading mails in "' + delayTime/1000 + '" seconds');
            this.checkMailsDelayedTask.delay(delayTime);
        }
    },
    
    
    /**
     * check mails delayed task
     */
    checkMails: function() {
        if (! this.getFolderStore().getCount() && this.defaultAccount) {
            Tine.log.debug('no folders in store yet, fetching first level...');
            this.getFolderStore().asyncQuery('parent_path', '/' + this.defaultAccount, this.checkMails.createDelegate(this), [], this, this.getFolderStore());
            return;
        }
        
        Tine.log.info('checking mails now: ' + new Date());
        this.updateFolderStatus();
    },
    
    /**
     * get folder store
     * 
     * @return {Tine.Felamimail.FolderStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            Tine.log.debug('creating folder store');
            this.folderStore = new Tine.Felamimail.FolderStore({
                listeners: {
                    scope: this,
                    update: this.onUpdateFolder
                }
            });
        }
        
        return this.folderStore;
    },
    
    /**
     * executed when  updateFolderStatus or updateMessageCache requests fail
     * 
     * NOTE: We show the error dlg only for the first error
     * NOTE: by chance, the updtes always operate on a single account ;-)
     * 
     * @param {Object} exception
     */
    onBackgroundRequestFail: function(exception) {
        var accountId   = Ext.decode(exception.request).params.accountId,
            account     = accountId ? Tine.Felamimail.loadAccountStore().getById(accountId): null,
            imapStatus  = account ? account.get('imap_status') : null;
            
        if (account) {
            account.setLastIMAPException(exception);
            
            this.getFolderStore().each(function(folder) {
                if (folder.get('account_id') === accountId) {
                    folder.set('cache_status', 'disconnect');
                }
            }, this);
            
            if (imapStatus !== 'failure') {
                Tine.Felamimail.folderBackend.handleRequestException(exception);
            }
        }
        
        Tine.log.info('background update failed (' + exception.message + ') -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
        this.checkMailsDelayedTask.delay(this.updateInterval);
    },
    
    /**
     * executed right before this app gets activated
     */
    onBeforeActivate: function() {
        Tine.log.info('activating felamimail now');
        // abort preloading/old actions and force frech fetch
        this.checkMailsDelayedTask.delay(0);
    },
    
    /**
     * on update folder
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
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
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Tine.Felamimail.Model.Folder} [folder]
     */
    updateFolderStatus: function(folder) {
        Tine.log.info('updateFolderStatus for folder "' + (folder ? folder.get('localname') : '--') + '"');
        
        var folders,
            folderIds = [],
            folderNames = [],
            accountId = folder ? folder.get('account_id') : this.getActiveAccount().id;
        
        //folderIds = folder ? [folders.add(folder, folder.id).id] : [],
            accountId = folder ? folder.get('account_id') : this.getActiveAccount().id;
            
        if (folder) {
            folders = new Ext.util.MixedCollection();
            folders.add(folder.id, folder);
        } else {
            Tine.log.debug('no folder given, assembling list of folder to update status for');
            folders = this.getFolderStore().queryBy(function(record) {
                var timestamp = record.get('imap_timestamp');
                return (record.get('account_id') == accountId && (timestamp == '' || timestamp.getElapsed() > this.updateInterval));
            }, this);
        }
        
        folders.each(function(f) {
            f.set('cache_status', 'pending');
            folderIds.push(f.get('id'));
            folderNames.push(f.get('localname'));
        }, this);
        
        
        // don't update if we got no folder ids 
        if (folderIds.length > 0) {
            Tine.log.debug('fetching status for folder(s) ' + folderNames.join(', '));
            
            Tine.Felamimail.folderBackend.updateFolderStatus(accountId, folderIds, {
                scope: this,
                failure: this.onBackgroundRequestFail,
                success: function(folders) {
                    Tine.Felamimail.loadAccountStore().getById(accountId).setLastIMAPException(null);
                    this.getFolderStore().updateFolder(folders);
                    this.updateMessageCache(folder);
                }
            });
        } else {
            this.updateMessageCache();
        }
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Tine.Felamimail.Model.Folder} [folder] force message cache update for this folder
     * @return boolean true if caching is complete
     */
    updateMessageCache: function(folder) {
        Tine.log.info('updateMessageCache for folder "' + (folder ? folder.get('path') : '--') + '"');
        
        folder = folder ? folder : this.getNextFolderToUpdate();
        if (! folder) {
            Tine.log.info('nothing more to do -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
            if (this.updateInterval > 0) {
                this.checkMailsDelayedTask.delay(this.updateInterval);
            }
            
            return true;
        }
        
        var executionTime = folder.isCurrentSelection() ? 10 : Math.min(this.updateInterval, 120);
        Tine.log.debug('updateing message cache for folder ' + folder.id + ' with ' + executionTime + ' seconds execution time');
        
        // cancel old request
        if (this.updateMessageCacheTransactionId) {
            Tine.Felamimail.folderBackend.abort(this.updateMessageCacheTransactionId);
        }
        
        this.updateMessageCacheTransactionId = Tine.Felamimail.folderBackend.updateMessageCache(folder.id, executionTime, {
            scope: this,
            failure: this.onBackgroundRequestFail,
            success: function(folder) {
                Tine.Felamimail.loadAccountStore().getById(folder.get('account_id')).setLastIMAPException(null);
                this.getFolderStore().updateFolder(folder);
                this.checkMailsDelayedTask.delay(0);
            }
        });
        return false;
    },
   
    /**
     * get next folder for update message cache
     * 
     * @return {Tine.Felamimail.Model.Folder|null}
     */
    getNextFolderToUpdate: function() {
        var account = this.getActiveAccount();
        
        if (account !== null) {
            // look for folder to update
            var candidates = this.folderStore.queryBy(function(record) {
                return (
                    record.get('account_id') == account.id 
                    && (record.get('cache_status') == 'incomplete' || record.get('cache_status') == 'invalid')
                );
            });
            
            if (candidates.getCount() > 0) {
                // if current selection is a candidate, take this one!
                if (this.getMainScreen().getTreePanel()) {
                    // get active node
                    var node = this.getMainScreen().getTreePanel().getSelectionModel().getSelectedNode();
                    if (node && node.attributes.folder_id && candidates.get(node.id)) {
                        return candidates.get(node.id);
                    }
                }
                
                // else take the first one
                return candidates.first();
            }
        }
        
        return null;
    },
    
    /**
     * get active account
     * @return {Tine.Felamimail.Model.Account}
     */
    getActiveAccount: function() {
        var account = null;
            
        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            account = treePanel.getActiveAccount();
        }
        
        if (account === null) {
            account = Tine.Felamimail.loadAccountStore().getById(Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount'));
        }
        
        return account;
    }
});

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.MainScreen
 * @extends Tine.widgets.MainScreen
 * 
 * MainScreen Definition
 */ 
Tine.Felamimail.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    /**
     * adapter fn to get folder tree panel
     * 
     * @return {Ext.tree.TreePanel}
     */
    getTreePanel: function() {
        return this.getWestPanel().getContainerTreePanel();
    }
});

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
 * @return {String}
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
        result = '<br><br><span id="felamimail-body-signature">--<br>' + signature + '</span>';
    }
    
    return result;
}

