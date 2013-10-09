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
banner('viewhumans.php');

if(array_key_exists("submit",$_POST)){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "updatehuman", "UPDATE human SET fname=$1, lname=$2, email=$3, phone=$4, corp=$5, title=$6, address=$7, sid=$8, ext1=$9, ext2=$10, ext3=$11 WHERE id=$12")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$data = array(utf8_encode($_POST["fname"]),utf8_encode($_POST["lname"]),utf8_encode($_POST["email"]),utf8_encode($_POST["phone"]),utf8_encode($_POST["corp"]),utf8_encode($_POST["title"]),utf8_encode($_POST["address"]),utf8_encode($_POST["species"]),utf8_encode($_POST["ext1"]),utf8_encode($_POST["ext2"]),utf8_encode($_POST["ext3"]),utf8_encode($_POST["id"]));
	$result = pg_execute($dbconn, "updatehuman", $data)
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
$result = pg_prepare($dbconn, "gethuman", "SELECT fname,lname,email,phone,corp,title,address,sid,id,ext1,ext2,ext3 FROM human WHERE id = $1")
	or die('Failed to prepare gethuman query: ' . pg_last_error());
$result = pg_execute($dbconn, "gethuman", array($id))
	or die('Failed to execute gethuman query: ' . pg_last_error());
$human = pg_fetch_assoc($result);

# Display Form
?>
<form method="post">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
<input type="hidden" name="id" value="<?php echo $human["id"]; ?>" />
<table>
	<tr><td>First Name</td><td><input type="text" name="fname" value="<?php echo $human["fname"]; ?>" /></td></tr>
	<tr><td>Last Name</td><td><input type="text" name="lname" value="<?php echo $human["lname"]; ?>" /></td></tr>
	<tr><td>Email Address</td><td><input type="text" name="email" value="<?php echo $human["email"]; ?>" /></td></tr>
	<tr><td>Phone Number</td><td><input type="text" name="phone" value="<?php echo $human["phone"]; ?>" /></td></tr>
	<tr><td>Company</td><td><input type="text" name="corp" value="<?php echo $human["corp"]; ?>" /></td></tr>
	<tr><td>Title</td><td><input type="text" name="title" value="<?php echo $human["title"]; ?>" /></td></tr>
	<tr><td>Address</td><td><input type="text" name="address" value="<?php echo $human["address"]; ?>" /></td></tr>
	<tr><td>Attendee Type</td>
	<td><select name="species"><?php
$result = pg_query("SELECT id,name FROM species");
while($row = pg_fetch_row($result) ){ ?>
		<option value="<?php echo htmlspecialchars($row[0]); ?>" <?php if($row[0]==$human["sid"]) echo "selected ";?>><?php echo htmlspecialchars($row[1]); ?></option>
<?php } ?>
	</select></td></tr>
	<tr><td><?php echo $CONFIG["ext1"]; ?></td><td><input type="text" name="ext1" value="<?php echo $human["ext1"]; ?>" /></td></tr>
	<tr><td><?php echo $CONFIG["ext2"]; ?></td><td><input type="text" name="ext2" value="<?php echo $human["ext2"]; ?>" /></td></tr>
	<tr><td><?php echo $CONFIG["ext3"]; ?></td><td><input type="text" name="ext3" value="<?php echo $human["ext3"]; ?>" /></td></tr>
	<tr><td><input type="submit" name="submit" value="Edit" /></td><td /></tr>
</table></form><br />
<?php
pg_close($dbconn);
?>
