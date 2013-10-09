<?
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
function banner($page = ''){
	echo '<a href="/admin/'.$page.'">Back</a>';
	echo ' &nbsp; ';
	echo '<a href="'.$_SERVER['PHP_SELF'].'">Reload</a>';
}
# Helper function for CSV exports
# based on http://stackoverflow.com/questions/217424/create-a-csv-file-for-a-user-in-php
function outputCSV($data) {
	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=export.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	$outstream = fopen("php://output", "w");
	function __outputCSV(&$vals, $key, $filehandler) {
		fputcsv($filehandler, $vals); // add parameters if you want
	}
	array_walk($data, "__outputCSV", $outstream);
	fclose($outstream);
}
# Connect to database
function dbconnect() {
	include("../config.php");
	$dbconn = pg_connect("dbname=".$CONFIG["dbname"]." user=".$CONFIG["dbuseradmin"])
		or die('Could not connect: ' . pg_last_error());
	return $dbconn;
}
function newpdf() {
	require_once "fpdf17/fpdf.php";
	$pdf = new FPDF('L','mm','letter');
	return $pdf;
}
function dohumanpage($pdf,$fname,$lname,$key,$ext1,$ext2,$ext3,$type) {
	require "../config.php";
	require_once "fpdf17/fpdf.php";

	# generate the QR Code for this entry
	require_once "phpqrcode/qrlib.php";
	$path = $CONFIG["temppath"].$key.'.png';
	QRcode::png($CONFIG["baseurl"]."human.php?key=".$key, $path);

	# set up another (the first) page
	$pdf->AddPage();
	# set the font for the human-readable info line
	$pdf->SetFont('Arial','',9);
	# move to the upper left corner of the bounding box defined in the next statement
	$pdf->SetY(76);
	$pdf->SetX(150);
	# insert the human-readable info line
	$pdf->Cell(45,1,$ext2." ".$lname.", ".$fname." ".$type." ".$ext1,0,1,'R');
	# set the font for the displayed "first name"
	$pdf->SetFont('Arial','B',28);
	# move to the upper left corner of the bounding box defined in the next statement
	$pdf->SetY(79);
	$pdf->SetX(15);
	# insert the first name (left panel)
	$pdf->Cell(75,17,$fname,0,1,'C');
	# move to the upper left corner of the inserted graphic
	$pdf->SetY(93);
	$pdf->SetX(37);
	# insert the graphic generated above
	$pdf->Image($path);
	# move to the upper left corner of the bounding box defined in the next statement
	$pdf->SetY(79);
	$pdf->SetX(125);
	# insert the first name (right panel)
	$pdf->Cell(75,17,$fname,0,1,'C');
	# move to the upper left corner of the inserted graphic
	$pdf->SetY(93);
	$pdf->SetX(147);
	# insert the graphic generated above
	$pdf->Image($path);
}
function dovendorpage($pdf,$name,$key) {
	require "../config.php";
	require_once "fpdf17/fpdf.php";

	# generate the QR Code for this entry
	require_once "phpqrcode/qrlib.php";
	$path = $CONFIG["temppath"].$key.'.png';
	QRcode::png($CONFIG["baseurl"]."vendor.php?key=".$key, $path);

	# see function "dohumanpage" for details on what's going on below
	$pdf->AddPage();
	$pdf->SetFont('Arial','',9);
	$pdf->SetY(76);
	$pdf->SetX(150);
	$pdf->Cell(45,1,$name,0,1,'R');
	$pdf->SetFont('Arial','B',14);
	$pdf->SetY(79);
	$pdf->SetX(15);
	$pdf->Cell(75,17,$name,0,1,'C');
	$pdf->SetY(93);
	$pdf->SetX(37);
	$pdf->Image($path);
	$pdf->SetY(79);
	$pdf->SetX(125);
	$pdf->SetFont('Arial','',12);
	$pdf->MultiCell(75,17,"This is your authentication token,\nbe sure not to lose it",0,'L');
}
function endpdf($pdf) {
	require_once "fpdf17/fpdf.php";
	$pdf->Output();
}
?>
