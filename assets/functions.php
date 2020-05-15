<?php
if(!defined("WHMCS"))
  die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

function sign_in_with_mcskripts_settings(){
  $settings = [];
  
  $entries = Capsule::table('tbladdonmodules')->select('setting', 'value')->where('module', '=', 'sign_in_with_mcskripts')->get();
  foreach($entries as $entry){
	$settings[$entry->setting] = $entry->value;
  }
  $settings['handler'] = ((!empty($settings['handler']) && $settings['handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
  
  return $settings;
}

function sign_in_with_mcskripts_get_userinfo($authorization_code){
  $settings = sign_in_with_mcskripts_settings();
  
  if($settings['handler'] == 'curl'){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.mcskri.pt/v1/connect/token");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Basic '.base64_encode($settings["client_id"]."::".$settings["client_secret"])
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(
	  array(
		'code' => $authorization_code,
		'grant_type' => 'authorization_code',
	  )
	));
	$tokenResponse = json_decode(curl_exec($ch), true);
	curl_close($ch);
	
	if($tokenResponse["success"]){
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, "https://api.mcskri.pt/v1/connect/userinfo");
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: '.$tokenResponse["data"]["token_type"].' '.$tokenResponse["data"]["access_token"]
	  ));
      return json_decode(curl_exec($ch), true);
      curl_close($ch);
	}else{
	  return false;
	}
  }else{
	return false;
  }
}

function sign_in_with_mcskripts_get_client_ip(){
  if(isset($_SERVER) && is_array($_SERVER)){
    $keys = [];
	$keys[] = 'HTTP_X_REAL_IP';
	$keys[] = 'HTTP_X_FORWARDED_FOR';
	$keys[] = 'HTTP_CLIENT_IP';
	$keys[] = 'REMOTE_ADDR';

	foreach ($keys as $key){
	  if(isset($_SERVER[$key])){
		if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $_SERVER[$key]) === 1){
		  return $_SERVER[$key];
		}
	  }
	}
  }
  
  return '';
}

function sign_in_with_mcskripts_get_userid_by_token($token){
  $userid = null;
  
  $entry = Capsule::table('tblmcs_user_token')->select('id', 'userid')->where('user_token', '=', strval(trim($token)))->first();
  if(is_object($entry) && isset($entry->id)){
	$user_tokenid = $entry->id;
	
	$entry = Capsule::table('tblclients')->select('id')->where('id', '=', $entry->userid)->first();
	if(is_object($entry) && isset($entry->id)){
	  $userid = $entry->id;
	}else{
	  Capsule::table('tblmcs_user_token')->where('id', '=', $user_tokenid)->delete();
	}
  }

  return $userid;
}

function sign_in_with_mcskripts_get_userid_by_email($email){
  $userid = null;
  
  $entry = Capsule::table('tblclients')->select('id')->where('email', '=', trim(strval($email)))->first();
  if(is_object($entry) && isset($entry->id)){
	$userid = $entry->id;
  }

  return $userid;
}

function sign_in_with_mcskripts_generate_hash($length = 5, $case_sensitive = false){
  $hash = '';
  for($i = 0; $i < $length; $i++){
	$hash .= sign_in_with_mcskripts_generate_hash_char($case_sensitive);
  }

  return $hash;
}

function sign_in_with_mcskripts_generate_hash_char($case_sensitive = false){
  $regexp = ($case_sensitive ? 'a-zA-Z0-9' : 'a-z0-9');
  do{
	$char = chr(mt_rand(48, 122));
  }while(!preg_match('/[' . $regexp . ']/', $char));

  return $char;
}

function sign_in_with_mcskripts_get_admin_username(){
  $username = null;
  $entry = Capsule::table('tbladmins')->select('username')->where('roleid', '=', 1)->first();
  if (is_object($entry) && isset($entry->username)){
	$username = $entry->username;
  }

  return $username;
}

function sign_in_with_mcskripts_link_token_to_userid($userid, $user_token){
  $entries = Capsule::table('tblmcs_user_token')->select('id')->where('userid', '=', intval($userid))->where('user_token', '<>', $user_token)->get();
  foreach($entries as $entry){
	Capsule::table('tblmcs_user_token')->where('id', '=', $entry->id)->delete();
  }

  $entry = Capsule::table('tblmcs_user_token')->select('id')->where('user_token', '=', $user_token)->first();
  if(is_object($entry) && isset($entry->id)){
	$user_tokenid = $entry->id;
  }else{
	$user_tokenid = Capsule::table('tblmcs_user_token')->insertGetId(['userid' => $userid, 'user_token' => $user_token]);
  }

  return true;
}

function sign_in_with_mcskripts_login_userid($userid, $ip_address){
  global $cc_encryption_hash;
  
  $entry = Capsule::table('tblclients')->select('id', 'password')->where('id', '=', $userid)->first();
  if(is_object($entry) && isset($entry->id)){
	if(!session_id()){
	  session_start();
	}

	if(method_exists('WHMCS\Authentication\Client', 'generateClientLoginHash')){
	  $_SESSION['uid'] = $entry->id;
	  $_SESSION['upw'] = WHMCS\Authentication\Client::generateClientLoginHash($entry->id, '', $entry->password);
	}else{
	  $_SESSION['uid'] = $entry->id;
	  $_SESSION['upw'] = sha1($entry->id . $entry->password . $ip_address . substr(sha1($cc_encryption_hash), 0, 20));
	}
	session_write_close();

    return true;
  }
  
  return false;
}