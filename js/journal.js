function init() {
	if(session_id == 'panorama'){
		$('#back-btn').show();
		$('#back-btn').click(function () {
			url = "index.php?sid="+session_id+"&region_id="+region_id;
			var win = window.open(url, '_self');//то перезагрузить всю страницу
			win.focus();
		});
	}else{
		$('#back-btn').hide(true);
	}

	init_datepicker();
	$('#header').text("Журнал параметров СКЗ, "+skz_address);
	showJournal();
}

function init_datepicker(){
	var date1_parts = date1.split('-');
	var date2_parts = date2.split('-');
	//please put attention to the month (parts[1]), Javascript counts months from 0: January - 0, February - 1, etc
	var js_date1 = new Date(date1_parts[0], date1_parts[1]-1, date1_parts[2]);
	var js_date2 = new Date(date2_parts[0], date2_parts[1]-1, date2_parts[2]);

	$('#datepicker').datepicker({
		format: "yyyy-mm-dd",
		language: "ru",
		startDate: "2000-01-01",
		endDate: new Date,
		daysOfWeekHighlighted: "0,6",
		autoclose: true,
		todayHighlight: true,
		todayBtn: "linked",
	});
	$('[name=date1]').datepicker("setDate", js_date1);
	$('[name=date2]').datepicker("setDate", js_date2);
}
function get_str_date(date){//возврашает дату в виде "2017-12-22"
	var year  = date.getFullYear();
	var month = ('0'+(date.getMonth()+1)).slice(-2);
	var day   = ('0'+date.getDate()).slice(-2);
	var strdate = year+"-"+month+"-"+day;
	return strdate;
}
function reload_page(){
	var date1 = $('[name=date1]').datepicker("getUTCDate");
	var str_date1 = get_str_date(date1);
	var date2 = $('[name=date2]').datepicker("getUTCDate");
	var str_date2 = get_str_date(date2);

	var url = "journal.php?sid="+session_id+"&region_id="+region_id+"&skz_id="+skz_id+"&date1="+str_date1+"&date2="+str_date2;
	var win = window.open(url, '_self');//перезагрузить всю страницу
	win.focus();
}

var cellclass = function (row, columnfield, value, data) {
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

function showJournal() {
	var hist_source = {
		datatype: "json",
		datafields: [
			{ name: 'timeevent' },
			{ name: 'protection' },
			{ name: 'u', type: 'float' },
			{ name: 'ue', type: 'float' },
			{ name: 'ui', type: 'float' },
			{ name: 'u_state' },
			{ name: 'ue_state' },
			{ name: 'ui_state' },
			{ name: 'state' },
			{ name: 'sparam' },
			{ name: 'sparam_value'},
			{ name: 'stest' },
			{ name: 'power' },
		],
		data: { type: 'history', sid: skz_id, date1: date1, date2: date2},
		id: 'sids',
		url: 'backend.php',
	};

	var cellsrndr = function (row, column, value, defaultHtml, columnSettings, rowData) {
		return "<div style='margin: 7px;'>" + value + "</div>";
	}

	var hist_dataadapter = new $.jqx.dataAdapter(hist_source);

	hist_grid = $("#history_table").jqxGrid({
		source: hist_dataadapter,
		theme: "bootstrap",
		pageable: true,
		pagesizeoptions: ['10', '20', '30', '40'],
		rowsheight: 30,
		autoheight: true,
		scrollmode: 'logical',
		updatedelay: 5,
		autoshowloadelement: false,
		selectionmode: "none",
		columns: [
			{ text: 'Время', datafield: 'timeevent', width: 140, cellsalign: 'left', sortable: false, cellsformat: 'D', cellsrenderer: cellsrndr},
			{ text: 'Задание', datafield: 'protection', width: 100, cellsalign: 'left', sortable: false, cellsrenderer: cellsrndr},
			{ text: 'U защ', datafield: 'u', cellclassname: cellclass,  cellsalign: 'center', sortable: true, cellsrenderer: cellsrndr},
			{ text: 'U вых', datafield: 'ue', cellclassname: cellclass,  cellsalign: 'center', sortable: false, cellsrenderer: cellsrndr},
			{ text: 'I вых', datafield: 'ui', cellclassname: cellclass,  cellsalign: 'center', sortable: false, cellsrenderer: cellsrndr},
			{ text: 'Состояние', datafield: 'state', width: 130, cellsalign: 'center', sortable: false, cellsrenderer: cellsrndr},
			{ text: 'Счётчик', datafield: 'power',  cellsalign: 'center', sortable: false, cellsrenderer: cellsrndr},
		]
	})
	$("#history_table").on("bindingcomplete", function (event) {
		var localizationobj = {};
		localizationobj.pagergotopagestring = "Страница:";
		localizationobj.pagershowrowsstring = "Строк на странице:";
		localizationobj.pagerrangestring = " из ";
		localizationobj.pagernextbuttonstring = "туда";
		localizationobj.pagerpreviousbuttonstring = "сюда";
		localizationobj.sortascendingstring = "По возрастанию";
		localizationobj.sortdescendingstring = "По убыванию";
		localizationobj.sortremovestring = "Не сортировать";
		localizationobj.firstDay = 1;
		localizationobj.percentsymbol = "%";
		localizationobj.decimalseparator = ".";
		localizationobj.thousandsseparator = " ";
		var days = {
			// full day names
			names: ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
			// abbreviated day names
			namesAbbr: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
			// shortest day names
			namesShort: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"]
		};
		localizationobj.days = days;
		var months = {
			// full month names (13 months for lunar calendards -- 13th month should be "" if not lunar)
			names: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь", ""],
			// abbreviated month names
			namesAbbr: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек", ""]
		};
		localizationobj.months = months;
		// apply localization.
		$("#history_table").jqxGrid('localizestrings', localizationobj);
	});
}
