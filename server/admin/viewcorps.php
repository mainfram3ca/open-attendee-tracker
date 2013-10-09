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
if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Add"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "insertcorp", "INSERT INTO corp (name) VALUES ($1)")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$data = array($_POST["name"]);
	$result = pg_execute($dbconn, "insertcorp", $data)
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "Corporation added.";
	exit;
# Delete Corp
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Delete"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "insertnewhuman", "DELETE FROM corp WHERE id = $1")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$result = pg_execute($dbconn, "insertnewhuman", array($_POST["id"]))
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "Corporation deleted.";
	exit;
# Import data from CSV file
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Import" && array_key_exists("file",$_FILES)){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Sanity checks
	if(strlen($_FILES["file"]["tmp_name"])<1) die("No data received for import");

	# Read uploaded file
	ini_set('auto_detect_line_endings',TRUE);
	$f = fopen($_FILES["file"]["tmp_name"],"r") or die("Failed to open import file");

	# Setup database query
	$result = pg_prepare($dbconn, "insertnewcorp", "INSERT INTO corp (name) VALUES ($1)")
		or die('Failed to prepare insert query: ' . pg_last_error());

	# Process input
	$n = 0;
	while (($data = fgetcsv($f, 0, ",")) !== FALSE) {
		# Check for malformed row
		if(count($data) != 1){
			echo "Error: invalid number of columns in row, skipping<br />";
			continue;
		}
		# Execute
		# NOTE: Due to constraints, this will fail if an entry already exists. So we just skip.
		$result = pg_execute($dbconn, "insertnewcorp", array($data[0]))
			or print('Failed to execute insert query: ' . pg_last_error());
		$n++;
	}
	fclose($f);
	pg_close($dbconn);
	echo "Processed $n rows";
	exit;
# Export data to CSV file
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Export"){
	# Setup query
	$result = pg_query($dbconn, "SELECT name FROM corp")
		or die('Failed to perform corp select query: ' . pg_last_error());
	# Pull data from DB
	$data = array();
	while ($row = pg_fetch_row($result)) {
		array_push($data,$row);
	}
	pg_close($dbconn);
	# Display CSV
	outputCSV($data);
	exit;
}
# Setup CSRF protection
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Display corp section
echo "<h2>Corporations</h2><br />";
# Display import function
?><form method="post" enctype="multipart/form-data"><input type="file" name="file" />
	<input type="hidden" name="type" value="corp" />
	<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
	<input type="submit" name="submit" value="Import" />
</form>
CSV format: Name<br /><hr /><br /><?php
# Display export function
?><form method="post">
	<input type="hidden" name="type" value="corp" />
	<input type="submit" name="submit" value="Export" />
</form><br /><hr /><br /><?php
# Display add function
?><form method="post">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
<table>
        <tr><td>Name</td><td><input type="text" name="name" /></td></tr>
	<tr><td><input type="submit" name="submit" value="Add" /></td><td /></tr>
</table></form><br /><hr /><br /><?php
# Display list of corps
if(array_key_exists("page",$_GET))
	$page = (int)$_GET["page"];
else
	$page = 0;
$offset = (int)($page * 100);
if($page>0){?><a href="viewcorps.php?page=<?php echo $page-1; ?>">Prev Page</a> &nbsp; <?php } ?><a href="viewcorps.php?page=<?php echo $page+1; ?>">Next Page</a><br /><?php
echo "<table><tr><th>Name</th><th />";
$result = pg_query($dbconn, "SELECT id,name FROM corp 
					LIMIT 100 OFFSET ".$offset)
	or die('Failed to perform corp select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)) {
	echo "<tr>";
	echo "<td>".htmlspecialchars($row[1])."</td>";
	echo "<td>";?>
	<form method=post>
		<input type=hidden name="id" value="<?php echo $row[0];?>" />
		<input type=hidden name="nonce" value="<?php echo $nonce; ?>" />
		<input type=submit name=submit value="Delete" />
	</form><?php
	echo "</td>";
	//echo '<td><a href="editcorp.php?id='.$row[0].'">EDIT</a></td>'; 
	echo "</tr>";
}
echo "</table>";
?></html>
