<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();
if ($_POST["submit"] != "Generate") banner();

# Check POST variables for menu actions
if(array_key_exists("submit",$_POST) && $_POST["submit"] == "Generate"){
	# Enable QR Code library
	require "phpqrcode/qrlib.php";

	$_POST["id"] = (int)$_POST["id"];
	if($_POST["type"] == "vendor" && $_POST["id"] > 0){
		# Pull key from database
		$result = pg_prepare($dbconn, "getvendorkey", "SELECT key FROM vendor WHERE id = $1")
			or die('Failed to prepare select query: ' . pg_last_error());
		$result = pg_execute($dbconn, "getvendorkey", array($_POST["id"]))
			or die('Failed to execute select query: ' . pg_last_error());
		$key = pg_fetch_result($result,0,0);

		# If no key exists, generate one
		if(strlen($key) < 1){
			$key = md5(rand());
			$result = pg_prepare($dbconn, "insertvendorkey", "UPDATE vendor SET key = $1 WHERE id = $2")
				or die('Failed to prepare update query: ' . pg_last_error());
			$result = pg_execute($dbconn, "insertvendorkey", array($key, $_POST["id"]))
				or die('Failed to execute update query: ' . pg_last_error());
		}
		pg_close($dbconn);

		# Print QR code
		print QRcode::png($CONFIG["baseurl"]."vendor.php?key=".$key);
		exit;
	}else{
		die("Invalid parameters for generation");
	}
# Add Vendor
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Add"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "insertvendor", "INSERT INTO vendor (name,email,cid,key) VALUES ($1,$2,$3,$4)")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$data = array($_POST["name"],$_POST["email"],$_POST["corp"],md5(rand()));
	$result = pg_execute($dbconn, "insertvendor", $data)
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "Vendor added.";
	exit;
# Delete Human
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Delete"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "insertnewhuman", "DELETE FROM vendor WHERE id = $1")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$result = pg_execute($dbconn, "insertnewhuman", array($_POST["id"]))
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "Vendor deleted.";
	exit;
# Import data from CSV file
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Import" && array_key_exists("file",$_FILES)){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Sanity checks
	if($_POST["type"]!="vendor") die("Invalid parameters for import");
	if(strlen($_FILES["file"]["tmp_name"])<1) die("No data received for import");

	# Read uploaded file
	ini_set('auto_detect_line_endings',TRUE);
	$f = fopen($_FILES["file"]["tmp_name"],"r") or die("Failed to open import file");

	# Setup catalog of corps
	$result = pg_query($dbconn, "SELECT id,name FROM corp");
	$corp = array();
	while($row = pg_fetch_row($result))
		$corp[$row[1]] = $row[0];

	# Setup database query
	$result = pg_prepare($dbconn, "insertnewvendor", "INSERT INTO vendor (name,cid,email,key) VALUES ($1,$2,$3,$4)")
		or die('Failed to prepare insert query: ' . pg_last_error());

	# Process input
	$n = 0;
	while (($data = fgetcsv($f, 0, ",")) !== FALSE) {
		# Check for malformed row
		if(count($data) != 3){
			echo "Error: invalid number of columns in row, skipping<br />";
			continue;
		}
		# Check corp ID
		if(!is_numeric($data[1])){
			if(!array_key_exists($data[1],$corp)){
				echo "Error: invalid corporation '".htmlspecialchars($data[1])."', skipping<br />";
				continue;
			}
			$data[1] = $corp[$data[1]];
		}
		# Create QR code data
		$data[3] = md5(rand());
		# Execute
		# NOTE: Due to constraints, this will fail if an entry already exists. So we just skip.
		$result = pg_execute($dbconn, "insertnewvendor", $data)
			or print('Failed to execute insert query: ' . pg_last_error());
		$n++;
	}
	fclose($f);
	pg_close($dbconn);
	echo "Processed $n rows";
	exit;
# Export data to CSV file
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Export"){
	$data = array();
	$extra = "";
	if(array_key_exists("qrdata",$_POST)) $extra = ",'".$CONFIG["baseurl"]."vendor.php?key=' || key";
	# Setup query
	if($_POST["type"]=="vendor"){
		$result = pg_query($dbconn, "SELECT name,cid,email$extra FROM vendor")
			or die('Failed to perform vendor select query: ' . pg_last_error());
	}else{
		die("Invalid parameters for export");
	}
	# Pull data from DB
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

# Display vendor section
echo "<h2>Vendors</h2><br />";
# Display import function
?><form method="post" enctype="multipart/form-data"><input type="file" name="file" />
	<input type="hidden" name="type" value="vendor" />
	<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
	<input type="submit" name="submit" value="Import" />
</form>
CSV format: Name, Company, Email<br /><hr /><br /><?php
# Display export function
?><form method="post">
	<input type="hidden" name="type" value="vendor" />
	<input type="submit" name="submit" value="Export" />
	<input type="checkbox" name="qrdata" value="Yes" /> Include QR code?
</form><br /><hr /><br /><?php
# Display add function
?><form method="post">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
<table>
        <tr><td>Full Name</td><td><input type="text" name="name" /></td></tr>
        <tr><td>Email Address</td><td><input type="text" name="email" /></td></tr>
	<tr><td>Corporation</td>
	<td><select name="corp"><?php
$result = pg_query("SELECT id,name FROM corp ORDER BY name");
while($row = pg_fetch_row($result) ){ ?>
		<option value="<?php echo htmlspecialchars($row[0]); ?>"><?php echo htmlspecialchars($row[1]); ?></option>
<?php } ?>
	</select></td>
	<tr><td><input type="submit" name="submit" value="Add" /></td><td /></tr>
</table></form><br /><hr /><br /><?php
# Display list of vendors
if(array_key_exists("page",$_GET))
	$page = (int)$_GET["page"];
else
	$page = 0;
$offset = (int)($page * 100);
if($page>0){?><a href="viewvendors.php?page=<?php echo $page-1; ?>">Prev Page</a> &nbsp; <?php } ?><a href="viewvendors.php?page=<?php echo $page+1; ?>">Next Page</a><br /><?php
echo "<table><tr><th>Full Name</th><th>Corporation</th><th>Email</th><th># of Scans</th><th>QR Code</th>";
$result = pg_query($dbconn, "SELECT vendor.id,vendor.name,corp.name,vendor.email,count(seen.hid) AS count FROM 
					vendor LEFT JOIN corp ON vendor.cid=corp.id LEFT JOIN seen ON vendor.id=seen.vid GROUP BY vendor.id,corp.name ORDER BY vendor.id
					LIMIT 100 OFFSET ".$offset)
	or die('Failed to perform vendor select query: ' . pg_last_error());
while ($row = pg_fetch_row($result)) {
	echo "<tr>";
	echo "<td>".htmlspecialchars($row[1])."</td>";
	echo "<td>".htmlspecialchars($row[2])."</td>";
	echo "<td>".htmlspecialchars($row[3])."</td>";
	echo "<td>".htmlspecialchars($row[4])."</td>";
	echo "<td>";?>
	<form method=post>
		<input type=hidden name="id" value="<?php echo $row[0];?>" />
		<input type=hidden name="type" value="vendor" />
		<input type=submit name=submit value="Generate" />
	</form><?php
	echo "</td>";
	echo "<td>";?>
	<form method=post>
		<input type=hidden name="id" value="<?php echo $row[0];?>" />
		<input type=hidden name="nonce" value="<?php echo $nonce; ?>" />
		<input type=submit name=submit value="Delete" />
	</form><?php
	echo "</td>";
	echo '<td><a href="editvendor.php?id='.$row[0].'">EDIT</a></td>'; 
	echo '<td><a href="pdf_vendor.php?id='.$row[0].'">BADGE</a></td>'; 
	echo "</tr>";
}
echo "</table>";
?></html>
