<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    repository_movingimageupload v2
 * @copyright  2019 Rainer Möller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // include required movingimage libs (all Moodle parameters are transfered as POST parameters)
if (!class_exists('VideoManagerPro'))
	require_once('../movingimagepicker/classes/vmpro.php');

// Create new instance and log into movingimage API to create new video asset
$vmpro = new VideoManagerPro();
$vmpro->setAccessToken($_POST['vmpro'], $_POST['mitoken']);
$entity = $vmpro->createVideoEntity($_POST['filename'],$_POST['title'],$_POST['description'],$_POST['keywords'],$_POST['channel'] ?? 0, $_POST['group'] ?? 0);

// If video asset is successfully created
if (is_array($entity) && isset($entity['id'])) {

	// Get URL for file upload
	$url = $vmpro->getUploadURL($entity['id']);

  // If URL request is successful
	if (is_array($url) && isset($url['upload_url'])) {

		// Set success code and output upload URL to body
		http_response_code(200);
		echo $url['upload_url'];

		// Check if uploader wants to save Moodle metadata to movingimage video asset
		$metadata = [];
		if (($_POST['coursefield'] ?? '') != '')
		  $metadata[$_POST['coursefield']] = $_POST['coursename'];
		if (($_POST['namefield'] ?? '') != '')
		  $metadata[$_POST['namefield']] = $_POST['author'];
		if (($_POST['emailfield'] ?? '') != '')
		  $metadata[$_POST['emailfield']] = $_POST['email'];

		// Set defined Moodle metadata in movingimage video asset
		if (count($metadata) > 0)
			$vmpro->setCustomMetadata($entity['id'], $metadata);

		// Set auto-delete timer for video asset, if given
		if ($_POST['deletiontimestamp'] != 0) {
			$vmpro->setVideoDeletionTimer($entity['id'],$_POST['deletiontimestamp']);
		}

		// Set security policy  video asset, if given
		if ($_POST['securitypolicy'] != 0)
			$vmpro->setVideoData($entity['id'], array('securityPolicyId' => $_POST['securitypolicy']));

	} else {

		// URL request was not successful, return error code + message
		http_response_code(404);
		echo 'Error: Could not retrieve upload URL<br>';
	}

} else {

	// Creating the new video asset was not successful, return error code + message
	http_response_code(404);
	echo 'Error: Could not create empty video asset<br>'.json_encode($entity);
}
