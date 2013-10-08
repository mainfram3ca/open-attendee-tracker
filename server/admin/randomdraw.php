<?php
require_once("top.php");

if(!$loginok){
	die("Unauthorized Access");
}

require_once("helpers.php");
$dbconn = dbconnect();
banner();

# Check POST vars
if(array_key_exists("submit",$_POST)){
	$number = (int)$_POST["number"];
	$vid = (int)$_POST["vendor"];

	echo "Selecting ".$number." winners";
	echo "<br />";

	$result = pg_prepare($dbconn, "numscanned", "SELECT count(*) FROM seen INNER JOIN vendor ON seen.vid=vendor.id WHERE vendor.cid=$1")
		or die("Failed to prepare count query: " . pg_last_error());
	$result = pg_execute($dbconn, "numscanned", array($vid))
		or die("Failed to execute count query: " . pg_last_error());
	$count = pg_fetch_result($result,0,0);

	if($count <= $number){
		echo "ERROR: only ".$count." scanned entries";
		echo "<br />";
		pg_close($dbconn);
		exit;
	}

	$query = "SELECT human.fname, human.lname, human.id FROM human INNER JOIN seen ON human.id=seen.hid INNER JOIN vendor ON seen.vid=vendor.id WHERE vendor.cid=$1 GROUP BY human.id OFFSET FLOOR( RANDOM() * $2 ) LIMIT 1";
	$result = pg_prepare($dbconn, "randomdraw", $query)
		or die("Failed to prepare query: " . pg_last_error());
	$winners = array();
	for($i=0;$i < $number;$i++){
		$ok = false;
		$row = array();
		while(!$ok){
			$result = pg_execute($dbconn, "randomdraw", array($vid,$count))
				or die("Failed to execute query: " . pg_last_error());
			$row = pg_fetch_row($result);
			if(in_array($row[2],$winners)) continue;
			if(strlen($row[0])>0 && strlen($row[1])>0) $ok=true;
		}
		array_push($winners,$row[2]);
		echo "Prize #".($i+1)." goes to ".$row[0]." ".$row[1];
		echo "<br />";
	}
	pg_close($dbconn);
	exit;
}

# Setup CSRF protection
$nonce = md5(rand());
$_SESSION["nonce"] = $nonce;

# Display form
?>
<form method="post">
	<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
	<select name="vendor"><?php
$result = pg_query("SELECT id,name FROM corp ORDER BY name");
while($row = pg_fetch_row($result) ){ ?>
		<option value="<?php echo htmlspecialchars($row[0]); ?>"><?php echo htmlspecialchars($row[1]); ?></option>
<?php } ?>
	</select>
	<input type="text" name="number" value="1" />
	<input type="submit" name="submit" value="Draw!" />
</form>
<?php
pg_close($dbconn);
?>
