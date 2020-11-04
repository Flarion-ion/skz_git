var jqxTheme = "bootstrap";
var now;
var rights = "viewer";
var sortinformation = null;

function init(){
	init_rights();
	//при изменении списка районов поменять заголовок и обновить таблицу
	$('#region-list').on("change", region_list_changed);
	/*при изменении масштаба страницы кнопками ctrl + '=', ctrl + '+', ctrl + '-', ctrl + '0'*/
	$(window).keypress(function(e){
		if((e.which == 61 && e.ctrlKey) ||
		   (e.which == 43 && e.ctrlKey) ||
		   (e.which == 45 && e.ctrlKey) ||
		   (e.which == 48 && e.ctrlKey)){
			setSelectWidth('#region-list');
		}
	});

	var dropdownlistSource = {
		datatype: "json",
		datafields: [
			{ name: 'fid' },
			{ name: 'name' }
		],
		data:{type: 'filials'},
		id: 'fids',
		url: "backend.php"
	};
	var filialsAdapter = new $.jqx.dataAdapter(dropdownlistSource,
		{
			loadComplete: function (records){
				var select = '';
				for(var i = 0; i < records.length; ++i){
					if(i == 0){
						select += '<option selected="selected" value="'+records[i]['fid']+'">'+records[i]['name']+'</option>';
					}else{
						select += '<option value="'+records[i]['fid']+'">'+records[i]['name']+'</option>';
					}
				}
				$('#region-list').html(select);
				setSelectWidth('#region-list');
				update_table();
			}
		}
	);

	//init stations grid
    var source = {
		datatype: "json",
		datafields: [
			{ name: 'sid' },
			{ name: 'fid'},
			{ name: 'phone'},
			{ name: 'filial' },
			{ name: 'address' },
			{ name: 'passport_id', type: 'float' },
			{ name: 'protection' },
			{ name: 'u', type: 'float' },
			{ name: 'ue', type: 'float' },
			{ name: 'ui', type: 'float' },
			{ name: 'u_state' },
			{ name: 'ue_state' },
			{ name: 'ui_state' },
			{ name: 'last_date' },
			{ name: 'state' },
			{ name: 'imai' },
			{ name: 'sparam' },
			{ name: 'sparam_value'},
			{ name: 'stest' },
			{ name: 'power' },
			{ name: 'color_state' },
			{ name: 'pe_setpoint' },
			{ name: 'pe_deviation' },
			{ name: 'exit_u_setpoint' },
			{ name: 'exit_u_deviation' },
			{ name: 'exit_i_setpoint' },
			{ name: 'exit_i_deviation' },
		],
		data:{type: 'stations', fid: 0},
		id: 'sids',
		url: 'backend.php',
	};
	var dataadapter = new $.jqx.dataAdapter(source,{
		loadComplete: stationsLoadComplete,
		formatData: stationsFormatData
	});

	grid = $("#jqxgrid").jqxGrid({
		width: "100%",
		source: dataadapter,
		theme: jqxTheme,
		rowsheight: 30,
		columnsresize: false,
		showemptyrow: false,
		autoheight: true,
		updatedelay: 5,
		autoshowloadelement: false,
		selectionmode: "none",
		sortable: true,
		columns: [
			{
				text: '#', sortable: false, filterable: false, editable: false,
				groupable: false, draggable: false, resizable: false,
				datafield: '', columntype: 'number', width: 50,
				cellsrenderer: function (row, column, value){
					return "<div style='margin: 7px;'>" + (value + 1) + "</div>";
				}
			},
			{ text: 'Паспорт', datafield: 'passport_id', width: 60, cellsrenderer: cellsrenderer, sortable: true},
			{ text: 'Адрес', datafield: 'address', width: 300, cellsrenderer: cellsrenderer, sortable: true},
			{ text: 'Последние данные', datafield: 'last_date', width: 140, cellsalign: 'center', cellclassname: cellclass, cellsrenderer:cellsrenderer,sortable: true},
			{ text: 'Задание', datafield: 'protection', width: 100, cellsalign: 'left', cellsrenderer: cellsrenderer,sortable: true},
			{ text: 'U защ', datafield: 'u', cellclassname: cellclass, /*width: 50,*/ cellsalign: 'center', cellsrenderer: cellsrenderer,sortable: true},
			{ text: 'U вых', datafield: 'ue', cellclassname: cellclass, /*width: 50,*/ cellsalign: 'center', cellsrenderer: cellsrenderer,sortable: true},
			{ text: 'I вых', datafield: 'ui', cellclassname: cellclass, /*width: 50,*/ cellsalign: 'center', cellsrenderer: cellsrenderer ,sortable: true},
			{ text: 'Счетчик', datafield: 'power', /*width: 50,*/ cellsalign: 'center', cellsrenderer: cellsrenderer ,sortable: true},
			{ text: 'Состояние', datafield: 'state', width: 130, cellsalign: 'center', cellsrenderer: cellsrenderer ,sortable: true},
		]
	});
	grid.bind('rowclick', stationsRowClick);

	// init station window with fields
	swindow = $("#jqxwindow").jqxWindow({
		width: "50%",
		maxWidth: "90%",
		minHeight: "440px",
		//height: "60%",
		theme: jqxTheme,
		autoOpen: false,
		isModal: true,
		resizable: false,
		draggable: true,
	});

	hist_window = $("#jqxwindow_hist").jqxWindow({
		width: "100%",
		height: "100%",
		theme: jqxTheme,
		autoOpen: false,
		isModal: true,
		resizable: false,
		draggable: false,
	});

	//блок настроек "о станции"
	spodr = $("#spodr").jqxDropDownList({
		source: filialsAdapter,
		width: "100%",
		autoDropDownHeight: true,
		itemHeight: 30,
		theme: jqxTheme,
		displayMember: 'name',
		valueMember: 'fid',
	});
	$("#saddr").jqxInput({minLength: 1});
	$("#sphone").jqxInput({});
	$("#simai").jqxInput({});
	$("#spassport_id").jqxInput({});
	var sbutton_save = $("#sbutton_save").jqxButton({});
	sbutton_save.bind('click', function(){$('#station_form').jqxValidator('validate', $('#station_form'));});

	//блок настроек защитных параметров
	var params = ["U защ.","U вых.","I вых."];
	$("#sparam").jqxDropDownList({
		source: params,
		width: "100%",
		selectedIndex: 0,
		autoDropDownHeight: true,
		itemHeight: 30,
		theme: jqxTheme,
	});
	$("#sparam_value").jqxNumberInput({
		height: '25px',
		digits: 2,
		promptChar: " ",
		theme: jqxTheme,
		decimal: 0,
    });
	$("#stest1").jqxRadioButton({checked: true });
	$("#stest2").jqxRadioButton({});
	var sbutton_task = $("#sbutton_task").jqxButton({});
	sbutton_task.bind('click', submitProtectionForm);

	//кнопка "удалить"
	$("#deleteButton").jqxButton({disabled:  true}).bind('click', ConfirmDeleteStation);

	//блок настроек для уставок
	$("#pe_setpoint").jqxNumberInput({
		height: '25px',
		digits: 2,
		promptChar: " ",
		theme: jqxTheme,
	});
	$("#pe_deviation").jqxNumberInput({
		height: '25px',
		digits: 3,
		decimalDigits: 0,
		promptChar: " ",
		theme: jqxTheme,
	});
	$("#exit_u_setpoint").jqxNumberInput({
		height: '25px',
		digits: 2,
		promptChar: " ",
		theme: jqxTheme,
	});
	$("#exit_u_deviation").jqxNumberInput({
		height: '25px',
		digits: 3,
		decimalDigits: 0,
		promptChar: " ",
		theme: jqxTheme,
	});
	$("#exit_i_setpoint").jqxNumberInput({
		height: '25px',
		digits: 2,
		promptChar: " ",
		theme: jqxTheme,
	});
	$("#exit_i_deviation").jqxNumberInput({
		height: '25px',
		digits: 3,
		decimalDigits: 0,
		promptChar: " ",
		theme: jqxTheme,
	});
	var setpoint_button = $("#setpoint_button").jqxButton({});
	setpoint_button.bind('click', submitSetpointForm);

	//проверка для формы "о станции"
	$("#station_form").jqxValidator({
		position: 'topcenter',
		scroll: false,
		onSuccess: submitStationForm,
		rules: [
			{ input: "#spodr", message: "Необходимо выбрать филиал", rule: filialChecked, action: "blur" },
			{ input: "#saddr", message: "Адрес не может быть пустым", rule: 'minLength=1', action: "blur" },
			{ input: "#sphone", message: "Телефон не может быть пустым", rule: 'minLength=1', action: "blur" },
			{
				input: "#simai",
				message: "IMEI должен быть длиной 15",
				rule:  function (){
					var imei = $('#simai').val();
					return (imei.length == 0) || (imei.length==15);
				},
				action: "blur"
			},
		]
	});

	//блок нижних кнопок
	$("#updateButton").jqxButton({ height: '34'}).bind('click', submitUpdateCommand);
	$("#journalButton").jqxButton({ height: '34'}).bind('click', showJournal);
	$("#journalInTabButton").jqxButton({ height: '34'}).bind('click', showJournalInTab);
	$("#chartButton").jqxButton({ height: '34'}).bind('click', showChartInTab);
	$("#cancelButton").jqxButton({ height: '34'}).bind('click', cancelAction);

	//update_table();
	auto_update();
}

function init_rights(){
	var url = "backend.php";
	$.ajax({
		type: 'POST',
		url: url,
		data: {
			type: "rights"
		},
		dataType: 'json',
		cache: false,
		error: function() {
			console.log("init_rights error");
		},
		success: function(resp){
			rights = resp.rights;
			if(rights == "user"){
				//init add station button
				$("#add-btn").show();
				$("#add-btn").on("click", addStation);
			}
			console.log("rights", rights);
		},
		complete: function(XMLHttpRequest, textStatus) {
		}
	});
}

var cellclass = function (row, columnfield, value, data){
	if(columnfield == 'u' || columnfield == 'ue' || columnfield == 'ui'){
		if(data.ue > 12 && data.ui < 0.2){
			return 'red';
		}
		return (data[columnfield+"_state"] != 3) ? 'warning' : '';
	}
	if(columnfield == 'last_date'){
		return (data['color_state'] == "yellow") ? 'warning' : '';
	}
}

var cellsrenderer = function (row, column, value, defaultHtml, columnSettings, rowData){
	return "<div style='margin: 8px;'>" + value + "</div>";
}

function ConfirmDeleteStation(){
	var retVal = confirm("Вы действительно хотите удалить станцию ?");
	if(retVal == true){
		submitDeleteStation();
	};
}

function cancelAction(){
	swindow.jqxWindow('close');
}
function showChartInTab(){
	//$("#jqxwindow").jqxWindow('close');
	var url = "chart.php?sid="+session_id+"&skz_id="+$("#station_id")[0].getAttribute('value')+"&region_id="+region_id;
	if(session_id == 'panorama'){                  //если смотрим из панорамы,
		var win = window.open(url, '_self');//то открыть в том же фрейме
	}else{
		var win = window.open(url, '_blank');
	}
	win.focus();
}
function showJournalInTab(){
	//$("#jqxwindow").jqxWindow('close');
	var url = "journal.php?sid="+session_id+"&skz_id="+$("#station_id")[0].getAttribute('value')+"&region_id="+region_id;
	if(session_id == 'panorama'){                  //если смотрим из панорамы,
		var win = window.open(url, '_self');//то открыть в том же фрейме
	}else{
		var win = window.open(url, '_blank');
	}
	win.focus();
}
function showJournal(){
	var hist_source = {
		datatype: "json",
		datafields: [
			{ name: 'timeevent' },
			{ name: 'protection' },
			{ name: 'u', type: 'float' },
			{ name: 'ue', type: 'float' },
			{ name: 'ui', type: 'float' },
			{ name: 'u_state'},
			{ name: 'ue_state'},
			{ name: 'ui_state'},
			{ name: 'state' },
			{ name: 'sparam' },
			{ name: 'sparam_value'},
			{ name: 'stest' },
			{ name: 'power' }
		],
		data:{ type: 'history', sid: $("#station_id")[0].getAttribute('value') },
		id: 'sids',
		url: 'backend.php',
	};

	var hist_dataadapter = new $.jqx.dataAdapter(hist_source);
	hist_grid =   $("#history_table").jqxGrid({
		width: "98%",
		height: "100%",
		source: hist_dataadapter,
		theme: jqxTheme,
		rowsheight: 30,
		scrollmode: 'logical',
		updatedelay: 5,
		autoshowloadelement: false,
		selectionmode: "none",
		columns: [
			{ text: 'Время', datafield: 'timeevent', width: 140, cellsalign: 'left', sortable: false,cellsformat: 'D', cellsrenderer: cellsrenderer},
			{ text: 'Задание', datafield: 'protection', width: 100, cellsalign: 'left', sortable: false, cellsrenderer: cellsrenderer},
			{ text: 'U защ', datafield: 'u', cellclassname: cellclass,  cellsalign: 'center', sortable: true, cellsrenderer: cellsrenderer},
			{ text: 'U вых', datafield: 'ue', cellclassname: cellclass,  cellsalign: 'center', sortable: false, cellsrenderer: cellsrenderer},
			{ text: 'I вых', datafield: 'ui', cellclassname: cellclass,  cellsalign: 'center', sortable: false, cellsrenderer: cellsrenderer},
			{ text: 'Состояние', datafield: 'state', width: 130, cellsalign: 'center', sortable: false, cellsrenderer: cellsrenderer},
			//{ text: 'Состояние', datafield: null, width: 130, cellsalign: 'center', sortable: false, cellsrenderer: cellsrenderer},
			{ text: 'Счётчик', datafield: 'power',  cellsalign: 'center', sortable: false, cellsrenderer: cellsrenderer},
		]
	})

	hist_window.jqxWindow('open');
}

function stationsRowClick(event){
	var dis = (rights == "viewer");

	$("#deleteButton").jqxButton('disabled', dis);
	$("#sbutton_save").jqxButton('disabled', dis);
	$("#sbutton_task").jqxButton('disabled', dis);
	$("#setpoint_button").jqxButton('disabled', dis);

	var irow = event.args.rowindex;
	//init station fields
	var data = $('#jqxgrid').jqxGrid('getrowdata', irow);

	var item = $("#spodr").jqxDropDownList("getItemByValue",data.fid);
	$("#spodr").jqxDropDownList('selectItem', item );
	$("#saddr").jqxInput('val', data.address);
	$("#sphone").jqxInput('val', data.phone);
	$("#simai").jqxInput('val', data.imai);
	$("#spassport_id").jqxInput('val', data.passport_id);
	//init protection fiedlds
	$('#sparam').jqxDropDownList('selectIndex',data.sparam);
	if(data.sparam_value !== null){
		$('#sparam_value').jqxNumberInput('inputValue',data.sparam_value);
	}else{
		$('#sparam_value').jqxNumberInput('inputValue', 0);
	}

	if(data.stest>0){
		$('#stest2').jqxRadioButton('check');
	}else{
		$('#stest1').jqxRadioButton('check');
	}
	//init forms hidden fields
	$("#station_id").attr("value",data.sid);
	$("#protection_id").attr("value",data.sid);
	$("#commands_id").attr("value",data.sid);

	$("#setpoint_station_id").attr("value",data.sid);
	$('#pe_setpoint').jqxNumberInput('inputValue', data.pe_setpoint);
	$('#pe_deviation').jqxNumberInput('inputValue', data.pe_deviation);
	$('#exit_u_setpoint').jqxNumberInput('inputValue', data.exit_u_setpoint);
	$('#exit_u_deviation').jqxNumberInput('inputValue', data.exit_u_deviation);
	$('#exit_i_setpoint').jqxNumberInput('inputValue', data.exit_i_setpoint);
	$('#exit_i_deviation').jqxNumberInput('inputValue', data.exit_i_deviation);
	swindow.jqxWindow('open');
}
function stationsFormatData(){
	this.data.fid = $('#region-list').find(":selected").val();
	if(!this.flag){
		this.flag = true;
	}
}
function stationsLoadComplete(){
	$('#loader').hide();
	sort_table();
}
function auto_update(){
	var timeout = delay;
	var upd = function (){
		setTimeout(function (){
			if($("#auto-update").prop('checked')){
				update_table();
			}
			upd();
		}, timeout);
	};
	upd();
}
function update_table(){
	sortinformation = $('#jqxgrid').jqxGrid('getsortinformation');
	$("#jqxgrid").jqxGrid('updatebounddata');
}
function sort_table(){
	if(sortinformation){
		var sortcolumn      = sortinformation.sortcolumn;
		var sortdirection   = sortinformation.sortdirection;
		//console.log("sort", sortcolumn, sortdirection);

		if(sortcolumn != undefined || sortcolumn != null){
			if(sortdirection['ascending']){
				$('#jqxgrid').jqxGrid('sortby', sortcolumn, 'asc');
			}else{
				$('#jqxgrid').jqxGrid('sortby', sortcolumn, 'des');
			}
		}
	}
}
function filialChecked(){
	return $("#spodr").jqxDropDownList('getSelectedIndex')>=0;
}

function addStation(){
	$("#spodr").val($('#region-list').find(":selected").val());
	$("#station_id").attr('value','');
	$("#saddr").attr('value', '');
	$("#sphone").attr('value', '');
	$("#simai").attr('value', '');
	$("#spassport_id").attr('value', '');
	swindow.jqxWindow('open');
}
function submitStationForm(){
	var options = {
		url: "backend.php",
		dataType: "json",
		error: function(){
			alert("Непредвиденная ошибка, потяряна связь с сервером.");
		},
		success: function(response, status){
			if(response.code == 0){
				alert("Данные сохранены.");
			}else if(response.code == 3){
				alert("Такой номер паспорта уже присвоен станции, расположенной по адресу "+response.addr+".");
			}else if(response.code == 4){
				alert("Такой IMEI уже присвоен станции, расположенной по адресу "+response.region+", "+response.addr+".");
			}else{
				alert("Ошибка сохранения, попробуйте позже.");
			}
			update_table();
		}
	};
	$("#saddr").attr('value','');
	$("#station_form").ajaxSubmit(options);
}
function submitProtectionForm(){
	var options = {
		url: "backend.php",
		data:{parami: $("#sparam").jqxDropDownList('getSelectedIndex')},
		dataType: "json",
		error: function(){
			alert("Непредвиденная ошибка, потяряна связь с сервером.");
		},
		success: function(response, status){
			if(response.code == 0){
				swindow.jqxWindow('close');
			}else if(response.code == 1){
				alert("Станция еще не обработала предыдущую команду.");
			}else{
				alert("Станция не онлайн, нельзя отправить команду.");
			}
			update_table();
		}
	};
	$("#protection_form").ajaxSubmit(options);
}
function submitSetpointForm(){
	var options = {
		url: "backend.php",
		data:{type: 'set_setpoint', user: user},
		dataType: "json",
		error: function(){
			alert("Непредвиденная ошибка, потяряна связь с сервером.");
		},
		success: function(response, status){
			if(response.code == 0){
				swindow.jqxWindow('close');
			}else if(response.code == -1){
				alert("Ошибка базы данных.");
			}else if(response.code == -3){
				alert("Не указана станция.");
			}else{
				alert("Ошибка сохранения, попробуйте позже.");
			}
			update_table();
		}
	};
	$("#setpoint_form").ajaxSubmit(options);
}
function submitDeleteStation(){
	var options = {
		url: "backend.php",
		dataType: "json",
		data:{type: "station_delete" },
		error: function(){
			alert("Непредвиденная ошибка, потяряна связь с сервером.");
		},
		success: function(response, status){
			if(response.code==0){
				swindow.jqxWindow('close');
			}else{
				alert("Ошибка удаления, попробуйте позже.");
			}
			update_table();
		}
	};
	$("#commandsForm").ajaxSubmit(options);
}
function submitUpdateCommand(){
	var options = {
		url: "backend.php",
		dataType: "json",
		data:{type: "update_task" },
		error: function(){
			alert("Непредвиденная ошибка, потяряна связь с сервером.");
		},
		success: function(response, status){
			if(response.code == 0){
				alert("Запрос на обновление отправлен");
				swindow.jqxWindow('close');
			}else if(response.code == 1){
				alert("Станция еще не обработала предыдущую команду.");
			}else if(response.code == 2){
				alert("Станция не онлайн, нельзя отправить команду.");
			}else{
				alert("Ошибка сохранения, попробуйте позже.");
			}
			update_table();
		}
	};
	$("#commandsForm").ajaxSubmit(options);
}


function region_list_changed(){
	region_id = $('#region-list').find(":selected").val();
	update_table();
}
//растягивает выпадающий список подразделений, если текст не помещается
function setSelectWidth(selector){
	var sel = $(selector);
	var tempSel = $("<select style='display:none'>").append($("<option>").text(sel.find("option:selected").text()));
	tempSel.appendTo($("body"));
	sel.width(tempSel.width()+23);
	tempSel.remove();
}
