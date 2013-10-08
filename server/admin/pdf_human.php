<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();

# ob_start() is required so that the PDF file will output correctly
ob_start(); 

$result = pg_prepare($dbconn, "onehuman", "SELECT human.fname,human.lname,human.key,human.ext1,human.ext2,human.ext3,species.short FROM human LEFT JOIN species ON human.sid=species.id WHERE human.id=$1 LIMIT 1")
	or die('Failed to prepare select query: ' . pg_last_error());
$result = pg_execute($dbconn, "onehuman", array($_GET["id"]))
	or die('Failed to execute select query: ' . pg_last_error());

# Start generating the PDF
$pdf = newpdf();

# We are using one page per attendee. This may not always be the case. 
$row = pg_fetch_row($result);
dohumanpage($pdf,$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6]);

# Close DB connection
pg_close($dbconn);

# Output the file
endpdf($pdf);
?>
