/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase');

/**
 * Main appStarter/picker tab panel
 * 
 * NOTE: Tab panels are not sortable yet {@see http://www.extjs.com/forum/showthread.php?p=55045#post55045}
 * 
 * @todo discuss: default app vs. last active tab
 * @todo discuss: have a set of default apps?
 * 
 * @class Tine.Tinebase.AppTabsPanel
 * @extends Ext.TabPanel
 */
Tine.Tinebase.AppTabsPanel = Ext.extend(Ext.TabPanel, {
    activeTab: 1,
    
    stateful: true,
    stateEvents: ['add', 'remove', 'tabchange'],
    stateId: 'tinebase-mainscreen-apptabs',
    
    /**
     * @cfg {Array} defaultTabs
     */
    currentTabs: null/*
        'Addressbook',
        'Calendar',
        'Felamimail',
        'Tasks'
    ]*/,
    
    /**
     * init appTabsPanel
     */
    initComponent: function() {
        Ext.apply(this, Ext.state.Manager.get(this.stateId));
        
        this.initMenu();
        
        this.items = [{
            id: this.app2id('menu'),
            title: Tine.title,
            iconCls: 'tine-favicon',
            closable: true,
            listeners: {
                scope: this,
                beforeclose: this.onBeforeTabClose
            }
        }].concat(this.getDefaultTabItems());
        
        // set states last active app to the sessions default app
        Tine.Tinebase.appMgr.setDefault(this.id2appName(this.activeTab));
        
        Tine.Tinebase.appMgr.on('activate', this.onActivateApp, this);
        this.on('beforetabchange', this.onBeforeTabChange, this);
        this.on('add', this.onTabChange, this);
        this.on('remove', this.onTabChange, this);
        
        this.supr().initComponent.call(this);
    },

    /**
     * init the combined appchooser/tine menu
     */
    initMenu: function() {
        this.menu = new Ext.menu.Menu({
            layout: 'column',
            width: 400,
            autoHeight: true,
            style: {
                'background-image': 'none'
            },
            defaults: {
                xtype: 'menu',
                floating: false,
                columnWidth: 0.5,
                hidden: false,
                style: {
                    'border-color': 'transparent'
                }
            },
            items: [{
                items: this.getAppItems()
            }, {
                items: [{
                    text: 'logout'
                }]
            }]
        });
    },
    
    /**
     * executed after render
     */
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        
        this.menuTabEl = Ext.get(this.getTabEl(0));
        this.menuTabEl.addClass('tine-mainscreen-apptabspanel-menu-tabel');
    },
    
    /**
     * get app items for the tabPanel
     * 
     * @return {Array}
     */
    getAppItems: function() {
        var appItems = [];
        Tine.Tinebase.appMgr.getAll().each(function(app) {
            appItems.push({
                text: app.getTitle(),
                iconCls: app.getIconCls(),
                handler: this.onAppItemClick.createDelegate(this, [app])
            });
        }, this);
        
        return appItems.reverse();
    },
    
    /**
     * get default tab items configurations
     * 
     * @return {Array}
     */
    getDefaultTabItems: function() {
        if (Ext.isEmpty(this.currentTabs)) {
            this.currentTabs = [this.id2appName(Tine.Tinebase.appMgr.getDefault())];
        }
        
        var tabItems = [];
        
        Ext.each(this.currentTabs, function(appName) {
            var app = Tine.Tinebase.appMgr.get(appName);
            if (app) {
                tabItems.push(this.getTabItem(app));
            }
        }, this);
        
        return tabItems;
    },
    
    /**
     * get tabs state
     * 
     * @return {Object}
     */
    getState: function() {
        return {
            currentTabs: this.currentTabs,
            activeTab: Ext.isNumber(this.activeTab) ? this.activeTab : this.items.indexOf(this.activeTab)
        };
    },
    
    /**
     * get tab item configuration
     * 
     * @param {Tine.Application} app
     * @return {Object}
     */
    getTabItem: function(app) {
        return {
            id: this.app2id(app),
            title: app.getTitle(),
            iconCls: app.getIconCls(),
            closable: true,
            listeners: {
                scope: this,
                beforeclose: this.onBeforeTabClose,
                activate: this.onTabActivate
            }
        };
    },
    
    /**
     * executed when an app get activated by mainscreen
     * 
     * @param {Tine.Application} app
     */
    onActivateApp: function(app) {
        var tab = this.getItem(this.app2id(app)) || this.add(this.getTabItem(app));
        
        this.setActiveTab(tab);
    },
    
    /**
     * executed when an app item in this.menu is clicked
     * 
     * @param {Tine.Application} app
     */
    onAppItemClick: function(app) {
        Tine.Tinebase.appMgr.activate(app);
        
        this.menu.hide();
    },
    
    /**
     * executed on tab changes
     * 
     * @param {TabPanel} this
     * @param {Panel} newTab The tab being activated
     * @param {Panel} currentTab The current active tab
     */
    onBeforeTabChange: function(tp, newTab, currentTab) {
        if (this.id2appName(newTab) === 'menu') {
            this.menu.show(this.menuTabEl, 'tl-bl');
            return false;
        }
    },
    
    /**
     * executed before a tab panel is closed
     * 
     * @param {Ext.Panel} tab
     * @return {boolean}
     */
    onBeforeTabClose: function(tab) {
        if (this.id2appName(tab) === 'menu') {
            return this.onBeforeTabChange(this, tab, this.activeTab);
        }
        
        // don't close last app panel
        return this.items.getCount() > 2;
    },
    
    /**
     * executed when a tab gets activated
     * 
     * @param {Ext.Panel} tab
     */
    onTabActivate: function(tab) {
        var appName = this.id2appName(tab);
        var app = Tine.Tinebase.appMgr.get(appName);
        
        // fixme
        if (Ext.getCmp('treecards').rendered) {
            Tine.Tinebase.appMgr.activate(app);
        }
    },
    
    /**
     * executed when tabs chages
     */
    onTabChange: function() {
        var tabCount = this.items.getCount();
        var closable = tabCount > 2;
        
        this.currentTabs = [];
        
        for (var i=1, tab, el; i<tabCount; i++) {
            tab = this.items.get(i);
            
            // update currentTabs
            this.currentTabs.push(this.id2appName(tab.id));
            
            // handle closeables
            tab.closable = closable;
            el = this.getTabEl(i);
            if (el) {
                Ext.get(el)[closable ? 'addClass' : 'removeClass']('x-tab-strip-closable');
            }
        }
    },
    
    /**
     * returns appName of given tab/id
     * 
     * @param {Ext.Panel/String/Number} id
     * @return {String} appName
     */
    id2appName: function(id) {
        if (Ext.isNumber(id)) {
            if (Ext.isArray(this.items)) {
                id = this.items[id].id;
            } else {
                id = this.items.get(id);
            }
        }
        
        if (Ext.isObject(id)) {
            id = id.id;
        }
        
        if (Ext.isString(id)) {
            return id.split('-').pop();
        }
        
        return null;
    },
    
    /**
     * returns tab id of given app
     * @param {Tine.Application/String} app
     */
    app2id: function(app) {
        var appName = app.appName || app;
        
        return this.id + '-' + appName;
    }
});