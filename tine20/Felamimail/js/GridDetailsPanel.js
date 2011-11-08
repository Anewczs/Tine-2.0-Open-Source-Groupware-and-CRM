/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Message Grid Details Panel</p>
 * <p>the details panel (shows message content)</p>
 * 
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         make only text body scrollable (headers should be always visible)
 * TODO         show image attachments inline
 * TODO         add 'download all' button
 * TODO         'from' to contact: check for duplicates
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.GridDetailsPanel
 */
 Tine.Felamimail.GridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    /**
     * config
     * @private
     */
    defaultHeight: 350,
    currentId: null,
    record: null,
    app: null,
    i18n: null,
    
    fetchBodyTransactionId: null,
    
    /**
     * init
     * @private
     */
    initComponent: function() {
        this.initTemplate();
        this.initDefaultTemplate();
        this.initTopToolbar();
        
        Tine.Felamimail.GridDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * use default Tpl for default and multi view
     */
    initDefaultTemplate: function() {
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-body">{[values ? values.msg : ""]}</div>',
            '</div>'
        );
    },
    
    /**
     * init bottom toolbar (needed for event invitations atm)
     * 
     * TODO add buttons (show header, add to addressbook, create filter, show images ...) here
     */
    initTopToolbar: function() {
        this.tbar = new Ext.Toolbar({
            hidden: true,
            items: []
        });
    },
    
    /**
     * process email invitation
     * 
     * @param {String} status
     */
    processInvitation: function(status) {
        Tine.log.debug('Setting event attender status: ' + status);

        var invitationEvent = new Tine.Calendar.Model.Event(this.record.get('invitation_event')),
            myAttenderRecord = invitationEvent.getMyAttenderRecord();
        
        myAttenderRecord.set('status', status);
        Tine.Felamimail.setInvitationStatus(invitationEvent.data, myAttenderRecord.data, this.onInvitationStatusUpdate.createDelegate(this));
    },

    /**
     * callback for invitation status update
     * 
     * @param {Object} response
     */
    onInvitationStatusUpdate: function(response) {
        this.getTopPanel().getTopToolbar().setVisible(false);
        this.getTopPanel().setHeight(26);
        this.getTopPanel().doLayout();
    },
    
    /**
     * add on click event after render
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.GridDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
    },
    
    /**
     * get panel for single record details
     * 
     * @return {Ext.Panel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.Panel({
                layout: 'vbox',
                layoutConfig: {
                    align:'stretch'
                },
                border: false,
                items: [
                    this.getTopPanel(),
                    this.getMessageRecordPanel(),
                    this.getBottomPanel()
                ]
            });
        }
        return this.singleRecordPanel;
    },

    /**
     * get top panel
     * 
     * @return {Ext.Panel}
     */
    getTopPanel: function() {
        if (! this.topPanel) {
            this.topPanel = new Ext.Panel({
                hidden: true,
                layout: 'fit',
                height: 26,
                border: false,
                // TODO style this
                html: this.app.i18n._('This invitation has already been processed.'),
                tbar: new Ext.Toolbar({
                    items: [
                        {
                            xtype: 'tbitem',
                            cls: 'CalendarIconCls',
                            width: 16,
                            height: 16
                        },
                        this.app.i18n._('You received an event invitation. Please select a response.'),
                        '->',
                        new Ext.Action({
                            text: this.app.i18n._('Accept'),
                            handler: this.processInvitation.createDelegate(this, ['ACCEPTED']),
                            iconCls: 'cal-response-action-ACCEPTED',
                            disabled: false,
                            scope: this
                        }),
                        new Ext.Action({
                            text: this.app.i18n._('Decline'),
                            handler: this.processInvitation.createDelegate(this, ['DECLINED']),
                            iconCls: 'cal-response-action-DECLINED',
                            disabled: false,
                            scope: this
                        }),
                        new Ext.Action({
                            text: this.app.i18n._('Tentative'),
                            handler: this.processInvitation.createDelegate(this, ['TENTATIVE']),
                            iconCls: 'cal-response-action-TENTATIVE',
                            disabled: false,
                            scope: this
                        })
                    ]
                 })
            });
        }
        return this.topPanel;
    },
    
    /**
     * get panel for single record details
     * 
     * @return {Ext.Panel}
     */
    getMessageRecordPanel: function() {
        if (! this.messageRecordPanel) {
            this.messageRecordPanel = new Ext.Panel({
                flex: 1
            });
        }
        return this.messageRecordPanel;
    },
    
    /**
     * get bottom panel
     * 
     * @return {Ext.Panel}
     */
    getBottomPanel: function() {
        if (! this.bottomPanel) {
            var calendarDetailsPanel = new Tine.Calendar.EventDetailsPanel();
            this.bottomPanel = calendarDetailsPanel.getSingleRecordPanel();
            this.bottomPanel.setHeight(150);
            this.bottomPanel.setVisible(false);
        }
        return this.bottomPanel;
    },
    
    /**
     * (on) update details
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {String} body
     * @private
     */
    updateDetails: function(record, body) {
        if (record.id === this.currentId) {
            // nothing to do
        } else if (! record.bodyIsFetched()) {
            this.waitForContent(record, this.getMessageRecordPanel().body);
        } else if (record === this.record) {
            this.setTemplateContent(record, this.getMessageRecordPanel().body);
        }
    },
    
    /**
     * wait for body content
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {String} body
     */
    waitForContent: function(record, body) {
        if (! this.grid || this.grid.getSelectionModel().getCount() == 1) {
            this.refetchBody(record, this.updateDetails.createDelegate(this, [record, body]), 'updateDetails');
            this.defaultTpl.overwrite(body, {msg: ''});
            this.getLoadMask().show();
        } else {
            this.getLoadMask().hide();
        }
    },
    
    /**
     * refetch message body
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Function} callback
     * @param {String} fnName
     */
    refetchBody: function(record, callback, fnName) {
        // cancel old request first
        if (this.fetchBodyTransactionId && ! Tine.Felamimail.messageBackend.isLoading(this.fetchBodyTransactionId)) {
            Tine.log.debug('Tine.Felamimail.GridDetailsPanel::' + fnName + '() cancelling current fetchBody request.');
            Tine.Felamimail.messageBackend.abort(this.fetchBodyTransactionId);
        }
        this.fetchBodyTransactionId = Tine.Felamimail.messageBackend.fetchBody(record, callback);
    },
    
    /**
     * overwrite template with (body) content
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {String} body
     */
    setTemplateContent: function(record, body) {
        this.currentId = record.id;
        this.getLoadMask().hide();

        if (this.record.get('invitation_event')) {
            Tine.log.debug('got invitation event');
            this.handleInvitationMessage(record);
        } else {
            Tine.log.debug('no invitation event');
            this.getTopPanel().setVisible(false);
            this.getBottomPanel().setVisible(false);
        }
        
        this.doLayout();

        this.tpl.overwrite(body, record.data);
        this.getEl().down('div').down('div').scrollTo('top', 0, false);
    },
    
    /**
     * handle invitation messages (show top + bottom panels)
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * 
     * TODO replace email body with event details panel?
     */
    handleInvitationMessage: function(record) {
        var invitationEvent = Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.record.get('invitation_event'))
            }),
            myAttenderRecord = invitationEvent.getMyAttenderRecord(),
            status = myAttenderRecord.get('status');

        Tine.log.debug('invitation status: ' + status);
        
        this.getTopPanel().setVisible(true);   
        if (status !== 'NEEDS-ACTION') {
            this.onInvitationStatusUpdate();
        }

        this.getBottomPanel().setVisible(true);
        this.getBottomPanel().loadRecord.defer(100, this.getBottomPanel(), [invitationEvent]);
    },
    
    /**
     * init single message template (this.tpl)
     * @private
     */
    initTemplate: function() {
        
        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-headers">',
                    '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                    '<b>' + this.i18n._('From') + ':</b>',
                    ' {[this.showFrom(values.from_email, values.from_name, "' + this.i18n._('Add') + '", "' 
                        + this.i18n._('Add contact to addressbook') + '")]}<br/>',
                    '<b>' + this.i18n._('Date') + ':</b> {[this.encode(values.received)]}',
                    '{[this.showRecipients(values.headers)]}',
                    '{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
                '</div>',
                '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
                '<div class="preview-panel-felamimail-body">{[this.showBody(values.body, values)]}</div>',
            '</div>',{
            app: this.app,
            panel: this,
            encode: function(value) {
                if (value) {
                    var encoded = Ext.util.Format.htmlEncode(value);
                    encoded = Ext.util.Format.nl2br(encoded);
                    // it should be enough to replace only 2 or more spaces
                    encoded = encoded.replace(/ /g, '&nbsp;');
                    
                    return encoded;
                } else {
                    return '';
                }
                return value;
            },
            
            showFrom: function(email, name, addText, qtip) {
                if (name === null) {
                    return '';
                }
                
                var result = this.encode(name + ' <' + email + '>');
                
                // add link with 'add to contacts'
                var id = Ext.id() + ':' + email;
                
                var nameSplit = name.match(/^"*([^,^ ]+)(,*) *(.+)/i);
                var firstname = (nameSplit && nameSplit[1]) ? nameSplit[1] : '';
                var lastname = (nameSplit && nameSplit[3]) ? nameSplit[3] : '';
                if (nameSplit && nameSplit[2] == ',') {
                    firstname = lastname;
                    lastname = nameSplit[1];
                }
                
                id += Ext.util.Format.htmlEncode(':' + Ext.util.Format.trim(firstname) + ':' + Ext.util.Format.trim(lastname));
                result += ' <span ext:qtip="' + qtip + '" id="' + id + '" class="tinebase-addtocontacts-link">[+]</span>';
                return result;
            },
            
            showBody: function(body, messageData) {
                body = body || '';
                if (body) {
                    var account = this.app.getActiveAccount();
                    if (account && (account.get('display_format') == 'plain' || 
                        (account.get('display_format') == 'content_type' && messageData.body_content_type == 'text/plain'))
                    ) {
                        var width = this.panel.body.getWidth()-25,
                            height = this.panel.body.getHeight()-90,
                            id = Ext.id();
                            
                        if (height < 0) {
                        	// sometimes the height is negative, fix this here
                            height = 500;
                        }
                            
                        body = '<textarea ' +
                            'style="width: ' + width + 'px; height: ' + height + 'px; " ' +
                            'autocomplete="off" id="' + id + '" name="body" class="x-form-textarea x-form-field x-ux-display-background-border" readonly="" >' +
                            body + '</textarea>';
                    }
                }                    
                return body;
            },
            
            showHeaders: function(qtip) {
                var result = ' <span ext:qtip="' + qtip + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                return result;
            },
            
            showRecipients: function(value) {
                if (value) {
                    var i18n = Tine.Tinebase.appMgr.get('Felamimail').i18n,
                        result = '';
                    for (header in value) {
                        if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                            result += '<br/><b>' + i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> ' 
                                + Ext.util.Format.htmlEncode(value[header]);
                        }
                    }
                    return result;
                } else {
                    return '';
                }
            },
            
            showAttachments: function(attachements, messageData) {
                var result = (attachements.length > 0) ? '<b>' + this.app.i18n._('Attachments') + ':</b> ' : '';
                
                for (var i=0, id, cls; i < attachements.length; i++) {
                    result += '<span id="' + Ext.id() + ':' + i + '" class="tinebase-download-link">' 
                        + '<i>' + attachements[i].filename + '</i>' 
                        + ' (' + Ext.util.Format.fileSize(attachements[i].size) + ')</span> ';
                }
                
                return result;
            }
        });
    },
    
    /**
     * on click for attachment download / compose dlg / edit contact dlg
     * 
     * @param {} e
     * @private
     */
    onClick: function(e) {
        var selectors = [
            'span[class=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'span[class=tinebase-addtocontacts-link]',
            'span[class=tinebase-showheaders-link]'
        ];
        
        // find the correct target
        for (var i=0, target=null, selector=''; i < selectors.length; i++) {
            target = e.getTarget(selectors[i]);
            if (target) {
                selector = selectors[i];
                break;
            }
        }
        
        Tine.log.debug('Tine.Felamimail.GridDetailsPanel::onClick found target:"' + selector + '".');
        
        switch (selector) {
            case 'span[class=tinebase-download-link]':
                var idx = target.id.split(':')[1];
                    attachment = this.record.get('attachments')[idx];
                    
                if (! this.record.bodyIsFetched()) {
                    // sometimes there is bad timing and we do not have the attachments available -> refetch body
                    this.refetchBody(this.record, this.onClick.createDelegate(this, [e]), 'onClick');
                    return;
                }
                    
                // remove part id if set (that is the case in message/rfc822 attachments)
                var messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.id;
                    
                if (attachment['content-type'] === 'message/rfc822') {
                    
                    Tine.log.debug('Tine.Felamimail.GridDetailsPanel::onClick openWindow for:"' + messageId + '_' + attachment.partId + '".');
                    // display message
                    Tine.Felamimail.MessageDisplayDialog.openWindow({
                        record: new Tine.Felamimail.Model.Message({
                            id: messageId + '_' + attachment.partId
                        })
                    });
                    
                } else {
                    // download attachment
                    new Ext.ux.file.Download({
                        params: {
                            requestType: 'HTTP',
                            method: 'Felamimail.downloadAttachment',
                            messageId: messageId,
                            partId: attachment.partId
                        }
                    }).start();
                }
                
                break;
                
            case 'a[class=tinebase-email-link]':
                // open compose dlg
                var email = target.id.split(':')[1];
                var defaults = Tine.Felamimail.Model.Message.getDefaultData();
                defaults.to = [email];
                defaults.body = Tine.Felamimail.getSignature();
                
                var record = new Tine.Felamimail.Model.Message(defaults, 0);
                var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                    record: record
                });
                break;
                
            case 'span[class=tinebase-addtocontacts-link]':
                // open edit contact dlg
            
                // check if addressbook app is available
                if (! Tine.Addressbook || ! Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
                    return;
                }
            
                var id = Ext.util.Format.htmlDecode(target.id);
                var parts = id.split(':');
                
                var popupWindow = Tine.Addressbook.ContactEditDialog.openWindow({
                    listeners: {
                        scope: this,
                        'load': function(editdlg) {
                            editdlg.record.set('email', parts[1]);
                            editdlg.record.set('n_given', parts[2]);
                            editdlg.record.set('n_family', parts[3]);
                        }
                    }
                });
                
                break;
                
            case 'span[class=tinebase-showheaders-link]':
                // show headers
            
                var parts = target.id.split(':');
                var targetId = parts[0];
                var action = parts[1];
                
                var html = '';
                if (action == 'show') {
                    var recordHeaders = this.record.get('headers');
                    
                    for (header in recordHeaders) {
                        if (recordHeaders.hasOwnProperty(header) && (header != 'to' || header != 'cc' || header != 'bcc')) {
                            html += '<br/><b>' + header + ':</b> ' 
                                + Ext.util.Format.htmlEncode(recordHeaders[header]);
                        }
                    }
                
                    target.id = targetId + ':' + 'hide';
                    
                } else {
                    html = ' <span ext:qtip="' + this.i18n._('Show or hide header information') + '" id="' 
                        + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>'
                }
                
                target.innerHTML = html;
                
                break;
        }
    }
});
