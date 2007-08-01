
EGWNameSpace.Asterisk=function(){var showClassesGrid=function(_layout){var center=_layout.getRegion('center',false);var bodyTag=Ext.Element.get(document.body);var gridDivTag=bodyTag.createChild({tag:'div',id:'gridAsterisk',cls:'x-layout-inactive-content'});var ds=new Ext.data.JsonStore({url:'index.php',baseParams:{method:'Asterisk.getData',datatype:'classes'},root:'results',totalProperty:'totalcount',id:'class_id',fields:[{name:'class_id'},{name:'model'},{name:'description'},{name:'config_id'},{name:'setting_id'},{name:'software_id'}],remoteSort:true});ds.setDefaultSort('class_id','desc');ds.load();var cm=new Ext.grid.ColumnModel([{resizable:true,id:'class_id',header:"ID",dataIndex:'class_id',width:30},{resizable:true,id:'model',header:"model",dataIndex:'model',width:250},{resizable:true,id:'description',header:'description',dataIndex:'description'},{resizable:true,id:'config_id',header:'config_id',dataIndex:'config_id',hidden:false},{resizable:true,id:'setting_id',header:'setting_id',dataIndex:'setting_id',hidden:false},{resizable:true,id:'software_id',header:'software_id',dataIndex:'software_id',hidden:false}]);cm.defaultSortable=true;var grid=new Ext.grid.Grid(gridDivTag,{ds:ds,cm:cm,autoSizeColumns:false,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),enableColLock:false,loadMask:true,enableDragDrop:true,ddGroup:'TreeDD',autoExpandColumn:'description'});center.remove(0);grid.render();var gridHeader=grid.getView().getHeaderPanel(true);var pagingHeader=new Ext.PagingToolbar(gridHeader,ds,{pageSize:50,displayInfo:true,displayMsg:'Displaying classes {0} - {1} of {2}',emptyMsg:"No class to display"});center.add(new Ext.GridPanel(grid));}
var showPhonesGrid=function(_layout){var center=_layout.getRegion('center',false);var bodyTag=Ext.Element.get(document.body);var gridDivTag=bodyTag.createChild({tag:'div',id:'gridAsterisk',cls:'x-layout-inactive-content'});var ds=new Ext.data.JsonStore({url:'index.php',baseParams:{method:'Asterisk.getData',datatype:'phones'},root:'results',totalProperty:'totalcount',id:'phone_id',fields:[{name:'phone_id'},{name:'macaddress'},{name:'phonemodel'},{name:'phoneswversion'},{name:'phoneipaddress'},{name:'lastmodify'},{name:'class_id'},{name:'description'}],remoteSort:true});ds.setDefaultSort('macaddress','desc');ds.load();var cm=new Ext.grid.ColumnModel([{resizable:true,id:'phone_id',header:"ID",dataIndex:'phone_id',width:30},{resizable:true,id:'macaddress',header:"macaddress",dataIndex:'macaddress'},{resizable:true,id:'description',header:'description',dataIndex:'description'},{resizable:true,id:'phonemodel',header:'phonemodel',dataIndex:'phonemodel'},{resizable:true,id:'phoneswversion',header:'phoneswversion',dataIndex:'phoneswversion'},{resizable:true,id:'phoneipaddress',header:'phoneipaddress',dataIndex:'phoneipaddress',hidden:true},{resizable:true,id:'lastmodify',header:'lastmodify',dataIndex:'lastmodify'},{resizable:true,id:'class_id',header:'classid',dataIndex:'class_id'}]);cm.defaultSortable=true;var grid=new Ext.grid.Grid(gridDivTag,{ds:ds,cm:cm,autoSizeColumns:false,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),enableColLock:false,loadMask:true,enableDragDrop:true,ddGroup:'TreeDD',autoExpandColumn:'description'});center.remove(0);grid.render();var gridHeader=grid.getView().getHeaderPanel(true);var pagingHeader=new Ext.PagingToolbar(gridHeader,ds,{pageSize:50,displayInfo:true,displayMsg:'Displaying phone {0} - {1} of {2}',emptyMsg:"No phones to display"});center.add(new Ext.GridPanel(grid));}
var showSoftwareGrid=function(_layout){var center=_layout.getRegion('center',false);var bodyTag=Ext.Element.get(document.body);var gridDivTag=bodyTag.createChild({tag:'div',id:'gridAsterisk',cls:'x-layout-inactive-content'});var ds=new Ext.data.JsonStore({url:'index.php',baseParams:{method:'Asterisk.getData',datatype:'software'},root:'results',totalProperty:'totalcount',id:'software_id',fields:[{name:'software_id'},{name:'phonemodel'},{name:'softwareimage'},{name:'description'}],remoteSort:true});ds.setDefaultSort('softwareimage','desc');ds.load();var cm=new Ext.grid.ColumnModel([{resizable:true,id:'software_id',header:"ID",dataIndex:'software_id',width:30},{resizable:true,id:'softwareimage',header:"softwareimage",dataIndex:'softwareimage',width:250},{resizable:true,id:'description',header:'description',dataIndex:'description'},{resizable:true,id:'phonemodel',header:'phonemodel',dataIndex:'phonemodel',hidden:false}]);cm.defaultSortable=true;var grid=new Ext.grid.Grid(gridDivTag,{ds:ds,cm:cm,autoSizeColumns:false,selModel:new Ext.grid.RowSelectionModel({multiSelect:true}),enableColLock:false,loadMask:true,enableDragDrop:true,ddGroup:'TreeDD',autoExpandColumn:'description'});center.remove(0);grid.render();var gridHeader=grid.getView().getHeaderPanel(true);var pagingHeader=new Ext.PagingToolbar(gridHeader,ds,{pageSize:50,displayInfo:true,displayMsg:'Displaying software {0} - {1} of {2}',emptyMsg:"No software to display"});center.add(new Ext.GridPanel(grid));}
return{show:function(_layout,_node){switch(_node.attributes.datatype){case'classes':showClassesGrid(_layout);break;case'overview':break;case'phones':showPhonesGrid(_layout);break;case'software':showSoftwareGrid(_layout);break;}}}}();