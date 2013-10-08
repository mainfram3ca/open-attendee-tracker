<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();

# ob_start() is required so that the PDF file will output correctly
ob_start(); 

$result = pg_prepare($dbconn, "pdfhumans",  "SELECT human.fname,human.lname,human.key,human.ext1,human.ext2,human.ext3,species.short,human.id FROM human LEFT JOIN species ON human.sid=species.id WHERE printed=false AND species.short=$1 LIMIT 100") or die(pg_last_error());
$result = pg_execute($dbconn, "pdfhumans", array($_GET["species"])) or die(pg_last_error());
if(pg_num_rows($result) <= 0)
	die('No results');
pg_prepare($dbconn, "updateprinted", "UPDATE human SET printed=true WHERE id = $1") or die(pg_last_error());
# Start generating the PDF
$pdf = newpdf();

# We are using one page per attendee. This may not always be the case. 
while($row = pg_fetch_row($result) ){
	dohumanpage($pdf,$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6]);
	pg_execute($dbconn, "updateprinted", array($row[7]));
}

# Close DB connection
pg_close($dbconn);

# Output the file
endpdf($pdf);
?>
