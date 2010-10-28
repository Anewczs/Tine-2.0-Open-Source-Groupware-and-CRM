/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FileUploadGrid
 * @extends     Ext.grid.GridPanel
 * 
 * <p>FileUpload grid for dialogs</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * 
 * @constructor Create a new  Tine.widgets.grid.FileUploadGrid
 */
Tine.widgets.grid.FileUploadGrid = Ext.extend(Ext.grid.GridPanel, {
    
    /**
     * @private
     */
    id: 'tinebase-file-grid',
    
    /**
     * config filesProperty
     * @type String
     */
    filesProperty: 'files',
    
    /**
     * actions
     * 
     * @type {Object}
     * @private
     */
    actions: {
        add: null,
        remove: null
    },
    
    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    loadMask: true,
    autoExpandColumn: 'name',
    
    /**
     * init
     * @private
     */
    initComponent: function() {
        this.record = this.record || null;
        this.id = this.id + Ext.id();
        
        this.initToolbar();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();
        
        this.plugins = [ new Ext.ux.grid.GridViewMenuPlugin({}) ];
        this.enableHdMenu = false;
        
        Tine.widgets.grid.FileUploadGrid.superclass.initComponent.call(this);
    },
    
    /**
     * on upload failure
     * @private
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(
            _('Upload Failed'), 
            _('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size: ') 
                + Tine.Tinebase.registry.get('maxFileUploadSize')
        ).setIcon(Ext.MessageBox.ERROR);
        this.loadMask.hide();
    },
    
    /**
     * on remove
     * @param {} _button
     * @param {} _event
     */
    onRemove: function(_button, _event) {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
        }                       
    },

    /**
     * init toolbar
     * @private
     */
    initToolbar: function() {
        this.actions.add = new Ext.Action({
            text: _('Add file'),
            iconCls: 'actionAdd',
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                dropElSelector: 'div[id=' + this.id + ']'
            }],
            handler: this.onFilesSelect
        });

        this.actions.remove = new Ext.Action({
            text: _('Remove file'),
            iconCls: 'actionRemove',
            scope: this,
            disabled: true,
            handler: this.onRemove
        });
        
        this.tbar = [                
            this.actions.add,
            this.actions.remove
        ]; 
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        this.store = new Ext.data.SimpleStore({
            fields: Ext.ux.file.Uploader.file
        });
        
        this.loadRecord(this.record);
    },
    
    getAddAction: function () {
        return this.actions.add;
    },
    
    /**
     * populate grid store
     * 
     * @param {} record
     */
    loadRecord: function(record) {
        if (record && record.get(this.filesProperty)) {
            var files = record.get(this.filesProperty);
            for (var i=0; i < files.length; i++) {
                var file = new Ext.ux.file.Uploader.file(files[i]);
                file.data.status = 'complete';
                this.store.add(file);
            }
        }
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'name',
                dataIndex: 'name',
                width: 300,
                header: _('name'),
                renderer: function(value, metadata, record) {
                    var val = value;
                    if (record.get('status') !== 'complete') {
                        //val += ' (' + record.get('progress') + '%)';
                        metadata.css = 'x-fmail-uploadrow';
                    }
                    
                    return val;
                }
            },{
                resizable: true,
                id: 'size',
                dataIndex: 'size',
                width: 70,
                header: _('size'),
                renderer: Ext.util.Format.fileSize
            },{
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 70,
                header: _('type')
                // TODO show type icon?
                //renderer: Ext.util.Format.fileSize
            }
        ]);
    },

    /**
     * init sel model
     * @private
     */
    initSelectionModel: function() {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        this.selModel.on('selectionchange', function(selModel) {
            var rowCount = selModel.getCount();
            this.actions.remove.setDisabled(rowCount == 0);
        }, this);
    },
    
    /**
     * upload new file and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function(fileSelector, e) {
        Tine.log.debug(fileSelector);
        
        var uploader = new Ext.ux.file.Uploader({
            maxFileSize: 67108864, // 64MB
            fileSelector: fileSelector
        });
                
        uploader.on('uploadfailure', this.onUploadFail, this);
        
        var files = fileSelector.getFileList();
        Ext.each(files, function(file){
            var fileRecord = uploader.upload(file);
            this.store.add(fileRecord);
        }, this);
    },
    
    /**
     * returns true if files are uploading atm
     * 
     * @return {Boolean}
     */
    isUploading: function() {
        var uploadingFiles = this.store.query('status', 'uploading');
        return (uploadingFiles.getCount() > 0);
    }
});
