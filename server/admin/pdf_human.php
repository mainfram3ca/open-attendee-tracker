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
