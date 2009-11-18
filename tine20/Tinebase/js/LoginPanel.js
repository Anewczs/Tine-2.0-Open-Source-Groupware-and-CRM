/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.namespace('Tine.Tinebase');

/**
 * @class Tine.Tinebase.LoginPanel
 * @namespace Tine.Tinebase
 * @extends Ext.Panel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @version $Id$
 */
Tine.Tinebase.LoginPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {String} defaultUsername prefilled username
     */
    defaultUsername: '',
    
    /**
     * @cfg {String} defaultPassword prefilled password
     */
    defaultPassword: '',
    
    /**
     * @cfg {String} loginMethod server side login method
     */
    loginMethod: 'Tinebase.login',
    
    /**
     * @cfg {String} loginLogo logo to show
     */
    loginLogo: 'images/tine_logo.gif',
    
    /**
     * @cfg {String} onLogin callback after successfull login
     */
    onLogin: Ext.emptyFn,
    
    /**
     * @cfg {String} scope scope of login callback
     */
    scope: null,
    
    layout: 'fit',
    border: false,
    
    /**
     * return loginPanel
     * 
     * @return {Ext.FromPanel}
     */
    getLoginPanel: function() {
        if (! this.loginPanel) {
            this.loginPanel = new Ext.FormPanel({
                frame:true,
                labelWidth: 90,
                cls: 'tb-login-panel',
                items: [{
                    cls: 'tb-login-lobobox',
                    border: false,
                    html: '<a target="_blank" href="http://www.tine20.org/" border="0"><img src="' + this.loginLogo +'" /></a>'
                }, {
                    xtype: 'label',
                    cls: 'tb-login-big-label',
                    text: _('Login')
                }, new Tine.widgets.LangChooser({
                    width: 170,
                    tabindex: 1
                }), {
                    xtype: 'textfield',
                    tabindex: 2,
                    width: 170,
                    fieldLabel: _('Username'),
                    id: 'username',
                    name: 'username',
                    selectOnFocus: true,
                    value: this.defaultUsername,
                    listeners: {render: function(field){field.focus(false, 250);}}
                }, {
                    xtype: 'textfield',
                    tabindex: 3,
                    width: 170,
                    inputType: 'password',
                    fieldLabel: _('Password'),
                    id: 'password',
                    name: 'password',
                    //allowBlank: false,
                    selectOnFocus: true,
                    value: this.defaultPassword
                },{
                    cls: 'tb-login-button-spacer',
                    border: false,
                    html: ''
                }, {
                    xtype: 'button',
                    width: 120,
                    text: _('Login'),
                    scope: this,
                    handler: this.onLoginPress
                }]
            });
        }
        
        return this.loginPanel;
    },
    
    getTinePanel: function() {
        if (! this.tinePanel) {
            this.tinePanel = new Ext.Panel({
                layout: 'fit',
                cls: 'tb-login-tinepanel',
                border: false,
                defaults: {xtype: 'label'},
                items:[{
                    cls: 'tb-login-big-label',
                    html: _('Tine 2.0 is made for you')
                }, {
                    html: '<p>' + _('Tine 2.0 wants to make business collaboration easier and more enjoyable - for your needs! So you are warmly welcome to discuss with us, bring in ideas and get help.') + '</p>'
                }, {
                    cls: 'tb-login-big-label-spacer',
                    html: '&nbsp;'
                }, {
                    html: '<p><a target="_blank" href="http://www.tine20.org/" border="0">' + _('Tine 2.0 Homepage') + '</a></p>'
                }, {
                    html: '<p><a target="_blank" href="http://www.tine20.org/forum/" border="0">' + _('Our Forum for questions and ideas') + '</a></p>'
                }
                ]
            });
        }
        
        return this.tinePanel;
    },
    
    getSurveyData: function(cb) {
        var ds = new Ext.data.Store({
            proxy: new Ext.data.ScriptTagProxy({
                url: 'https://versioncheck.officespot20.com/surveyCheck/surveyCheck.php'
            }),
            reader: new Ext.data.JsonReader({
                root: 'survey'
            }, ['title', 'subtitle', 'duration', 'langs', 'link', 'enddate', 'htmlmessage'])
        });
        
        ds.on('load', function(store, records) {
            var survey = records[0];
            
            cb.call(this, survey);
        }, this);
        ds.load({params: {lang: Tine.Tinebase.registry.get('locale').locale}});
    },
    
    getSurveyPanel: function() {
        if (! this.surveyPanel) {
            this.surveyPanel = new Ext.Panel({
                layout: 'fit',
                cls: 'tb-login-surveypanel',
                border: false,
                defaults: {xtype: 'label'},
                items: []
            });
            
            if (! Tine.Tinebase.registry.get('denySurveys')) {
                this.getSurveyData(function(survey) {
                    if (typeof survey.get == 'function') {
                        var enddate = Date.parseDate(survey.get('enddate'), Date.patterns.ISO8601Long);
                        
                        if (Ext.isDate(enddate) && enddate.getTime() > new Date().getTime()) {
                            survey.data.lang_duration = String.format(_('about {0} minutes'), survey.data.duration);
                            survey.data.link = 'https://versioncheck.officespot20.com/surveyCheck/surveyCheck.php?participate';
                            
                            this.surveyPanel.add([{
                                cls: 'tb-login-big-label',
                                html: _('Tine 2.0 needs your help')
                            }, {
                                html: '<p>' + _('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
                            }, {
                                html: this.getSurveyTemplate().apply(survey.data)
                            }, {
                                xtype: 'button',
                                width: 120,
                                text: _('participate!'),
                                handler: function() {
                                    window.open(survey.data.link);
                                }
                            }]);
                            this.surveyPanel.doLayout();
                        }
                    }
                });
            }
        }
        
        return this.surveyPanel;
    },
    
    getSurveyTemplate: function() {
        if (! this.surveyTemplate) {
            this.surveyTemplate = new Ext.XTemplate(
                '<br/ >',
                '<p><b>{title}</b></p>',
                '<p><a target="_blank" href="{link}" border="0">{subtitle}</a></p>',
                '<br/>',
                '<p>', _('Languages'), ': {langs}</p>',
                '<p>', _('Duration'), ': {lang_duration}</p>',
                '<br/>'
                
            ).compile();
        }
        
        return this.surveyTemplate;
    },
    
    initComponent: function() {
        this.initLayout();
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * initialize base layout
     */
    initLayout: function() {
        
        this.items = [{
            layout: 'vbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                border: false,
                flex: 0,
                height: 140
            }, {
                layout: 'hbox',
                flex: 1,
                border: false,
                layoutConfig: {
                    align: 'stretch'
                },
                items: [{
                    flex: 7,
                    border: false,
                    layout: 'hbox',
                    layoutConfig: {
                        align: 'stretch'
                    },
                    items: [{
                        flex: 1,
                        border: false
                    }, {
                        flex: 0,
                        border: false,
                        width: 460,
                        items: this.getLoginPanel()
                    }]
                }, {
                    layout: 'vbox',
                    cls: 'tb-login-infosection',
                    layoutConfig: {
                        align: 'stretch'
                    },
                    border: false,
                    flex: 3,
                    items: [
                        this.getTinePanel(),
                        this.getSurveyPanel()
                    ]
                }]
            }]
        }]; 
    },
    
    /**
     * do the actual login
     */
    onLoginPress: function(){
        var form = this.getLoginPanel().getForm();
        var values = form.getValues();
        if (form.isValid()) {
            Ext.MessageBox.wait(_('Logging you in...'), _('Please wait'));
            
            Ext.Ajax.request({
                scope: this,
                params : {
                    method: this.loginMethod,
                    username: values.username,
                    password: values.password
                },
                callback: function(request, httpStatus, response) {
                    var responseData = Ext.util.JSON.decode(response.responseText);
                    if (responseData.success === true) {
                        Ext.MessageBox.wait(String.format(_('Login successful. Loading {0}...'), Tine.title), _('Please wait!'));
                        window.document.title = this.originalTitle;
                        this.onLogin.call(this.scope);
                    } else {
                        Ext.MessageBox.show({
                            title: _('Login failure'),
                            msg: _('Your username and/or your password are wrong!!!'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR,
                            fn: function() {
                                this.getLoginPanel().getForm().findField('username').focus(true);
                            }.createDelegate(this)
                        });
                    }
                }
            });
        }
    },
    
    onRender: function(ct, position) {
        this.supr().onRender.apply(this, arguments);
        
        this.map = new Ext.KeyMap(this.el, [{
            key : [10, 13],
            scope : this,
            fn : this.onLoginPress
        }]);
        
        this.originalTitle = window.document.title;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        window.document.title = Tine.title + postfix + ' - ' + _('Please enter your login data');
    },
    
    renderSurveyPanel: function(survey) {
        console.log(survey);
        
        var items = [{
            cls: 'tb-login-big-label',
            html: _('Tine 2.0 needs your help')
        }, {
            html: '<p>' + _('We regularly need your feedback to make the next Tine 2.0 releases fit your needs even better. Help us and yourself by participating:') + '</p>'
        }];
                
    }
});
