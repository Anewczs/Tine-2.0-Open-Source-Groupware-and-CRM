Ext.namespace("Egw");var EGWNameSpace=Egw;Egw.Addressbook=function(){var v;var I;var H;var B,A;var R=function(l,n){var f=l.getRegion("center",false);f.remove(0);var K=Ext.Element.get("content");var e=K.createChild({tag:"div",id:"outergriddiv"});switch(n.attributes.datatype){case "list":var F={listId:n.attributes.listId};break;case "contacts":var F={displayContacts:true,displayLists:true};break;case "otherpeople":var F={displayContacts:true,displayLists:true,};break;case "sharedaddressbooks":var F={displayContacts:true,displayLists:true,};break;}v=new Ext.data.JsonStore({url:"index.php",baseParams:{method:"Addressbook.getContacts",datatype:n.attributes.datatype,owner:n.attributes.owner,nodeid:n.attributes.id,options:Ext.encode(F),},root:"results",totalProperty:"totalcount",id:"contact_id",fields:[{name:"contact_id"},{name:"contact_tid"},{name:"contact_owner"},{name:"contact_private"},{name:"cat_id"},{name:"n_family"},{name:"n_given"},{name:"n_middle"},{name:"n_prefix"},{name:"n_suffix"},{name:"n_fn"},{name:"n_fileas"},{name:"contact_bday"},{name:"org_name"},{name:"org_unit"},{name:"contact_title"},{name:"contact_role"},{name:"contact_assistent"},{name:"contact_room"},{name:"adr_one_street"},{name:"adr_one_street2"},{name:"adr_one_locality"},{name:"adr_one_region"},{name:"adr_one_postalcode"},{name:"adr_one_countryname"},{name:"contact_label"},{name:"adr_two_street"},{name:"adr_two_street2"},{name:"adr_two_locality"},{name:"adr_two_region"},{name:"adr_two_postalcode"},{name:"adr_two_countryname"},{name:"tel_work"},{name:"tel_cell"},{name:"tel_fax"},{name:"tel_assistent"},{name:"tel_car"},{name:"tel_pager"},{name:"tel_home"},{name:"tel_fax_home"},{name:"tel_cell_private"},{name:"tel_other"},{name:"tel_prefer"},{name:"contact_email"},{name:"contact_email_home"},{name:"contact_url"},{name:"contact_url_home"},{name:"contact_freebusy_uri"},{name:"contact_calendar_uri"},{name:"contact_note"},{name:"contact_tz"},{name:"contact_geo"},{name:"contact_pubkey"},{name:"contact_created"},{name:"contact_creator"},{name:"contact_modified"},{name:"contact_modifier"},{name:"contact_jpegphoto"},{name:"account_id"}],remoteSort:true});v.setDefaultSort("contact_id","desc");v.load({params:{start:0,limit:50}});v.on("beforeload",function(i,a){i.baseParams.options=Ext.encode({displayContacts:B.pressed,displayLists:A.pressed});});var Q=new Ext.grid.ColumnModel([{resizable:true,id:"contact_id",header:"Id",dataIndex:"contact_id",width:30},{resizable:true,id:"contact_tid",dataIndex:"contact_tid",width:30,renderer:r},{resizable:true,id:"n_family",header:"Family name",dataIndex:"n_family"},{resizable:true,id:"n_given",header:"Given name",dataIndex:"n_given"},{resizable:true,header:"Middle name",dataIndex:"n_middle",hidden:true},{resizable:true,id:"n_prefix",header:"Prefix",dataIndex:"n_prefix",hidden:true},{resizable:true,header:"Suffix",dataIndex:"n_suffix",hidden:true},{resizable:true,header:"Full name",dataIndex:"n_fn",hidden:true},{resizable:true,header:"Birthday",dataIndex:"contact_bday",hidden:true},{resizable:true,header:"Organisation",dataIndex:"org_name"},{resizable:true,header:"Unit",dataIndex:"org_unit"},{resizable:true,header:"Title",dataIndex:"contact_title",},{resizable:true,header:"Role",dataIndex:"contact_role",},{resizable:true,id:"addressbook",header:"addressbook",dataIndex:"addressbook",hidden:true}]);Q.defaultSortable=true;I=new Ext.grid.Grid(e,{ds:v,cm:Q,autoSizeColumns:false,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),enableColLock:false,loadMask:true,enableDragDrop:true,ddGroup:"TreeDD",autoExpandColumn:"n_given"});I.render();I.on("rowclick",function(S,T,X){var a=I.getSelectionModel().getCount();var i=k.items.map;if(a<1){i.editbtn.disable();i.deletebtn.disable();}else{if(a==1){i.editbtn.enable();i.deletebtn.enable();}else{i.editbtn.disable();i.deletebtn.enable();}}});I.on("rowdblclick",function(i,T,X){var a=i.getDataSource().getAt(T);try{z(a.data.contact_id);}catch(S){}});I.on("rowcontextmenu",function(i,X,a){a.stopEvent();Y.showAt(a.getXY());});var J=I.getView().getHeaderPanel(true);var k=new Ext.PagingToolbar(J,v,{pageSize:50,cls:"x-btn-icon-22",displayInfo:true,displayMsg:"Displaying contacts {0} - {1} of {2}",emptyMsg:"No contacts to display"});k.insertButton(0,{id:"addbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/add-user.png",tooltip:"add new contact",handler:V});k.insertButton(1,{id:"editbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/edit-user.png",tooltip:"edit current contact",disabled:true,handler:d});k.insertButton(2,{id:"deletebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/delete-user.png",tooltip:"delete selected contacts",disabled:true,handler:C});k.insertButton(3,new Ext.Toolbar.Separator());k.insertButton(4,{id:"addlstbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/add-users.png",tooltip:"add new list",handler:W});k.insertButton(5,{id:"editlstbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/edit-users.png",tooltip:"edit current list",disabled:true,handler:G});k.insertButton(6,{id:"deletelstbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/delete-users.png",tooltip:"delete selected lists",disabled:true,handler:j});k.insertButton(7,new Ext.Toolbar.Separator());B=k.insertButton(8,{id:"filtercontactsbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/user.png",tooltip:"display contacts",enableToggle:true,pressed:true,handler:q});A=k.insertButton(9,{id:"filterlistsbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/users.png",tooltip:"display lists",enableToggle:true,pressed:true,handler:w});k.insertButton(10,{id:"exportbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/file-export.png",tooltip:"export selected contacts",disabled:false,onClick:h});f.add(new Ext.GridPanel(I));};var r=function(J,Q,k,f,K,e){switch(J){case "l":return "<img src='images/oxygen/16x16/actions/users.png' width='12' height='12' alt='list'/>";default:return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";}};var q=function(K,f){v.reload();};var w=function(K,f){v.reload();};var C=function(e,f){var k=Array();var J=I.getSelectionModel().getSelections();for(var K=0;K<J.length;++K){k.push(J[K].id);}s(k,function(){EGWNameSpace.Addressbook.reload();});v.reload();};var d=function(J,f){var k=I.getSelectionModel().getSelections();var K=k[0].id;z(K);};var V=function(K,f){z();};var j=function(){};var G=function(){};var W=function(K,f){z("list");};var Y=new Ext.menu.Menu({id:"ctxMenuAddress",items:[{id:"edit",text:"edit contact",icon:"images/oxygen/22x22/actions/edit-user.png",handler:d},{id:"delete",text:"delete contact",icon:"images/oxygen/22x22/actions/delete-user.png",handler:C},"-",{id:"new",text:"new contact",icon:"images/oxygen/22x22/actions/add-user.png",handler:V}]});var h=function(K,f){};var z=function(l){var K;var F=1024,e=786;var k=950,n=600;if(document.all){F=document.body.clientWidth;e=document.body.clientHeight;x=window.screenTop;y=window.screenLeft;}else{if(window.innerWidth){F=window.innerWidth;e=window.innerHeight;x=window.screenX;y=window.screenY;}}var J=((F-k)/2)+y,Q=((e-n)/2)+x;if(l=="list"){K="index.php?getpopup=addressbook.editlist";}if(l){K="index.php?getpopup=addressbook.editcontact&contactid="+l;}else{K="index.php?getpopup=addressbook.editcontact";}appId="addressbook";var f=window.open(K,"popupname","width="+k+",height="+n+",top="+Q+",left="+J+",directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no");return ;};var c=function(f){f=(f==null)?false:f;window.opener.EGWNameSpace.Addressbook.reload();if(f==true){window.setTimeout("window.close()",400);}};var s=function(K,f,k){var J=Ext.util.JSON.encode(K);new Ext.data.Connection().request({url:"index.php",method:"post",scope:this,params:{method:"Addressbook.deleteContacts",_contactIDs:J},success:function(l,n){var Q;try{Q=Ext.util.JSON.decode(l.responseText);if(Q.success){if(typeof f=="function"){f;}}else{Ext.MessageBox.alert("Failure!","Deleting contact failed!");}}catch(F){Ext.MessageBox.alert("Failure!",F.message);}},failure:function(e,Q){}});};var D=function(K,f){Ext.MessageBox.alert("Export","Not yet implemented.");};var L=function(){};var o=function(){Ext.QuickTips.init();Ext.form.Field.prototype.msgTarget="side";var J=new Ext.BorderLayout(document.body,{north:{split:false,initialSize:28},center:{autoScroll:true}});J.beginUpdate();J.add("north",new Ext.ContentPanel("header",{fitToFrame:true}));J.add("center",new Ext.ContentPanel("content"));J.endUpdate();var F=true;if(formData.values){F=false;}var K=new Ext.Toolbar("header");K.add({id:"savebtn",cls:"x-btn-text-icon",text:"Save and Close",icon:"images/oxygen/22x22/actions/document-save.png",tooltip:"save this contact and close window",onClick:function(){if(k.isValid()){var a={};if(formData.values){a._contactID=formData.values.contact_id;}else{a._contactID=0;}k.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:a,success:function(i,X,T){window.opener.EGWNameSpace.Addressbook.reload();window.setTimeout("window.close()",400);},failure:function(i,X){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"savebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/save-all.png",tooltip:"apply changes for this contact",onClick:function(){if(k.isValid()){var a={};if(formData.values){a._contactID=formData.values.contact_id;}else{a._contactID=0;}k.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:a,success:function(i,X,T){window.opener.EGWNameSpace.Addressbook.reload();},failure:function(i,X){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"deletebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/edit-delete.png",tooltip:"delete this contact",disabled:F,handler:function(i,a){if(formData.values.contact_id){Ext.MessageBox.wait("Deleting contact...","Please wait!");s([formData.values.contact_id]);c(true);}}},{id:"exportbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/file-export.png",tooltip:"export this contact",disabled:F,handler:D});var l=new Ext.data.JsonStore({url:"index.php",baseParams:{method:"Egwbase.getCountryList"},root:"results",id:"shortName",fields:["shortName","translatedName"],remoteSort:false});var e=new Ext.data.SimpleStore({fields:["id","addressbooks"],data:formData.config.addressbooks});var f=Ext.Element.get("content");var k=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveAddress",reader:new Ext.data.JsonReader({root:"results"},[{name:"contact_id"},{name:"contact_tid"},{name:"contact_owner"},{name:"contact_private"},{name:"cat_id"},{name:"n_family"},{name:"n_given"},{name:"n_middle"},{name:"n_prefix"},{name:"n_suffix"},{name:"n_fn"},{name:"n_fileas"},{name:"contact_bday"},{name:"org_name"},{name:"org_unit"},{name:"contact_title"},{name:"contact_role"},{name:"contact_assistent"},{name:"contact_room"},{name:"adr_one_street"},{name:"adr_one_street2"},{name:"adr_one_locality"},{name:"adr_one_region"},{name:"adr_one_postalcode"},{name:"adr_one_countryname"},{name:"contact_label"},{name:"adr_two_street"},{name:"adr_two_street2"},{name:"adr_two_locality"},{name:"adr_two_region"},{name:"adr_two_postalcode"},{name:"adr_two_countryname"},{name:"tel_work"},{name:"tel_cell"},{name:"tel_fax"},{name:"tel_assistent"},{name:"tel_car"},{name:"tel_pager"},{name:"tel_home"},{name:"tel_fax_home"},{name:"tel_cell_private"},{name:"tel_other"},{name:"tel_prefer"},{name:"contact_email"},{name:"contact_email_home"},{name:"contact_url"},{name:"contact_url_home"},{name:"contact_freebusy_uri"},{name:"contact_calendar_uri"},{name:"contact_note"},{name:"contact_tz"},{name:"contact_geo"},{name:"contact_pubkey"},{name:"contact_created"},{name:"contact_creator"},{name:"contact_modified"},{name:"contact_modifier"},{name:"contact_jpegphoto"},{name:"account_id"}])});k.on("beforeaction",function(i,a){i.baseParams={};i.baseParams._contactOwner=i.getValues().contact_owner;if(formData.values&&formData.values.contact_id){i.baseParams._contactID=formData.values.contact_id;}else{i.baseParams._contactID=0;}console.log(i.baseParams);});k.fieldset({legend:"Contact information"});k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"First Name",name:"n_given",width:175}),new Ext.form.TextField({fieldLabel:"Middle Name",name:"n_middle",width:175}),new Ext.form.TextField({fieldLabel:"Last Name",name:"n_family",width:175,allowBlank:false}));k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Prefix",name:"n_prefix",width:175}),new Ext.form.TextField({fieldLabel:"Suffix",name:"n_suffix",width:175}),new Ext.form.ComboBox({fieldLabel:"Addressbook",name:"contact_owner",hiddenName:"contact_owner",store:e,displayField:"addressbooks",valueField:"id",allowBlank:false,editable:false,mode:"remote",triggerAction:"all",emptyText:"Select a addressbook...",selectOnFocus:true,width:175}));k.end();k.fieldset({legend:"Business information"});k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Company",name:"org_name",width:175}),new Ext.form.TextField({fieldLabel:"Street",name:"adr_one_street",width:175}),new Ext.form.TextField({fieldLabel:"Street 2",name:"adr_one_street2",width:175}),new Ext.form.TextField({fieldLabel:"Postalcode",name:"adr_one_postalcode",width:175}),new Ext.form.TextField({fieldLabel:"City",name:"adr_one_locality",width:175}),new Ext.form.TextField({fieldLabel:"Region",name:"adr_one_region",width:175}),new Ext.form.ComboBox({fieldLabel:"Country",name:"adr_one_countryname",hiddenName:"adr_one_countryname",store:l,displayField:"translatedName",valueField:"shortName",typeAhead:true,mode:"remote",triggerAction:"all",emptyText:"Select a state...",selectOnFocus:true,width:175}));k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Phone",name:"tel_work",width:175}),new Ext.form.TextField({fieldLabel:"Cellphone",name:"tel_cell",width:175}),new Ext.form.TextField({fieldLabel:"Fax",name:"tel_fax",width:175}),new Ext.form.TextField({fieldLabel:"Car phone",name:"tel_car",width:175}),new Ext.form.TextField({fieldLabel:"Pager",name:"tel_pager",width:175}),new Ext.form.TextField({fieldLabel:"Email",name:"contact_email",vtype:"email",width:175}),new Ext.form.TextField({fieldLabel:"URL",name:"contact_url",vtype:"url",width:175}));k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Unit",name:"org_unit",width:175}),new Ext.form.TextField({fieldLabel:"Role",name:"contact_role",width:175}),new Ext.form.TextField({fieldLabel:"Title",name:"contact_title",width:175}),new Ext.form.TextField({fieldLabel:"Room",name:"contact_room",width:175}),new Ext.form.TextField({fieldLabel:"Name Assistent",name:"contact_assistent",width:175}),new Ext.form.TextField({fieldLabel:"Phone Assistent",name:"tel_assistent",width:175}));k.end();k.fieldset({legend:"Private information"});k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"Street",name:"adr_two_street",width:175}),new Ext.form.TextField({fieldLabel:"Street2",name:"adr_two_street2",width:175}),new Ext.form.TextField({fieldLabel:"Postalcode",name:"adr_two_postalcode",width:175}),new Ext.form.TextField({fieldLabel:"City",name:"adr_two_locality",width:175}),new Ext.form.TextField({fieldLabel:"Region",name:"adr_two_region",width:175}),new Ext.form.ComboBox({fieldLabel:"Country",name:"adr_two_countryname",hiddenName:"adr_two_countryname",store:l,displayField:"translatedName",valueField:"shortName",typeAhead:true,mode:"remote",triggerAction:"all",emptyText:"Select a state...",selectOnFocus:true,width:175}));k.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.DateField({fieldLabel:"Birthday",name:"contact_bday",format:formData.config.dateFormat,altFormats:"Y-m-d",width:175}),new Ext.form.TextField({fieldLabel:"Phone",name:"tel_home",width:175}),new Ext.form.TextField({fieldLabel:"Cellphone",name:"tel_cell_private",width:175}),new Ext.form.TextField({fieldLabel:"Fax",name:"tel_fax_home",width:175}),new Ext.form.TextField({fieldLabel:"Email",name:"contact_email_home",vtype:"email",width:175}),new Ext.form.TextField({fieldLabel:"URL",name:"contact_url_home",vtype:"url",width:175}));k.column({width:"33%",labelSeparator:"",hideLabels:true},new Ext.form.TextArea({name:"contact_note",grow:false,preventScrollbars:false,width:"95%",maxLength:255,height:150}));k.end();var n=new Ext.form.TriggerField({fieldLabel:"Categories",name:"categories",width:320,readOnly:true});n.onTriggerClick=function(){var U=Ext.Element.get("container");var p=U.createChild({tag:"div",id:"iWindowTag"});var E=U.createChild({tag:"div",id:"iWindowContTag"});var a=new Ext.data.SimpleStore({fields:["category_id","category_realname"],data:[["1","erste Kategorie"],["2","zweite Kategorie"],["3","dritte Kategorie"],["4","vierte Kategorie"],["5","fuenfte Kategorie"],["6","sechste Kategorie"],["7","siebte Kategorie"],["8","achte Kategorie"]]});a.load();ds_checked=new Ext.data.SimpleStore({fields:["category_id","category_realname"],data:[["2","zweite Kategorie"],["5","fuenfte Kategorie"],["6","sechste Kategorie"],["8","achte Kategorie"]]});ds_checked.load();var t=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveAdditionalData",reader:new Ext.data.JsonReader({root:"results"},[{name:"category_id"},{name:"category_realname"},])});var X=1;var u=new Array();ds_checked.each(function(i){u[i.data.category_id]=i.data.category_realname;});a.each(function(i){if((X%12)==1){t.column({width:"33%",labelWidth:50,labelSeparator:""});}if(u[i.data.category_id]){t.add(new Ext.form.Checkbox({boxLabel:i.data.category_realname,name:i.data.category_realname,checked:true}));}else{t.add(new Ext.form.Checkbox({boxLabel:i.data.category_realname,name:i.data.category_realname}));}if((X%12)==0){t.end();}X=X+1;});t.render("iWindowContTag");if(!S){var S=new Ext.LayoutDialog("iWindowTag",{modal:true,width:700,height:400,shadow:true,minWidth:700,minHeight:400,autoTabs:true,proxyDrag:true,center:{autoScroll:true,tabPosition:"top",closeOnTab:true,alwaysShowTabs:true}});S.addKeyListener(27,this.hide);S.addButton("save",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");S.hide;},S);S.addButton("cancel",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");S.hide;},S);var T=S.getLayout();T.beginUpdate();T.add("center",new Ext.ContentPanel("iWindowContTag",{autoCreate:true,title:"Category"}));T.endUpdate();}S.show();};k.column({width:"45%",labelWidth:80,labelSeparator:" ",labelAlign:"right"},n);var Q=new Ext.form.TriggerField({fieldLabel:"Lists",name:"lists",width:320,readOnly:true});Q.onTriggerClick=function(){var U=Ext.Element.get("container");var p=U.createChild({tag:"div",id:"iWindowTag"});var E=U.createChild({tag:"div",id:"iWindowContTag"});var a=new Ext.data.SimpleStore({fields:["list_id","list_realname"],data:[["1","Liste A"],["2","Liste B"],["3","Liste C"],["4","Liste D"],["5","Liste E"],["6","Liste F"],["7","Liste G"],["8","Liste H"]]});a.load();ds_checked=new Ext.data.SimpleStore({fields:["list_id","list_realname"],data:[["2","Liste B"],["5","Liste E"],["6","Liste F"],["8","Liste H"]]});ds_checked.load();var t=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveAdditionalData",reader:new Ext.data.JsonReader({root:"results"},[{name:"list_id"},{name:"list_realname"},])});var X=1;var u=new Array();ds_checked.each(function(i){u[i.data.list_id]=i.data.list_realname;});a.each(function(i){if((X%12)==1){t.column({width:"33%",labelWidth:50,labelSeparator:""});}if(u[i.data.list_id]){t.add(new Ext.form.Checkbox({boxLabel:i.data.list_realname,name:i.data.list_realname,checked:true}));}else{t.add(new Ext.form.Checkbox({boxLabel:i.data.list_realname,name:i.data.list_realname}));}if((X%12)==0){t.end();}X=X+1;});t.render("iWindowContTag");if(!S){var S=new Ext.LayoutDialog("iWindowTag",{modal:true,width:700,height:400,shadow:true,minWidth:700,minHeight:400,autoTabs:true,proxyDrag:true,center:{autoScroll:true,tabPosition:"top",closeOnTab:true,alwaysShowTabs:true}});S.addKeyListener(27,this.hide);S.addButton("save",function(){Ext.MessageBox.alert("Todo","Not yet implemented!");},S);S.addButton("cancel",function(){window.location.reload();S.hide;},S);var T=S.getLayout();T.beginUpdate();T.add("center",new Ext.ContentPanel("iWindowContTag",{autoCreate:true,title:"Lists"}));T.endUpdate();}S.show();};k.column({width:"45%",labelWidth:80,labelSeparator:" ",labelAlign:"right"},Q);k.column({width:"10%",labelWidth:50,labelSeparator:" ",labelAlign:"right"},new Ext.form.Checkbox({fieldLabel:"Private",name:"categories",width:10}));k.render("content");return k;};var L=function(){Ext.QuickTips.init();Ext.form.Field.prototype.msgTarget="side";var k=new Ext.BorderLayout(document.body,{north:{split:false,initialSize:28},center:{autoScroll:true}});k.beginUpdate();k.add("north",new Ext.ContentPanel("header",{fitToFrame:true}));k.add("center",new Ext.ContentPanel("content"));k.endUpdate();var J=true;if(formData.values){J=false;}var f=new Ext.Toolbar("header");f.add({id:"savebtn",cls:"x-btn-text-icon",text:"Save and Close",icon:"images/oxygen/22x22/actions/document-save.png",tooltip:"save this contact and close window",onClick:function(){if(e.isValid()){e.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:additionalData,success:function(l,n,F){window.opener.EGWNameSpace.Addressbook.reload();window.setTimeout("window.close()",400);},failure:function(l,n){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"savebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/save-all.png",tooltip:"apply changes for this contact",onClick:function(){if(e.isValid()){var l={};if(formData.values){l._contactID=formData.values.contact_id;}else{l._contactID=0;}e.submit({waitTitle:"Please wait!",waitMsg:"saving contact...",params:l,success:function(n,F,a){window.opener.EGWNameSpace.Addressbook.reload();},failure:function(n,F){}});}else{Ext.MessageBox.alert("Errors","Please fix the errors noted.");}}},{id:"deletebtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/edit-delete.png",tooltip:"delete this contact",disabled:J,handler:function(n,l){if(formData.values.contact_id){Ext.MessageBox.wait("Deleting contact...","Please wait!");s([formData.values.contact_id]);c(true);}}},{id:"exportbtn",cls:"x-btn-icon-22",icon:"images/oxygen/22x22/actions/file-export.png",tooltip:"export this contact",disabled:J,handler:D});var Q=new Ext.data.JsonStore({url:"index.php",baseParams:{method:"Egwbase.getCountryList"},root:"results",id:"shortName",fields:["shortName","translatedName"],remoteSort:false});var K=Ext.Element.get("content");var e=new Ext.form.Form({labelWidth:75,url:"index.php?method=Addressbook.saveList",reader:new Ext.data.JsonReader({root:"results"},[{name:"list_id"},{name:"list_name"},{name:"list_owner"},{name:"list_created"},{name:"list_creator"}])});e.fieldset({legend:"List information"});e.column({width:"33%",labelWidth:90,labelSeparator:""},new Ext.form.TextField({fieldLabel:"List Name",name:"list_name",width:175}),new Ext.form.TextField({fieldLabel:"List Owner",name:"list_owner",width:175}));e.end();e.render("content");return e;};var Z=function(K,k){for(var J in k){var f=K.findField(J);if(f){f.setValue(k[J]);}}};return {show:R,reload:function(){v.reload();},handleDragDrop:function(f){alert("Best Regards From Addressbook");},openDialog:function(){z();},displayContactDialog:function(){var f=o();if(formData.values){Z(f,formData.values);}},displayListDialog:function(){var f=L();if(formData.values){Z(f,formData.values);}}};}();