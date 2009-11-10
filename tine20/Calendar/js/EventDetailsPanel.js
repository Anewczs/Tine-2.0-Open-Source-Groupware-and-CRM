/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.EventDetailsPanel
 * @namespace Tine.Calendar
 * @extends Tine.Tinebase.widgets.grid.DetailsPanel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @version $Id$
 */
Tine.Calendar.EventDetailsPanel = Ext.extend(Tine.Tinebase.widgets.grid.DetailsPanel, {
    border: false,
    
    /**
     * renders attendee names
     * 
     * @param {Array} attendeeData
     * @return {String}
     */
    attendeeRenderer: function(attendeeData) {
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(attendeeData);
        
        var a = [];
        attendeeStore.each(function(attender) {
            a.push(Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender));
        });
        
        return a.join("\n");
    },
    
    /**
     * renders container name + color
     * 
     * @param {Array} container
     * @return {String} html
     */
    containerRenderer: function(container) {
        return this.containerTpl.apply({
            color: Tine.Calendar.colorMgr.getColor(this.record).color,
            name: Ext.util.Format.htmlEncode(container && container.name ? container.name : '')
        });
    },
    
    /**
     * renders datetime
     * 
     * @param {Date} dt
     * @return {String}
     */
    datetimeRenderer: function(dt) {
        return String.format(this.app.i18n._("{0} {1} o'clock"), Tine.Tinebase.common.dateRenderer(dt), dt.format('H:i'));
    },
    
    /**
     * inits this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.defaultPanel = this.getDefaultPanel();
        this.eventDetailsPanel = this.getEventDetailsPanel();
        
        this.cardPanel = new Ext.Panel({
            layout: 'card',
            border: false,
            activeItem: 0,
            items: [
                this.defaultPanel,
                this.eventDetailsPanel
            ]
        });
        
        this.items = [
            this.cardPanel
        ];
        
        this.containerTpl = new Ext.XTemplate(
            '<div class="x-tree-node-el x-tree-node-leaf x-unselectable file">',
                '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                '<span style="color: {color};">&nbsp;&#9673;&nbsp</span>',
                '<span>{name}</span>',
            '</div>'
        ).compile();
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * default panel w.o. data
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getDefaultPanel: function() {
        return new Ext.ux.display.DisplayPanel ({
            layout: 'fit',
            border: false,
            items: [{
                layout: 'hbox',
                border: false,
                defaults:{margins:'0 5 0 0'},
                layoutConfig: {
                    padding:'5',
                    align:'stretch'
                },
                items: [{
                    flex: 1,
                    border: false,
                    layout: 'ux.display',
                    layoutConfig: {
                        background: 'solid',
                        declaration: this.app.i18n._('Event', 'Events', 50)
                    }
                }, {
                    flex: 1,
                    border: false,
                    layout: 'ux.display',
                    layoutConfig: {
                        background: 'border'
                    }
                }]
            }]
        });
    },
    
    /**
     * main event details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getEventDetailsPanel: function() {
        return new Ext.ux.display.DisplayPanel ({
            //xtype: 'displaypanel',
            layout: 'fit',
            border: false,
            items: [{
                layout: 'vbox',
                border: false,
                layoutConfig: {
                    align:'stretch'
                },
                items: [{
                    layout: 'hbox',
                    flex: 0,
                    height: 16,
                    border: false,
                    style: 'padding-left: 5px; padding-right: 5px',
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [{
                        flex: 1,
                        xtype: 'ux.displayfield',
                        name: 'summary'
                        //fieldLabel: this.app.i18n._('Summary')
                    }, {
                        flex: 1,
                        xtype: 'ux.displayfield',
                        style: 'text-align: right;',
                        name: 'container_id',
                        htmlEncode: false,
                        renderer: this.containerRenderer.createDelegate(this)
                    }]
                }, {
                    layout: 'hbox',
                    flex: 1,
                    border: false,
                    layoutConfig: {
                        padding:'5',
                        align:'stretch'
                    },
                    defaults:{margins:'0 5 0 0'},
                    items: [{
                        flex: 2,
                        layout: 'ux.display',
                        labelWidth: 60,
                        layoutConfig: {
                            background: 'solid'
                        },
                        items: [{
                            xtype: 'ux.displayfield',
                            name: 'dtstart',
                            fieldLabel: this.app.i18n._('Start Time'),
                            renderer: this.datetimeRenderer.createDelegate(this)
                        }, {
                            xtype: 'ux.displayfield',
                            name: 'dtend',
                            fieldLabel: this.app.i18n._('End Time'),
                            renderer: this.datetimeRenderer.createDelegate(this)
                        }, {
                            xtype: 'ux.displayfield',
                            name: 'location',
                            fieldLabel: this.app.i18n._('Location')
                        }]
                    }, {
                        flex: 2,
                        layout: 'ux.display',
                        labelAlign: 'top',
                        autoScroll: true,
                        layoutConfig: {
                            background: 'solid'
                        },
                        items: [{
                            xtype: 'ux.displayfield',
                            name: 'attendee',
                            nl2br: true,
                            fieldLabel: this.app.i18n._('Attendee'),
                            renderer: this.attendeeRenderer
                        }]
                    }, {
                        flex: 3,
                        layout: 'fit',
                        
                        border: false,
                        items: [{
                            cls: 'x-ux-display-background-border',
                            xtype: 'ux.displaytextarea',
                            name: 'description'
                        }]
                    }]
                }]
            }]
        });
    },
    
    /**
     * update event details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.eventDetailsPanel));
        
        this.eventDetailsPanel.loadRecord(record);
    },
    
    /**
     * show default panel
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.defaultPanel));
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        //if (this.multiTpl) {
        //    this.multiTpl.overwrite(body);
        //}
    }
});
