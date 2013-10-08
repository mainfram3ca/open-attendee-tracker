<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();
if(array_key_exists("submit",$_POST) && $_POST["submit"] != "Generate") banner();

# Check POST variables for menu actions
if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Generate"){
	# Enable QR Code library
	require "phpqrcode/qrlib.php";

	$_POST["id"] = (int)$_POST["id"];
	if($_POST["type"] == "human" && $_POST["id"] > 0){
		# Pull key from database
		$result = pg_prepare($dbconn, "gethumankey", "SELECT key FROM human WHERE id = $1")
			or die('Failed to prepare select query: ' . pg_last_error());
		$result = pg_execute($dbconn, "gethumankey", array($_POST["id"]))
			or die('Failed to execute select query: ' . pg_last_error());
		$key = pg_fetch_result($result,0,0);

		# If no key exists, generate one
		if(strlen($key) < 1){
			$key = md5(rand());
			print $key;
			$result = pg_prepare($dbconn, "inserthumankey", "UPDATE human SET key = $1 WHERE id = $2")
				or die('Failed to prepare update query: ' . pg_last_error());
			$result = pg_execute($dbconn, "inserthumankey", array($key, $_POST["id"]))
				or die('Failed to execute update query: ' . pg_last_error());
		}
		pg_close($dbconn);

		# Print QR code
		print QRcode::png($CONFIG["baseurl"]."human.php?key=".$key);
		exit;
	}else{
		die("Invalid parameters for generation");
	}
# Add Human
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Add"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Execute query
	$result = pg_prepare($dbconn, "insertnewhuman", "INSERT INTO human (fname,lname,email,phone,corp,title,address,key,sid,ext1,ext2,ext3) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$data = array(utf8_encode($_POST["fname"]),utf8_encode($_POST["lname"]),utf8_encode($_POST["email"]),utf8_encode($_POST["phone"]),utf8_encode($_POST["corp"]),utf8_encode($_POST["title"]),utf8_encode($_POST["address"]),md5(rand()),utf8_encode($_POST["species"]),utf8_encode($_POST["ext1"]),utf8_encode($_POST["ext2"]),utf8_encode($_POST["ext3"]));
	$result = pg_execute($dbconn, "insertnewhuman", $data)
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "User added.";
	exit;
# Delete Human
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Delete"){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Remove linked records
	$result = pg_prepare($dbconn, "nukeseen", "DELETE FROM seen WHERE hid = $1")
		or die('Failed to prepare delete query: ' . pg_last_error());
	$result = pg_execute($dbconn, "nukeseen", array($_POST["id"]))
		or die('Failed to execute delete query: ' . pg_last_error());

	# Execute query
	$result = pg_prepare($dbconn, "insertnewhuman", "DELETE FROM human WHERE id = $1")
		or die('Failed to prepare insert query: ' . pg_last_error());
	$result = pg_execute($dbconn, "insertnewhuman", array($_POST["id"]))
		or die('Failed to execute insert query: ' . pg_last_error());
	echo "User deleted.";
	exit;
# Import data from CSV file
}else if(array_key_exists("submit",$_POST) && $_POST["submit"]=="Import" && array_key_exists("file",$_FILES)){
	# Check CSRF token
	if(!array_key_exists("nonce",$_SESSION) || $_POST["nonce"] != $_SESSION["nonce"]) die("Security error on import");

	# Sanity checks
	if($_POST["type"]!="human") die("Invalid parameters for import");
	if(strlen($_FILES["file"]["tmp_name"])<1) die("No data received for import");

	# Read uploaded file
	ini_set('auto_detect_line_endings',TRUE);
	$f = fopen($_FILES["file"]["tmp_name"],"r") or die("Failed to open import file");

	$result = pg_query($dbconn, "SELECT id,name FROM species");
	$species = array();
	while($row = pg_fetch_row($result))
		$species[$row[1]] = $row[0];

	//CSV format: First Name, Last Name, Email, Phone Number, Company, Title, Address, Type, ext1, ext2, ext3, key
	# Setup database query
	$result = pg_prepare($dbconn, "insertnewhuman", "INSERT INTO human (fname,lname,email,phone,corp,title,address,sid,ext1,ext2,ext3,key) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)")
		or die('Failed to prepare insert query: ' . pg_last_error());

	# Process input
	$n = 0;
	while (($data = fgetcsv($f, 0, ",")) !== FALSE) {
		#Debug helping
		print "Processing row ".$n;
		# Check for malformed row
		if(count($data) != 11){
			echo "Error: invalid number of columns in row, skipping<br />";
			continue;
		}
		# Create QR code data
		$data[11] = md5(rand());
		if(!is_numeric($data[7])){
			if(!array_key_exists($data[7],$species)){
				echo "Error: invalid attendee type '".htmlspecialchars($data[7])."', skipping<br />";
				continue;
			}
			$data[7] = $species[$data[7]];
		}
		# UTF-8 encode magic
		for($i=0;$i<12;$i++)
			$data[$i] = utf8_encode($data[$i]);
		# Execute
		# NOTE: Due to constraints, this will fail if an entry already exists. So we just skip.
		$result = pg_execute($dbconn, "insertnewhuman", $data)
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
	if(array_key_exists("qrdata",$_POST)) $extra = ",'".$CONFIG["baseurl"]."human.php?key=' || key";
	# Setup query
	if($_POST["type"]=="human"){
		$result = pg_query($dbconn, "SELECT fname,lname,email,phone,corp,title,address,sid,ext1,ext2,ext3$extra FROM human")
			or die('Failed to perform human select query: ' . pg_last_error());
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
# Setup CSRF protection (only used for Import)
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Display human section
echo "<h2>Humans</h2><br />";
# Display import function
?><form method="post" enctype="multipart/form-data"><input type="file" name="file" />
	<input type="hidden" name="type" value="human" />
	<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
	<input type="submit" name="submit" value="Import" />

</form>
CSV format: First Name, Last Name, Email, Phone Number, Company, Title, Address, Type, ext1, ext2, ext3<br />
<i>Note: This will generate a new QR code, do not supply QR code URL in CSV</i><br /><hr /><br /><?php
# Display export function
?><form method="post">
	<input type="hidden" name="type" value="human" />
	<input type="submit" name="submit" value="Export" />
	<input type="checkbox" name="qrdata" value="Yes" /> Include QR code?
</form><br /><hr /><br /><?php
# Display add function
?><form method="post">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
<table>
	<tr><td>First Name</td><td><input type="text" name="fname" /></td></tr>
	<tr><td>Last Name</td><td><input type="text" name="lname" /></td></tr>
	<tr><td>Email Address</td><td><input type="text" name="email" /></td></tr>
	<tr><td>Phone Number</td><td><input type="text" name="phone" /></td></tr>
	<tr><td>Company</td><td><input type="text" name="corp" /></td></tr>
	<tr><td>Title</td><td><input type="text" name="title" /></td></tr>
	<tr><td>Address</td><td><input type="text" name="address" /></td></tr>
	<tr><td>Attendee Type</td>
	<td><select name="species"><?php
$result = pg_query("SELECT id,name FROM species");
while($row = pg_fetch_row($result) ){ ?>
		<option value="<?php echo htmlspecialchars($row[0]); ?>"><?php echo htmlspecialchars($row[1]); ?></option>
<?php } ?>
	</select></td></tr>
	<tr><td><?php echo $CONFIG["ext1"]; ?></td><td><input type="text" name="ext1" /></td></tr>
	<tr><td><?php echo $CONFIG["ext2"]; ?></td><td><input type="text" name="ext2" /></td></tr>
	<tr><td><?php echo $CONFIG["ext3"]; ?></td><td><input type="text" name="ext3" /></td></tr>
	<tr><td><input type="submit" name="submit" value="Add" /></td><td /></tr>
</table></form><br /><hr /><br /><?php
# Display search option
?><form method="get">
	<input type="text" name="search" />
	<input type="submit" name="submit" value="Search" />
</form><br /><hr /><br /><?php
# Display list of humans
if(array_key_exists("search",$_GET)){
	$result = pg_prepare($dbconn,"humansearch","SELECT human.id,fname,lname,email,phone,corp,title,address,species.name,ext1,ext2,ext3 FROM human LEFT JOIN species ON human.sid=species.id WHERE fname ILIKE $1 OR lname ILIKE $1")
		or die('Failed to prepare human select query: ' . pg_last_error());
	$result = pg_execute($dbconn,"humansearch",array("%".$_GET["search"]."%"))
		or die('Failed to execute human select query: ' . pg_last_error());
}else{
	if(array_key_exists("page",$_GET))
		$page = (int)$_GET["page"];
	else
		$page = 0;
	$offset = (int)($page * 100);
	if($page>0){?><a href="viewhumans.php?page=<?php echo $page-1; ?>">Prev Page</a> &nbsp; <?php } ?><a href="viewhumans.php?page=<?php echo $page+1; ?>">Next Page</a><br /><?php
	$result = pg_query($dbconn, "SELECT human.id,fname,lname,email,phone,corp,title,address,species.name,ext1,ext2,ext3 FROM human LEFT JOIN species ON human.sid=species.id ORDER BY id LIMIT 100 OFFSET ".$offset)
		or die('Failed to perform human select query: ' . pg_last_error());
}
echo "<table><tr><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone #</th><th>Company</th><th>Title</th><th>Address</th><th>Type</th><th>".$CONFIG["ext1"]."</th><th>".$CONFIG["ext2"]."</th><th>".$CONFIG["ext3"]."</th><th>QR Code</th></tr>";
while ($row = pg_fetch_row($result)) {
	echo "<tr>";
	echo "<td>".htmlspecialchars($row[1])."</td>";
	echo "<td>".htmlspecialchars($row[2])."</td>";
	echo "<td>".htmlspecialchars($row[3])."</td>";
	echo "<td>".htmlspecialchars($row[4])."</td>";
	echo "<td>".htmlspecialchars($row[5])."</td>";
	echo "<td>".htmlspecialchars($row[6])."</td>";
	echo "<td>".htmlspecialchars($row[7])."</td>";
	echo "<td>".htmlspecialchars($row[8])."</td>";
	echo "<td>".htmlspecialchars($row[9])."</td>";
	echo "<td>".htmlspecialchars($row[10])."</td>";
	echo "<td>".htmlspecialchars($row[11])."</td>";
	echo "<td>";?>
	<form method=post>
		<input type=hidden name="id" value="<?php echo $row[0];?>" />
		<input type=hidden name="type" value="human" />
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
	echo '<td><a href="edithuman.php?id='.$row[0].'">EDIT</a></td>'; 
	echo '<td><a href="pdf_human.php?id='.$row[0].'">BADGE</a></td>'; 
	echo "</tr>";
}
echo "</table>";
?></html>
