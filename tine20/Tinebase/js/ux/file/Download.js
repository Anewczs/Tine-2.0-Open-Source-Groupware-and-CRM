/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux.file');

Ext.ux.file.Download = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Ext.ux.file.Download.superclass.constructor.call(this);
    
    this.addEvents({
        'success': true,
        'fail': true,
        'abort': true
    });
};

Ext.extend(Ext.ux.file.Download, Ext.util.Observable, {    
    url: null,
    method: 'POST',
    params: null,
    
    /**
     * @private 
     */
    form: null,
    transactionId: null,
    
    /**
     * start download
     */
    start: function() {
        this.form = Ext.getBody().createChild({
            tag:'form',
            method: this.method,
            cls:'x-hidden'
        });

        // firefox specific problem -> see http://www.extjs.com/forum/archive/index.php/t-44862.html
        //  "It appears that this is because the "load" is completing once the initial download dialog is displayed, 
        //  but the frame is then destroyed before the "save as" dialog is shown."
        //
        // TODO check if we can handle firefox event 'onSaveAsSubmit' (or something like that)
        //
        if (Ext.isGecko()) {
            // use Ext.data.Connection instead of Ext.Ajax.request
            var con = new Ext.data.Connection({
                // don't remove the iframe with this param
                debugUploads: true
            });
            
            this.transactionId = con.request({
                isUpload: true,
                form: this.form,
                params: this.params,
                scope: this,
                success: this.onSuccess,
                failure: this.onFailure,
                url: 'index.php'
            });
        } else {
            this.transactionId = Ext.Ajax.request({
                isUpload: true,
                form: this.form,
                params: this.params,
                scope: this,
                success: this.onSuccess,
                failure: this.onFailure,
                debugUploads: true
            });
        }
    },
    
    /**
     * abort download
     */
    abort: function() {
        Ext.Ajax.abort(this.transactionId);
        this.form.remove();
        this.fireEvent('abort', this);
    },
    
    /**
     * @private
     * 
     */
    onSuccess: function() {
        this.form.remove();
        this.fireEvent('success', this);
    },
    
    /**
     * @private
     * 
     */
    onFailure: function() {
        this.form.remove();
        this.fireEvent('fail', this);
    }
    
});
