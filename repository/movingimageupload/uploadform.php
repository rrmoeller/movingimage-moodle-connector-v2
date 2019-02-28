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

// include all required Moodle and movingimage libs
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
if (!class_exists('VideoManagerPro'))
	require_once($CFG->dirroot . '/repository/movingimagepicker/classes/vmpro.php');


// Helper function to route option requests to the correct repository
// (connection settings are stored in the repository "movingimagepicker" only)
function get_movingimage_option($config = '') {
	if (in_array($config,array('pluginname','deletiondays','uploadrootchannel','securitypolicyid','coursefield','namefield','emailfield')))
		return trim(get_config('movingimageupload', $config));
	else
		return trim(get_config('movingimagepicker', $config));
}
?>

<!--
 	PART ONE: HTML FORM
	Embedded upload form including all states (choose file, upload status, finished)
-->

<body>
	<link rel="stylesheet" type="text/css" href="../../theme/styles.php/boost/1502182850/all">
	<div class="fp-content card" style="height: 100%; width: 99%">
		<form action="" method="post" enctype="multipart/form-data">
		  <div class="fp-formset">

			<!-- Top row: Upload button on startup, file name and progress during upload -->

			<div class="form-group">
				<div class="row">
					<div class="col-xs-6" id="choosebutton">
						<label class="btn btn-primary btn-file">
							Choose file for upload<input type="file" style="display: none;" onchange="fileChange();" id="fileA">
						</label>
					</div>
					<div class="col-xs-6">
						<span style="display:none;" id="fileInfo"></span>
					</div>
					<div id="progressbar" style="display: none;" class="col-xs-6">
						<progress id="progress" style="margin-top:10px"></progress> <span id="percent"></span>
					</div>
				</div>
			</div>

			<!-- Form metadata: Hidden on startup, displayed when file is chosen -->

			<div id="metadata" style="display:none;">
				<div class="form-group col-xs-10">
					<label for="title" style="margin-bottom:0"><?php echo get_string('upload_title_input', 'repository_movingimageupload'); ?>:</label>
					<input type="text" class="form-control" id="title">
				</div>
				<div class="form-group col-xs-2">
					<label for="secsetting" style="margin-bottom:0"><?php echo get_string('upload_protected_input', 'repository_movingimageupload'); ?>:</label>
					<input type="checkbox" class="form-control" id="protection">
				</div>
				<div class="form-group col-xs-12">
					<label for="description" style="margin-bottom:0"><?php echo get_string('upload_description_input', 'repository_movingimageupload'); ?>:</label>
					<textarea rows="2" class="form-control" id="description"></textarea>
				</div>
				<div class="form-group col-xs-12">
					<label for="keywords" style="margin-bottom:0"><?php echo get_string('upload_keywords_input', 'repository_movingimageupload'); ?>:</label>
					<textarea rows="1" class="form-control" id="keywords"></textarea>
				</div>
				<div class="form-group col-xs-12">
					<label for="channel" style="margin-bottom:0"><?php echo get_string('upload_channel_input', 'repository_movingimageupload'); ?>:</label>
					<select class="form-control" id="channel">

						<!-- PHP insert: Dynamically create channel dropdown from movingimage API information -->

						<?php

						  // Create movimgimage API access instance and try to log in
						  $vmpro = new VideoManagerPro();
							if (!$vmpro->tryAccessToken(get_movingimage_option('vmproid'),optional_param('mitoken','',PARAM_TEXT)))
							  throw new moodle_exception('apierror-login', 'repository_movingimageupload', get_string('admin_login_error', 'repository_movingimageupload'), '');

							// Get list of video channels
							$api_channels = $vmpro->getChannels(get_movingimage_option('rootchannel'));
							$channels = array();

							// Recursive helper function to flatten channel array to one dimension for dropdown output, include space prefixes to illustrate channel depth
							function flattenChannelTree ($channel,$prefix = '') {
								global $channels;
								if ($prefix.$channel['name'] != 'root_channel' || get_movingimage_option('uploadrootchannel') == 1)
									$channels[] = array('id' => $channel['id'], 'name' => $prefix.($channel['name'] == 'root_channel' ? 'All videos' : $channel['name']));
								uasort($channel['children'], function ($a, $b) { return strcmp($a['name'], $b['name']); });
								foreach ($channel['children'] as $c)
									flattenChannelTree($c,$prefix.'&nbsp;&nbsp;&nbsp;&nbsp;');
							}

							// Execute helper function and output channels as dropdown options
							if (is_array($api_channels) && isset($api_channels["id"]))
							flattenChannelTree($api_channels);
							foreach ($channels as $key => $channel)
								echo '
											  <option value="'.$channel['id'].'" '.($key == 0 ? 'selected="selected"' : '').'>'.$channel['name'].'</option>';
						?>

					</select>
				</div>
			</div>
		  </div>
		</form>
		<div id="outtext"></div>

		<!-- Form action buttons: Hidden on startup, displayed once at a time depending on status -->

		<div class="mdl-align" id="uploadbutton" style="display: none;">
			<button class="btn-success btn" onclick="startUpload();"/><?php echo get_string('upload_start_button', 'repository_movingimageupload'); ?></button>
		</div>
		<div class="mdl-align" id="cancelbutton" style="display: none;">
			<button class="btn-danger btn" onclick="abortUpload();"/><?php echo get_string('upload_cancel_button', 'repository_movingimageupload'); ?></button>
		</div>
		<div class="mdl-align" id="redobutton" style="display: none;">
			<button class="btn-primary btn" onclick="resetForm();"/><?php echo get_string('upload_more_button', 'repository_movingimageupload'); ?></button>
		</div>
	</div>

	<script>

/*
   PART TWO: JavaScript form handling
	 Controlling display options of HTML elements, upload file to movingimage EVP, monitor upload progress
*/

	var client = null;

	function fileChange()
	{

		// Store file path once file is defined
	  var fileList = document.getElementById('fileA').files;
	  var file = fileList[0];
	  if (!file)
	      return;

		// Switch to "enter metadata" state once upload file is defined
	  document.getElementById('fileInfo').innerHTML = '<b>' + file.name + '</b><br><?php echo get_string('upload_filesize', 'repository_movingimageupload'); ?>: ' + (Math.round(file.size / 1024 / 1024 * 10)/10) + ' MB';
		document.getElementById('fileInfo').style.display = 'block';
		document.getElementById('uploadbutton').style.display = 'block';
		document.getElementById('metadata').style.display = 'block';
	  document.getElementById('progress').value = 0;
	  document.getElementById('percent').innerHTML = '0%';

	}

	function resetForm() {

		// Reset all form values for additional video upload
	  document.getElementById('fileA').value = '';
	  document.getElementById('fileInfo').innerHTML = '';
		document.getElementById('fileInfo').style.display = 'none';
		document.getElementById('uploadbutton').style.display = 'none';
		document.getElementById('metadata').style.display = 'none';
	  document.getElementById('progress').value = 0;
	  document.getElementById('percent').innerHTML = '0%';
		document.getElementById('cancelbutton').style.display = 'none';
		document.getElementById('redobutton').style.display = 'none';
		document.getElementById('title').disabled = false;
		document.getElementById('description').disabled = false;
		document.getElementById('keywords').disabled = false;
		document.getElementById('channel').disabled = false;
		document.getElementById('title').value = '';
		document.getElementById('description').value = '';
		document.getElementById('keywords').value = '';
		document.getElementById('choosebutton').style.display = 'block';
		document.getElementById('progressbar').style.display = 'none';

	}

	function abortUpload() {

		// Handle cancel events both for helper function and upload requests
	  if (client instanceof XMLHttpRequest)
	      client.abort();

		// Set display state to cancelled and offer re-uopload
		document.getElementById('percent').innerHTML += '<h4><span class="label" style="background-color:#d9534f; padding:10px 30px;"<?php echo get_string('upload_cancelled', 'repository_movingimageupload'); ?></span></h4>';
		document.getElementById('cancelbutton').style.display = 'none';
		document.getElementById('redobutton').style.display = 'block';

	}

	function startUpload()
	{

		// set form display options to uploading state and disable form input fields
		document.getElementById('choosebutton').style.display = 'none';
		document.getElementById('progressbar').style.display = 'block';
		document.getElementById('uploadbutton').style.display = 'none';
		document.getElementById('cancelbutton').style.display = 'block';
		document.getElementById('title').disabled = true;
		document.getElementById('description').disabled = true;
		document.getElementById('keywords').disabled = true;
		document.getElementById('channel').disabled = true;

		// Initialize request and progress bar
	  var file = document.getElementById('fileA').files[0];
	  var req = new XMLHttpRequest();
	  var prog = document.getElementById('progress');

		// Cancel if file if not defined
	  if (!file)
	      return;

		req.onload = function(e) {
			result = this.responseText;     // save result of helper function createasset.php
			prog.value = 0;
			prog.max = 100;
			client = new XMLHttpRequest();  // the final upload request

			client.onerror = function(e) {

				// If upload process fails, display error message and log to console
				document.getElementById('percent').innerHTML += '<h4><span class="label" style="background-color:#d9534f; padding:10px 30px;"><?php echo get_string('upload_error', 'repository_movingimageupload'); ?></span></h4>';
				console.log('Error while uploading.');
			};

			client.onload = function(e) {

				// display upload success and allow another upload via re-do button
				document.getElementById('percent').innerHTML = '100%<h5><span class="label" style="background-color:#5cb85c; padding:5px 20px;"><?php echo get_string('upload_success', 'repository_movingimageupload'); ?></span></h5>';
				document.getElementById('cancelbutton').style.display = 'none';
				document.getElementById('redobutton').style.display = 'block';
				prog.value = prog.max;

			};

			client.upload.onprogress = function(e) {

				// display upload progress in percent
				var p = Math.round(100 / e.total * e.loaded);
				document.getElementById('progress').value = p;
				document.getElementById('percent').innerHTML = p + '%';

			};

			client.onabort = function(e) {

				// upload has been cancelled, log to console
				console.log('Upload canceled.');

			};

			// proceed if helper function createasset.php has returned a success code
			if (result.indexOf('http') >= 0) {

				// If the result of helper function createasset.php returned a URL, proceed with uploading the file to this URL (set headers, send file)
				client.open('POST', result);
				client.setRequestHeader('Content-Type', 'application/octet-stream');
				client.setRequestHeader('Mi24-Upload-Total-Chunks','1');
				client.setRequestHeader('Mi24-Upload-Current-Chunk','1');
				client.send(file);

			} else {

				// If the result of helper function createasset.php did NOT return a URL, display error message and re-do button)
				document.getElementById('percent').innerHTML += '<h4><span class="label" style="background-color:#d9534f; padding:10px 30px;"><?php echo get_string('upload_error', 'repository_movingimageupload'); ?></span></h4>'
					+ '<small>' + result + '</small>';
				document.getElementById('cancelbutton').style.display = 'none';
				document.getElementById('redobutton').style.display = 'block';

			}
		}

		req.onerror = function(e) {

			// helper function in createasset.php returned an error, display it in the status bar and log to console

			document.getElementById('percent').innerHTML += '<h4><span class="label" style="background-color:#d9534f; padding:10px 30px;"><?php echo get_string('upload_request_error', 'repository_movingimageupload'); ?></span></h4>';
			console.log('Error on upload request!');
		}

		// call createasset.php helper function to handle all movingimage API communication (create new video asset, get upload URL from API)
		// call will return upload URL for video file upload

		req.open('POST', 'createasset.php');
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

		// Set POST parameters for createasset.

		$params = [];
		$params['mitoken'] = '<?php echo optional_param('mitoken','',PARAM_TEXT); ?>';
		$params['vmpro'] = '<?php echo get_config('movingimagepicker', 'vmproid'); ?>';
		$params['filename'] = file.name;
		$params['title'] = document.getElementById('title').value;
		$params['description'] = document.getElementById('description').value;
		$params['channel'] = document.getElementById('channel').value;
		$params['keywords'] = document.getElementById('keywords').value.split(',');
		$params['coursename'] = '<?php echo $COURSE->fullname; ?>';
		$params['author'] = '<?php echo fullname($USER,true); ?>';
		$params['email'] = '<?php echo $USER->email; ?>';
		$params['group'] = '<?php echo (get_movingimage_option('sso') == 1 ? $vmpro->getGroupIDByName($USER->email) : 0); ?>';
		$params['deletiontimestamp'] = '<?php echo (get_movingimage_option('deletiondays') == 0 ? 0 :
		                                           (time() + get_movingimage_option('deletiondays') *60*60*24) * 1000); ?>';
		$params['securitypolicy'] = document.getElementById('protection').checked ? <?php echo get_movingimage_option('securitypolicyid'); ?> : 0;
		$params['coursefield'] = '<?php echo get_movingimage_option('coursefield'); ?>';
		$params['namefield'] = '<?php echo get_movingimage_option('namefield'); ?>';
		$params['emailfield'] = '<?php echo get_movingimage_option('emailfield'); ?>';

		// Encode all parameters and start POST requests

		req.send(Object.keys($params).reduce(function(a,k){a.push(k+'='+encodeURIComponent($params[k]));return a},[]).join('&'));

	}

	</script>
</body>
