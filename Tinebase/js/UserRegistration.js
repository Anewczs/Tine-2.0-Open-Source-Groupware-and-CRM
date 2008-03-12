/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine', 'Tine.Tinebase');

Tine.Tinebase.UserRegistration = Ext.extend(Ext.Window, {
    name: 'userRegistration',
    title: 'Registration Wizzard',
    //layout: 'card',
    //activeItem: 0,
    layout: 'fit',
    width: 400,
    height: 400,
    /**
     * holds reg form (card layout)
     */
    regForm: false,
    registrationData: {},
    
    /**
     * @private
     */
    initComponent: function() {
        this.items = [this.getWizard()];
        Tine.Tinebase.UserRegistration.superclass.initComponent.call(this);
        
    },
    
    navHandler: function(button) {
        var direction = 1;
        if (button.id == 'move-prev') {
            direction = -1;
        }
        var cl = Ext.getCmp('UserRegistrationCardLayout');
        //cl.setActiveItem(cl.activeItem + direction);
        console.log(cl);
        //this.getRegForm().setActiveItem(this.regFrom.activeItem + direction);
    },
    
    getSuggestedUsername: function() {
        Ext.Ajax.request({
            url: 'index.php',
            //method: POST, (should not be required)
            params: {
                method: 'Tinebase_UserRegistration.suggestUsername',
                regData: Ext.util.JSON.encode(this.registrationData)
            },
            
            success: function(result, request) {
            	Ext.getCmp('accountLoginName').setValue(Ext.util.JSON.decode(result.responseText));
            	this.registrationData.accountLoginName = Ext.util.JSON.decode(result.responseText);
                //accountLoginName.setValue( Ext.util.JSON.decode(result.responseText));
                // hack to detect user change
                //accountLoginName.originalValue = accountLoginName.getValue();
                //this.accountLoginName = accountLoginName.getValue();
            },
            
            scope: this
        });
    },
    
    /**
     * First card, all about names
     * 
     * @todo Ajax validation of accountLoginName is a bit complex, as ExtJS don't support syncrous requests.
     * As such, we have to introduce a valid flag. If the user presses the next button, and the valid flag is not
     * set, we have to ask the ajax-request if it's running, if yes, display a waitbar, if no ask the user to change the name.
     * Therefore we also need to add a on-change listener to the filed.
     * 
     * @todo username change is not working yet
     */
    cardNames: function () {
        
        var accountFirstName = new Ext.form.TextField({
            fieldLabel: 'Given name',
            name: 'accountFirstName',
            id: 'accountFirstName',
            allowBlank: true
        });
        accountFirstName.on('blur', function(textField){
        	this.registrationData.accountFirstName = textField.getValue();
        	this.getSuggestedUsername();
        }, this);
        
        var accountLastName = new Ext.form.TextField({
            fieldLabel: 'Family name',
            name: 'accountLastName',
            id: 'accountLastName',
            allowBlank: false
        });
        accountLastName.on('blur', function(textField){
            this.registrationData.accountLastName = textField.getValue();
            this.getSuggestedUsername();
        }, this);
        
        var accountLoginName = new Ext.form.TextField({
            fieldLabel: 'Login name',
            name: 'accountLoginName',
            id: 'accountLoginName',
            allowBlank: false
        });
        accountLoginName.on('blur', function(textField){
            this.registrationData.accountLoginName = textField.getValue();
        }, this);
        accountLoginName.on('change', function(accountLoginName) {
            //console.log('hallo');
        }, this);        

        var accountEmailaddress = new Ext.form.TextField({
            fieldLabel: 'Emailaddress',
            name: 'accountEmailaddress',
            id: 'accountEmailaddress',
            vtype: 'email',
            allowBlank: false
        });
        accountEmailaddress.on('blur', function(textField){
            this.registrationData.accountEmailaddress = textField.getValue();
        }, this);
        
        var cardNamesPanel = new Ext.form.FormPanel({
            id: 'cardNames',
            layout: 'form',
            bodyStyle: 'paddingLeft:15px',
            anchor:'100%',
            defaults: {
                anchor: '95%',
                xtype: 'textfield'
            },
            items: [
                accountFirstName,
                accountLastName,
                accountEmailaddress,
                accountLoginName
            ]
        });
        
        return cardNamesPanel;
    },
    /**
     * @private 
     */
    getWizard: function() {
        if (! this.wizard) {
    
            this.wizard = new Ext.ux.Wizard({
                id: 'myWizard',
                //title: 'My Example Wizard',
                mandatorySteps: 2, // at least two steps are required
    
                // the panels (or "cards") within the layout
                items: [
                    this.cardNames(),
                {
                    id: 'card-1',
                    style: {'padding': '5px'},
                    html: '<h1>Congratulations!</h1><p>You have entered all needed information. If you press the Finish button we will send you the registration email.</p>'
                }/*,{
                    id: 'card-2',
                    style: {'padding': '5px'},
                    html: '<p>Step 3 of 5</p>'
                },{
                    id: 'card-3',
                    style: {'padding': '5px'},
                    html: '<p>Step 4 of 5</p>'
                },{
                    id: 'card-4',
                    style: {'padding': '5px'},
                    html: '<h1>Congratulations!</h1><p>Step 5 of 5 - Complete</p>'
                }*/]
            });
            
            
            this.wizard.on({
                'leave': {
                    fn: function(currentItem, nextItem, forward) {
                        switch(currentItem.id) {
                            case 'cardNames':
                            	// check valid username
                            	//console.log('checking username .... ' + this.accountLoginName );
                            	//-- i'll set it to true for the moment (till the synchronous ajax function works)                            	
								//var validUsername = false;
                            	var validUsername = true;
                            	
/*								if ( this.registrationData.accountLoginName ) {
					                Ext.Ajax.request({
					                    url: 'index.php',
					                    params: {
					                        method: 'Tinebase_UserRegistration.checkUniqueUsername',
					                        username: Ext.util.JSON.encode(this.registrationData.accountLoginName)
					                    },

					                    success: function(result, request) {
					                    	// get result
					                    	if ( Ext.util.JSON.decode(result.responseText) == true ) {
					                    		validUsername = true;
					                    	}
					                    },

					                    scope: this
					                });
								}

                				if ( !this.registrationData.accountLoginName || validUsername !== true) {
                                    Ext.Msg.show({
										title:'Login name',
										msg: 'The login name you chose is not valid. Please choose a valid login name.',
										buttons: Ext.Msg.OK,
										//fn: processResult,
										animEl: 'elId',
										icon: Ext.MessageBox.INFO
                                    });
                                    return false;
                                }*/
                        }
                    },
                    scope: this 
                },
                
                'activate': {
                    fn: function(currentItem) {
                        //Ext.MessageBox.alert('Wizard', 'Entering ' + currentItem.id);
                    },
                    scope: this
                },
                 
                'cancel': {
                    fn: function() {
                        this.close();
                        //Ext.MessageBox.alert('Wizard', 'Cancel');
                    },
                    scope: this
                },
                
                'finish': {
                    fn: function() {
                        Ext.Ajax.request({
                            url: 'index.php',
                            params: {
                                method: 'Tinebase_UserRegistration.registerUser',
                                regData: Ext.util.JSON.encode(this.registrationData)
                            },

                            success: function(result, request) {
                                //console.log('creating new user account...' );
                                this.close();
                                // get result
                            },
                            scope: this
                        });
                    },
                    scope: this
                }    
            }, this);
        }
        return this.wizard;
    }
});