<?php
/*
 *  calib_camera/gencache.php
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
 *  Copyright 2016-2017 Daqri, LLC.
 *  Copyright 2010-2015 ARToolworks, Inc.
 *
 *  Author(s): Thorsten Bux, Philip Lamb.
 *
 */

define('LOGFILE', '/var/log/calib_camera_gencache.log');
define("MAX_IDLE_TIME", 30); // seconds.

//Set the names for all calibration databases. We create one database per plattform.
$supported_platforms = array(
    1 => 'android',
    2 => 'ios',
    3 => 'macos',
    4 => 'linux'
);

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
    bailWithMessage($messageCode,"");
	exit;
}

function bailWithMessage($messageCode, $explanation)
{
	header ($_SERVER['SERVER_PROTOCOL'] . " " . $messageCode);
	echo "<html><head><title>" . $messageCode . "</title></head><body><h1>" . $messageCode . "</h1>";
	if (!empty($explanation)){
        echo "<p>" . $explanation . "</p>";
    }
	echo "</body></html>\n";
	exit;
}

function inline_data($data, $filename = 'data.bin', $mime_type = 'application/octet-stream')
{
	if (!empty($data)) {
		$size = strlen($data);
        if (ob_get_level()) ob_end_clean(); // Turn off output buffering.
	    if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off'); // Turn off output compression.
		$ftime = gmdate('D, d M Y H:i:s', time()) . ' GMT'; // i.e. now.
		//$etag = md5(serialize(stat($temp_image_file)));
	
		header($_SERVER["SERVER_PROTOCOL"].' 200 OK');  
		header('Content-Length:' . $size);
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: inline; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: ' . $ftime);
		//header("ETag: $etag");
		header('Connection: close'); 
		// Override PHP's automatically output versions of these headers.
		header('Cache-Control: private');
		header('Pragma: private'); 
		echo($data);
		exit;
	}
}

function cidr_match($ip, $range)
{
    list ($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask; // In case the supplied subnet wasn't correctly aligned.
    return ($ip & $mask) == $subnet;
}

// Quick logging.
$log = $_SERVER['REQUEST_TIME'] .','. date("c", $_SERVER['REQUEST_TIME']) .','. $remote_addr .','. $_REQUEST['version'];
file_put_contents(LOGFILE, $log, FILE_APPEND | LOCK_EX);

// In production version, prior to servicing any request, should check a few things:
//  * client IP blacklist.
//  * client IP request rate limiting.
// Rate limiting obviously needs to be counted as well.

// A very basic static blacklist.
// Example: $blacklist = "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16";
$blacklist = "";
if (!empty($blacklist)) {
    $blacklist_list = explode(',', $blacklist);
    foreach ($blacklist_list as $blacklist_list_entry) {
        if (cidr_match($remote_addr, $blacklist_list_entry)) {
            bail("403 Forbidden");
        }
    }
}

header_remove('X-Powered-By');
header_remove('MS-Author-Via');

if (!isset($_REQUEST['version'])) {
	bailWithMessage("400 Bad Request", "Missing parameter.");
}

if ($_REQUEST['version'] === '1') {

	include_once('server_data.php');

	// Check that the uploading client knows the shared secret.
	if (strcmp(md5($serverAuthTokenDownload), $_REQUEST['ss']) != 0) {
		bailWithMessage("400 Bad Request", "You didn't say the magic word.");
	}
	
	// Check that the requested platform is an allowed platform.
	if (!in_array($_REQUEST['platform'], $supported_platforms)) {
		bailWithMessage("400 Bad Request", "Unsupported platform.");
	}
	
    //
	// Look up.
	//
	
	$mysqli = new mysqli($dbIP, $dbUser, $dbPass, $dbName, $dbPort);	
	if ($mysqli->connect_errno) {
	    // Failure to connect to the database server should just bomb.
	    bailWithMessage('503 Service Unavailable', 'MySQL connect error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error);
	}
	
	$sql = 'SELECT c1.device_id, c1.camera_index, c1.camera_width, c1.camera_height, c1.aspect_ratio, c1.focal_length, c1.camera_para_base64 FROM calib_camera c1';
	// Ensure we get only the row with lowest err_avg for each combination of device_id, focal_length, camera_index, camera_width, camera_height.
	$sql .= ' LEFT JOIN calib_camera c2 ON c1.device_id = c2.device_id AND c1.focal_length = c2.focal_length AND c1.camera_index = c2.camera_index AND c1.camera_width = c2.camera_width AND c1.camera_height = c2.camera_height AND c1.err_avg > c2.err_avg WHERE c2.device_id IS NULL';
	$sql .= ' AND c1.os_name=\'' . $mysqli->escape_string($_REQUEST['platform']) . '\'';
	$sql .= ';';

    if (!($result = $mysqli->query($sql))) {
        bailWithMessage("500 Internal Server Error", 'MySQL connect error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error);
    }

    if ($result->num_rows == 0) {
        bail("204 No Content");
    }

    // One or more parameters found. Filter wanted values.
    $filterFields = array_fill_keys(array('device_id', 'camera_index', 'camera_width', 'camera_height', 'aspect_ratio', 'focal_length', 'camera_para_base64'), 0);
    //$results = array();
    date_default_timezone_set('utc');

    // Create sqlite database in temporary file on disk.
    $cachedb_path = tempnam(sys_get_temp_dir(), 'cparam_cache_'); 
    try {
        $cachedb = new SQLite3($cachedb_path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    } catch (Exception $e) {
        bailWithMessage("500 Internal Server Error", $e->getMessage());
    }
    
    $cachedb->exec('CREATE TABLE IF NOT EXISTS cache(device_id TEXT NOT NULL, camera_index INTEGER NOT NULL, camera_width INTEGER NOT NULL, camera_height INTEGER NOT NULL, aspect_ratio TEXT NOT NULL, focal_length REAL, camera_para_base64 TEXT NOT NULL, expires INTEGER)');

    while ($row = $result->fetch_assoc()) {
        $recordFiltered = array_intersect_key($row, $filterFields);       
        //$results[] = $recordFiltered;
        
        $sql = 'INSERT INTO cache (';
        $values = ') VALUES (';
        foreach ($recordFiltered as $key => $value) {
            $sql .= $key . ',';
            $values .= '?,';
        }
        $sql = rtrim($sql, ",");
        $values = rtrim($values,",");
        $sql = $sql . $values .')';
        $stmt = $cachedb->prepare($sql);
        if (!$stmt) {
            bailWithMessage("500 Internal Server Error", $cachedb->lastErrorCode());;
        }

        $count = 1;
        foreach ($recordFiltered as $key => $value) {
            $stmt->bindValue($count++, $value);
        }

        $result2 = $stmt->execute();
        $result2->finalize();
        $stmt->close();
    }
    $cachedb->close();
    
	$result->close();
	$mysqli->close();

    // Return results in response body in JSON format.
    //header("Content-type: application/json");
    //echo json_encode($results, JSON_NUMERIC_CHECK) . "\n";
    
    $data = file_get_contents($cachedb_path);
    unlink($cachedb_path);
    
    $filename = 'cparam_cache_init.db';
    inline_data($data, $filename);

} else {
    // Unknown version.
    bailWithMessage("400 Bad Request", "Client version not supported.");
}

?>