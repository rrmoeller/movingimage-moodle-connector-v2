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
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
if (!class_exists('VideoManagerPro'))
	require_once($CFG->dirroot . '/repository/movingimagepicker/classes/vmpro.php');


class repository_movingimageupload extends repository {

  // object for movingimage API connection
	private $service = null;


	// create a VMPro instance on repository constrcut
  public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
    parent::__construct($repositoryid, $context, $options, $readonly = 0);
    $this->service = new VideoManagerPro();
  }


	// Helper function to route option requests to the correct repository
	// (connection settings are stored in the repository "movingimagepicker" only)
  public function get_movingimage_option($config = '') {
		if (in_array($config,self::get_type_option_names()))
			return trim(get_config('movingimageupload', $config));
		else
			return trim(get_config('movingimagepicker', $config));
  }


	// we only serve videos
  public function supported_filetypes() {
    return array('video');
  }


	// we only serve external links
  public function supported_returntypes() {
    return FILE_EXTERNAL;
  }


	// list of all option parameters defined by this repository
	// (connection settings are defined in the repository "movingimagepicker" only)
	public static function get_type_option_names() {
    return array('pluginname','deletiondays','uploadrootchannel','securitypolicyid','coursefield','namefield','emailfield');
  }


  // create config form
  public static function type_config_form($mform, $classname = 'repository') {
    parent::type_config_form($mform);

		// Guide user where to find connection setting
		$mform->addElement('static', 'description', get_string('connection_label', 'repository_movingimageupload'),get_string('connection_text', 'repository_movingimageupload'));

    // Create new movingimage API instance and try to log in
		$vmpro = new VideoManagerPro();
		if ($vmpro->login(get_config('movingimagepicker', 'login'), get_config('movingimagepicker', 'password'), get_config('movingimagepicker', 'vmproid'))) {

			// Get available video security policies from API and list them in an array
			$options_policies = ['0' => '** unused **'];
			$policies = $vmpro->getSecurityPolicies();
			foreach ($policies as $key => $policy)
				$options_policies[$key] = $policy['name'];

			// Get available video custom metadata fields from API and list them in an array
			$options_metadata = ['0' => '** unused **'];
			$metadata = $vmpro->getCustomMetadataFields();
			foreach ($metadata as $meta)
				$options_metadata[$meta["keyName"]] = $meta["keyName"];

			// Store that we have a valid API conection available, options can be provided as dropdowns
			$validVMPro = true;

		}	else {

			// Store that we do not have a valid API conection available, options need to be provided as ID number or plain text
			$validVMPro = false;

		}

		// Provide video security options either as dropdown or as numeric ID
		if ($validVMPro) {
			$mform->addElement('select', 'securitypolicyid', get_string('securitypolicyid', 'repository_movingimageupload'),$options_policies);
		} else {
			$mform->addElement('text', 'securitypolicyid', get_string('securitypolicyid', 'repository_movingimageupload'));
	    $mform->setType('securitypolicyid', PARAM_INT);
			$mform->addRule('securitypolicyid', null, 'numeric', null, 'client');
		}

		// Provide checkbox for video uload to root channel option
		$mform->addElement('advcheckbox', 'uploadrootchannel', get_string('uploadrootchannel', 'repository_movingimageupload'),'',[],array(0,1));
    $mform->setType('uploadrootchannel', PARAM_INT);
		$mform->setDefault('uploadrootchannel', 0);

		// Provide numeric input field for number of days before auto-deletion of uploaded videos (0 = no auto-deletion)
		$mform->addElement('text', 'deletiondays', get_string('deletiondays', 'repository_movingimageupload'));
    $mform->setType('deletiondays', PARAM_INT);
		$mform->addRule('deletiondays', null, 'numeric', null, 'client');
		$mform->setDefault('deletiondays', 0);

		// Provide video custom metadata field for Moodle course name either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'coursefield', get_string('coursefield', 'repository_movingimageupload'),$options_metadata);
		} else {
			$mform->addElement('text', 'coursefield', get_string('coursefield', 'repository_movingimageupload'));
	    $mform->setType('coursefield', PARAM_RAW_TRIMMED);
		}

		// Provide video custom metadata field for Moodle course author name either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'namefield', get_string('namefield', 'repository_movingimageupload'),$options_metadata);
		} else {
			$mform->addElement('text', 'namefield', get_string('namefield', 'repository_movingimageupload'));
	    $mform->setType('namefield', PARAM_RAW_TRIMMED);
		}

		// Provide video custom metadata field for Moodle course author emal address either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'emailfield', get_string('emailfield', 'repository_movingimageupload'),$options_metadata);
		} else {
			$mform->addElement('text', 'emailfield', get_string('emailfield', 'repository_movingimageupload'));
	    $mform->setType('emailfield', PARAM_RAW_TRIMMED);
		}
	}


  // Validate inputs in config form
	public static function type_form_validation($mform, $data, $errors) {

		// Create new movingimage API instance and try to log in
		$vmpro = new VideoManagerPro();
		if (!$vmpro->login(get_config('movingimagepicker','login'), get_config('movingimagepicker','password'), get_config('movingimagepicker','vmproid'))) {

			// Output login error
			// Note: The connection parameters are configured in the repository plugin "movingimagepicker". But as we are dependant on a
			//       successful connection to the movingimage API, we check the dependcy in this form again.
      $errors['login'] = $errors['password'] = $errors['vmproid'] = get_string('admin_login_error', 'repository_movingimageupload');
			return $errors;
		}

		// Check if security policy ID is set to invalid ID and output error message
		if (!empty($data['securitypolicyid'])) {
			$securitypolicies = $vmpro->getSecurityPolicies();
			if (!isset($securitypolicies[$data['securitypolicyid']]))
				$errors['securitypolicyid'] = get_string('config_policy_error', 'repository_movingimageupload');
		}

		// Check if any of the custom metadata fields is set to invalid ID
		$custommetadata = $vmpro->getCustomMetadataFields();
		$check = ['coursefield' => false,
							'namefield'   => false,
							'emailfield'  => false];

		// Iterate through all three options: Course Name, Author Name, Author Email
		foreach ($check as $k => $c) {
			if (!empty($data[$k])) {

				// Iterate through all available metadata fields in movingimage account
				foreach ($custommetadata as $m)
					if ($data[$k] == $m["keyName"])
						$check[$k] = true;

				// Output error message if any parameter ID failes
			  if (!$check[$k])
			    $errors[$k] = get_string('config_metadata_error', 'repository_movingimageupload');
			}
		}

		// Report errors
		return $errors;
  }


  // Display SSO login form
  public function print_login() {

		// AJAX available?
		if ($this->options['ajax']) {

		  // Create new window popup array
			$ret = array();
			$btn = new \stdClass();
			$btn->type = 'popup';

			// Create and assign window popup URL and SSO redirect URL
			$callback_url = new moodle_url('/repository/repository_callback.php');
			$auth_url = new moodle_url('/repository/movingimagepicker/keycloak.html', [
			              'callback'  => 'yes',
			              'repo_id'   => $this->id,
			              'sesskey'   => sesskey(),
										'redirect'  => $callback_url->out(),
										'client'    => $this->get_movingimage_option('client'),
										'idp'       => $this->get_movingimage_option('idphint')
			          ]);
			$btn->url = $auth_url->out(false);

			// Store config array for window popup and return
			$ret['login'] = array($btn);
			return $ret;

		} else {

			// Use conventional HTML output
			echo html_writer::link($url, get_string('login', 'repository'), array('target' => '_blank'));

		}
	}


  // Log out user by removing the access token
	public function logout() {
		global $SESSION;

		// Remove the token from the movingimage API instance
		$this->service->setAccessToken($this->get_movingimage_option('vmproid'),'');

		// Remove the token from the session variables and return
		unset($SESSION->miAccessToken);
		return $this->print_login();
	}


  // Check if user is displayed as already logged in
	public function check_login() {
	  global $SESSION;

		// User is always displayed as logged in when using technical user connection only
		if ($this->get_movingimage_option('sso') == 0)
			return true;

	 // Check if no access token is available in session variables
		if (empty($SESSION->miAccessToken)) {

			// not logged in
			return false;

		} else {

			// Logged in status equals validity of access token
	  	return $this->service->tryAccessToken($this->get_movingimage_option('vmproid'),$SESSION->miAccessToken);
		}
	}


  // Callback function for SSO redirect handling
	public function callback() {
		global $SESSION;

		// Store access token sent by SSO popup window in session variable
  	$SESSION->miAccessToken = optional_param('mitoken','',PARAM_TEXT);
	}


  // Helper function: Get access token depending on SSO option
	public function getMiAccessToken() {
		global $SESSION;

		// If access token is already available
		if (!empty($SESSION->miAccessToken)) {

			// Try to log in with existing token
			if ($this->service->tryAccessToken($this->get_movingimage_option('vmproid'),$SESSION->miAccessToken))

				// Login succeeded
				return true;
		}

		// If SSO option is not set, log in with technical user credentials, store token and return
		if ($this->get_movingimage_option('sso') == 0) {
			if ($this->service->login($this->get_movingimage_option('login'),
															  $this->get_movingimage_option('password'),
																$this->get_movingimage_option('vmproid'))) {
				$SESSION->miAccessToken	= $this->service->getAccessToken();
				return true;
			}
		}

		// No token available, SSO log in required
		return false;
	}


  // Helper function: Ensure that user, group and channel exist before logging in
	//                  Auto create non-existent user, group and channel if required in the confoig settings
	public function checkUserIDandCreateIfNeeded ($userEmail) {

		// Start with user and group unknown
		$userID = $groupID = 0;

		// Create movingimage EVP instance for admin access and try to log in
		$adminVMPro = new VideoManagerPro();
		if (!$adminVMPro->login($this->get_movingimage_option('login'),
				 						  			$this->get_movingimage_option('password'),
														$this->get_movingimage_option('vmproid')))
			throw new moodle_exception('apierror-login', 'repository_movingimageupload', get_string('admin_login_error', 'repository_movingimageupload'), '');

		// Try to find user
		$userID = $adminVMPro->getUserIDByEmail($userEmail);
		// If user is not available, auto-create user if config settings require to do so
		if ($userID == 0  && $this->get_movingimage_option('autocreateuser') == 1)
			$adminVMPro->createSAMLGroupAndRole($userEmail,$adminVMPro->getDefaultGroup(),$this->get_movingimage_option('usercompanyrole'));

		// If user could be created successfully and config settings require to auto-create a content group for user
		if ($this->get_movingimage_option('autocreategroup') == 1) {

			// Try to find user content group
			$groupID = $adminVMPro->getGroupIDByName($userEmail);

			// If user content group is not available, auto-create content group
			if ($groupID == 0)
				$groupID = $adminVMPro->createGroup($userEmail);

			// Add user to his content group
			$adminVMPro->createSAMLGroupAndRole($userEmail,$groupID,$this->get_movingimage_option('usergrouprole'));

			// Identify admin and add admin to user content group as well
			$adminID = $adminVMPro->getUserIDByEmail($this->get_movingimage_option('login'));
			$adminVMPro->addUserToGroup($adminID, $groupID, $this->get_movingimage_option('adminrole'));
		}

		// If user could be created successfully and config settings require to auto-create a video channel for user
		if ($this->get_movingimage_option('autocreatechannel') == 1) {

			// Try to find user video channel
			$channelID = $adminVMPro->getChannelIDByName($userEmail);

			// If user video channel is not available, auto-create user video channel
			if ($channelID == 0) {
    		$channelID = $adminVMPro->createChannel($userEmail, $adminVMPro->getRootChannelID(), $groupID);
			}

		}
		return;
	}


  // Display content of repository window (display upload form)
  public function get_listing($path = '', $page = '') {
		global $SESSION;
		global $USER;

		// If SSO is enabled, make sure user, group and channel exist before trying to log in
		if ($this->get_movingimage_option('sso') == 1)
		  $this->checkUserIDandCreateIfNeeded($USER->email);

    // If we do not have or get a valid access token
		if (!$this->getMiAccessToken()) {

			// Require login if SSO is used or throw error message if technical user cannot log in
			if ($this->get_movingimage_option('sso') == 1)
				return $this->print_login();
			else
				throw new moodle_exception('apierror-login', 'repository_movingimageupload', get_string('admin_login_error', 'repository_movingimageupload'), '');
		}

    // Get correct Moodle context ID and create URL for upload form frame
    $contextid = $this->context;
    if (is_object($contextid)) {
      $contextid = $contextid->id;
    }
    $url = new moodle_url('/repository/movingimageupload/uploadform.php',
                            array('type' => 'init',
                                'repositoryid' => $this->id,
                                'contextid' => $contextid,
                                'sesskey' => sesskey(),
                                'mitoken' => $SESSION->miAccessToken));

    // Create listing array options to handover upload form frame URL
    $list = array();
    $list['object']         = array();
    $list['object']['type'] = 'text/html';
    $list['object']['src']  = $url->out(false);
    $list['nosearch']       = true;
    $list['norefresh']      = true;
		$list['manage']         = $this->get_movingimage_option('vmprologin');
		$list['nologin']        = ($this->get_movingimage_option('sso') == 0);
    return $list;
  }
}
