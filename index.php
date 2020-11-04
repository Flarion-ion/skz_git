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
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<link rel="stylesheet" href="modules/bootstrap_yeti/css/bootstrap.css"/>
<link rel="stylesheet" href="modules/bootstrap_yeti/css/bootstrap_oblgas.css"/>
<link rel="stylesheet" href="modules/jqwidgets/jqwidgets/styles/jqx.base.css">
<link rel="stylesheet" href="modules/jqwidgets/jqwidgets/styles/jqx.bootstrap.css">
<link rel="stylesheet" href="css/loader.css?time=<?php echo filemtime('css/loader.css'); ?>"/>
<link rel="stylesheet" href="css/styles.css?time=<?php echo filemtime('css/styles.css'); ?>"/>

<script type="text/javascript">
	var session_id = <?php echo (isset($_REQUEST['sid']))       ? "'".$_REQUEST['sid']."'"          : "''";?>;
	var region_id  = <?php echo (isset($_REQUEST['region_id'])) ? $_REQUEST['region_id']            : 0;?>;
	var user       = <?php echo (isset($_SESSION['user']))      ? "'".$_SESSION['user']['name']."'" : "'шпион!'";?>;
	var delay      = <?php echo (isset($_REQUEST['delay']))     ? $_REQUEST['delay']*1000           : 10000;?>;
</script>

<script src="modules/jquery/js/jquery-3.2.0.min.js"></script>
<script src="modules/jquery/js/jquery.form.js"></script>
<script src="modules/bootstrap/js/bootstrap.min.js"></script>
<script src="modules/jqwidgets/jqwidgets/jqx-all.js"></script>
<script src="js/main.js?time=<?php echo filemtime('js/main.js'); ?>"></script>

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
			<form class="navbar-form navbar-left">
				<div class="form-group">
					<select class="form-control" id="region-list">
					</select>
				</div>
			</form>
			<ul class="nav navbar-nav navbar-right">
				<li class="navbar-form" style="padding-top: 7px">
					<label class="navbar-link" for="auto-update" data-toggle="tooltip" data-placement="bottom" title="Включить автообновление">
						<input class="autosubmit" id="auto-update" checked="checked" value="true" type="checkbox">
						Автообновление
					</label>
				</li>
				<li id="add-btn" style="display: none"><a href="#" title="Добавить объект"><span class="glyphicon glyphicon-plus"></span>Добавить</a></li>
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
		<div id="content">
			<!--<h1>Параметры объектов</h1>-->
			<div id="jqxgrid"></div>
			<div id="jqxwindow">
				<div>Параметры и журнал станции</div>
				<div><!-- window content -->
					<div class="horizontal">
						<div id="station_panel" class="panel"><!-- station attrs -->
							<div class="block-header">О станции</div>
							<div class="block-content">
								<form id="station_form">
									<div class="field">
										<div class="field-label">Подразделение:</div>
										<div id="spodr" name="spodr"></div>
									</div>
									<div class="field">
										<div class="field-label">Адрес:</div>
										<div class="field-value">
											<input id="saddr" name="address">
										</div>
									</div>
									<div class="field">
										<div class="field-label">Телефон:</div>
										<div class="field-value">
											<input id="sphone" name="phone">
										</div>
									</div>
									<div class="field">
										<div class="field-label">IMEI:</div>
										<div class="field-value">
											<input id="simai" name="imai">
										</div>
									</div>
									<div class="field">
										<div class="field-label">Номер паспорта:</div>
										<div class="field-value">
											<input id="spassport_id" name="passport_id">
										</div>
									</div>
									<div class="field">
										<div class="field-button">
											<input id="sbutton_save" type="button" value="Сохранить">
										</div>
									</div>
									<input id="station_id" type="hidden" name="station_id">
									<input type="hidden" name="type" value="station_save">
								</form>
							</div> <!-- .blockcontent -->
						</div><!-- station attrs -->
						<div id="protection_panel" class="panel"><!-- protection params -->
							<div class="block-header">Параметры станции</div>
							<div class="block-content">
								<form id="protection_form">
									<div class="field">
										<div class="field-label">Параметр защиты:</div>
										<div id="sparam" class="field-value" name="param"></div>
									</div>
									<div class="field">
										<div class="field-label">Значение параметра:</div>
										<div id="sparam_value" name="param_value"></div>
									</div>
									<div class="field">
										<div class="field-label">Тест обрывов:</div>
										<div class="field-value">
											<div id="stest1" name="stest1">отключен</div>
											<div id="stest2" name="stest2">включен</div>
										</div>
									</div>
									<div class="field">
										<div class="field-button">
											<input id="sbutton_task" type="button" value="Установить">
										</div>
									</div>
									<div class="field">
										<div class="field-button">
											<input id="deleteButton" type="button" value="Удалить">
										</div>
									</div>
									<input type="hidden" name="type" value="protection_task">
									<input id="protection_id" type="hidden" name="station_id">
								</form>
							</div> <!-- .blockcontent -->
						</div><!-- setpoint params -->
						<div id="setpoint_panel" class="panel"><!-- setpoint params -->
							<div class="block-header">Уставки</div>
							<div class="block-content">
								<form id="setpoint_form">
									<div class="field">
										<div class="field-label">U защ.:</div>
										<div class="horizontal">
											<div id="pe_setpoint" name="pe_setpoint" class="setpoint"></div>
											<span>  &plusmn; </span>
											<div id="pe_deviation" name="pe_deviation" class="deviation"></div>
											<span>%</span>
										</div>
									</div>
									<div class="field">
										<div class="field-label">U вых.:</div>
										<div class="horizontal">
											<div id="exit_u_setpoint" name="exit_u_setpoint" class="setpoint"></div>
											<span>  &plusmn; </span>
											<div id="exit_u_deviation" name="exit_u_deviation" class="deviation"></div>
											<span>%</span>
										</div>
									</div>
									<div class="field">
										<div class="field-label">I вых.:</div>
										<div class="horizontal">
											<div id="exit_i_setpoint" name="exit_i_setpoint" class="setpoint"></div>
											<span>  &plusmn; </span>
											<div id="exit_i_deviation" name="exit_i_deviation" class="deviation"></div>
											<span>%</span>
										</div>
									</div>
									<div class="field">
										<div class="field-button">
											<input id="setpoint_button" type="button" value="Применить">
										</div>
									</div>
									<input id="setpoint_station_id" type="hidden" name="setpoint_station_id">
								</form>
							</div> <!-- .blockcontent -->
						</div><!-- protection params -->
					</div>
					<div id="window-toolbar"><!-- toolbar -->
						<form id="commandsForm">
							<input type="button" id="updateButton" value="Опросить">
							<input type="button" id="journalButton" value="Журнал">
							<input type="button" id="journalInTabButton" value="Журнал во вкладке">
							<input type="button" id="chartButton" value="Графики">
							<input type="button" id="cancelButton" value="Отмена">
							<input id="commands_id" type="hidden" name="station_id">
						</form>
					</div><!-- toolbar -->
				</div><!-- window content -->
			</div> <!--jqxwindow -->
			<div id="jqxwindow_hist">
				<div class="block-header"> Журнал </div>
				<div id="history_table"> </div>
			</div> <!-- history window -->
		</div>
		<!--вспомогательная фигня-->
		<div id="loader" class="loader">Загрузка...</div>
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
