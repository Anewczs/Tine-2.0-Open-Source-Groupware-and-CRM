/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Crm', 'Tine.Crm.Model');

/**
 * @namespace Tine.Crm.Model
 * @class Tine.Crm.Model.Lead
 * @extends Tine.Tinebase.data.Record
 * 
 * Message Record Definition
 */ 
Tine.Crm.Model.Lead = Tine.Tinebase.data.Record.create([
        {name: 'id',            type: 'int'},
        {name: 'lead_name',     type: 'string'},
        {name: 'leadstate_id',  type: 'int'},
        {name: 'leadtype_id',   type: 'int'},
        {name: 'leadsource_id', type: 'int'},
        {name: 'container_id'              },
        {name: 'start',         type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'description',   type: 'string'},
        {name: 'end',           type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'turnover',      type: 'int'},
        {name: 'probability',   type: 'int'},
        {name: 'end_scheduled', type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'lastread'},
        {name: 'lastreader'},
        {name: 'responsible'},
        {name: 'customer'},
        {name: 'partner'},
        {name: 'tasks'},
        {name: 'relations'},
        {name: 'products'},
        {name: 'tags'},
        {name: 'notes'},
        {name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'created_by',         type: 'int'                  },
        {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'last_modified_by',   type: 'int'                  },
        {name: 'is_deleted',         type: 'boolean'              },
        {name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'deleted_by',         type: 'int'                  }
    ], {
    appName: 'Crm',
    modelName: 'Lead',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Lead', 'Leads', n);
    recordName: 'Lead',
    recordsName: 'Leads',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Leads',
    containersName: 'Leads',
    getTitle: function() {
        return this.get('lead_name') ? this.get('lead_name') : false;
    }
});

/**
 * @namespace Tine.Crm.Model
 * 
 * get default data for a new lead
 *  
 * @return {Object} default data
 * @static
 * 
 * TODO get default leadstate/source/type from registry
 * TODO add default container id / account grants?
 * TODO get responsible contact from registry
 */ 
Tine.Crm.Model.Lead.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Crm');
    
    //var currentAccount = Tine.Tinebase.registry.get('currentAccount');
    //var userContact = new Tine.Addressbook.Model.Contact(Tine.Tinebase.registry.get('userContact'));

    var userContact = Tine.Tinebase.registry.get('userContact');
    //console.log(userContact);
    var defaults = Tine.Crm.registry.get('defaults');
    
    var data = {
        start: new Date().clearTime().add(Date.HOUR, (new Date().getHours() + 1)),
        /*
        container_id: {
            account_grants: {
                editGrant: true
            }
        },
        */
        leadstate_id: defaults.leadstate_id,
        leadtype_id: defaults.leadtype_id,
        leadsource_id: defaults.leadsource_id,
        probability: 0,
        turnover: 0,
        relations: [{
            type: 'responsible',
            related_record: userContact
            /* {
                n_fileas: currentAccount.accountDisplayName,
                id: currentAccount.contact_id
            } */
        }]
    };
    
    return data;
};


/**
 * @namespace Tine.Crm.Model
 * @class Tine.Crm.Model.ProductLink
 * @extends Ext.data.Record
 * 
 * Product Link Record Definition
 */ 
Tine.Crm.Model.ProductLink = Tine.Tinebase.data.Record.create([
        {name: 'id'},
        {name: 'product_id'},
        {name: 'product_desc'},
        {name: 'product_price'}
    ], {
    appName: 'Crm',
    modelName: 'Product',
    idProperty: 'id',
    titleProperty: 'product_desc',
    // ngettext('Product', 'Products', n);
    recordName: 'Product',
    recordsName: 'Products',
    // ngettext('record list', 'record lists', n);
    getTitle: function() {
        return this.get('product_desc') ? this.get('product_desc') : false;
    }
});

// work arround nasty ext date bug
// TODO is that still needed?
/*
Tine.Crm.Model.Lead.FixDates = function(lead) {
    lead.data.start         = lead.data.start         ? Date.parseDate(lead.data.start, Date.patterns.ISO8601Long)         : lead.data.start;
    lead.data.end           = lead.data.end           ? Date.parseDate(lead.data.end, Date.patterns.ISO8601Long)           : lead.data.end;
    lead.data.end_scheduled = lead.data.end_scheduled ? Date.parseDate(lead.data.end_scheduled, Date.patterns.ISO8601Long) : lead.data.end_scheduled;
};
*/
        
