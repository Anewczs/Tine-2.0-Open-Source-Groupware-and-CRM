/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Common.js 4995 2008-10-20 10:20:01Z c.weiss@metaways.de $
 */
 
Ext.ns('Tine', 'Tine.Tinebase');

/**
 * static common helpers
 */
Tine.Tinebase.common = {
    
    /**
     * Open browsers native popup
     * @param {string} _windowName
     * @param {string} _url
     * @param {int} _width
     * @param {int} _height
     */
    openWindow: function(_windowName, _url, _width, _height){
        // M$ IE has its internal location bar in the viewport
        if(Ext.isIE) {
            _height = _height + 20;
        }
        
        // chrome counts window decoration and location bar to window height
        if (Ext.isChrome) {
            _height += 40;
        }
        
        var windowName = Ext.isString(_windowName) ? _windowName.replace(/[^a-zA-Z0-9_]/g, '') : _windowName;
        var w,h,x,y,leftPos,topPos,popup;

        if (document.all) {
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else { 
            if (window.innerWidth) {
                w = window.innerWidth;
                h = window.innerHeight;
                x = window.screenX;
                y = window.screenY;
            }
        }
        leftPos = ((w - _width) / 2) + y;
        topPos = ((h - _height) / 2) + x;
        
        popup = window.open(_url, windowName, 'width=' + _width + ',height=' + _height + ',top=' + topPos + ',left=' + leftPos +
        ',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no');
        
        return popup;
    },
    
    showDebugConsole: function() {
        if (! Ext.debug) {
            var head = document.getElementsByTagName("head")[0];
            var scriptTag = document.createElement("script");
            scriptTag.setAttribute("src", 'library/ExtJS/src/debug.js');
            scriptTag.setAttribute("type", "text/javascript");
            head.appendChild(scriptTag);
            
            var scriptEl = Ext.get(scriptTag);
            scriptEl.on('load', function() {
                Ext.log('debug console initialised');
            });
            scriptEl.on('fail', function() {
                Ext.msg.alert('could not activate debug console');
            });
        } else {
            Ext.log('debug console reactivated');
        }
    },
    
    /**
     * Returns localised date and time string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised date and time
     */
    dateTimeRenderer: function($_iso8601){
        return Ext.util.Format.date($_iso8601, Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium'));
    },

    /**
     * Returns localised date string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised date
     */
    dateRenderer: function(date){
        return Ext.util.Format.date(date, Locale.getTranslationData('Date', 'medium'));
    },
    
    /**
     * Returns localised time string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised time
     */
    timeRenderer: function(date){
        return Ext.util.Format.date(date, Locale.getTranslationData('Time', 'medium'));
    },
    
    /**
     * Returns rendered tags for grids
     * 
     * @param {mixed} tags
     * @return {String} tags as colored squares with qtips
     * 
     * TODO add style for tag divs
     */
    tagsRenderer: function(tags) {
        var result = '';
        if (tags) {
            for (var i=0; i < tags.length; i++) {
                var qtipText = tags[i].name;
                if (tags[i].description) {
                    qtipText += ' | ' + tags[i].description;
                }
                result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags" style="background-color:' 
                    + tags[i].color 
                    + ';">&#160;</div>';
            }
        }
        return result;
    },
    
    /**
     * Returns prettyfied minutes
     * @param  {Number} minutes
     * @return {String}
     */
    minutesRenderer: function(minutes){
        
        var i = minutes%60;
        var H = Math.floor(minutes/60);//%(24);
        //var d = Math.floor(minutes/(60*24));
        
        var s = String.format(Tine.Tinebase.translation.ngettext('{0} minute', '{0} minutes', i), i);
        var Hs = String.format(Tine.Tinebase.translation.ngettext('{0} hour', '{0} hours', H), H);
        //var ds = String.format(Tine.Tinebase.translation.ngettext('{0} workday', '{0} workdays', d), d);
        
        if (i == 0) {
        	s = Hs;
        } else {
            s = H ? Hs + ', ' + s : s;
        }
        //s = d ? ds + ', ' + s : s;
        
        return s;
    },
    
    /**
     * Returns the formated username
     * 
     * @param {object} account object 
     * @return {string} formated user display name
     */
    usernameRenderer: function(accountObject){
        var result = (accountObject) ? accountObject.accountDisplayName : '';
        return Ext.util.Format.htmlEncode(result);
    },
    
    /**
     * Returns a username or groupname with according icon in front
     */
    accountRenderer: function(_accountObject, _metadata, _record, _rowIndex, _colIndex, _store) {
        if (! _accountObject) return '';
        var type, iconCls, displayName;
        
        if(_accountObject.accountDisplayName){
            type = 'user';
            displayName = _accountObject.accountDisplayName;
        } else if (_accountObject.name){
            type = 'group';
            displayName = _accountObject.name;
        } else if (_record.data.name) {
            type = _record.data.type;
            displayName = _record.data.name;
        } else if (_record.data.account_name) {
            type = _record.data.account_type;
            displayName = _record.data.account_name;
        }
        iconCls = type == 'user' ? 'renderer renderer_accountUserIcon' : 'renderer renderer_accountGroupIcon';
        return '<div class="' + iconCls  + '">&#160;</div>' + Ext.util.Format.htmlEncode(displayName); 
    },
    
    /**
     * Returns account type icon
     */
    accountTypeRenderer: function(type) {
        iconCls = (type) == 'user' ? 'renderer_accountUserIcon' : 'renderer_accountGroupIcon';
        return '<div style="background-position: 0px" class="' + iconCls  + '">&#160;</div>'; 
    },
    
    /**
     * return yes or no in the selected language for a boolean value
     * 
     * @param {string} value
     * @return {string}
     */
    booleanRenderer: function(value) {
        var translationString = String.format("{0}",(value==1) ? Locale.getTranslationData('Question', 'yes') : Locale.getTranslationData('Question', 'no'));
        
        return translationString.substr(0, translationString.indexOf(':'));
    },
    
    /**
     * custom field renderer
     * 
     * @param {Object} customfields of record
     * @param {} metadata
     * @param {} record
     * @param {} rowIndex
     * @param {} colIndex
     * @param {} store
     * @param {String} name of the customfield
     * @return {String}
     */
    customfieldRenderer: function(customfields, metadata, record, rowIndex, colIndex, store, name) {
        return Ext.util.Format.htmlEncode(customfields[name]); 
    },
    
    /**
     * check if user has right to view/manage this application/resource
     * 
     * @param   string      right (view, admin, manage)
     * @param   string      application
     * @param   string      resource (for example roles, accounts, ...)
     * @returns boolean 
     */
    hasRight: function(_right, _application, _resource) {
        var userRights = [];
        
        if (!(Tine && Tine[_application] && Tine[_application].registry && Tine[_application].registry.get('rights'))) {
            if (! Tine.Tinebase.appMgr) {
                console.error('Tine.Tinebase.appMgr not yet available');
            } else if (Tine.Tinebase.appMgr.get(_application)) {
                console.error('Tine.' + _application + '.rights is not available, initialisation Error!');
            }
            return false;
        }
        userRights = Tine[_application].registry.get('rights');
        
        //console.log(userRights);
        var result = false;
        
        for (var i=0; i < userRights.length; i++) {
            if (userRights[i] == 'admin') {
                result = true;
                break;
            }
            
            if (_right == 'view' && (userRights[i] == 'view_' + _resource || userRights[i] == 'manage_' + _resource) ) {
                result = true;
                break;
            }
            
            if (_right == 'manage' && userRights[i] == 'manage_' + _resource) {
                result = true;
                break;
            }
            
            if (_right == userRights[i]) {
                result = true;
                break;
            }
        }
    
        return result;
    }
};
