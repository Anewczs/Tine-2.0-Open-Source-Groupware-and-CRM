/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Felamimail', 'Tine.Felamimail.Model');

/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Message
 * @extends Tine.Tinebase.data.Record
 * 
 * Message Record Definition
 */ 
Tine.Felamimail.Model.Message = Tine.Tinebase.data.Record.create([
      { name: 'id' },
      { name: 'subject' },
      { name: 'from' },
      { name: 'to' },
      { name: 'cc' },
      { name: 'bcc' },
      { name: 'sent',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
      { name: 'received', type: 'date', dateFormat: Date.patterns.ISO8601Long },
      { name: 'flags' },
      { name: 'size' },
      { name: 'body' },
      { name: 'headers' },
      { name: 'content_type' },
      { name: 'attachments' },
      { name: 'original_id' },
      { name: 'note' }
    ], {
    appName: 'Felamimail',
    modelName: 'Message',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Message', 'Messages', n);
    recordName: 'Message',
    recordsName: 'Messages',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Message list',
    containersName: 'Message lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

/**
 * get default message data
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Message.getDefaultData = function() {
    var defaultFrom = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    var autoAttachNote = Tine.Felamimail.registry.get('preferences').get('autoAttachNote');
    return {
        from: defaultFrom,
        note: autoAttachNote,
        content_type: 'text/html'
    };
};

/**
 * Account model fields
 */
Tine.Felamimail.Model.AccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'user_id' },
    { name: 'name' },
    { name: 'type' },
    { name: 'user' },
    { name: 'host' },
    { name: 'email' },
    { name: 'password' },
    { name: 'from' },
    { name: 'organization' },
    { name: 'port' },
    { name: 'ssl' },
    { name: 'sent_folder' },
    { name: 'trash_folder' },
    { name: 'intelligent_folders' },
    { name: 'has_children_support', type: 'bool' },
    { name: 'sort_folders' },
    { name: 'delimiter' },
    { name: 'display_format' },
    { name: 'ns_personal' },
    { name: 'signature' },
    { name: 'smtp_port' },
    { name: 'smtp_hostname' },
    { name: 'smtp_auth' },
    { name: 'smtp_ssl' },
    { name: 'smtp_user' },
    { name: 'smtp_password' }
]);

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.messageBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Message Backend
 */ 
Tine.Felamimail.messageBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message
});

/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Account
 * @extends Tine.Tinebase.data.Record
 * 
 * Account Record Definition
 */ 
Tine.Felamimail.Model.Account = Tine.Tinebase.data.Record.create(Tine.Felamimail.Model.AccountArray, {
    appName: 'Felamimail',
    modelName: 'Account',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Account', 'Accounts', n);
    recordName: 'Account',
    recordsName: 'Accounts',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Account list',
    containersName: 'Account lists' /*,
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    } */
});

/**
 * get default data for account
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Account.getDefaultData = function() { 
    var defaults = (Tine.Felamimail.registry.get('defaults')) 
        ? Tine.Felamimail.registry.get('defaults')
        : {};
    
    return {
        host: (defaults.host) ? defaults.host : '',
        port: (defaults.port) ? defaults.port : 143,
        smtp_hostname: (defaults.smtp && defaults.smtp.hostname) ? defaults.smtp.hostname : '',
        smtp_port: (defaults.smtp && defaults.smtp.port) ? defaults.smtp.port : 25,
        signature: 'Sent with love from the new tine 2.0 email client ...<br/>'
            + 'Please visit <a href="http://tine20.org">http://tine20.org</a>',
        sent_folder: (defaults.sent_folder) ? defaults.sent_folder : 'Sent',
        trash_folder: (defaults.trash_folder) ? defaults.trash_folder : 'Trash',
        smtp_ssl: (defaults.smtp && defaults.smtp.ssl) ? defaults.smtp.ssl : 'none'
    };
};

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.accountBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Account Backend
 */ 
Tine.Felamimail.accountBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Account',
    recordClass: Tine.Felamimail.Model.Account
});

/**
 * @namespace Tine.Felamimail.Model
 * @class Tine.Felamimail.Model.Record
 * @extends Ext.data.Record
 * 
 * Folder Record Definition
 */ 
Tine.Felamimail.Model.Folder = Tine.Tinebase.data.Record.create([
      { name: 'id' },
      { name: 'localname' },
      { name: 'globalname' },
      { name: 'path' }, // /accountid/folderid/...
      { name: 'parent' },
      { name: 'parent_path' }, // /accountid/folderid/...
      { name: 'account_id' },
      { name: 'has_children',       type: 'bool' },
      { name: 'system_folder',      type: 'bool' },
      { name: 'imap_status' },
      { name: 'imap_timestamp',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
      { name: 'imap_uidnext',       type: 'int' },
      { name: 'imap_uidvalidity',   type: 'int' },
      { name: 'imap_totalcount',    type: 'int' },
      { name: 'cache_status' },
      { name: 'cache_uidnext',      type: 'int' },
      { name: 'cache_recentcount',  type: 'int' },
      { name: 'cache_totalcount',   type: 'int' },
      { name: 'cache_unreadcount',  type: 'int' },
      { name: 'cache_timestamp',    type: 'date', dateFormat: Date.patterns.ISO8601Long  },
      { name: 'cache_job_actions_estimate',     type: 'int' },
      { name: 'cache_job_actions_done',         type: 'int' }
], {
    // translations for system folder:
    // _('INBOX') _('Drafts') _('Sent') _('Templates') _('Junk') _('Trash')

    appName: 'Felamimail',
    modelName: 'Folder',
    idProperty: 'id',
    titleProperty: 'localname',
    // ngettext('Folder', 'Folders', n);
    recordName: 'Folder',
    recordsName: 'Folders',
    // ngettext('record list', 'record lists', n);
    containerName: 'Folder list',
    containersName: 'Folder lists',
    
    /**
     * is this folder the currently selected folder
     * 
     * @return {Boolean}
     */
    isCurrentSelection: function() {
        if (Tine.Tinebase.appMgr.get(this.appName).getMainScreen().getTreePanel()) {
            // get active node
            var node = Tine.Tinebase.appMgr.get(this.appName).getMainScreen().getTreePanel().getSelectionModel().getSelectedNode();
            if (node && node.attributes.folder_id) {
                return node.id == this.id;
            }
        }
        
        return false;
    }
});

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.folderBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Folder Backend
 */ 
Tine.Felamimail.folderBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Felamimail',
    modelName: 'Folder',
    recordClass: Tine.Felamimail.Model.Folder,
    
    /**
     * update folderStatus for given folderIds of given account
     * 
     * @param   {String} accountId
     * @param   {Array} folderIds
     * @return  {Number} Ext.Ajax transaction id
     */
    updateFolderStatus: function(accountId, folderIds, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.updateFolderStatus';
        p.accountId = accountId;
        p.folderIds = folderIds;
        
        options.beforeSuccess = function(response) {
            return [this.jsonReader.read(response).records];
        };
        
        // increase timeout as this can take a longer (1 minute)
        options.timeout = 60000;
                
        return this.doXHTTPRequest(options);
    },
    
    /**
     * update message cache of given folder for given execution time
     * 
     * @param   {String} folderId
     * @param   {Number} executionTime (seconds)
     * @return  {Number} Ext.Ajax transaction id
     */
    updateMessageCache: function(folderId, executionTime, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.updateMessageCache';
        p.folderId = folderId;
        p.time = executionTime;
        
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        // double the timeout
        options.timeout = executionTime * 2000;
                
        return this.doXHTTPRequest(options);
    },
    
    /**
     * generic exception handler for this proxy
     * 
     * @todo move all 902 exception handling here!
     * @todo invent requery on 902 with cred. dialog
     * 
     * @param {Tine.Exception} exception
     */
    handleRequestException: function(exception) {
        Tine.log.err('request exception :');
        console.log(exception);
        
        switch(exception.code) {
            case 902: // Felamimail_Exception_InvalidCredentials
                break;
                
            case 903: // Felamimail_Exception_ServiceUnavailable
                break;
                
            default:
                break;
        }
        var app = Tine.Tinebase.appMgr.get(this.appName);
        if (exception && exception.code == 902) {
            Ext.Msg.show({
               title:   app.i18n._('Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to IMAP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
        }
    }
    
//    /**
//     * handle failure to show credentials dialog if imap login failed
//     * 
//     * @param {String}  response
//     * @param {Object}  options
//     * @param {Node}    node optional account node
//     * @param {Boolean} handleException
//     */
//    handleFailure: function(response, options) {
//        var responseText = Ext.util.JSON.decode(response.responseText);
//        
//        if (responseText.data.code == 902) {
//            
//            var jsonData = Ext.util.JSON.decode(options.jsonData);
//            var accountId = (jsonData.params.accountId) ? jsonData.params.accountId : Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
//            var account = Tine.Felamimail.loadAccountStore().getById(accountId);
//                        
//            if (! Tine.Felamimail.credentialsDialog) {
//                Tine.Felamimail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
//                    title: String.format(this.i18n._('IMAP Credentials for {0}'), account.get('name')),
//                    appName: 'Felamimail',
//                    credentialsId: accountId,
//                    i18nRecordName: this.i18n._('Credentials'),
//                    recordClass: Tine.Tinebase.Model.Credentials,
//                    listeners: {
//                        scope: this,
//                        'update': function(data) {
//                            this.checkMails();
//                        }
//                    }
//                });
//            }
//            
//        } else {
//            Ext.Msg.show({
//               title:   this.i18n._('Error'),
//               msg:     (responseText.data.message) ? responseText.data.message : this.i18n._('No connection to IMAP server.'),
//               icon:    Ext.MessageBox.ERROR,
//               buttons: Ext.Msg.OK
//            });
//
//            // TODO call default exception handler on specific exceptions?
//            //var exception = responseText.data ? responseText.data : responseText;
//            //Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
//        }
//    },
});

