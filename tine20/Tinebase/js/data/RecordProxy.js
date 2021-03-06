/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Tinebase.data');

/**
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.RecordProxy
 * @extends     Ext.data.DataProxy
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * Generic record proxy for an model/datatype of an application
 * 
 * @constructor
 * @param {Object} config Config Object
 */
Tine.Tinebase.data.RecordProxy = function(c) {
    // we support all actions
    c.api = {read: true, create: true, update: true, destroy: true};
    
    Tine.Tinebase.data.RecordProxy.superclass.constructor.call(this, c);
    
    Ext.apply(this, c);
    this.appName    = this.appName    ? this.appName    : c.recordClass.getMeta('appName');
    this.modelName  = this.modelName  ? this.modelName  : c.recordClass.getMeta('modelName');
    this.idProperty = this.idProperty ? this.idProperty : c.recordClass.getMeta('idProperty');
    
    /* NOTE: in ExtJS records always are part of a store. The store is
             the only instance which triggers read/write actions.
             
             In our edit dialoges in contrast we work with single (store-less) 
             records and the handlers itselve trigger the read/write actions.
             This might change in future, but as long as we do so, we also need
             the reader/writer here.
     */
    this.jsonReader = new Ext.data.JsonReader({
        id: this.idProperty,
        root: 'results',
        totalProperty: 'totalcount'
    }, this.recordClass);
};

Ext.extend(Tine.Tinebase.data.RecordProxy, Ext.data.DataProxy, {
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    /**
     * @type String 
     * @property appName
     * internal/untranslated app name
     */
    appName: null,
    
    /**
     * @type String 
     * @property idProperty
     * property of the id of the record
     */
    idProperty: null,
    
    /**
     * @type String 
     * @property modelName
     * name of the model/record 
     */
    modelName: null,
    
    /**
     * id of last transaction
     * @property transId
     */
    transId: null,
    
    /**
     * Aborts any outstanding request.
     * @param {Number} transactionId (Optional) defaults to the last transaction
     */
    abort : function(transactionId) {
        return Ext.Ajax.abort(transactionId);
    },
    
    /**
     * Determine whether this object has a request outstanding.
     * @param {Number} transactionId (Optional) defaults to the last transaction
     * @return {Boolean} True if there is an outstanding request.
     */
    isLoading : function(transId){
        return Ext.Ajax.isLoading(transId);
    },
        
    /**
     * loads a single 'full featured' record
     * 
     * @param   {Ext.data.Record} record
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    loadRecord: function(record, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.get' + this.modelName;
        p.id = record.get(this.idProperty);
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * searches all (lightweight) records matching filter
     * 
     * @param   {Object} filter
     * @param   {Object} paging
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Object} root:[records], totalcount: number
     */
    searchRecords: function(filter, paging, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.search' + this.modelName + 's';
        p.filter = (filter) ? filter : [];
        p.paging = paging;
        
        options.beforeSuccess = function(response) {
            return [this.jsonReader.read(response)];
        };
        
        // increase timeout as this can take a longer (1 minute)
        options.timeout = 60000;
                
        return this.doXHTTPRequest(options);
    },
    
    /**
     * saves a single record
     * 
     * @param   {Ext.data.Record} record
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    saveRecord: function(record, options, additionalArguments) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.save' + this.modelName;
        p.recordData = record.data;
        if (additionalArguments) {
            Ext.apply(p, additionalArguments);
        }
        
        // increase timeout as this can take a longer (5 minutes)
        options.timeout = 300000;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * deletes multiple records identified by their ids
     * 
     * @param   {Array} records Array of records or ids
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecords: function(records, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 's';
        options.params.ids = this.getRecordIds(records);

        // increase timeout as this can take a long time (2 mins)
        options.timeout = 120000;
        
        return this.doXHTTPRequest(options);
    },

    /**
     * deletes multiple records identified by a filter
     * 
     * @param   {Object} filter
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecordsByFilter: function(filter, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 'sByFilter';
        options.params.filter = filter;
        
        // increase timeout as this can take a long time (5 mins)
        options.timeout = 300000;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * updates multiple records with the same data
     * 
     * @param   {Array} filter filter data
     * @param   {Object} updates
     * @return  {Number} Ext.Ajax transaction id
     * @success
     */
    updateRecords: function(filter, updates, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.updateMultiple' + this.modelName + 's';
        options.params.filter = filter;
        options.params.values = updates;
        
        options.beforeSuccess = function(response) {
            return [Ext.util.JSON.decode(response.responseText)];
        };
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * returns an array of ids
     * 
     * @private 
     * @param  {Ext.data.Record|Array}
     * @return {Array} of ids
     */
    getRecordIds : function(records) {
        var ids = [];
        
        if (! Ext.isArray(records)) {
            records = [records];
        }
        
        for (var i=0; i<records.length; i++) {
            ids.push(records[i].id ? records[i].id : records.id);
        }
        
        return ids;
    },
    
    /**
     * required method for Ext.data.Proxy, used by store
     */
    load : function(params, reader, callback, scope, arg){
        if(this.fireEvent("beforeload", this, params) !== false){
            
            // move paging to own object
            var paging = {
                sort:  params.sort,
                dir:   params.dir,
                start: params.start,
                limit: params.limit
            };
            
            this.searchRecords(params.filter, paging, {
                scope: this,
                success: function(records) {
                    callback.call(scope||this, records, arg, true);
                },
                failure: function(exception) {
                    //@todo compute traditional options/request/response here -> dont' waste time ondepricated stuff
                    //this.fireEvent('exception', this, 'remote', 'read', options, response, arg);
                    this.fireEvent('loadexception', this, 'remote',  exception, arg);
                }
            });
            
        } else {
            callback.call(scope||this, null, arg, false);
        }
    },
    
    /**
     * do the request
     * 
     * @param {} action
     * @param {} rs
     * @param {} params
     * @param {} reader
     * @param {} callback
     * @param {} scope
     * @param {} options
     */
    doRequest : function(action, rs, params, reader, callback, scope, options) {
        var opts = {
            params: params, 
            callback: callback,
            scope: scope
        };
        
        switch (action) {
            case Ext.data.Api.actions.create:
                this.saveRecord(rs, opts);
                break;
            case Ext.data.Api.actions.read:
                this.load(params, reader, callback, scope, options);
                break;
            case Ext.data.Api.actions.update:
                this.saveRecord(rs, opts);
                break;
            case Ext.data.Api.actions.destroy:
                this.deleteRecords(rs, opts);
                break;
        }
    },

    
    /**
     * returns reader
     * 
     * @return {Ext.data.DataReader}
     */
    getReader: function() {
        return this.jsonReader;
    },
    
    /**
     * reads a single 'fully featured' record from json data
     * 
     * NOTE: You might want to overwride this method if you have a more complex record
     * 
     * @param  XHR response
     * @return {Ext.data.Record}
     */
    recordReader: function(response) {
        var recordData = Ext.util.JSON.decode('{"results": [' + response.responseText + ']}');
        var data = this.jsonReader.readRecords(recordData);
        
        var record = data.records[0];
        var recordId = record.get(record.idProperty);
        
        record.id = recordId ? recordId : 0;
        
        return record;
    },
    
    /**
     * is request still loading?
     * 
     * @param  {Number} Ext.Ajax transaction id
     * @return {Bool}
     */
    isLoading: function(tid) {
        return Ext.Ajax.isLoading(tid);
    },
    
    /**
     * performs an Ajax request
     */
    doXHTTPRequest: function(options) {
        var requestOptions = {
            scope: this,
            params: options.params,
            callback: options.callback,
            success: function(response) {
                if (typeof options.success == 'function') {
                    var args = [];
                    if (typeof options.beforeSuccess == 'function') {
                        args = options.beforeSuccess.call(this, response);
                    }
                    
                    options.success.apply(options.scope, args);
                }
            },
            // note incoming options are implicitly jsonprc converted
            failure: function (response, jsonrpcoptions) {
                var responseData = Ext.decode(response.responseText),
                    exception = responseData.data ? responseData.data : responseData;
                    
                exception.request = jsonrpcoptions.jsonData;
                exception.response = response.responseText;
                
                if (typeof options.failure == 'function') {
                    var args = [];
                    if (typeof options.beforeFailure == 'function') {
                        args = options.beforeFailure.call(this, response);
                    } else {
                        args = [exception];
                    }
                
                    options.failure.apply(options.scope, args);
                } else {
                    this.handleRequestException(exception);
                }
            }
        };
        
        if (options.timeout) {
            requestOptions.timeout = options.timeout;
        }
        
        this.transId = Ext.Ajax.request(requestOptions);
        
        return this.transId;
    },
    
    /**
     * default exception handler
     * 
     * @param {Object} exception
     */
    handleRequestException: function(exception) {
        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    }
});
