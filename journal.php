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
<link rel="stylesheet" href="modules/jqwidgets/jqwidgets/styles/jqx.base.css">
<link rel="stylesheet" href="modules/jqwidgets/jqwidgets/styles/jqx.bootstrap.css">
<link rel="stylesheet" href="css/loader.css?time=<?php echo filemtime('css/loader.css'); ?>"/>
<link rel="stylesheet" href="css/styles.css?time=<?php echo filemtime('css/styles.css'); ?>"/>
<link rel="stylesheet" href="css/journal.css?time=<?php echo filemtime('css/journal.css'); ?>"/>

<script type="text/javascript">
	var skz_id      = <?php echo (isset($_GET['skz_id']))                ? $_GET['skz_id']                       : 0;?>;
	var skz_address = <?php echo (isset($skz_settings['address']))       ? "'".$skz_settings['address']."'"      : "''";?>;
	var session_id  = <?php echo (isset($_REQUEST['sid']))               ? "'".$_REQUEST['sid']."'"              : "''";?>;
	var region_id   = <?php echo (isset($_REQUEST['region_id']))         ? $_REQUEST['region_id']                : 0;?>;
	var date1        = '<?php echo $date1;?>';
	var date2        = '<?php echo $date2;?>';
</script>

<script src="modules/jquery/js/jquery-3.2.0.min.js"></script>
<script src="modules/bootstrap/js/bootstrap.min.js"></script>
<script src="modules/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="modules/bootstrap-datepicker/locales/bootstrap-datepicker.ru.min.js"></script>
<script src="modules/jqwidgets/jqwidgets/jqx-all.js"></script>
<script src="js/journal.js?time=<?php echo filemtime('js/journal.js'); ?>"></script>


<title>Станции катодной защиты</title>
</head>
<body data-spy="scroll" data-target=".navbar" data-offset="50" onload="init();">
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
			<a class="navbar-brand" href="#" id="header">Станции катодной защиты</a>
		</div>
		<div class="collapse navbar-collapse" id="myNavbar">
			<ul class="nav navbar-nav navbar-right">
				<!--<li class="navbar-form" style="padding-top: 7px">
					<label class="navbar-link" for="auto-update" data-toggle="tooltip" data-placement="bottom" title="Включить автообновление">
						<input class="autosubmit" id="auto-update" checked="checked" value="true" type="checkbox">
						Автообновление
					</label>
				</li>-->

				<li class="navbar-form">
					<div class="form-group">
						<div class="input-daterange input-group" id="datepicker">
							<input type="text" class="form-control" name="date1" />
							<span class="input-group-addon">-</span>
							<input type="text" class="form-control" name="date2" />
						</div>
					</div>
				</li>

				<li class="navbar-form">
					<button class="btn btn-default" onclick="reload_page();">Открыть</button>
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
	<div class="container" style="text-align: center; margin-top: 25px; margin-bottom: 25px;">
		<!--параметры объектов-->
		<div id="history_table" ></div>
		<!--вспомогательная фигня-->
		<div id="loader" class="loader" style="display: none">Загрузка...</div>
		<div id="no_data" style="display: none">
			<div class="jumbotron">
				<h1>Нет данных</h1>
				<p>Попробуйте обновить страницу позже.</p>
			</div>
		</div>
	</div>
</div>
</body>
</html>
