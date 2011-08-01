/* Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux.file');

/**
 * a simple file upload
 * objects of this class represent a single file uplaod
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.Upload
 * @extends     Ext.util.Observable
 */
Ext.ux.file.Upload = function(config, file, id) {
    Ext.apply(this, config);
    
    Ext.ux.file.Upload.superclass.constructor.apply(this, arguments);
    
    this.addEvents(
        /**
         * @event uploadcomplete
         * Fires when the upload was done successfully 
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         */
         'uploadcomplete',
        /**
         * @event uploadfailure
         * Fires when the upload failed 
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         */
         'uploadfailure',
        /**
         * @event uploadprogress
         * Fires on upload progress (html5 only)
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         * @param {XMLHttpRequestProgressEvent}
         */
         'uploadprogress',
         /**
          * @event uploadstart
          * Fires on upload progress (html5 only)
          * @param {Ext.ux.file.Upload} this
          * @param {Ext.Record} Ext.ux.file.Upload.file
          */
          'uploadstart'
    );
        
    this.file = file;
    this.fileSize = (this.file.size ? this.file.size : this.file.fileSize);

    this.maxChunkSize = this.maxPostSize - 16384;
    this.currentChunkSize = this.maxChunkSize;
    
    if(id && id > -1) {
        this.id = id;
    }
    
    this.tempFiles = new Array();
    
};
 

Ext.extend(Ext.ux.file.Upload, Ext.util.Observable, {
    
    id: -1,
    
    /**
     * @cfg {Int} maxFileUploadSize the maximum file size for traditinal form updoads
     */
    maxFileUploadSize: 20971520, // 20 MB
    /**
     * @cfg {Int} maxPostSize the maximum post size used for html5 uploads
     */
    maxPostSize: 20971520, // 20 MB
    /**
     * @cfg {Int} maxChunkSize the maximum chunk size used for html5 uploads
     */
    maxChunkSize: 20955136,
    /**
     * @cfg {Int} minChunkSize the minimal chunk size used for html5 uploads
     */
    minChunkSize: 102400,
    
    /**
     *  max number of upload retries
     */
    MAX_RETRY_COUNT: 10,
    
    /**
     *  retry timeout in milliseconds
     */
    RETRY_TIMEOUT_MILLIS: 3000,
    
    /**
     * @cfg {String} url the url we upload to
     */
    url: 'index.php',
    /**
     * @cfg {Ext.ux.file.BrowsePlugin} fileSelector
     * a file selector
     */
    fileSelector: null,
    
    /**
     * coresponding file record
     */
    fileRecord: null,
    
    /**
     * currentChunk to upload
     */
    currentChunk: null,
    
    /**
     * file to upload
     */
    file: null,
    
    /**
     * is this upload paused
     */
    paused: false,
    
    /**
     * is this upload queued
     */
    queued: false,    
    
    /**
     * collected tempforary files
     */
    tempFiles: new Array(),
    
    /**
     * did the last chunk upload fail
     */
    lastChunkUploadFailed: false,
    
    /**
     * current chunk to upload
     */
    currentChunk: null,
    
    /**
     * how many retries were made while trying to upload current chunk
     */
    retryCount: 0,
    
    /**
     * size of the current chunk
     */
    currentChunkSize: 0,
    
    /**
     * where the chunk begins in file (byte number)
     */
    currentChunkPosition: 0,
    
    /**
     * size of the file to upload
     */
    fileSize: 0,
    
    
    /**
     * creates a form where the upload takes place in
     * @private
     */
    createForm: function() {
        var form = Ext.getBody().createChild({
            tag:'form',
            action:this.url,
            method:'post',
            cls:'x-hidden',
            id:Ext.id(),
            cn:[{
                tag: 'input',
                type: 'hidden',
                name: 'MAX_FILE_SIZE',
                value: this.maxFileUploadSize
            }]
        });
        return form;
    },
    
    /**
     * perform the upload
     * 
     * @return {Ext.Record} Ext.ux.file.Upload.file
     */
    upload: function() {
                                   
        if ((
                (! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
                (Ext.isGecko && window.FileReader) // FF
        ) && this.file) {

            if (this.isHtml5ChunkedUpload()) {

                if(this.fileSize > this.maxFileUploadSize) {
                    this.createFileRecord(true);
                    this.fileRecord.beginEdit();
                    this.fileRecord.set('status', 'failure');
                    this.fileRecord.endEdit();
                    this.fireEvent('uploadfailure', this, this.fileRecord); 
                    return this.fileRecord;
                }
                
                // calculate optimal maxChunkSize       
                
                var chunkMax = this.maxChunkSize;
                var chunkMin = this.minChunkSize;       
                var actualChunkSize = this.maxChunkSize;

                if(this.fileSize > 5 * chunkMax) {
                    actualChunkSize = chunkMax;
                }
                else {
                    actualChunkSize = Math.max(chunkMin, this.fileSize / 5);
                }       
                this.maxChunkSize = actualChunkSize;
                
                if(Tine.Tinebase.uploadManager && Tine.Tinebase.uploadManager.isBusy()) {
                    this.createFileRecord(true);
                    this.setQueued(true);
                }
                else {
                    this.createFileRecord(false);
                    this.fireEvent('uploadstart', this);
                    this.html5ChunkedUpload();
                    
                }
                
                return this.fileRecord;

            } else {
                this.createFileRecord(false);
                this.fireEvent('uploadstart', this);
                this.html5upload();
                
                return this.fileRecord;
            }
        } else {
            return this.html4upload();
        }

    },
    
    /**
     * 2010-01-26 Current Browsers implemetation state of:
     *  http://www.w3.org/TR/FileAPI
     *  
     *  Interface: File | Blob | FileReader | FileReaderSync | FileError
     *  FF       : yes  | no   | no         | no             | no       
     *  safari   : yes  | no   | no         | no             | no       
     *  chrome   : yes  | no   | no         | no             | no       
     *  
     *  => no json rpc style upload possible
     *  => no chunked uploads posible
     *  
     *  But all of them implement XMLHttpRequest Level 2:
     *   http://www.w3.org/TR/XMLHttpRequest2/
     *  => the only way of uploading is using the XMLHttpRequest Level 2.
     */
    html5upload: function() {
                    
        if(this.maxPostSize/1 < this.file.size/1 && !this.isHtml5ChunkedUpload()) {
            this.fileRecord.html5upload = true;
            this.onUploadFail(null, null, this.fileRecord);
            return this.fileRecord;
        }
        
        var defaultHeaders = {
            "Content-Type"          : "application/x-www-form-urlencoded",
            "X-Tine20-Request-Type" : "HTTP",
            "X-Requested-With"      : "XMLHttpRequest"
        };
        
        var xmlData = this.file;
               
        if(this.isHtml5ChunkedUpload()) {
            defaultHeaders["X-Chunk-finished"] = this.lastChunk;
            xmlData = this.currentChunk;
        }

        var conn = new Ext.data.Connection({
            disableCaching: true,
            method: 'POST',
            url: this.url + '?method=Tinebase.uploadTempFile',
            timeout: 300000, // 5 mins
            defaultHeaders: defaultHeaders
        });
                
        var transaction = conn.request({
            headers: {
                "X-File-Name"           : this.fileRecord.get('name'),
                "X-File-Type"           : this.fileRecord.get('type'),
                "X-File-Size"           : this.fileRecord.get('size')
            },
            xmlData: xmlData,
            success: this.onUploadSuccess.createDelegate(this, null, true),
            failure: this.onUploadFail.createDelegate(this, null, true) 
        });       

        return this.fileRecord;
    },

    /**
     * Starting chunked file upload
     * 
     * @param {Boolean} whether this restarts a paused upload
     */
    html5ChunkedUpload: function(resumeUpload) {
                    
        if(!resumeUpload) {
            this.prepareChunk();        
        }       
        this.html5upload();                
    },
    
    /**
     * resume this upload
     */
    resumeUpload: function() {
        this.setPaused(false);
        this.html5ChunkedUpload(true);
    },
    
    /**
     * calculation the next chunk size and slicing file
     */
    prepareChunk: function() {
        
        if(this.lastChunkUploadFailed) {
            this.currentChunkPosition = Math.max(0
                    , this.currentChunkPosition - this.currentChunkSize);

            this.currentChunkSize = Math.max(this.minChunkSize, this.currentChunkSize / 2);
        }
        else {
            this.currentChunkSize = Math.min(this.maxChunkSize, this.currentChunkSize * 2);
        }
        this.lastChunkUploadFailed = false;
        
        var nextChunkPosition = Math.min(this.fileSize, this.currentChunkPosition 
                +  this.currentChunkSize);
        var newChunk = this.sliceFile(this.file, this.currentChunkPosition, nextChunkPosition);
        
        if(nextChunkPosition/1 == this.fileSize/1) {
            this.lastChunk = true;
        }
                     
        this.currentChunkPosition = nextChunkPosition;
        this.currentChunk = newChunk;
       
    },
    
    /**
     * Setting final fileRecord states
     */
    finishUploadRecord: function(success) {
        
        if(success) {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'complete');
            this.fileRecord.set('progress', 100);
            this.fileRecord.commit(false);
            this.fireEvent('uploadcomplete', this, this.fileRecord);               
        }
        else {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'failure');
            this.fileRecord.set('progress', -1);
            this.fileRecord.commit(false);
                       
        }
                
    },
    
   
    /**
     * executed if a chunk or file got uploaded successfully
     */
    onUploadSuccess: function(response, options, fileRecord) {
        
        response =
            Ext.util.JSON.decode(response.responseText);
        
        this.retryCount = 0;
        
        this.fileRecord.beginEdit();
        this.fileRecord.set('tempFile', response.tempFile);
        this.fileRecord.set('name', response.tempFile.name);
        this.fileRecord.set('size', response.tempFile.size);
        this.fileRecord.set('type', response.tempFile.type);
        this.fileRecord.set('path', response.tempFile.path);
        
        if(!this.isHtml5ChunkedUpload()) {
            this.fileRecord.set('status', 'complete');
        }
        
        this.fileRecord.commit(false);
        
        if(!this.isHtml5ChunkedUpload()) {
            this.fireEvent('uploadcomplete', this, this.fileRecord);
            if(response.status && response.status !== 'success') {
                this.onUploadFail(response, options, fileRecord);
            } 
        }       
        else {
            if(response.status && response.status !== 'success') {
                this.onChunkUploadFail(response, options, fileRecord);
            } 
            else if(!this.isPaused()) {

                this.addTempfile(this.fileRecord.get('tempFile'));

                var percent = parseInt(this.currentChunkPosition * 100 / this.fileSize/1);

                if(this.lastChunk) {
                    percent = 99;
                }

                this.fileRecord.beginEdit();
                this.fileRecord.set('progress', percent);
                this.fileRecord.commit(false);

                if(this.lastChunk) {

                    Ext.Ajax.request({
                        timeout: 10*60*1000, // Overriding Ajax timeout - important!
                        params: {
                            method: 'Tinebase.joinTempFiles',
                            tempFilesData: this.tempFiles
                        },
                        success: this.finishUploadRecord.createDelegate(this, [true]), 
                        failure: this.finishUploadRecord.createDelegate(this, [false])
                    });
                }
                else {
                    this.prepareChunk();
                    this.html5upload();
                }                                               
            }  
        }
    },
    
      
    /**
     * executed if a chunk / file upload failed
     */
    onUploadFail: function(response, options, fileRecord) {

        if (this.isHtml5ChunkedUpload()) {
            
            this.lastChunkUploadFailed = true;
            this.retryCount++;
            
            if (this.retryCount > this.MAX_RETRY_COUNT) {
//                alert("Upload failed: " + this.fileRecord.get('name'));
                
                this.fileRecord.beginEdit();
                this.fileRecord.set('status', 'failure');
                this.fileRecord.endEdit();

                this.fireEvent('uploadfailure', this, this.fileRecord);
            }
            else {
                window.setTimeout(function() {
                    this.prepareChunk();
                    this.html5upload();
                }.createDelegate(this), this.RETRY_TIMEOUT_MILLIS);
            }
        }
        else {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'failure');
            this.fileRecord.endEdit();

            this.fireEvent('uploadfailure', this, this.fileRecord);
        }
    },
    
    
    /**
     * uploads in a html4 fashion
     * 
     * @return {Ext.data.Connection}
     */
    html4upload: function() {
                
        alert("html4upload");
        
        var form = this.createForm();
        var input = this.getInput();
        form.appendChild(input);
        
        this.fileRecord = new Ext.ux.file.Upload.file({
            name: this.fileSelector.getFileName(),          
            size: 0,
            type: this.fileSelector.getFileCls(),
            input: input,
            form: form,
            status: 'uploading',
            progress: 0
        });
        
        
        if(this.maxFileUploadSize/1 < this.file.size/1) {
            this.fileRecord.html4upload = true;
            this.onUploadFail(null, null, this.fileRecord);
            return this.fileRecord;
        }
        
        Ext.Ajax.request({
            fileRecord: this.fileRecord,
            isUpload: true,
            method:'post',
            form: form,
            success: this.onUploadSuccess.createDelegate(this, [this.fileRecord], true),
            failure: this.onUploadFail.createDelegate(this, [this.fileRecord], true),
            params: {
                method: 'Tinebase.uploadTempFile',
                requestType: 'HTTP'
            }
        });
        
        return this.fileRecord;
    },
    
    /**
     * creating initial fileRecord for this upload
     */
    createFileRecord: function(pending) {
               
        var status = "uploading";
        if(pending) {
            status = "pending";
        }

        this.fileRecord = new Ext.ux.file.Upload.file({
            name: this.file.name ? this.file.name : this.file.fileName,  // safari and chrome use the non std. fileX props
            type: (this.file.type ? this.file.type : this.file.fileType), // missing if safari and chrome
            size: (this.file.size ? this.file.size : this.file.fileSize) || 0, // non standard but all have it ;-)
            status: status,
            progress: 0,
            input: this.getInput(),
            uploadKey: this.id
        });
    },
   
    /** 
     * adding temporary file to array 
     * 
     * @param tempfile to add
     */
    addTempfile: function(tempFile) {              
        this.tempFiles.push(tempFile);               
        return true;
    },
    
    /**
     * returns the temporary files
     * 
     * @returns {Array} temporary files
     */
    getTempfiles: function() {
        return this.tempFiles;
    },
    
    /**
     * pause oder resume file upload
     * 
     * @param paused {Boolean} set true to pause file upload
     */
    setPaused: function(paused) {
        this.paused = paused;
        
        var pausedState = 'paused';
        if(!this.paused) {
            pausedState = 'uploading';
        }
            
        this.fileRecord.beginEdit();
        this.fileRecord.set('status', pausedState);
        this.fileRecord.endEdit();
    },
    
    /**
     * indicates whether this upload ist paused
     * 
     * @returns {Boolean}
     */
    isPaused: function() {
        return this.paused;
    },
    
    /**
     * checks for the existance of a method of an object
     * 
     * @param object    {Object}
     * @param property  {String} method name 
     * @returns {Boolean}
     */
    isHostMethod: function (object, property) {
        var t = typeof object[property];
        return t == 'function' || (!!(t == 'object' && object[property])) || t == 'unknown';
    },
    
    /**
     * indicates whether the current browser supports der File.slice method
     * 
     * @returns {Boolean}
     */
    isHtml5ChunkedUpload: function() {
        
        if(window.File == undefined) return false;
        if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {
            return true;
        }
        else {
            return false;
        }       
    },
    
    // private
    getInput: function() {
        if (! this.input) {
            this.input = this.file;
        }
        
        return this.input;
    },

    /**
     * slices the given file
     * 
     * @param file  File object
     * @param start start position
     * @param end   end position            
     * @param type  file type
     * @returns
     */
    sliceFile: function(file, start, end, type) {
        
        if(file.mozSlice) {
            return file.mozSlice(start, end, type);
        }
        else if(file.webkitSlice) {
            return file.webkitSlice(start, end);
        }
        else {
            return false;
        }
        
    },
    
    /**
     * sets dthe queued state of this upload
     * 
     * @param queued {Boolean}
     */
    setQueued: function (queued) {
        this.queued = queued;
    },
    
    /**
     * indicates whethe this upload is queued
     * 
     * @returns {Boolean}
     */
    isQueued: function() {
        return this.queued;
    }
    
            
});

/**
 * upload file record
 */
Ext.ux.file.Upload.file = Ext.data.Record.create([
    {name: 'id', type: 'text', system: true},
    {name: 'uploadKey', type: 'number', system: true},
    {name: 'name', type: 'text', system: true},
    {name: 'size', type: 'number', system: true},
    {name: 'type', type: 'text', system: true},
    {name: 'status', type: 'text', system: true},
    {name: 'progress', type: 'number', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'request', system: true},
    {name: 'path', system: true},
    {name: 'tempFile', system: true}
]);

Ext.ux.file.Upload.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type']);
};
