/**
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

/**************************** message model *******************************/

/**
 * @type {Array}
 * Message model fields
 */
Tine.Felamimail.Model.MessageArray = Tine.Tinebase.Model.genericFields.concat([
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
    { name: 'original_id' }
]);

/**
 * @type {Tine.Tinebase.Message}
 * record definition
 */
Tine.Felamimail.Model.Message = Tine.Tinebase.data.Record.create(Tine.Felamimail.Model.MessageArray, {
    appName: 'Felamimail',
    modelName: 'Message',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Message', 'Messages', n);
    recordName: 'Message',
    recordsName: 'Messages',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

/**
 * get default message data (i.e. account id)
 * 
 * @return {Object}
 */
Tine.Felamimail.Model.Message.getDefaultData = function() {
    var defaultFrom = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    return {
        from: defaultFrom
    };
};

/**************************** account model *******************************/
/**
 * @type {Array}
 * Account model fields
 */
Tine.Felamimail.Model.AccountArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'user_id' },
    { name: 'name' },
    { name: 'user' },
    { name: 'host' },
    { name: 'email' },
    { name: 'password' },
    { name: 'from' },
    { name: 'organization' },
    { name: 'port' },
    { name: 'secure_connection' },
    { name: 'sent_folder' },
    { name: 'trash_folder' },
    { name: 'show_intelligent_folders' },
    { name: 'has_children_support' },
    { name: 'delimiter' },
    { name: 'ns_personal' },
    { name: 'signature' },
    { name: 'smtp_port' },
    { name: 'smtp_hostname' },
    { name: 'smtp_auth' },
    { name: 'smtp_secure_connection' },
    { name: 'smtp_user' },
    { name: 'smtp_password' }
]);

/**
 * @type {Tine.Tinebase.Account}
 * record definition
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
    containerName: 'record list',
    containersName: 'record lists' /*,
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
        smtp_secure_connection: (defaults.smtp && defaults.smtp.ssl) ? defaults.smtp.ssl : 'none'
        
        // some more possible defaults
        /*
        name: 'new account',
        email: 'test@tine20.org',
        user: 'test@tine20.org',
        */
    };
};

/**************************** attachment model *******************************/

/**
 * @type {Tine.Tinebase.Message}
 * record definition
 */
Tine.Felamimail.Model.Attachment = Tine.Tinebase.data.Record.create([
   { name: 'name' },
   { name: 'size' },
   { name: 'path' },
   { name: 'type' }
]);
