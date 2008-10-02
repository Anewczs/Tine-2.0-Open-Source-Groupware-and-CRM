/*
 * Fixes to ExtJS core library
 * 
 * Ext JS Library 2.0.2
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * fix timezone handling for date picker
 * 
 * The getValue function always returns 00:00:00 as time. So if a form got filled
 * with a date like 2008-10-01T21:00:00 the form returns 2008-10-01T00:00:00 although 
 * the user did not change the fieled.
 * 
 * In a multi timezone context this is fatal! When a user in a certain timezone set
 * a date (just a date and no time information), this means in his timezone the 
 * time range from 2008-10-01T00:00:00 to 2008-10-01T23:59:59. 
 * _BUT_ for an other user sitting in a different timezone it means e.g. the 
 * time range from 2008-10-01T02:00:00 to 2008-10-02T21:59:59.
 * 
 * So on the one hand we need to make shure, that the date picker only returns 
 * changed datetime information when the user did a change. 
 * 
 * @todo On the other hand we
 * need adjust the day +/- one day according to the timeshift. 
 */
/**
 * @private
 */
 Ext.form.DateField.prototype.setValue = function(date){
    // preserv original datetime information
    this.fullDateTime = date;
    Ext.form.DateField.superclass.setValue.call(this, this.formatDate(this.parseDate(date)));
};
/**
 * @private
 */
Ext.form.DateField.prototype.getValue = function(){
    //var value = this.parseDate(Ext.form.DateField.superclass.getValue.call(this));
    
    // return the value that was set (has time information when unchanged in client) 
    // and not just the date part!
    value =  this.fullDateTime;
    return value || "";
};

/**
 * fix interpretation of ISO-8601  formatcode ('c') 
 * 
 * Browsers do not support timezones and also javascripts Date object has no 
 * support for it.  All Date Objects are in _one_ timezone which may ore may 
 * not be the operating systems timezone the browser runs on.
 * 
 * parsing dates in ISO format having the timeshift appended ('c') lead to 
 * correctly converted Date Objects in the browsers timezone. This timezone 
 * conversion changes the the Date Parts and as such, javascipt widget 
 * representing date time information print values of the browsers timezone 
 * and _not_ the values send by the server!
 * 
 * So in a multi timezone envireonment, datetime information in the browser 
 * _must not_ be parsed including the offset. Just the values of the server 
 * side converted datetime information are allowed to be parsed.
 */
Date.parseIso = function(isoString) {
    return Date.parseDate(isoString.replace(/\+\d{2}\d{2}/, ''), 'Y-m-d\\Th:i:s');
};


/**
 * utility class used by Button
 * 
 * Fix: http://yui-ext.com/forum/showthread.php?p=142049
 * adds the ButtonToggleMgr.getSelected(toggleGroup, handler, scope) function
 */
Ext.ButtonToggleMgr = function(){
   var groups = {};
   
   function toggleGroup(btn, state){
       if(state){
           var g = groups[btn.toggleGroup];
           for(var i = 0, l = g.length; i < l; i++){
               if(g[i] != btn){
                   g[i].toggle(false);
               }
           }
       }
   }
   
   return {
       register : function(btn){
           if(!btn.toggleGroup){
               return;
           }
           var g = groups[btn.toggleGroup];
           if(!g){
               g = groups[btn.toggleGroup] = [];
           }
           g.push(btn);
           btn.on("toggle", toggleGroup);
       },
       
       unregister : function(btn){
           if(!btn.toggleGroup){
               return;
           }
           var g = groups[btn.toggleGroup];
           if(g){
               g.remove(btn);
               btn.un("toggle", toggleGroup);
           }
       },
       
       getSelected : function(toggleGroup, handler, scope){
           var g = groups[toggleGroup];
           for(var i = 0, l = g.length; i < l; i++){
               if(g[i].pressed === true){
                   if(handler) {
                        handler.call(scope || g[i], g[i]);   
                   }
                   return g[i];
               }
           }
           return;
       }
   };
}();
