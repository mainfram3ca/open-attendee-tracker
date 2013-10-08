<?php
# human.php -- Badge ID accumulator
#
# This code verifies that a session exists then adds the ID scanned to the
# list of badges seen by the session's account.
#
require_once("config.php");
# Begin session
session_start();

$json = isset($_GET['json']) ? true : false;

# Error out if no valid session
if(!array_key_exists("id",$_SESSION)){
	setcookie(session_name(),'');
	session_destroy();
	$response = $json ? json_encode(array ("response" => 302, "error" => "No Session, please login")) : "No session, please login";
	die($response);
}

# Pull vendor id from session
$vendorid = $_SESSION['id'];

# Get human code
define("KEYLEN",32);
$key = $_GET['key'];
if(strlen($key) != KEYLEN){
	$response = $json ? json_encode(array ("response" => 500, "error" => "Unable to read token")) : "Unable to read token";
	die($response);
}

# Establish DB connection
$dbconn = pg_connect("dbname=".$CONFIG["dbname"]." user=".$CONFIG["dbuserhuman"])
	or die('Could not connect: ' . pg_last_error());

# Lookup human code
$result = pg_prepare($dbconn, "gethuman", 'SELECT id,fname FROM human WHERE key = $1')
	or die('Failed to prepare select query: ' . pg_last_error());
$result = pg_execute($dbconn, "gethuman", array($key))
	or die('Failed to execute select query: ' . pg_last_error());

# Check code
$rows = pg_num_rows($result);
if($rows != 1){
	$response = $json ? json_encode(array ("response" => 500, "error" => "Invalid Token " . htmlspecialchars($key))) : "Invalid Token " . htmlspecialchars($key);
	die($response);
}
$humanid = pg_fetch_result($result,0,0);
$fname = pg_fetch_result($result,0,1);

# Add to list
$result = pg_prepare($dbconn, "addtolist", "INSERT INTO seen (vid, hid) VALUES ($1, $2)")
	or die ('Failed to prepare insert query: ' . pg_last_error());
$result = pg_execute($dbconn, "addtolist", array($vendorid,$humanid))
	or die ('Failed to execute insert query: ' . pg_last_error());

# Display human's name (make sure to encode HTML entities)

# Close DB connection
pg_close($dbconn);
if ($json === true) {
    $data = array();
    $data['response'] = 200;
    $data['fname'] = $fname;
    echo json_encode($data);
} else {
    echo "<html><head><title>Badge Collector</title></head><body><h1>".htmlspecialchars($fname)."</h1></body></html>";
}
