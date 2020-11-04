<?php
date_default_timezone_set("Europe/Minsk");

	session_start();
	if(!$_SESSION['auth']) die("auth error");
	session_write_close();

	$powers = array(274.53,790.34,2475.09,1448.4,1853.54,729.01,1845.81,1958.67,2994.21,2119.55,1221.13,1015.14,1180.55,908.41,690.54,248.86,896.17,1632.16,1594.37,1544.01,2119.91,696.86,1254.7,735.2,1983.19,2669.95,2993.74,200.19,888.59,1502.24,856.23,1063.12,2192.58,331.31,2411.53,1046.12,960.33,1257.33,2904.79,954.53);

	$db = null;
	require "station_api.php";

	$arr = parse_ini_file("skz.ini");
	$db_host = $arr['db_host'];
	$db_user = $arr['db_user'];
	$db_pass = $arr['db_pass'];
	$db_name = $arr['db_name'];

	$db = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_query($db, "set names utf8");/**/

	$result = array();
	switch($_REQUEST['type']){
		case "stations":
			$stations = null;
			if(isset($_REQUEST['fid'])){
				$fid = (int)$_REQUEST['fid'];
				$query = "SELECT
						  regions.id_region, regions.name,
						  stations.id_station, stations.address, stations.phone, stations.imai, stations.passport_id,
						  state, pt, pt_w, pe, pe_state, exit_u, exit_u_state, exit_i, exit_i_state,
						  el_state, door_state,
						  max_date, imai, ping, power,
						  pe_setpoint, pe_deviation, exit_u_setpoint, exit_u_deviation, exit_i_setpoint, exit_i_deviation
						  FROM
						  (stations INNER JOIN regions ON stations.id_region=regions.id_region)
						  LEFT JOIN last_measures ON stations.id_station=last_measures.id_station
						  LEFT JOIN setpoint ON stations.id_station = setpoint.id_station AND
									setpoint.id = (SELECT id FROM setpoint WHERE setpoint.id_station = stations.id_station ORDER BY time DESC LIMIT 1)
						  WHERE stations.id_region=$fid";
				$res = mysqli_query($db, $query);
				$stations = array();
				$i = 0;
				$nowtime = time();

				while($row = mysqli_fetch_assoc($res)) {
					$color_state = "default";//to colorise rows on page, with old date or broken wires
					if (($nowtime - $row['max_date']) > 86400){
						$color_state="yellow";
					}
					$stations[] = array(
						'sid' => $row['id_station'],
						'fid' => $row['id_region'],
						'phone' => $row['phone'],
						'filial' => $row['name'],
						'address' => $row['address'],
						'passport_id' => $row['passport_id'],
						'last_date' => getSDate($row['max_date']),
						'protection' => getProtection($row['pt'],$row['pt_w']),
						'u' => getU($row['pe']),
						'ue' => getUE($row['exit_u']),
						'ui' => getUI($row['exit_i']),
						'u_state' => getU_state($row['pe_state']),
						'ue_state' => getUE_state($row['exit_u_state']),
						'ui_state' => getUI_state($row['exit_i_state']),
						'state' => getStates($row),
						'imai' => $row['imai'],
						'sparam' => $row['pt']>0?($row['pt']&3)-1:0,
						'sparam_value' => $row['pt_w'],
						'stest' => $row['pt']&4,
						'power' => $row['power'],//$powers[$i++]
						'color_state' => $color_state,
						'test' => $row['max_date'],
						'pe_setpoint'      => ($row['pe_setpoint']      != null) ? $row['pe_setpoint']      : 0,
						'pe_deviation'     => ($row['pe_deviation']     != null) ? $row['pe_deviation']     : 0,
						'exit_u_setpoint'  => ($row['exit_u_setpoint']  != null) ? $row['exit_u_setpoint']  : 0,
						'exit_u_deviation' => ($row['exit_u_deviation'] != null) ? $row['exit_u_deviation'] : 0,
						'exit_i_setpoint'  => ($row['exit_i_setpoint']  != null) ? $row['exit_i_setpoint']  : 0,
						'exit_i_deviation' => ($row['exit_i_deviation'] != null) ? $row['exit_i_deviation'] : 0
					);
				}
			}
			$result = $stations;
			break;
		case "filials":
			$where = array();
			foreach($_SESSION['user']['regions'] as $region => $rule){
				if($region=='obl') {
					$where = array();
					break;
				}else{
					$where[] = $region;
				}
			}
			if(sizeof($where)){
				$str = "WHERE ldap_name in ('".implode("','", $where)."')";
			}else{
				$str = "";
			}
			$query = "select * from regions $str";
			$res = mysqli_query($db, $query);
			while($row = mysqli_fetch_assoc($res)) {
				$result[] = array("fid"=>$row['id_region'], 'name'=>$row['Name']);
			}
			break;
		case "rights":
			$where = array();
			$result['rights']='viewer';
			foreach($_SESSION['user']['regions'] as $region => $rule){
 				if($rule=='user' || $rule=='admin'){
					$result['rights']='user';
 					break;
 				}
			}
			break;
		case "station_save":
			$result['debug'] = array();
			$result['debug']['spodr'] = $_REQUEST['spodr'];
			if($_REQUEST['passport_id'] != 0){
				$query = " SELECT stations.address, stations.id_station,regions.Name
					FROM (stations INNER JOIN regions ON stations.id_region = regions.id_region)
					WHERE passport_id = '{$_REQUEST['passport_id']}' AND stations.id_region = {$_REQUEST['spodr']}";
				$result['debug']['sql'][] = $query;
				$res = mysqli_query($db, $query);
				$rr = mysqli_fetch_row($res);
				if($rr[1] != null && $_REQUEST['station_id'] != $rr[1]){
					$result['code']   = 3;
					$result['addr']   = $rr[0];
					$result['region'] = $rr[2];
					break;
				}
			}
			if($_REQUEST['imai'] != 0){
				$query = " SELECT stations.address, stations.id_station,regions.Name
					FROM (stations INNER JOIN regions ON stations.id_region = regions.id_region)
					WHERE imai = '{$_REQUEST['imai']}'";
				$result['debug']['sql'][] = $query;
				$res = mysqli_query($db, $query);
				$rr = mysqli_fetch_row($res);
				if($rr[1] != null && $_REQUEST['station_id'] != $rr[1]){
					$result['code']   = 4;
					$result['addr']   = $rr[0];
					$result['region'] = $rr[2];
					break;
				}
			}
			if(isset($_REQUEST['station_id']) && $_REQUEST['station_id']){
				$query = "UPDATE stations set id_region={$_REQUEST['spodr']}, address='{$_REQUEST['address']}', phone='{$_REQUEST['phone']}', imai='{$_REQUEST['imai']}', passport_id = '{$_REQUEST['passport_id']}' WHERE id_station={$_REQUEST['station_id']}";
				$result['debug']['sql'][] = $query;
				if(mysqli_query($db, $query)){
					$result['code'] = 0;
				}else{
					$result['code'] = 5;
				}
			}else if(!$_REQUEST['station_id']){
				$query = "INSERT INTO stations (id_region, address, phone, last_rec, max_date, imai, ping, passport_id, protocol, ip)
                          VALUES ({$_REQUEST['spodr']},'{$_REQUEST['address']}','{$_REQUEST['phone']}',0,0,'{$_REQUEST['imai']}',0,'{$_REQUEST['passport_id']}', 1, NULL)";
				$result['debug']['sql'][] = $query;
				if(mysqli_query($db, $query)){
					$result['code'] = 0;
				}else{
					$result['code'] = 5;
				}
			}
			$result['error'] = mysqli_error($db);
			break;
		case "station_delete":
			if(isset($_REQUEST['station_id']) && $_REQUEST['station_id']){
				$sid = $_REQUEST['station_id'];
				$query = "DELETE FROM stations where id_station = $sid";
				$rslt = mysqli_query($db, $query);
				$query = "DELETE FROM last_measures where id_station = $sid";
				$rslt = mysqli_query($db, $query);
				$query = "DELETE FROM imai where station_id = $sid";
				$rslt = mysqli_query($db, $query);
				$result['code'] = 0;
			}
			break;
		case "protection_task":
			$debug = array();
			$sid = $_REQUEST['station_id'];
			$station = getStation($sid);

			$command = sendCommand($station,'changeTask',$_REQUEST['parami'],$_REQUEST['param_value'],($_REQUEST['stest1']=='true' ? 0 : 1));
			//$debug[] = $command;

			$result['debug'] = $debug;
			$result['code']  = $command['code'];
			break;
		case "update_task":
			$debug = array();
			$sid = $_REQUEST['station_id'];
			$station = getStation($sid);

			$command = sendCommand($station,'getCurState');
			//$debug[] = $command;

			$result['debug'] = $debug;
			$result['code']  = $command['code'];
			break;
		case "set_setpoint":
			if(isset($_REQUEST['setpoint_station_id']) && $_REQUEST['setpoint_station_id']){
				$station_id       = $_REQUEST['setpoint_station_id'];
				$user             = $_REQUEST['user'];
				$pe_setpoint      = $_REQUEST['pe_setpoint'];
				$pe_deviation     = $_REQUEST['pe_deviation'];
				$exit_u_setpoint  = $_REQUEST['exit_u_setpoint'];
				$exit_u_deviation = $_REQUEST['exit_u_deviation'];
				$exit_i_setpoint  = $_REQUEST['exit_i_setpoint'];
				$exit_i_deviation = $_REQUEST['exit_i_deviation'];

				$result['code'] = setSetpoint($station_id, $user, $pe_setpoint, $pe_deviation, $exit_u_setpoint, $exit_u_deviation, $exit_i_setpoint, $exit_i_deviation);
			}else{
				$result['code'] = -3;
			}
			break;
		case "history":
			$sid   = (int)$_REQUEST['sid'];
			$date1 = (isset($_REQUEST['date1']))    ? htmlspecialchars(urldecode($_REQUEST['date1']))    : date("Y-m-d", time()-7*24*60*60);
			$date2 = (isset($_REQUEST['date2']))    ? htmlspecialchars(urldecode($_REQUEST['date2']))    : date("Y-m-d");
			$query = "SELECT * FROM measures WHERE id_station = $sid AND timeevent >= ".strtotime($date1."00:00:00")." AND timeevent <= ".strtotime($date2."23:59:59")." ORDER BY timeevent DESC;";
  			$res = mysqli_query($db, $query);
  			$hist_values = array();
  			$power = 0;
			while($row = mysqli_fetch_assoc($res)){
				if($row['power'] != null){
					$power = $row['power'];
				}
				if($row['state'] != null){
					$hist_values[] = array(
						'timeevent'    => date('Y-m-d H:i:s',$row['timeevent']),
						'protection'   => getProtection($row['pt'],$row['pt_w']),
						'u'            => getU($row['pe']),
						'ue'           => getUE($row['exit_u']),
						'ui'           => getUI($row['exit_i']),
						'u_state'      => getU_state($row['pe_state']),
						'ue_state'     => getUE_state($row['exit_u_state']),
						'ui_state'     => getUI_state($row['exit_i_state']),
						'state'        => getJournalStates($row),
						'sparam'       => $row['pt']>0?($row['pt']&3)-1:0,
						'sparam_value' => $row['pt_w'],
						'stest'        => $row['pt']&4,
						'power'        => $power,
					);
				}
			}
			$result = $hist_values;
			break;
		case "chart_settings":
			$skz_id = (int)$_REQUEST['skz_id'];
			$query = "SELECT
                        regions.name,
                        stations.address
                      FROM
                        stations INNER JOIN regions ON stations.id_region=regions.id_region
                      WHERE stations.id_station = ".$skz_id.";";
			$res = mysqli_query($db, $query);
			$settings = mysqli_fetch_assoc($res);
			$result = $settings;
			break;
		case "chart_data":
			$sid   = (int)$_REQUEST['sid'];
			$date1 = (isset($_REQUEST['date1']))    ? htmlspecialchars(urldecode($_REQUEST['date1']))    : date("Y-m-d", time()-7*24*60*60);
			$date2 = (isset($_REQUEST['date2']))    ? htmlspecialchars(urldecode($_REQUEST['date2']))    : date("Y-m-d");

			$query = "SELECT * FROM measures WHERE id_station = $sid AND timeevent >= ".strtotime($date1."00:00:00")." AND timeevent <= ".strtotime($date2."23:59:59")." ORDER BY timeevent ASC;";

			$res = mysqli_query($db, $query);
			$hist_values = array();
			$prev_date = 0;
			while($row = mysqli_fetch_assoc($res)){
				if($row['power'] != null){
					$power = $row['power'];
				}
				if($row['state']!=null){
					if($prev_date != $row['timeevent']){
						$hist_values[] = array(
							'date' => date("Y-m-d H:i:s.u", $row['timeevent']),
							'up'   => (float)getU($row['pe']),
							'uout' => (float)getUE($row['exit_u']),
							'iout' => (float)getUI($row['exit_i'])
						);
					}
				}
				$prev_date = $row['timeevent'];
			}
			$result = $hist_values;
			break;
	}

	echo json_encode($result);
	mysqli_close($db);
?>
