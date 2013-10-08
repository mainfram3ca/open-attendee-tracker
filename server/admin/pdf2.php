<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();

# ob_start() is required so that the PDF file will output correctly
ob_start(); 

$result = pg_query($dbconn, "SELECT name, key FROM vendor");

# Start generating the PDF
$pdf = newpdf();

# We are using one page per attendee. This may not always be the case. 
while($row = pg_fetch_row($result) ){
	dovendorpage($pdf,$row[0],$row[1]);
}

# Close DB connection
pg_close($dbconn);

# Output the file
endpdf($pdf);
?>
