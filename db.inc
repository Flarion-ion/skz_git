<?php
function connectToDB($host, $user, $password, $database) {
  $db = mysql_connect($host, $user, $password) or die("Could not connect: ".mysql_error());
  mysql_select_db($database, $db) or die ('Can\'t use database : ' . mysql_error());
  mysql_query("set names utf8");
  return $db;
}

function electrodState($state) {
  $states = array(0=>'<div class="ui-icon ui-icon-plus">',  			//ok
		  1=>'<div class="ui-icon-error ui-icon-minusthick">',		//error
		  2=>'<div class="ui-icon ui-icon-closethick">');		//no data
  if($state<0) $r = $states[2];
  else if($state<3) $r = $states[0];
  else $r = $states[1];
  return $r;
}
function doorState($state) {
  $states = array(0=>'<div class="ui-icon ui-icon-locked">',
		  1=>'<div class="ui-icon-error ui-icon-unlocked">',
		  2=>'<div class="ui-icon ui-icon-closethick">');
  if($state<0) $r = $states[2];
  else $r = $states[$state];
  return $r;
}
function noData() {
  return '<div class="ui-icon ui-icon-closethick">';
}

function getAll($db, $regions) {
  $page = $_REQUEST['page'];
  $limit = $_REQUEST['rows'];
//  echo "select count(*) from stations where $regions";
  $result = mysql_query("select count(*) from stations where $regions",$db);
  $row = mysql_fetch_array($result);
  $count = $row[0];
  if($count > 0 && $limit > 0) 
    $total_pages = ceil($count/$limit);
  else $total_pages = 0;
  if ($page > $total_pages) $page=$total_pages;
  $start = $limit*$page - $limit;
  if($start < 0) $start = 0; 
  $query = "SELECT stations.id_station,regions.name, stations.address, stations.phone, state, pt, pt_w, pe, exit_u, exit_i, el_state, door_state, max_date
    FROM stations,regions,measures
    WHERE ".$regions." and stations.id_region = regions.id_region AND (stations.max_date = measures.timeevent OR stations.max_date=0)
    group by stations.id_station";
//  echo $query;
  $result = mysql_query($query);	
  $responce->page=$page; 
  $responce->total=$total_pages; 
  $responce->records=$count; 
  $i = 0;
//PVA[] = {"Выключена","Uзащ = ","Uвых = ","Iвых = "}; pt_w
  while($row = mysql_fetch_array($result)) {
    $responce->rows[$i]['id']=$row[0];
    if($row[12])
      $responce->rows[$i]['cell']=array($row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],date('G:i:s d-m-Y', $row[12]));
     else $responce->rows[$i]['cell']=array($row[1],$row[2],$row[3],-1,-1,-1,-1,-1,-1,-1,-1,'Нет данных');
    $i++;
  }
  return $responce;
}

function changeTask($phone,$par,$val,$reg) {
  $lines = array();
  exec("/usr/local/bin/skz/changeTask.py $phone $par $val $reg",$lines,$excode);
  return $excode;
}

function getAllowRegions($db,$user) {
  $list = array();
  $tmp = array();
  $f = false;

  $q = "select * from regions";
  $result = mysql_query($q) or die("Invalid query: " . mysql_error());
  if(isset($user['regions']['obl'])) $f = true;
  while(list($id,$name,$ldap_name) = mysql_fetch_array($result)) {
    $tmp[$ldap_name] = array('id'=>$id,'name'=>$name);
    if($f) $tmp[$ldap_name]['rule'] = $user['regions']['obl'];
  }
  foreach($user['regions'] as $region => $rule) 
    if(isset($tmp[$region])) $tmp[$region]['rule'] = $rule;
  foreach($tmp as $val) 
    if(isset($val['rule'])) $list[] = $val;
//    $q = "select id_region,name from regions where ldap_name in ('".implode("','",array_keys($user['regions']))."')";
  return $list;
}

function getStation($db, $id) {
	$query = "select * from stations where id_station=$id";
	$result = mysql_query($query,$db);
	if(!$result) {
		echo "sql error\n";
		return;
	}
	if(mysql_num_rows($result)!=1) return;
	return mysql_fetch_assoc($result);
}

function insertStation($db, $id_region, $address, $phone) {
  $query = "insert into stations set id_region=".$id_region.",address='".$address."',phone='".$phone."'";
  mysql_query($query,$db);
}
function editStation($db, $id, $id_region, $address, $phone) {
  $query = "update stations set id_region=".$id_region.",address='".$address."',phone='".$phone."' where id_station=$id";
  mysql_query($query,$db);
}
function deleteStation($db, $id) {
  $query = "delete from stations where id_station in ($id)";
  mysql_query($query,$db);
}
function updateStations($db, $id) {
  $lines = array();
  exec("/usr/local/bin/skz/getState.py $id",$lines,$excode);
  $query = "SELECT stations.id_station,regions.name, stations.address, stations.phone, pt, pt_w, pe, exit_u, exit_i, max_date
	FROM stations,regions,measures
	WHERE stations.id_station in (".$id.") and stations.id_region = regions.id_region AND (stations.max_date = measures.timeevent OR stations.max_date=0)
	group by stations.id_station";
  $result = mysql_query($query,$db);	
  $res->excode = $excode;
  $i = 0;
  while($row = mysql_fetch_array($result)) {
    $res->rows[$i]['id']=$row[0];
    if($row[9])
      $res->rows[$i]['cell']=array('protect'=>$row[4],'pt_w'=>$row[5],'PT'=>$row[6],'EU'=>$row[7],'EI'=>$row[8],'last_date'=>date('G:i:s d-m-Y', $row[9]));
    else $res->rows[$i]['cell']=array($row[1],$row[2],$row[3],0,0,0,'Нет данных');
    $i++;
  }
  return $res;
}

function getStationsJournals($db, $id) {
  $lines = array();
  exec("/usr/local/bin/skz/getJournals.py $id",$lines,$excode);
  $res->excode = $excode;
  return $res;
}

function getJournal($db, $id) {
  $page = $_REQUEST['page'];
  $limit = $_REQUEST['rows'];
  $query = "SELECT timeevent,state, pt, pt_w, pe, exit_u, exit_i, el_state, door_state
    FROM measures
    WHERE id_station = $id AND state IS NOT NULL
    ORDER BY timeevent DESC";
  $result = mysql_query($query);	
  $count = mysql_num_rows($result);
  if($count > 0 && $limit > 0) 
    $total_pages = ceil($count/$limit);
  else $total_pages = 0;
  if ($page > $total_pages) $page=$total_pages;
  $start = $limit*$page - $limit;
  if($start < 0) $start = 0; 
  $responce->page=$page; 
  $responce->total=$total_pages; 
  $responce->records=$count; 
  $i = 0;
  while($row = mysql_fetch_array($result)) {
    if($i>=$start) {
      for($j=0;$j<=8;$j++) if(is_null($row[$j])) $row[$j] = -1;
      $responce->rows[$i]['id']=$i;
      $responce->rows[$i]['cell']=array(date('G:i:s d-m-Y',$row[0]),$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8]);
    }
    $i++;
    if($i>$start+$limit) break;
  }
  return $responce;
}

function formatRegionsList($str) {
	print $str;
	$res = explode(',',$str);
	foreach($res as $key => $val) $res[$key] = (integer) $val; 
	$s = implode(',',$res);
	return $s;
}
?>
