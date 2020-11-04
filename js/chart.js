var initialized = false;

$(document).ready(function(){
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
	//заголовок меню
	$('#header').text("Графики параметров СКЗ, "+skz_address);
	//не скрывать выпадающий список настроек при нажатии по элементу
	$('#chart-settings .dropdown-menu').on('click', function (e) {
		e.stopPropagation();
	});
	//не скрывать выпадающий список настроек при нажатии по элементу
	$('#chart-settings .dropdown-menu').on('click', function (e) {
		e.stopPropagation();
	});
});

$( window ).load(function() {
});

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
		endDate: new Date(),
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

	var url = "chart.php?sid="+session_id+"&region_id="+region_id+"&skz_id="+skz_id+"&date1="+str_date1+"&date2="+str_date2;
	var win = window.open(url, '_self');//перезагрузить всю страницу
	win.focus();
}

var data_url = "backend.php?type=chart_data&sid="+skz_id+"&date1="+date1+"&date2="+date2;
var chart = AmCharts.makeChart("chartdiv", {
	type: "stock",
	theme: "light",
	language: "ru",
	marginRight: 80,
	autoMarginOffset: 20,
	marginTop: 7,
	mouseWheelScrollEnabled: true,
	mouseWheelZoomEnabled: false,
	dataDateFormat: "YYYY-MM-DD HH:NN:SS",
	responsive: {
		enabled: true
	},
	dataSets: [{
		dataLoader: {
			url: data_url,
			format: "json"
		},
		fieldMappings: [{
			fromField: "iout",
			toField: "iout"
		},{
			fromField: "uout",
			toField: "uout"
		},{
			fromField: "up",
			toField: "up"
		}],
		color: "#7f8da9",
		categoryField: "date",
	},],
	panels: [{
		title: "Защитное напряжение",
		stockLegend: [],
		showCategoryAxis: true,
		chartCursor:{
			categoryBalloonEnabled: false,
		},
		valueAxes: [{
			id: "v1",
			dashLength: 5,
		}],
		categoryAxis: {
			dashLength: 0
		},
		stockGraphs: [{
			type: "smoothedLine",
			id: "g1",
			valueField: "up",
		}]
	},{
		title: "Выходное напряжение",
		stockLegend: [],
		showCategoryAxis: true,
		chartCursor:{
			categoryBalloonEnabled: false,
		},
		valueAxes: [{
			dashLength: 5,
		}],
		categoryAxis: {
			dashLength: 0
		},
		stockGraphs: [{
			type: "smoothedLine",
			valueField: "uout",
		}],
	},{
		title: "Выходной ток",
		stockLegend: [],
		showCategoryAxis: true,
		valueAxes: [{
			dashLength: 5,
		}],
		categoryAxis: {
			dashLength: 0
		},
		stockGraphs: [{
			type: "smoothedLine",
			valueField: "iout",
		}],
	}],
	chartScrollbarSettings: {
		autoGridCount: true,
		graph: "g1",
		graphType: "line",
		usePeriod: "DD",
		height: 60
	},
	chartCursorSettings: {
		bulletsEnabled: true,
		valueBalloonsEnabled: true,
		fullWidth: false,
		cursorAlpha: 0.5,
		valueLineBalloonEnabled: true,
		valueLineEnabled: true,
		valueLineAlpha: 0.5,
		categoryBalloonDateFormats: [
			{period:'fff',format:'JJ:NN'},
			{period:'ss',format:'JJ:NN'},
			{period:'mm',format:'JJ:NN'},
			{period:'hh',format:'JJ:NN'},
			{period:'DD',format:'MMM DD'},
			{period:'WW',format:'MMM DD'},
			{period:'MM',format:'MMM'},
			{period:'YYYY',format:'YYYY'}
		],
	},
	categoryAxesSettings:{
		parseDates: true,
		minPeriod: "fff",
		maxSeries: 250,
		dateFormats: [
			{period:'fff',format:'JJ:NN'},
			{period:'ss',format:'JJ:NN'},
			{period:'mm',format:'JJ:NN'},
			{period:'hh',format:'JJ:NN'},
			{period:'DD',format:'MMM DD'},
			{period:'WW',format:'MMM DD'},
			{period:'MM',format:'MMM'},
			{period:'YYYY',format:'YYYY'}
		],
		axisColor: "#DADADA",
		minorGridEnabled: true
	},
	periodSelector: {
		position: "bottom",
		fromText: "",
		toText: " - ",
		periodsText: "",
		inputFieldWidth: 170,
		dateFormat: "YYYY-MM-DD JJ:NN:SS",
		periods: [
			{period: "DD", count: 1, label: "1 день"},
			{period: "DD", count: 10, label: "10 дней"},
			{period: "MM", count: 1, label: "1 месяц"},
			{period: "YYYY", count: 1, label: "1 год"},
			{period: "MAX", label: "Всё", selected: true}
		]
	},
	panelsSettings: {
		marginLeft: 30,
		marginRight: 30,
	},
	export: {
		enabled: true
	},
});

chart.addListener("drawn", function e() {
	if(!initialized){
		initialized = true;
		var periodSelectorDiv = $(".amChartsButton.amcharts-period-input").parent();
		//кнопка туда
		$("#nav-btn-next").detach().appendTo(periodSelectorDiv).css('display', 'inline');
		//кнопка сюда
		$("#nav-btn-prev").detach().prependTo(periodSelectorDiv).css('display', 'inline');
		//кнопка настройки
		periodSelectorDiv = $(".amChartsInputField.amcharts-start-date-input").parent().parent();
		$('#chart-settings').detach().prependTo(periodSelectorDiv).css('display', 'inline');
	}
});

function navChart(direction) {
	// calculate new start date and end dates
	var to        = new Date();
	var from      = new Date();
	var firstDate = new Date(chart.periodSelector.firstDate);
	var lastDate  = new Date(chart.periodSelector.lastDate);
	var startDate = new Date(chart.scrollbarChart.startDate);
	var endDate   = new Date(chart.scrollbarChart.endDate);
	var diff = endDate.getTime() - startDate.getTime();
	if(direction == -1){//prev
		if((new Date(startDate.getTime() - diff)) < firstDate){
			from = firstDate;
			to   = new Date(firstDate.getTime() + diff);
		}else{
			from = new Date(startDate.getTime() - diff);
			to   = startDate;
		}
	}else if(direction == 1){//next
		if((new Date(endDate.getTime() + diff)) > lastDate){
			from = new Date(lastDate.getTime() - diff);
			to   = lastDate;
		}else{
			from = endDate;
			to   = new Date(endDate.getTime() + diff);
		}
	}else if(direction == 0){//redraw
		from = startDate;
		to   = endDate;
	}
	// zoom chart
	chart.zoomChart(from, to);
}

function zoom_x(){
	chart.categoryAxesSettings.maxSeries = $("#zoom_input").val();
	navChart(0);
}

function set_y_max(chart_number){
	var y_max          = $('#y_max'+chart_number);
	chart.panels[chart_number].valueAxes[0].maximum = y_max.val();
	navChart(0);
}

function set_y_min(chart_number){
	var y_min          = $('#y_min'+chart_number);
	chart.panels[chart_number].valueAxes[0].minimum = y_min.val();
	navChart(0);
}

function zoom_y(chart_number){
	/*chart.*/
	var y_max          = $('#y_max'+chart_number);
	var y_min          = $('#y_min'+chart_number);
	var y_max_checkbox = $('#y_max_checkbox'+chart_number);
	var y_min_checkbox = $('#y_min_checkbox'+chart_number);
	if(y_max_checkbox.prop('checked')){
		y_max.prop('readonly', false);
		chart.panels[chart_number].valueAxes[0].maximum = y_max.val();
	}else{
		y_max.prop('readonly', true);
		chart.panels[chart_number].valueAxes[0].maximum = undefined;
	}
	if(y_min_checkbox.prop('checked')){
		y_min.prop('readonly', false);
		chart.panels[chart_number].valueAxes[0].minimum = y_min.val();
	}else{
		y_min.prop('readonly', true);
		chart.panels[chart_number].valueAxes[0].minimum = undefined;
	}
	navChart(0);
}
