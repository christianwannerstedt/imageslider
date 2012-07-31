<?php
/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once('../../../wp-config.php');
include_once('../../../wp-load.php');

// Settings
// Store the images in a folder named imsl under the wp-content/uploads root
$upload_dir = wp_upload_dir();
$targetDir = $upload_dir['basedir'] .'/imsl';
if (!is_dir($targetDir)) mkdir($targetDir);

$cleanupTargetDir = true; // Remove old files
$maxFileAge = 5 * 3600; // Temp file age in seconds

// 5 minutes execution time
@set_time_limit(5 * 60);

// Uncomment this one to fake upload time
// usleep(5000);

// Get parameters
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

// Clean the fileName for security reasons
$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

// Make sure the fileName is unique but only if chunking is disabled
if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
	$ext = strrpos($fileName, '.');
	$fileName_a = substr($fileName, 0, $ext);
	$fileName_b = substr($fileName, $ext);

	$count = 1;
	while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
		$count++;

	$fileName = $fileName_a . '_' . $count . $fileName_b;
}

$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

// Create target dir
if (!file_exists($targetDir))
	@mkdir($targetDir);

// Remove old temp files	
if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
	while (($file = readdir($dir)) !== false) {
		$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

		// Remove temp file if it is older than the max age and is not the current file
		if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
			@unlink($tmpfilePath);
		}
	}

	closedir($dir);
} else
	die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
	

// Look for the content type header
if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
	$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

if (isset($_SERVER["CONTENT_TYPE"]))
	$contentType = $_SERVER["CONTENT_TYPE"];

// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
if (strpos($contentType, "multipart") !== false) {
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
		// Open temp file
		$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
		if ($out) {
			// Read binary input stream and append it to temp file
			$in = fopen($_FILES['file']['tmp_name'], "rb");

			if ($in) {
				while ($buff = fread($in, 4096))
					fwrite($out, $buff);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
			fclose($in);
			fclose($out);
			@unlink($_FILES['file']['tmp_name']);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
	} else
		die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
} else {
	// Open temp file
	$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
	if ($out) {
		// Read binary input stream and append it to temp file
		$in = fopen("php://input", "rb");

		if ($in) {
			while ($buff = fread($in, 4096))
				fwrite($out, $buff);
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

		fclose($in);
		fclose($out);
	} else
		die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
}


// Check if file has been uploaded
if (!$chunks || $chunk == $chunks - 1) {

	// Strip the temp .part suffix off 
	rename("{$filePath}.part", $filePath);

	// Check if the image is big enough
	$image_width = $_POST["min_width"];
	$image_height = $_POST["min_height"];
	$arrSize = getimagesize($filePath);

	if ($arrSize[0] < $image_width || $arrSize[1] < $image_height){
		unlink($filePath);
		die('{"jsonrpc" : "2.0", "error" : {"code": 201, "message": "The image was too small ('. $arrSize[0] .'x'. $arrSize[1] .'px). It must atleast be '. $image_width .'x'. $image_height .'px."}, "id" : "id"}');
	} else {

		// Create thumb
		require_once(ABSPATH . '/wp-admin/includes/image.php');
		$thumb_width = $_POST["thumb_width"];
		$thumb_height = $_POST["thumb_height"];
		$thumb_dest = image_resize($filePath, $thumb_width, $thumb_height, true, "thumb");

		$filetype = wp_check_filetype($fileName, null);
		$fileExtension = strtolower($filetype['ext']);
		$fileNameWithoutExtension = substr($fileName, 0, strlen($fileName) - strlen($fileExtension) - 1);

		// Resize the large image
		if ($arrSize[0] == $image_width && $arrSize[1] == $image_height){
			copy($filePath, $targetDir ."/". $fileNameWithoutExtension ."-large.". $fileExtension);
		} else {
			image_resize($filePath, $image_width, $image_height, true, "large");
		}
		
		// Save in db
		include_once('../../../wp-includes/wp-db.php');

	    global $wpdb;
		$wpdb->query(sprintf("INSERT INTO %s (`slideShowId` ,	`filename` , `dirname` , `url` , `large_url` , `thumb_url` , `title` , `type` , `mime`, `width` , `height` , `thumb_width` , `thumb_height` , `created` , `updated`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '', '%s', '%s', '%d', '%d', '%d', '%d', NOW(), NOW());",
			$wpdb->prefix ."imsl_slides",
			$_POST["slide_show_id"],
			$fileNameWithoutExtension,
			$targetDir,
			$filePath,
			$upload_dir["baseurl"] ."/imsl/". $fileNameWithoutExtension ."-large.". $fileExtension,
			$upload_dir["baseurl"] ."/imsl/". $fileNameWithoutExtension ."-thumb.". $fileExtension,
			$fileExtension,
			$filetype['type'],
			$image_width,
			$image_height,
			$thumb_width,
			$thumb_height
		));

		// Get the id
		$result = $wpdb->get_results(sprintf("SELECT id FROM `%s` WHERE slideShowId='%s' AND filename='%s' ORDER BY id DESC LIMIT 1;",
			$wpdb->prefix ."imsl_slides",
			$_POST["slide_show_id"],
			$fileNameWithoutExtension
		));

		die('{"jsonrpc" : "2.0", "result" : null, "id" : "'. $result[0]->id .'", "thumb_url" : "'. $upload_dir["baseurl"] ."/imsl/". $fileNameWithoutExtension ."-thumb.". $fileExtension .'"}');
		
	}
	
}

// Return JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : null}');

?>