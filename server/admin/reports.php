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

# Process reports
if(array_key_exists("report",$_GET)){
	$data = array();
	switch($_GET["report"]) {
	case "allhumans":
		$result = pg_query($dbconn, "SELECT fname,lname,email,phone,corp,title,address FROM human")
			or die('Failed to perform human select query: ' . pg_last_error());
		while ($row = pg_fetch_row($result)) {
			array_push($data,$row);
		}
		break;
	case "allscanned":
		$result = pg_query($dbconn, "SELECT fname,lname,email,phone,corp,title,address FROM human 
						INNER JOIN seen ON human.id=seen.hid GROUP BY human.id")
			or die('Failed to perform human select query: ' . pg_last_error());
		while ($row = pg_fetch_row($result)) {
			array_push($data,$row);
		}
		break;
	case "allunscanned":
		$result = pg_query($dbconn, "SELECT fname,lname,email,phone,corp,title,address FROM human 
						WHERE id NOT IN (SELECT hid FROM seen)")
			or die('Failed to perform human select query: ' . pg_last_error());
		while ($row = pg_fetch_row($result)) {
			array_push($data,$row);
		}
		break;
	case "scannedby":
		if(array_key_exists("vendor",$_GET)){
			$result = pg_prepare($dbconn, "scannedby", "SELECT human.fname,human.lname,human.email,human.phone,human.corp,human.title,human.address FROM human 
							INNER JOIN seen ON human.id=seen.hid WHERE seen.vid=$1 GROUP BY human.id")
				or die('Failed to prepare human select query: ' . pg_last_error());
			$result = pg_execute($dbconn, "scannedby", array($_GET["vendor"]))
				or die('Failed to execute human select query: ' . pg_last_error());
		}else if(array_key_exists("corp",$_GET)){
			$result = pg_prepare($dbconn, "scannedby", "SELECT human.fname,human.lname,human.email,human.phone,human.corp,human.title,human.address FROM human 
							INNER JOIN seen ON human.id=seen.hid INNER JOIN vendor ON seen.vid=vendor.id WHERE vendor.cid=$1 GROUP BY human.id")
				or die('Failed to prepare human select query: ' . pg_last_error());
			$result = pg_execute($dbconn, "scannedby", array($_GET["corp"]))
				or die('Failed to execute human select query: ' . pg_last_error());
		}else die("Error, no vendor or corp ID in 'scannedby' report");
		while ($row = pg_fetch_row($result)) {
			array_push($data,$row);
		}
		break;
	}
	pg_close($dbconn);
	outputCSV($data);
	exit;
}
# List of static reports
?>
<a href="reports.php?report=allhumans">All Attendees</a><br />
<a href="reports.php?report=allscanned">All Attendees, Scanned</a><br />
<a href="reports.php?report=allunscanned">All Attendees, Not Scanned</a><br />
<br />
<?php
# List of dynamic reports
$result = pg_query($dbconn, "SELECT vendor.id, vendor.name, corp.name FROM vendor INNER JOIN corp ON corp.id=vendor.cid")
	or die('Failed to perform vendor select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)){
?>
<a href="reports.php?report=scannedby&vendor=<?php echo htmlspecialchars($row[0]); ?>">Scanned By <?php echo htmlspecialchars($row[1]); echo " <i>from</i> "; echo htmlspecialchars($row[2]); ?></a><br />
<?php
}
?><br /><?php
$result = pg_query($dbconn, "SELECT id,name FROM corp")
	or die('Failed to perform vendor select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)){
?>
<a href="reports.php?report=scannedby&corp=<?php echo htmlspecialchars($row[0]); ?>">Scanned By <?php echo htmlspecialchars($row[1]); ?></a><br />
<?php
}
?>
