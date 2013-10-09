<?php
/*
    Copyright (C) 2013  Mainfram3.ca

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
# vendor.php -- QR code-based session establishment
#
# This page merely processes the QR code data and establishes a session
#
require_once("config.php");
define("KEYLEN",32);

# Destroy old session
session_start();
session_destroy();

# Read key from GET url
$key = $_GET['key'];
$json = isset($_GET['json']) ? true : false;

if(strlen($key) != KEYLEN){
	$response = $json ? json_encode(array("response" => 500, "error" => "Unable to read access token")) : "Unable to read access token";
	die($response);
}

# Establish DB connection
$dbconn = pg_connect("dbname=".$CONFIG["dbname"]." user=".$CONFIG["dbuservendor"])
	or die('Could not connect: ' . pg_last_error());

# Lookup key in DB
$result = pg_prepare($dbconn, "vendorlogin", 'SELECT id,name,cid FROM vendor WHERE key = $1')
	or die('Failed to prepare query: ' . pg_last_error());
$result = pg_execute($dbconn, "vendorlogin", array($key))
	or die('Failed to execute query: ' . pg_last_error());

# If invalid, tell user there's an error and bail.
$rows = pg_num_rows($result);
if($rows != 1){
	$response = $json ? json_encode(array("response" => 500, "error" => "Invalid Login Token " . htmlspecialchars($key))) : "Invalid Login Token " . htmlspecialchars($key);
	die($response);
}
$vendorid = pg_fetch_result($result,0,0);
$vendorname = pg_fetch_result($result,0,1);
$corpid = pg_fetch_result($result,0,2);

# Set session
session_start();
$_SESSION = array();
$_SESSION['id'] = $vendorid;

# Close DB
pg_close($dbconn);

# Tell user they're good to go
if ($json === true) {
    $data = array();
    $data['response'] = 200;
    $data['session'] = session_id();
    $data['baseurl'] = $CONFIG["baseurl"];
    $data['pin'] = $CONFIG["pin"];
    $data['confimg'] = $CONFIG["image"];
    $data['key'] = $key;
    $data['vendorname'] = $vendorname;
    $data['corpid'] = $corpid;
    // add more config values here as we move forward
    $response = json_encode($data);
} else {
    $response = "<html><head><title>Login Successful</title></head><body><h1>Login Successful</h1></body></html>";
}
echo $response;

