<?php
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
