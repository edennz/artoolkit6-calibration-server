<?php
/*
 *  calib_camera/upload.php
 *  ARToolKit6
 *
 *  This file is part of ARToolKit.
 *
 *  ARToolKit is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  ARToolKit is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with ARToolKit.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Copyright 2015-2017 Daqri, LLC.
 *  Copyright 2010-2015 ARToolworks, Inc.
 *
 *  Author(s): Philip Lamb, Thorsten Bux.
 *
 */

define("MAX_IDLE_TIME", 60*30); // seconds.

//define("AR_VIDEO_ASPECT_RATIO_UNIQUE","Unique");

ini_set("error_reporting", E_ALL & !E_USER_DEPRECATED & !E_DEPRECATED);

$php_self_url_root = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$php_self_url = $php_self_url_root . $_SERVER['PHP_SELF'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $commaPos = strrchr($_SERVER['HTTP_X_FORWARDED_FOR'], ',');
    if ($commaPos === FALSE) $remote_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else $remote_addr = trim(substr($_SERVER['HTTP_X_FORWARDED_FOR'], $commaPos + 1));
} else {
    $remote_addr = $_SERVER['REMOTE_ADDR'];
}

function bail($messageCode)
{
	header ($_SERVER['SERVER_PROTOCOL'] . " " . $messageCode);
	exit;
}

function bailWithMessage($messageCode, $explanation)
{
	header ($_SERVER['SERVER_PROTOCOL'] . " " . $messageCode);
	echo "<html><head><title>" . $messageCode . "</title></head><body><h1>" . $messageCode . "</h1>";
	if (!empty($explanation)) echo "<p>" . $explanation . "</p>";
	echo "</body></html>\n";
	exit;
}

function gcd($a,$b) {
    return ($a % $b) ? gcd($b,$a % $b) : $b;
}

function calcAspectRatio($w, $h)
{
	$wLCD = $w;
	$hLCD = $h;
	$cameraGCD = gcd($wLCD,$hLCD);

	while ($cameraGCD > 1){
		$wLCD = $wLCD / $cameraGCD;
		$hLCD = $hLCD / $cameraGCD;
		$cameraGCD = gcd($wLCD,$hLCD);
	}

	$arTemp = $wLCD . ":" . $hLCD;
	
	// Some values that are close to standard ratios.
	switch ($arTemp) {
		case "683:384": 
		case "85:48":
		case "53:30":
		case "427:240":
			$arTemp = "16:9";
			break;
		case "256:135":
		case "128:69":
			$arTemp = "17:9";
			break;
		case "512:307":
			$arTemp = "5:3";
			break;
		case "30:23":
		case "192:145":
			$arTemp = "4:3";
			break;
		case "37:30":
			$arTemp = "11:9";
			break;
		case "64:27":
			$arTemp = "21:9";
			break;
		case "640:427":
			$arTemp = "3:2";
			break;
	}
	return $arTemp;
}

if (!isset($_REQUEST['version'])) {
	bailWithMessage("400 Bad Request", "Missing parameter.");
}

if ($_REQUEST['version'] === '1') {
	// Structure of record for a given file with form name 'files':
	// $_FILES['file']['name'] The original name of the file on the client machine.
	// $_FILES['file']['type'] The mime type of the file, if the browser provided this information.
	// $_FILES['file']['size'] The size, in bytes, of the uploaded file.
	// $_FILES['file']['tmp_name'] The temporary filename of the file in which the uploaded file was stored on the server.
	// $_FILES['file']['error'] The error code associated with this file upload.
	
	//
	// File upload checks.
	//
	
	// Upload OK.
	if ($_FILES["file"]["error"] > 0) {
		bailWithMessage("400 Bad Request", $_FILES["file"]["error"]);
	}
	
	// Check that client didn't fake a file upload.
	if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
		bailWithMessage("400 Bad Request", "Not a file");
	}
	
	// Check that it's the right length for a camera_para.dat file.
	// Used to also check ( substr($_FILES['file']['name'], -15) === 'camera_para.dat' )
	if ( !($_FILES['file']['size'] === 176) ) {
		bailWithMessage("400 Bad Request", "Not a camera parameter file.");
	}
	
	include_once('server_data.php');

	// Check that the uploading client knows the shared secret.	
	if (strcmp(md5($serverAuthTokenUpload), $_REQUEST['ss']) != 0) {
		bailWithMessage("400 Bad Request", "You didn't say the magic word.");
	}
	
	// Read the file.
	$camera_para = file_get_contents($_FILES['file']['tmp_name']);
	if ($camera_para === FALSE) {
		bailWithMessage("400 Bad Request", "File not readable.");
	}

    // All submissions must have: timestamp, device_id, camera_index, camera_width, camera_height.
    if (!isset($_REQUEST['timestamp']) || !isset($_REQUEST['device_id']) || !isset($_REQUEST['camera_index']) || !isset($_REQUEST['camera_width']) || !isset($_REQUEST['camera_height'])) {
		bailWithMessage("400 Bad Request", "Missing parameter.");
	}
    
	//
	// Save the submission in the database.
	//
	
	$mysqli = new mysqli($dbIP, $dbUserWrite, $dbPassWrite, $dbName, $dbPort);	
	if ($mysqli->connect_errno) {
	    // Failure to connect to the database server should just bomb.
	    bailWithMessage('503 Service Unavailable', 'MySQL connect error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error);
	}
	
	$aspectRatio = calcAspectRatio($_REQUEST['camera_width'], $_REQUEST['camera_height']);

    $sql = 'INSERT INTO calib_camera (remote_addr, timestamp, device_id, focal_length, camera_index, camera_face, camera_width, camera_height, err_min, err_avg, err_max, os_name, os_arch, os_version, aspect_ratio, camera_para_base64) VALUES (';
    $sql .= '\'' . $remote_addr . '\', ';
	$sql .= '\'' . $mysqli->escape_string($_REQUEST['timestamp']) . '\', ';
	$sql .= '\'' . $mysqli->escape_string($_REQUEST['device_id']) . '\', ';
	$sql .= (!empty($_REQUEST['focal_length']) ? $mysqli->escape_string($_REQUEST['focal_length']) : '0.0') . ', ';
	$sql .= $mysqli->escape_string($_REQUEST['camera_index']) . ', ';
	$sql .= (!empty($_REQUEST['camera_face']) ? '\'' . $mysqli->escape_string($_REQUEST['camera_face']) . '\'' : 'NULL') . ', ';
	$sql .= $mysqli->escape_string($_REQUEST['camera_width']) . ', ';
	$sql .= $mysqli->escape_string($_REQUEST['camera_height']) . ', ';
	$sql .= (!empty($_REQUEST['err_min']) ? $mysqli->escape_string($_REQUEST['err_min']) : '0.0') . ', ';
	$sql .= (!empty($_REQUEST['err_avg']) ? $mysqli->escape_string($_REQUEST['err_avg']) : '0.0') . ', ';
	$sql .= (!empty($_REQUEST['err_max']) ? $mysqli->escape_string($_REQUEST['err_max']) : '0.0') . ', ';
	$sql .= (!empty($_REQUEST['os_name']) ? '\'' . $mysqli->escape_string($_REQUEST['os_name']) . '\'' : 'NULL') . ', ';
	$sql .= (!empty($_REQUEST['os_arch']) ? '\'' . $mysqli->escape_string($_REQUEST['os_arch']) . '\'' : 'NULL') . ', ';
	$sql .= (!empty($_REQUEST['os_version']) ? '\'' . $mysqli->escape_string($_REQUEST['os_version']) . '\'' : 'NULL') . ', ';
	$sql .= '\'' . $mysqli->escape_string($aspectRatio) . '\', ';
	$sql .= '\'' . base64_encode($camera_para) . '\');';
	
	if (!$result = $mysqli->query($sql)) {
        bailWithMessage("500 Internal Server Error", 'MySQL connect error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error);
	}
	$mysqli->close();
  
} else {
    // Unknown version.
	bailWithMessage("400 Bad Request", "Client version not supported.");
}
?>