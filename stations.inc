<script type="text/javascript">
var states = Array("Норм.", "Обрыв сил. ТРУБЫ", "Обрыв сил. АНОДА", "Обрыв изм. електрода", "Обрыв изм. трубы", "KЗ");
var PVA = Array("Выключена","Uзащ = ","Uвых = ","Iвых = ");
var updatingIDs;
var IDindex = 0;
var selRegion = Array();
<?php
$regions = getAllowRegions($db,$_SESSION['user']);
$regions_str = "{";
foreach($regions as $region) $regions_str .= $region['id'].":'".$region['name']."',";
$regions_str = substr($regions_str, 0, strlen($regions_str)-1);
$regions_str .= "};";
echo "var regions=".$regions_str."\n";
?>
function updateStations() {
  id = jQuery('#stable').getGridParam('selarrrow');
  if(!id) { return; }
  $.get('backend.php?oper=update&id='+id,function(data,textStatus) {
    $("#dialog").dialog('close');
    if(textStatus=='success') {
      data = $.parseJSON(data);
      if(data['excode']>2) {
		alert("Некоторые станции не вышли на связь");
      } else if(data['excode']) alert("Ошибка модема"); else alert("Обновление выполнено успешно");
//      for(key in data['rows']) $("#stable").jqGrid('setRowData',data['rows'][key]['id'],data['rows'][key]['cell']);
       $("#stable").trigger('reloadGrid');
    } else alert("Сбой оборудавания");
  });
  $("#dialog").dialog({closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close").hide();}, modal:true});
}

function getStationsJournals() {
  id = jQuery('#stable').getGridParam('selarrrow');
  if(!id) { return; }
  $.get('backend.php?oper=getJournals&id='+id,function(data,textStatus) {
    $("#dialog").dialog('close');
    if(textStatus=='success') {
      data = $.parseJSON(data);
      if(data['excode']>2) {
	alert("Некоторые станции не вышли на связь");
      } else if(data['excode']) alert("Ошибка модема"); else alert("Журналы успешно загружены");
//      for(key in data['rows']) $("#stable").jqGrid('setRowData',data['rows'][key]['id'],data['rows'][key]['cell']);
       $("#stable").trigger('reloadGrid');
    } else alert("Сбой оборудавания");
  });
  $("#dialog").dialog({closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close").hide();}, modal:true});
}

function updating(id) {
  $('#taskDialog').dialog({
    modal: true,
    title: "Параметры станции",
    width: 400,
    resizable: false,
    closeOnEscape: true,
    buttons: {
	    "Ok": function() {
	    	par = $('#param option:selected').val();
	    	value = $('#param_value').val()*100;
	    	if((par==1) && (value<50 || value>350)) {
		    	alert("Значение Uзащ. должно быть от 0.5В до 3.5В");
		    	return;
		    }
	    	if((par==2) && (value<100)) {
		    	alert("Значение Uвых. должно быть больше 1В");
		    	return;
		    }
	    	if((par==3) && (value<20)) {
		    	alert("Значение Iвых. должно быть больше 0.2А");
		    	return;
		    }
	    	reg = $('#reg_cont input:radio:checked').val();
	        line = $("#stable").getRowData(id);
	    	$("#dialog").dialog({resizable: false, width:500, closeOnEscape: false,open: function(event, ui) { $(".ui-dialog-titlebar-close").hide();}, modal:true});
		    $.get('backend.php?oper=change&id='+id+'&par='+par+'&value='+value+'&reg='+reg,function(data,textStatus) {
		    	$("#dialog").dialog('close');
		    	if(textStatus=='success') {
		      		data = $.parseJSON(data);
		      		if(data['excode']>2) {
				    alert("Произошла ошибка. Поробуте еще раз.");
		      		} else {
			      	    alert("Новое задание заиты успешно установлено.");/**/
		      		}
		    	} else alert("Сбой оборудавания");
		    	$('#taskDialog').dialog("close");
		  	});/**/
	    }, 
	    "Отмена": function() { $(this).dialog("close"); }
  	},
    open: function(event, ui) {
      //init fields
      line = $("#stable").getRowData(id);
      protect = 'Нет данных';
      if(line.protect[0]!='<') protect = line.protect; 
      $('#stationInfo').html("<b>Владелец:  </b>"+line['region']+"<BR>"+
    	      			 "<b>Адрес: </b>"+line['addres']+"<BR>"+
    	      			 "<b>Телефон: </b>"+line['phone']+"<br>"+
    	      			 "<b>Параметры защиты: </b>"+protect+"");
    },
    close: function(event, ui) {
      //starting dialog for next station
      IDindex++;
      if(IDindex < updatingIDs.length) updating(updatingIDs[IDindex]); else $("#stable").trigger('reloadGrid');
    }
  });/**/
}

function showing(id) {
  $('#jtable').setGridParam({url:"backend.php?oper=journal&id="+id}).trigger('reloadGrid');
  $('#journalDialog').dialog({
    modal: true,
    title: "Параметры станции",
    width: 800,
    resizable: false,
    position: 'center',
    close: function(event, ui) {
      //starting dialog for next station
      IDindex++;
      if(IDindex < updatingIDs.length) showing(updatingIDs[IDindex]);
    }
  });/**/
  $('#jtable').hideCol('pt_w');
  $('#jtable').setGridWidth($('#journalDialog').width(),true);
}

function showJournal() {
  updatingIDs = jQuery('#stable').getGridParam('selarrrow');
  IDindex = 0;
  if(IDindex < updatingIDs.length) showing(updatingIDs[IDindex]);
}

function protectionTask() {
  updatingIDs = jQuery('#stable').getGridParam('selarrrow');
  IDindex = 0;
  if(IDindex < updatingIDs.length) updating(updatingIDs[IDindex]);
}

$(document).ready(function(){  
  <?php
  echo "backend_url = 'backend.php?com=get&reg=".$regions[0]['id']."';\n";
  ?>
  $('#stable').jqGrid({
      url:backend_url,
      datatype: 'json',
      multiselect: true,
      mtype: 'GET',
      colNames:['Подразделение','Адрес', 'Телефон','Состояние','Защита','','U защ.','U вых.','I вых.','','','Последний опрос'],
      colModel :[
 	{name:'region', width:250, index:'region',editable: true,edittype:'select',editoptions:{value:regions}},
	{name:'addres', width:250, index:'addres',editable: true,edittype:'textarea',editoptions:{size:3}},
	{name:'phone', index:'phone',editable: true, editrules:{edithidden:true}},
	{name:'state',index:'state'},
	{name:'protect',index:'protect'},
	{name:'pt_w'},
	{name:'PT', index:'PT', width:100, align:'center'},
	{name:'EU', index:'EU', width:100, align:'center'},
	{name:'EI', index:'EI', width:100, align:'center'},
 	{name:'ELECTROD', index:'ELECTROD', width:23, align:'center'},
	{name:'DOOR', index:'DOOR', width:23, align:'center'},
	{name:'last_date', index:'last_date', width:200, align:'center'}],
      autowidth: true,
      height: 350,
//      rownumbers: true,
      pager: $('#spaper'),
      rowNum:50,
//      rowList:[20,50,100,200,400,800,1000],
      sort: 'region',
      sortorder: 'asc',
      ajaxGridOptions: {cache: false},
      ondblClickRow: function(rowid,iRow,iCol,e) {
	alert('double');
      },
      afterInsertRow: function(row_id, row_data) {
			//change electrod state to icon
			state = Number(row_data.ELECTROD);
			if(state<0) $('#stable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon ui-icon-closethick">');
			else if(state<3) $('#stable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon ui-icon-plus" title="Электрод в норме">');
			else $('#stable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon-error ui-icon-minusthick" title="Обрав электрода">');
			//change door state to icon
			state = Number(row_data.DOOR);
			if(state<0) $('#stable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon ui-icon-closethick">');
			else if(state) $('#stable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon-error ui-icon-unlocked" title="Дверь открыта">');
			else $('#stable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon ui-icon-locked" title="Дверь закрыта">');
			//change state
			state=Number(row_data.state);
			if(state>=0) {
			  if(state>6) state = 1;
			  $('#stable').setCell(row_id,'state',states[state]);
			}
			//change protect state
			state = Number(row_data.protect);
			if(state&3)	str = PVA[state&3]+row_data.pt_w;
			else str = PVA[state&3];
			if(state&4) str += " - К";
			if(state&8) str += " - Д";
			$('#stable').jqGrid('setCell',row_id,'protect',str); 
			//check params
			if(Number(row_data.PT)<0) $('#stable').jqGrid('setCell',row_id,'PT','<div class="ui-icon ui-icon-closethick">');
			if(Number(row_data.EU)<0) $('#stable').jqGrid('setCell',row_id,'EU','<div class="ui-icon ui-icon-closethick">');
			if(Number(row_data.EI)<0) $('#stable').jqGrid('setCell',row_id,'EI','<div class="ui-icon ui-icon-closethick">');
			if(Number(row_data.state)<0) $('#stable').jqGrid('setCell',row_id,'state','<div class="ui-icon ui-icon-closethick">');
			if(Number(row_data.protect<0)) $('#stable').jqGrid('setCell',row_id,'protect','<div class="ui-icon ui-icon-closethick">');
	   }
  }).jqGrid('navGrid','#spaper',{edit:true, add:true, del:true, view:false, search: false, refresh: false},
								{url:'backend.php',jqModal:true, checkOnUpdate:true, checkOnSubmit : true, title:'Редактирование выбранной станции', width:500, reloadAfterSubmit:true, closeAfterEdit : true}, //edit
								{url:'backend.php',jqModal:true, checkOnUpdate:true, checkOnSubmit : true, title:'Добавить станцию', width:500, reloadAfterSubmit:true, closeAfterAdd : true}, //add
								{url:'backend.php',jqModal:true, width:500, title:'Удалить станцию', reloadAfterSubmit:true, closeAfterAdd : true}) //del 
    .jqGrid('navButtonAdd','#spaper',{caption:" Опрос ",buttonicon:'ui-icon-info',title:'Опросить выбранные станции',onClickButton: updateStations})
    .jqGrid('navButtonAdd','#spaper',{caption:" Журнал ",buttonicon:'ui-icon-info',title:'Получить журналы с выбранных станций',onClickButton: getStationsJournals})
    .jqGrid('navButtonAdd','#spaper',{caption:" Защита ", buttonicon:'ui-icon-document',title:'Изменить параметры защиты',onClickButton: protectionTask})
    .jqGrid('navButtonAdd','#spaper',{caption:" Просмотр ", buttonicon:'ui-icon-search',title:'Просмотреть журнал',onClickButton: showJournal});
  $('#spaper_right').html('<table cellspacing="0" cellpadding="0" border="0" style="float: right; table-layout: auto;" class="ui-pg-table navtable"><tbody><tr><td id="jl" class="ui-pg-button ui-corner-all" title="Просмотреть журналы выбранных станций"><div class="ui-pg-div"><span class="ui-icon ui-icon-document"></span> Просмор журнала </div></td></tr></tbody></table>');
  $('#jl').hover(function() {$('#jl').addClass("ui-state-hover ui-pg-button");},function() {$('#jl').removeClass("ui-state-hover ui-pg-button");});
  $('#jl').click(showJournal);
  $('#stable').hideCol("phone").hideCol('pt_w');
  $("#stable").jqGrid('setLabel','ELECTROD',"<div class='ui-icon ui-icon-pencil'>",'','')
	      	  .jqGrid('setLabel','DOOR',"<div class='ui-icon ui-icon-note'>",'','');
  $(window).bind('resize', function() {
    $("#stable").setGridWidth($("#stations").width());
  });

  $('#select').selectable({
    stop: function(event,ui) {
      selRegion = Array();
      $(".ui-selected", this ).each(function() {
	  selRegion.push(this.getAttribute('id_region'));
      });
      $('#stable').jqGrid('setGridParam',{url:"backend.php?com=get&reg="+selRegion.map(function(x) { return ""+x+""}).join(),page:1,search:false}).trigger('reloadGrid');
    },
  });
  $("#stable").setGridWidth($("#stations").width());
  $('#param_value').numeric({ negative: false });
  $('#jtable').jqGrid({
//    url:"backend.php?oper=journal&id="+id,
    datatype: 'json',
    mtype: 'GET',
    colNames:['Дата','Состояние','Защита','','U защ.','U вых.','I вых.','',''],
    colModel :[
      {name:'date',index:'state',width:170},
      {name:'state',index:'state'},
      {name:'protect',index:'protect'},
      {name:'pt_w'},
      {name:'PT', index:'PT', width:100, align:'center'},
      {name:'EU', index:'EU', width:100, align:'center'},
      {name:'EI', index:'EI', width:100, align:'center'},
      {name:'ELECTROD', index:'ELECTROD', width:23, align:'center'},
      {name:'DOOR', index:'DOOR', width:23, align:'center'}],
    autowidth: true,
    height: 350,
//	rownumbers: true,
    pager: $('#jpaper'),
    rowNum:50,
// 	rowList:[20,50,100,200,400,800,1000],
    ajaxGridOptions: {cache: false},
    afterInsertRow: function(row_id, row_data) {
    //change electrod state to icon
    state = Number(row_data.ELECTROD);
    if(state<0) $('#jtable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon ui-icon-closethick">');
    else if(state<3) $('#jtable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon ui-icon-plus" title="Электрод в норме">');
    else $('#jtable').jqGrid('setCell',row_id,'ELECTROD','<div class="ui-icon-error ui-icon-minusthick" title="Обрав электрода">');
    //change door state to icon
    state = Number(row_data.DOOR);
    if(state<0) $('#jtable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon ui-icon-closethick">');
    else if(state) $('#jtable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon-error ui-icon-unlocked" title="Дверь открыта">');
    else $('#jtable').jqGrid('setCell',row_id,'DOOR','<div class="ui-icon ui-icon-locked" title="Дверь закрыта">');
    //change state
    state=Number(row_data.state);
    if(state>=0) {
      if(state>6) state = 1;
	$('#jtable').setCell(row_id,'state',states[state]);
    }
    //change protect state
    state = Number(row_data.protect);
    if(state&3)	str = PVA[state&3]+row_data.pt_w;
    else str = PVA[state&3];
    if(state&4) str += " - К";
    if(state&8) str += " - Д";
    $('#jtable').jqGrid('setCell',row_id,'protect',str); 
    //check params
    if(Number(row_data.PT)<0) $('#jtable').jqGrid('setCell',row_id,'PT','<div class="ui-icon ui-icon-closethick">');
    if(Number(row_data.EU)<0) $('#jtable').jqGrid('setCell',row_id,'EU','<div class="ui-icon ui-icon-closethick">');
    if(Number(row_data.EI)<0) $('#jtable').jqGrid('setCell',row_id,'EI','<div class="ui-icon ui-icon-closethick">');
    if(Number(row_data.state)<0) $('#jtable').jqGrid('setCell',row_id,'state','<div class="ui-icon ui-icon-closethick">');
    if(Number(row_data.protect<0)) $('#jtable').jqGrid('setCell',row_id,'protect','<div class="ui-icon ui-icon-closethick">');
    }
  });
});
</script>
<div>
  <div id="regions">
    <ol id="select">
      <?php
      $first = true;
      foreach($regions as $region) 
	if($first) {
	  echo "<li class='ui-widget-content ui-selected' id_region=".$region['id'].">".$region['name']."</li>\n";/**/
	  $first = false;
	} else echo "<li class='ui-widget-content' id_region=".$region['id'].">".$region['name']."</li>\n";/**/
      ?>
    </ol>
  </div>
  <div id="stations">
    <div id="dialog" style="display:none" title="Работа с модемом"><p>Запрос на обновление отправлен, это может занять много времени.<br>Пожалуйста не закрывайте окно программы</p><br><p align=center style="text-size:12pt">Ожидайте</p></div>
    <div id="taskDialog" style="display:none" title="Параметры защиты">
	<label id="stationInfo"></label><br><br>
	<p align=center><b>Новые парамерты</b></p><br><HR>
	<label id="new_param"><b>Параметр стабилизации:</b></label><br>
	<select id="param" name="param" class="ui-corner-all" style="border:1px solid">
        <option id="par1" value="1">U защ.</option>
        <option id="par2" value="2">U вых.</option>
        <option id="par3" value="3">I вых.</option>
    </select>
    <input type="text" id="param_value" value="" class="text ui-widget-content ui-corner-all" size=10/><br><br>
    <label><b>Тест обрывов:</b></label><br>
    <div id="reg_cont" style="padding-left:20px">
    <INPUT TYPE=RADIO NAME=regim VALUE=0 > отключить</input><br> 
    <INPUT TYPE=RADIO NAME=regim VALUE=1 CHECKED> включить</input><br>
    </div>
    </div>
    <div>
      <div id=""></div>
    </div>
    <table id="stable"></table>
    <div id="spaper"></div>
    <div id="journalDialog">
      <table id="jtable"></table>
      <div id="jpaper"></div>
    </div>
  </div>
</div>