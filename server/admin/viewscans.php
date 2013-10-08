<?php
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
