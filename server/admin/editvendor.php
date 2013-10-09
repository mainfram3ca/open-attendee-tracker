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
banner('viewvendors.php');

if(array_key_exists("submit",$_POST)){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "updatevendor", "UPDATE vendor SET name=$1, email=$2, cid=$3 WHERE id=$4")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$data = array($_POST["name"],$_POST["email"],$_POST["corp"],$_POST["id"]);
	$result = pg_execute($dbconn, "updatevendor", $data)
		or die('Failed to execute insert query: ' . pg_last_error());
}

if(!array_key_exists("id",$_GET)){
	header("Location: /admin/");
	exit;
}
$id = (int)$_GET["id"];

# Setup CSRF protection
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Query
$result = pg_prepare($dbconn, "getvendor", "SELECT name,email,cid,id FROM vendor WHERE id = $1")
	or die('Failed to prepare getvendor query: ' . pg_last_error());
$result = pg_execute($dbconn, "getvendor", array($id))
	or die('Failed to execute getvendor query: ' . pg_last_error());
$vendor = pg_fetch_assoc($result);

# Display Form
?>
<form method="post">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
<input type="hidden" name="id" value="<?php echo $vendor["id"]; ?>" />
<table>
	<tr><td>Full Name</td><td><input type="text" name="name" value="<?php echo $vendor["name"]; ?>" /></td></tr>
	<tr><td>Email Address</td><td><input type="text" name="email" value="<?php echo $vendor["email"]; ?>" /></td></tr>
		<tr><td>Corporation</td>
		<td><select name="corp"><?php
	$result = pg_query("SELECT id,name FROM corp");
	while($row = pg_fetch_row($result) ){ ?>
			<option value="<?php echo htmlspecialchars($row[0]); ?>" <?php if($row[0]==$vendor["cid"]) echo "selected ";?>><?php echo htmlspecialchars($row[1]); ?></option>
	<?php } ?>
		</select></td>
	<tr><td><input type="submit" name="submit" value="Edit" /></td><td /></tr>
</table></form><br />
<?php
pg_close($dbconn);
?>
