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
