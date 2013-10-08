<?php
# userlist.php -- Downloads the list of valid ids
#
# This code verifies that a session exists then gets the list of all humans
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

# Establish DB connection
$dbconn = pg_connect("dbname=".$CONFIG["dbname"]." user=".$CONFIG["dbuserhuman"])
	or die('Could not connect: ' . pg_last_error());

# Lookup all human codes
$result = pg_query($dbconn, 'SELECT key,fname,lname FROM human')
	or die('Failed to perform select query: ' . pg_last_error());

$data = array();
$data['response'] = 200;

# Get human's name
$i = 0;
while ($row = pg_fetch_row($result)) {
    $data['user'][$i]['key'] = $row[0]; 
    $data['user'][$i]['fname'] = $row[1]; 
    if ($CONFIG['ShowLastName'] === true) {
	$data['user'][$i]['lname'] = $row[2]; 
    }
    ++$i;
}

# Close DB connection
pg_close($dbconn);
if ($json === true) {
    echo json_encode($data);
} else {
    echo "<html><head><title>Badge Collector</title></head><body><h1>".htmlspecialchars($fname)."</h1></body></html>";
}
