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
banner();

# Setup CSRF protection (only used for Import)
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Display scans
echo "<h2>Scans</h2><br />";
echo "<table border=1 cellspacing=2><tr><th>Corporation</th><th>Vendor Name</th><th>Human Name</th><th>Time</th><th>Note</th></tr>";
$result = pg_query($dbconn, "SELECT corp.name,vendor.name,human.fname,human.lname,seen.seen,seen.note FROM seen INNER JOIN vendor ON seen.vid=vendor.id INNER JOIN corp ON corp.id=vendor.cid INNER JOIN human ON seen.hid=human.id")
	or die('Failed to perform seen select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)) {
	echo "<tr>";
	echo "<td>".htmlspecialchars($row[0])."</td>";
	echo "<td>".htmlspecialchars($row[1])."</td>";
	echo "<td>".htmlspecialchars($row[2])." ".htmlspecialchars($row[3])."</td>";
	echo "<td>".htmlspecialchars($row[4])."</td>";
	echo "<td>".htmlspecialchars($row[5])."</td>";
	echo "</tr>";
}
echo "</table>";
