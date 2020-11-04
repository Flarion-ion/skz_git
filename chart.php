<?php
session_start();
require 'auth.inc';

if(!isset($_SESSION['auth'])){
	$_SESSION['auth'] = false;
}
if(!$_SESSION['auth']){
	if(isset($_REQUEST['sid'])){
		$_SESSION['sid'] = $_REQUEST['sid'];
		if($_SESSION['sid'] == 'panorama'){
			$_SESSION['user'] = Array ('name' => 'МПК Панорама', 'regions' => Array ( 'obl' => 'viewer' ) );
		}else{
			$_SESSION['user'] = ldapAuth($_REQUEST['sid']);
		}
		if($_SESSION['user']){
			$_SESSION['auth'] = true;
		}else{
			$_SESSION['auth'] = false;
		}
	}
}
if(!$_SESSION['auth']){
	echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>Вы не прошли авторизацию.</body></html>';
	return;
}

session_write_close();

$sid        = $_SESSION['sid'];
$user_name  = $_SESSION['user']['name'];

$date1        = (isset($_REQUEST['date1']))    ? htmlspecialchars(urldecode($_REQUEST['date1']))    : date("Y-m-d", time()-7*24*60*60);
$date2        = (isset($_REQUEST['date2']))    ? htmlspecialchars(urldecode($_REQUEST['date2']))    : date("Y-m-d");
$skz_id       = (isset($_GET['skz_id']))       ? $_GET['skz_id']                                    : 0;

function url(){
	if(isset($_SERVER['HTTPS'])){
		$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
	}
	else{
		$protocol = 'http';
	}
	return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER["REQUEST_URI"].'?');
}
//запрос параметров скз
$base_url = url();
$url = $base_url."/backend.php?type=chart_settings&skz_id=".$skz_id;
$opts = array('http' => array('header'=> 'Cookie: ' . $_SERVER['HTTP_COOKIE']."\r\n"));
$context = stream_context_create($opts);
$contents = file_get_contents($url, true, $context);
$skz_settings = json_decode($contents, true);

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="modules/bootstrap_yeti/css/bootstrap.css"/>
<link rel="stylesheet" href="modules/bootstrap_yeti/css/bootstrap_oblgas.css"/>
<link rel="stylesheet" href="modules/bootstrap-datepicker/css/bootstrap-datepicker3-yeti.css"/>
<link rel="stylesheet" href="css/loader.css?time=<?php echo filemtime('css/loader.css'); ?>"/>
<link rel="stylesheet" href="css/chart.css?time=<?php  echo filemtime('css/chart.css'); ?>"/>
<link rel="stylesheet" href="css/styles.css?time=<?php echo filemtime('css/styles.css'); ?>"/>

<script type="text/javascript">
	var skz_id      = <?php echo (isset($_GET['skz_id']))                ? $_GET['skz_id']                       : 0;?>;
	var skz_address = <?php echo (isset($skz_settings['address']))       ? "'".$skz_settings['address']."'"      : "''";?>;
	var session_id  = <?php echo (isset($_REQUEST['sid']))               ? "'".$_REQUEST['sid']."'"              : "''";?>;
	var region_id   = <?php echo (isset($_REQUEST['region_id']))         ? $_REQUEST['region_id']                : 0;?>;
	var date1        = '<?php echo $date1;?>';
	var date2        = '<?php echo $date2;?>';
</script>

<script src="modules/jquery/js/jquery-1.9.1.min.js"></script>
<script src="modules/bootstrap/js/bootstrap.min.js"></script>
<script src="modules/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="modules/bootstrap-datepicker/locales/bootstrap-datepicker.ru.min.js"></script>
<script src="modules/amcharts/amcharts/amcharts.js"></script>
<script src="modules/amcharts/amcharts/serial.js"></script>
<script src="modules/amstockcharts/amcharts/amstock.js"></script>
<script src="modules/amcharts/amcharts/plugins/dataloader/dataloader.min.js" type="text/javascript"></script>
<script src="modules/amcharts/amcharts/themes/light.js"></script>
<script src="modules/amcharts/amcharts/lang/ru.js"></script>
<script src="js/chart.js?time=<?php  echo filemtime('js/chart.js'); ?>"></script>

<title>Станции катодной защиты</title>
</head>
<body>
<!--меню-->
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="hide-left-block">
	</div>
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<button type="button" class="navbar-brand btn btn-link" id="back-btn">
				<span class="glyphicon glyphicon-chevron-left"></span>
			</button>
			<a class="navbar-brand" id="header">Параметры СКЗ</a>
		</div>
		<div class="collapse navbar-collapse" id="myNavbar">
			<ul class="nav navbar-nav navbar-right">
				<li class="navbar-form">
					<div class="form-group">
						<div class="input-group" lang="en-US">
							<span class="input-group-addon"><i class="glyphicon glyphicon-zoom-in"></i></span>
							<input type="number" class="form-control" value="250" step="50" autocomplete="off" placeholder="zoom" max="10000" style="width: 5em;" oninput="zoom_x();" id="zoom_input">
						</div>
						<div class="input-daterange input-group" id="datepicker">
							<input type="text" class="form-control" name="date1" />
							<span class="input-group-addon">-</span>
							<input type="text" class="form-control" name="date2" />
						</div>
						<button class="btn btn-default" onclick="reload_page();">Открыть</button>
					</div>
				</li>
			</ul>
		</div>
	</div>
</nav>

<!--основная страница-->
<div class="container-fluid content-container">
	<!--пользователь-->
	<div class="container-fluid">
		<div style="float: right"><?php echo $user_name; ?></div>
	</div>
	<!--содержимое-->
	<div class="container-fluid" style="text-align: center; height: 100%">
		<div id="chartdiv"></div>
		<input id="nav-btn-prev" class="amChartsButtonSelected amcharts-period-input amButton" type="button" value="<< сюда" onclick="navChart(-1);" astyle="display: none">
		<input id="nav-btn-next" class="amChartsButtonSelected amcharts-period-input amButton" type="button" value="туда >>" onclick="navChart(1);" astyle="display: none">
		<div id="chart-settings" class="dropup amSettings" style="display: none">
			<button class="dropdown-toggle amButton" type="button" data-toggle="dropdown">
				<span class="glyphicon glyphicon-cog"></span>
				Настройки
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li class="dropdown-header">Защитное напряжение</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymax</span>
						<input readonly="readonly" type="number" class="form-control" value="5" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_max(0);" id="y_max0">
						<label class="checkbox-inline input-group-addon-right" disabled="disabled">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_max_checkbox0" onclick="zoom_y(0);">
						</label>
					</div>
				</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymin</span>
						<input readonly="readonly" type="number" class="form-control" value="0" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_min(0);" id="y_min0">
						<label class="checkbox-inline input-group-addon-right">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_min_checkbox0" onclick="zoom_y(0);">
						</label>
					</div>
				</li>

				<li class="dropdown-header">Выходное напряжение</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymax</span>
						<input readonly="readonly" type="number" class="form-control" value="5" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_max(1);" id="y_max1">
						<label class="checkbox-inline input-group-addon-right" disabled="disabled">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_max_checkbox1" onclick="zoom_y(1);">
						</label>
					</div>
				</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymin</span>
						<input readonly="readonly" type="number" class="form-control" value="0" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_min(1);" id="y_min1">
						<label class="checkbox-inline input-group-addon-right">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_min_checkbox1" onclick="zoom_y(1);">
						</label>
					</div>
				</li>

				<li class="dropdown-header">Выходной ток</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymax</span>
						<input readonly="readonly" type="number" class="form-control" value="5" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_max(2);" id="y_max2">
						<label class="checkbox-inline input-group-addon-right" disabled="disabled">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_max_checkbox2" onclick="zoom_y(2);">
						</label>
					</div>
				</li>
				<li>
					<div class="input-group" lang="en-US">
						<span class="input-group-addon" style="width: 5em;">Ymin</span>
						<input readonly="readonly" type="number" class="form-control" value="0" step="0.1" autocomplete="off" max="10000" style="width: 5em;" oninput="set_y_min(2);" id="y_min2">
						<label class="checkbox-inline input-group-addon-right">
							<input style="position: relative; margin: auto" type="checkbox" autocomplete="off" value="" id="y_min_checkbox2" onclick="zoom_y(2);">
						</label>
					</div>
				</li>
			</ul>
		</div>
	</div>
</div>
</body>
</html>
