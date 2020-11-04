<?php
//добавить уставки для станции
function setSetpoint($station_id, $user, $pe_setpoint, $pe_deviation, $exit_u_setpoint, $exit_u_deviation, $exit_i_setpoint, $exit_i_deviation){
	global $db;
	$station_id       = ($station_id       != null) ? $station_id       : 0;
	$user             = ($user             != null) ? $user             : '';
	$pe_setpoint      = ($pe_setpoint      != null) ? $pe_setpoint      : 0;
	$pe_deviation     = ($pe_deviation     != null) ? $pe_deviation     : 0;
	$exit_u_setpoint  = ($exit_u_setpoint  != null) ? $exit_u_setpoint  : 0;
	$exit_u_deviation = ($exit_u_deviation != null) ? $exit_u_deviation : 0;
	$exit_i_setpoint  = ($exit_i_setpoint  != null) ? $exit_i_setpoint  : 0;
	$exit_i_deviation = ($exit_i_deviation != null) ? $exit_i_deviation : 0;
	if($station_id != null && $station_id != 0){
		$query = "INSERT INTO
                   setpoint
                  (id_station, user,
                   pe_setpoint, pe_deviation,
                   exit_u_setpoint, exit_u_deviation,
                   exit_i_setpoint, exit_i_deviation)
                  VALUES
                   (".mysqli_real_escape_string($db, $station_id).",     '".mysqli_real_escape_string($db, $user)."',
                    ".mysqli_real_escape_string($db, $pe_setpoint).",     ".mysqli_real_escape_string($db, $pe_deviation).",
                    ".mysqli_real_escape_string($db, $exit_u_setpoint).", ".mysqli_real_escape_string($db, $exit_u_deviation).",
                    ".mysqli_real_escape_string($db, $exit_i_setpoint).", ".mysqli_real_escape_string($db, $exit_i_deviation).");";
		if(mysqli_query($db, $query) == TRUE){
			return 0;
		}else{
			return -1;
		}
	}
	return -2;
}

function sendCommand($station, $command, $p1=NULL, $p2=NULL, $p3=NULL) {
	global $db;
	$debug = array();
	$sid = $station['id_station'];

	$result = null;
	$code   = false;

	if($station){
		if(isStationOnline($station)){
			if(!hasActivCommand($station)){
				$p2 = str_replace(' ', '', $p2);
				if($p2){
					$p2 = round($p2*100);
				}
				if($p1!=NULL){
					$p1 += 1;
				}
				$query = "INSERT INTO commands SET station_id=$sid, command='$command', param1='$p1', param2='$p2', param3='$p3', state=0";
				$debug['sql'] = $query;
				mysqli_query($db, $query);
				$code = 0;
			}else{
				$code = 1;
			}
		}else{
			$code = 2;
		}
	}

	$result['debug'] = $debug;
	$result['code']  = $code;
	return $result;
}

function getStation($sid) {
	global $db;
	$res = mysqli_query($db, "SELECT * from stations WHERE id_station=$sid");
	return $row=mysqli_fetch_array($res);
}

function isGPRS($station) {
	return $station['imai']!='';
}

function isStationOnline($station) {
	$ping = strtotime($station['ping']);
	return (!isGPRS($station) || ($ping>0 && (time()-$ping)<300));
}

function hasActivCommand($station) {
	global $db;
	$res = mysqli_query($db, "SELECT * FROM commands WHERE station_id={$station['id_station']} AND state=0");
	return mysqli_num_rows($res)>0;
}

function getProtection($pt, $pt_w) {
	$PVA = array("Неизвестно","Uзащ = ","Uвых = ","Iвых = ");
	$state = '';
	$pt = (int)$pt;
	if($pt&3)
		$state = $PVA[$pt&3].$pt_w;
	else
		$state = $PVA[$pt&3];
	if($pt&4) $state .= "-К";
	if($pt&8) $state .= "-Д";
	return $state;
}

function getSDate($utc) {
	if($utc>0) return date("Y-m-d H:i:s",$utc);
	else return '';
}

function getU($u) {
	return $u;
}
function getU_state($u_state) {
	return $u_state;
}
function getUE($ue) {
	return $ue;
}
function getUE_state($ue_state) {
	return $ue_state;
}
function getUI($ui) {
	return $ui;
}
function getUI_state($ui_state) {
	return $ui_state;
}

function getStates($row){//$row['id_station'],$row['state'],$row['el_state'],$row['door_state']$sid, $s_state, $el_state, $door_state) {
	$states = array("Станция в норме", "Обрыв силовой трубы", "Обрыв силового анода", "Обрыв измерительного електрода", "Обрыв измерительной трубы", "КОроткое замыкание");

	$state = '<div class="container-fluid text-center">';
	if(isGPRS($row)){
		if(isStationOnline($row)){
			$state .= '<span style="color: green" class="glyphicon glyphicon-signal state_icon" title="Подключена по GPRS"><div style="display: none">'.$row['ping'].'</div> </span>';
		}else{
			$state .= '<span style="color: red" class="glyphicon glyphicon-signal state_icon" title="GPRS подключение отсутствует"><div style="display: none">'.$row['ping'].'</div> </span>';
		}
	}else{
		$state .= '<span class="glyphicon glyphicon-earphone state_icon" title="Работает в режиме дозвонки"> </span>';
	}
	if($row['el_state']!=NULL) {
		if($row['el_state']<3){
			$state .= '<span style="color: green" class="glyphicon glyphicon-flash state_icon" title="Электрод в норме"> </span>';
		}else{
			$state .= '<span style="color: red" class="glyphicon glyphicon-flash state_icon" title="Электрод не в порядке"> </span>';
		}
	}
	if($row['door_state']!=NULL) {
		if($row['door_state']){
			$state .= '<span style="color: red" class="glyphicon glyphicon-lock state_icon" title="Дверь открыта"> </span>';
		}else{
			$state .= '<span style="color: green" class="glyphicon glyphicon-lock state_icon" title="Дверь закрыта"> </span>';
		}
	}
	//if($row['s_state']>6) $row['s_state'] = 1;
	//if($row['s_state'])
	//	$state .= "<span title=\"{$states[$row['s_state']]}\"><i class=\"icon-alert alarm\"></i></span>";
	//else
	//	$state .= "<span title=\"{$states[$row['s_state']]}\"><i class=\"icon-ok ok\"></i></span>";

	if(hasActivCommand($row)){
		$state .= '<span class="glyphicon glyphicon-refresh state_icon" title="Выполение команды"> </span>';
	}

	if(stationIsFreezes($row)){
		$state .= '<span class="glyphicon glyphicon-remove state_icon" title="Возможно станция зависла"> </span>';
	}

	$state .= '</div>';
	return $state;
}
function getJournalStates($row){
	$states = array("Станция в норме", "Обрыв силовой трубы", "Обрыв силового анода", "Обрыв измерительного електрода", "Обрыв измерительной трубы", "КОроткое замыкание");

	$state = '<div class="container-fluid text-center">';

	if($row['el_state']!=NULL) {
		if($row['el_state']<3){
			$state .= '<span style="color: green" class="glyphicon glyphicon-flash state_icon" title="Электрод в норме"> </span>';
		}else{
			$state .= '<span style="color: red" class="glyphicon glyphicon-flash state_icon" title="Электрод не в порядке"> </span>';
		}
	}
	if($row['door_state']!=NULL) {
		if($row['door_state']){
			$state .= '<span style="color: red" class="glyphicon glyphicon-lock state_icon" title="Дверь открыта"> </span>';
		}else{
			$state .= '<span style="color: green" class="glyphicon glyphicon-lock state_icon" title="Дверь закрыта"> </span>';
		}
	}

	$state .= '</div>';
	return $state;
}

function stationIsFreezes($station) {
	return false;
	global $db;
	$date = date("Y-m-d H:i:s", time()-30*60);
	$q = "SELECT * FROM measures
          WHERE id_station = {$station['id_station']} AND
          timeevent >= ".strtotime($date)." ORDER BY timeevent DESC";
	$q = "SELECT * FROM measures
          WHERE id_station = {$station['id_station']} ORDER BY timeevent DESC LIMIT 25";
	$res = mysqli_query($db, $q);$hist_values = array();
	$prev = null;
	while($row = mysqli_fetch_assoc($res)){
		$curr = array(
			$row['pe'],
			$row['exit_u'],
			$row['exit_i']
		);
		if($prev != null && $prev != $curr){
			return false;
		}
		$prev = $curr;
	}
	return true;
}
?>
