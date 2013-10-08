<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();

# ob_start() is required so that the PDF file will output correctly
ob_start(); 

$result = pg_prepare($dbconn, "onevendor", "SELECT name, key FROM vendor WHERE vendor.id=$1 LIMIT 1")
	or die('Failed to prepare select query: ' . pg_last_error());
$result = pg_execute($dbconn, "onevendor", array($_GET["id"]))
	or die('Failed to execute select query: ' . pg_last_error());

# Start generating the PDF
$pdf = newpdf();

# We are using one page per vendor. This may not always be the case. 
$row = pg_fetch_row($result);
dovendorpage($pdf,$row[0],$row[1]);

# Close DB connection
pg_close($dbconn);

# Output the file
endpdf($pdf);
?>
