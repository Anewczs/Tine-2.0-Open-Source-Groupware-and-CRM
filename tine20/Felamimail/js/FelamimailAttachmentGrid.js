/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.AttachmentGrid
 * @extends     Ext.grid.GridPanel
 * 
 * <p>Attachment grid for compose dialog</p>
 * <p>
 * TODO         remove handlers / replace with onXXX
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * 
 * @constructor
 * Create a new  Tine.Felamimail.AttachmentGrid
 */
Tine.Felamimail.AttachmentGrid = Ext.extend(Ext.grid.GridPanel, {
    
	/**
	 * @private
	 */
    id: 'felamimail-attachment-grid',
    i18n: null,
    
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
        
        this.initToolbar();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();
        
        Tine.Felamimail.AttachmentGrid.superclass.initComponent.call(this);
    },
    
    /**
     * button event handlers
     * @private
     */
    handlers: {   
        /**
         * remove attachment from store
         * 
         * @param {} _button
         * @param {} _event
         */
        remove: function(_button, _event) {
            var selectedRows = this.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                this.store.remove(selectedRows[i]);
            }                       
        }
    },
    
    /**
     * on upload failure
     * @private
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(
            this.i18n._('Upload Failed'), 
            this.i18n._('Could not upload attachment. Filesize could be too big. Please notify your Administrator. Max upload size: ') 
                + Tine.Felamimail.registry.get('maxAttachmentSize')
        ).setIcon(Ext.MessageBox.ERROR);
        this.loadMask.hide();
    },

    /**
     * init toolbar
     * @private
     */
    initToolbar: function() {
        this.actions.add = new Ext.Action({
            text: this.i18n._('Add Attachment'),
            iconCls: 'actionAdd',
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({
                multiple: true,
                dropElSelector: 'div[id=felamimail-attachment-grid]'
            })],
            handler: this.onFilesSelect
        });

        this.actions.remove = new Ext.Action({
            text: this.i18n._('Remove Attachment'),
            iconCls: 'actionRemove',
            scope: this,
            disabled: true,
            handler: this.handlers.remove
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
        
        // init attachments (on forward)
        if (this.record.get('attachments')) {
            var attachments = this.record.get('attachments');
            for (var i=0; i < attachments.length; i++) {
                this.store.add(new Ext.data.Record(attachments[i]));
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
                header: 'name',
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
                width: 100,
                header: 'size',
                renderer: Ext.util.Format.fileSize
            },{
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 100,
                header: 'type'
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
     * upload new attachment and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function(fileSelector, e) {
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
    }
});
