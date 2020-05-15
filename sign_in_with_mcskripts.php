<?php
if(!defined("WHMCS"))
  die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;
require_once realpath(dirname(__FILE__)) . '/assets/functions.php';

function sign_in_with_mcskripts_config(){
  $configarray = [
	"name" => "Sign In With McSkripts",
	"description" => "Allows users to sign in with their McSkripts account",
	"version" => "1.0",
	"author" => "McSkripts",
	"language" => "english",
	"fields" =>[]
  ];
  
  $configarray['fields']['client_id'] = [
	"FriendlyName" => "Client ID",
	"Type" => "text",
	"Size" => "30",
	"Description" => 'You can obtain your Client ID from the <a href="https://mcskripts.net/apiintegrations" target="_blank"><strong>api credentials page</strong></a>.',
	"Default" => ""
  ];
  
  $configarray['fields']['client_secret'] = [
	"FriendlyName" => "Client Secret",
	"Type" => "text",
	"Size" => "30",
	"Description" => 'You can obtain your Client Secret from the <a href="https://mcskripts.net/apiintegrations" target="_blank"><strong>api credentials page</strong></a>.',
	"Default" => ""
  ];

  $configarray['fields']['scope'] = [
	"FriendlyName" => "Scope",
	"Type" => "text",
	"Size" => "30",
	"Description" => 'You can obtain your Scope from the <a href="https://mcskripts.net/apiintegrations" target="_blank"><strong>api credentials page</strong></a>.',
	"Default" => ""
  ];
  
  $configarray['fields']['redirect_uri'] = [
	"FriendlyName" => "Redirect URI",
	"Type" => "text",
	"Size" => "30",
	"Description" => 'You can obtain your Redirect URI from the <a href="https://mcskripts.net/apiintegrations" target="_blank"><strong>api credentials page</strong></a>.',
	"Default" => ""
  ];

  $configarray['fields']['handler'] = [
	"FriendlyName" => "API Handler",
	"Type" => "dropdown",
	"Options" => "CURL,FSOCKOPEN",
	"Description" => "Using CURL is recommended but it might be disabled on some servers.",
	"Default" => "CURL"
  ];

  $configarray['fields']['language'] = [
	"FriendlyName" => "Language",
	"Type" => "dropdown",
	"Options" => "English,Dansk",
	"Description" => "Change the language",
	"Default" => "English"
  ];

  return $configarray;
}

function sign_in_with_mcskripts_activate(){
  if(!Capsule::schema()->hasTable('tblmcs_user_token')){
	Capsule::schema()->create('tblmcs_user_token', function($table){
	  $table->increments('id');
	  $table->integer('userid');
	  $table->string('user_token');
	});
  }
  
  return [
	'status' => 'success',
	'description' => 'Sign in with McSkripts has successfully been activated. Please setup your API keys in order to enable the addon.'
  ];
}

function sign_in_with_mcskripts_deactivate(){
  // Don't remove the tables, otherwise the customer looses all McSkript client information
  // Capsule::schema()->dropIfExists('tblmcs_user_token');

  return [
	'status' => 'success',
	'description' => 'Sign in with McSkripts has successfully been deactivated.'
  ];
}

function sign_in_with_mcskripts_upgrade($vars){;}

function sign_in_with_mcskripts_output($vars){
  echo 'Version: '.$vars['version'].'<br /><br />';
  echo 'Please ensure you have entered your McSkripts API Client ID/Secret Keys into the settings under Setup->Addon Modules<br />';
  echo 'Place the template variable {$sign_in_with_mcskripts_embedded} in your login.tpl file where you would like the Sign in with McSkripts icon to appear.<br />';
}

function sign_in_with_mcskripts_sidebar($vars){return '';}
