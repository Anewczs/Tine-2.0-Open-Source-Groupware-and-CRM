/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: CalendarPanel.js 7900 2009-04-30 07:23:02Z c.weiss@metaways.de $
 */

Ext.namespace('Tine.Calendar');

/**
 * update app icon to reflect the current date
 */
Ext.onReady(function(){
    Ext.util.CSS.updateRule('.CalendarIconCls', 'background-image', 'url(../../images/view-calendar-day-' + new Date().getDate() + '.png)');
    
});

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.Application
 * @extends     Tine.Tinebase.Application
 * Calendar Application Object <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 */
Tine.Calendar.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Calendar', 'Calendars', 1);
    }
});

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.MainScreen
 * @extends Tine.Tinebase.widgets.app.MainScreen
 * MainScreen of the Calendar Application <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 * @constructor
 * Constructs mainscreen of the calendar application
 */
Tine.Calendar.MainScreen = function(config) {
    Ext.apply(this, config);
    Tine.Calendar.colorMgr = new Tine.Calendar.ColorManager({});
    
    Tine.Calendar.MainScreen.superclass.constructor.call(this);
}

Ext.extend(Tine.Calendar.MainScreen, Tine.Tinebase.widgets.app.MainScreen, {
    
    /**
     * Set content panel in Tinebase.MainScreen
     */
    setContentPanel: function() {
        if (! this.contentPanel) {
            this.contentPanel = new Tine.Calendar.MainScreenCenterPanel({
            
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.contentPanel, true);
    },
    
    /**
     * Get content panel of calendar application
     * 
     * @return {Tine.Calendar.MainScreenCenterPanel}
     */
    getContentPanel: function() {
        return this.contentPanel;
    },
    
    /**
     * Set toolbar panel in Tinebase.MainScreen
     */
    setToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                items: this.contentPanel.actionToolbarActions
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    }
});

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.TreePanel
 * @extends Ext.Panel
 * Left Calendar Panel including Tree and DatePicker<br>
 * @todo add d&d support to tree (change calendar)
 * @todo why the hack is the strech option not working???
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 */
Tine.Calendar.TreePanel = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'vbox',
    align: 'stretch',
    cls: 'cal-tree',
    defaults: {
        border: false
    },
    
    initComponent: function() {
        
        this.calSelector = new Tine.widgets.container.TreePanel({
            //region: 'center',
            width: 200,
            app: Tine.Tinebase.appMgr.get('Calendar'),
            recordClass: Tine.Calendar.Model.Event,
            allowMultiSelection: true,
            afterRender: Tine.widgets.container.TreePanel.prototype.afterRender.createSequence(function() {
                this.selectPath('/root/all/user');
            }),
            listeners: {
                scope: this,
                click: function() {
                    var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                    contentPanel.refresh();
                }
            }
        });
        
        this.items = [this.calSelector, {
            xtype:'spacer',
            flex:1
        }, {
            //region: 'south',
            //collapsible: true,
            height: 190,
            width: 200,
            items: new Ext.DatePicker({
                id :'cal-mainscreen-minical',
                plugins: [new Ext.ux.DatePickerWeekPlugin({
                    weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK'),
                    inspectMonthPickerClick: function(btn, e) {
                        if (e.getTarget().id) {
                            var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                            contentPanel.changeView('month', this.activeDate);
                            
                            return false;
                        }
                    }
                })],
                listeners: {
                    scope: this, 
                    select: function(picker, value, weekNumber) {
                        var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                        contentPanel.changeView(weekNumber ? 'week' : 'day', value);
                    },
                    render2: function(picker) {
                        // fix height of minipicker panel (south panel)
                        var layout = this.layout;
                        layout.south.el.setHeight(picker.el.getHeight());
                        layout.layout();
                    }
                }
            })
        }];   
        Tine.Calendar.TreePanel.superclass.initComponent.call(this);
    },
    
    /**
     * return calendar selector tree
     * 
     * @return {Tine.widgets.container.TreePanel}
     */
    getCalSelector: function() {
        return this.calSelector;
    },
    
    /**
     * returns a calendar to take for an add event action
     * 
     * @todo add more sophisticated logic
     * 
     * @return {Tine.Model.Container}
     */
    getAddCalendar: function() {
        var selections = this.getCalSelector().getSelectionModel().getSelectedNodes();
        
        var addCalendar = Tine.Calendar.registry.get('defaultCalendar');
        // take first container with add grant
        Ext.each(selections, function(node){
            var attr = node.attributes;
            if (attr.containerType == "singleContainer" && attr.container.account_grants.addGrant) {
                addCalendar = attr.container;
                return false;
            }
        });
        
        return addCalendar
    }
});

