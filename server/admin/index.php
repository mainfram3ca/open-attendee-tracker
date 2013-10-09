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

# Check POST variables for login details
if(!$loginok){
	require_once("helpers.php");
	$error = "";
	if(array_key_exists("submit",$_POST) && $_POST["submit"] == "Login" && $_POST["nonce"] == $_SESSION["nonce"]){
		$dbconn = dbconnect();
		$result = pg_prepare($dbconn, "adminlogin", "SELECT pass FROM admin WHERE username = $1")
			or die('Failed to prepare select query: ' . pg_last_error());
		$result = pg_execute($dbconn, "adminlogin", array($_POST["username"]))
			or die('Failed to execute select query: ' . pg_last_error());
		$hash = pg_fetch_result($result,0,0);
		pg_close($dbconn);

		# Verify password hashes match
		if(strlen($hash) > 0 && crypt($_POST["password"],$hash) == $hash){
			$_SESSION["admin"] = $_POST["username"];
		}else{
			$error = "<font color=\"red\">Invalid username or password</font>";
		}
	}

	# Check login status
	if(!array_key_exists("admin",$_SESSION) || strlen($_SESSION["admin"]) < 1){
		# Setup nonce
		$nonce = md5(rand());
		$_SESSION["nonce"] = $nonce;

		# Display login page
		?><html><head><title>Admin Login</title></head>
		<body><h3>Admin Login</h3><br />
		<?php print $error; ?><br />
		<form name="login" method="post">
		Username: <input type=text name="username" /><br />
		Password: <input type=password name="password" /><br />
		<input type=hidden name="nonce" value="<?php echo $nonce; ?>" />
		<input type=submit name="submit" value="Login" /><br />
		</form>
		</body></html>
		<?php
		exit();
	}
}
?>
<a href="viewvendors.php">View Vendors</a> &nbsp; <a href="viewcorps.php">View Corporations</a><br />
<a href="viewhumans.php">View Attendees</a> &nbsp; <a href="viewspecies.php">View Attendee Types</a><br />
<a href="viewadmins.php">View Admins</a><br />
<br />
<a href="viewscans.php">View Scans</a><br />
<a href="reports.php">Execute Reports</a><br /><?php
require_once("helpers.php");
$dbconn = dbconnect();
$result = pg_query($dbconn, "SELECT name,short FROM species ") or die(pg_last_error());
while ($row = pg_fetch_row($result)) { ?>
<a href="pdf.php?species=<?php echo $row[1]; ?>">Generate <?php echo $row[0]; ?> Badges</a><br />
<?php } ?>
<a href="pdf2.php">Generate Vendor Badges</a><br />
<a href="randomdraw.php">Perform Random Draw</a><br />
<br />
<a href="logout.php">LOGOUT</a></br />
</body>
</html>
