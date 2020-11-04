<?php

function drupalData($sid) {
  $link = mysql_connect("mysql2.oblgaz", "drupal", "cms") or die("Could not connect: ".mysql_error());
  mysql_select_db('drupal', $link) or die ('Can\'t use database : ' . mysql_error());
  $result = mysql_query("SELECT users.data, users.name FROM users LEFT JOIN sessions ON sessions.uid=users.uid WHERE sid='$sid'") or die("Invalid query: " . mysql_error());
  if(mysql_num_rows($result)<>1) return;
  $r = mysql_fetch_assoc($result);
  mysql_close($link);
  return $r;
}

function ldapAuth($sid) {
  $userData = drupalData($sid);
  if($userData) {
//    $userData = unserialize($userData['data']);
    $ds=ldap_connect("192.168.100.1");
    if(!$ds) die("Unable to connect to LDAP-server");
    ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
    $r=ldap_bind($ds,"uid=perladmin,ou=Vitebskoblgas,o=gas","perladmin");
    if(!$r) die("Unable to logon to LDAP-server");
    $user = $userData['name'];
    $sr=ldap_search($ds, "o=gas", "(uid=$user)", array('dn', 'confValue', 'cn'));
    if(!$sr) die("User no found.");
    $info = ldap_get_entries($ds, $sr);
    if(!$info) die("Found more then one user. ");
    foreach($info[0]['confvalue'] as $key => $val) {
      $words = explode(' ',$val);
      if(count($words)<2) continue;
      if($words[0]=='skz') {
	$t = explode('|',$words[1]);
	if(count($t)<2) continue;
	if(!isset($res)) $res = array('name'=>$info[0]['cn'][0]);
	if(!isset($res['regions'])) $res['regions'] = array();
	$res['regions'][$t[0]] = $t[1];
      }
    }
    ldap_unbind($ds);
  }
  return $res;
}

?>
