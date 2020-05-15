<?php
if(!defined("WHMCS"))
  die("This file cannot be accessed directly");

require_once realpath(dirname(__FILE__)) . '/assets/functions.php';

function sign_in_with_mcskripts_callback(){
  global $CONFIG;
  if(isset($_GET) AND !empty($_GET['code'])){
	$authorization_code = trim($_GET['code']);
	$settings = sign_in_with_mcskripts_settings();
	if(!empty($settings['client_id']) AND !empty($settings['client_secret']) AND !empty($settings['scope']) AND !empty($settings['redirect_uri'])){
	  $userInfo = sign_in_with_mcskripts_get_userinfo($authorization_code);
	  
	  if($userInfo["success"]){
		$userid = sign_in_with_mcskripts_get_userid_by_token($userInfo["data"]["uuid"]);
		if(!is_numeric($userid)){
		  if(!empty($userInfo["data"]["email"]) AND $userInfo["data"]["email_is_verified"] === true){
			$userid = sign_in_with_mcskripts_get_userid_by_email($userInfo["data"]["email"]);
		  }
		}
	    
		if(!is_numeric($userid)){
		  $client_data = [];
		  $client_data['firstname'] = $userInfo["data"]["firstname"];
		  $client_data['lastname'] = $userInfo["data"]["lastname"];
		  $client_data['password2'] = sign_in_with_mcskripts_generate_hash(10);
		  $client_data["clientip"] = sign_in_with_mcskripts_get_client_ip();
		
		  $client_data['skipvalidation'] = true;
		
		  if(!empty($userInfo["data"]["email"])){
			$client_data['email'] = $userInfo["data"]["email"];
		  }else{
			$client_data['noemail'] = true;
		  }
		
		  $admin_username = sign_in_with_mcskripts_get_admin_username();
		  $result = localAPI('AddClient', $client_data, $admin_username);
		  if(is_array($result) && !empty($result['clientid'])){
			$userid = $result['clientid'];
		  }
		}
	  
		if(!empty($userid)){
		  sign_in_with_mcskripts_link_token_to_userid($userid, $userInfo["data"]["uuid"]);
		
		  if(sign_in_with_mcskripts_login_userid($userid, sign_in_with_mcskripts_get_client_ip())){
			if(!empty($_GET['return_url']) && strpos($_GET['return_url'], $CONFIG['SystemURL']) == 0){
			  $redirect_to = $_GET['return_url'];
			}else{
			  $redirect_to = rtrim($CONFIG['SystemURL'], ' /') . '/clientarea.php';
			}

			header("Location: " . $redirect_to);
			exit;
		  }
		}
	  }
	}
  }
}
add_hook("ClientAreaPage", 1, "sign_in_with_mcskripts_callback");

function sign_in_with_mcskripts_library_html(){
  global $CONFIG;
  
  $html = '';
  $settings = sign_in_with_mcskripts_settings();
  if(!empty($settings['client_id']) AND !empty($settings['scope']) AND !empty($settings['redirect_uri'])){
	$output = [];
	$output[] = '';
	$output[] = "<!-- McSkripts.net / Sign in with McSkripts v".$settings['version']." for WHMCS -->";
	$output[] = '<link rel="stylesheet" href="https://mcskri.pt/css/signinwmcs.css">';
	
	$html = implode("\n", $output);
  }
  
  return $html;
}
add_hook("ClientAreaHeadOutput", 1, "sign_in_with_mcskripts_library_html");

function sign_in_with_mcskripts_button(){
  $html = '';
  if(empty($_SESSION['uid'])){
	$settings = sign_in_with_mcskripts_settings();
	if(!empty($settings['client_id']) AND !empty($settings['scope']) AND !empty($settings['redirect_uri'])){
	  require_once realpath(dirname(__FILE__)) . '/lang/'.strtolower($settings['language']).'.php';
	  $output = [];
	  $output[] = '<br>';
	  $output[] = " <!-- McSkripts.net / Sign in with McSkripts v".$settings['version']." for WHMCS -->";
	  $output[] = ' <a class="mcs-button" href="https://mcskripts.net/connect/signin?client_id='.urlencode($settings['client_id']).'&scope='.str_replace(",", " ", $settings['scope']).'&redirect_uri='.urlencode($settings['redirect_uri']).'"><img src="https://mcskri.pt/img/favicon/default-32x32.png">'.$sign_in_with_mcskripts_lang["button"].'</a>';
	  
	  $html = implode("\n", $output);
	}
  }
  
  return $html;
}

function sign_in_with_mcskripts_shortcodes(){
  return [
	'sign_in_with_mcskripts_button' => sign_in_with_mcskripts_button()
  ];
}
add_hook("ClientAreaPage", 1, "sign_in_with_mcskripts_shortcodes");