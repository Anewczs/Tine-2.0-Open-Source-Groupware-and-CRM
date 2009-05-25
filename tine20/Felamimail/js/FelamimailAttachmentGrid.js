/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         init attachments (on forward)
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * attachment grid for compose dialog
 * 
 * @class Tine.Felamimail.AttachmentGrid
 * @extends Ext.grid.GridPanel
 */
Tine.Felamimail.AttachmentGrid = Ext.extend(Ext.grid.GridPanel, {
    
    id: 'felamimail-attachment-grid',
    il8n: null,
    
    /**
     * actions
     * 
     * @type Object
     */
    actions: {
        add: null,
        remove: null
    },
    
    /**
     * config values
     */
    height: 100,
    header: false,
    frame: true,
    border: false,
    deferredRender: false,
    loadMask: true,
    
    /**
     * init
     */
    initComponent: function() {
        
        this.initToolbar();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();
        
        Tine.Felamimail.AttachmentGrid.superclass.initComponent.call(this);
    },
    
    /**************************************** event handlers ***************************************/
    
    /**
     * button event handlers
     */
    handlers: {   
        
        /**
         * upload new attachment and add to store
         * 
         * @param {} _button
         * @param {} _event
         */
        add: function(_button, _event) {

            var input = _button.detachInputFile();
            var uploader = new Ext.ux.file.Uploader({
                input: input
            });
            uploader.on('uploadcomplete', function(uploader, file){
                this.loadMask.hide();
                
                var attachment = new Tine.Felamimail.Model.Attachment(file.get('tempFile'));
                this.store.add(attachment);
                
            }, this);
            uploader.on('uploadfailure', this.onUploadFail, this);
            
            this.loadMask.show();
            uploader.upload();
        },

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
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(
            this.il8n._('Upload Failed'), 
            this.il8n._('Could not upload attachment. Filesize could be too big. Please notify your Administrator.')
        ).setIcon(Ext.MessageBox.ERROR);
        this.loadMask.hide();
    },

    /**************************************** init funcs ***************************************/
    
    /**
     * init toolbar
     */
    initToolbar: function() {
        this.actions.add = new Ext.Action({
            text: this.il8n._('Add Attachment'),
            iconCls: 'actionAdd',
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({})],
            handler: this.handlers.add
        });

        this.actions.remove = new Ext.Action({
            text: this.il8n._('Remove Attachment'),
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
     */
    initStore: function() {
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Felamimail.Model.Attachment
        });
        
        // init attachments (on forward)
        /*
        if (this.record.get('to') && this.record.get('to') != '') {
            this.store.add(new Ext.data.Record({type: 'to', 'address': this.record.get('to')}));
            this.record.data.to = [this.record.get('to')];
        } else {
            this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        }
        */
    },
    
    /**
     * init cm
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'name',
                dataIndex: 'name',
                width: 300,
                header: 'name'
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
     */
    initSelectionModel: function() {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        this.selModel.on('selectionchange', function(selModel) {
            var rowCount = selModel.getCount();
            this.actions.remove.setDisabled(rowCount == 0);
        }, this);
    }
});
