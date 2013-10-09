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

# Check POST variables for menu actions
if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Delete"){
	# XXX: This could check for CSRF
	if($_POST["type"]=="admin"){
		$result = pg_prepare($dbconn, "deleteadmin", "DELETE FROM admin WHERE username = $1")
			or die('Failed to prepare delete query: ' . pg_last_error());
		$result = pg_execute($dbconn, "deleteadmin", array($_POST["name"]))
			or die('Failed to execute delete query: ' . pg_last_error());
	}
}
# Setup CSRF protection (only used for Import)
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Display admin section
echo "<h2>Admins</h2><br />";
# Display list of admins
echo "<table><tr><th>Name</th><th></th></tr>";
$result = pg_query($dbconn, "SELECT username FROM admin")
	or die('Failed to perform admin select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)) {
	echo "<tr>";
	echo "<td>".htmlspecialchars($row[0])."</td>";
	echo "<td>";?>
	<form method=post>
		<input type=hidden name="name" value="<?php echo $row[0];?>" />
		<input type=hidden name="type" value="admin" />
		<input type=submit name=submit value="Delete" />
	</form><?php
	echo "</td>";
	echo "</tr>";
}
echo "</table>";
