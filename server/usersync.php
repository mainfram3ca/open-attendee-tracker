<?php
# usersync.php -- Badge ID multi-accumulator
#
# This code verifies that a session exists then adds the IDs scanned to the
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

// Need to check we got a POST

# Pull vendor id from session
$vendorid = $_SESSION['id'];
$accepted = array();

# Get the POST blob
$jsondata = file_get_contents("php://input");

# JSON decode the POST blob
$sdata = json_decode($jsondata,true);

# Verify json_decode() was successful
if($sdata == NULL){
	$response = $json ? json_encode(array ("response" => 500, "error" => "Invalid JSON data")) : "Invalid JSON data";
	die($response);
}

# Establish DB connection
$dbconn = pg_connect("dbname=".$CONFIG["dbname"]." user=".$CONFIG["dbuserhuman"])
        or die('Could not connect: ' . pg_last_error());

# Prep our queries
$result = pg_prepare($dbconn, "gethuman", 'SELECT id FROM human WHERE key = $1')
	or die('Failed to prepare select query: ' . pg_last_error());
$result = pg_prepare($dbconn, "synchumans", "INSERT INTO seen (vid, hid, seen, note) VALUES ($1, $2, $3, $4)")
	or die ('Failed to prepare insert query: ' . pg_last_error());

# Iterate through each data point
foreach($sdata as $line){
	if(!array_key_exists("key",$line)||!array_key_exists("time",$line))
		continue;
	# Lookup human code
	$result = pg_execute($dbconn, "gethuman", array($line["key"]))
	    or die('Failed to execute select query: ' . pg_last_error());

	if(pg_num_rows($result) == 1){	
		$humanid = pg_fetch_result($result,0,0);
		$timestamp = date(DATE_ISO8601,$line["time"]);
		$note = $line["notes"];
		# Insert record
		$result = pg_execute($dbconn, "synchumans", array($vendorid,$humanid,$timestamp,$note))
		    or die ('Failed to execute insert query: '. pg_last_error());
		$accepted[] = $line["scanid"];
	}
}

# Close DB
pg_close($dbconn);

# Return success
$response = $json ? json_encode(array ("response" => 200, "accepted" => $accepted)) : "Accepted";
echo $response;

