<?php
error_reporting(E_ALL); # Turn off in production
require_once("../config.php");
# Start session
session_name("ADMINSESS");
session_start();

$loginok = false;
# Check login status
if(array_key_exists("admin",$_SESSION) && strlen($_SESSION["admin"]) >= 1){
	$loginok = true;
}
