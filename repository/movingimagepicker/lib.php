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
 * This plugin is used to access movingimage videos
 *
 * @package    repository_movingimagepicker v2
 * @copyright  2019 Rainer Möller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// include all required Moodle and movingimage libs
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
if (!class_exists('VideoManagerPro'))
	require_once($CFG->dirroot . '/repository/movingimagepicker/classes/vmpro.php');



class repository_movingimagepicker extends repository {

  const movingimage_THUMBS_PER_PAGE = 12;

	// object for movingimage API connection
	private $service = null;


	// create a VMPro instance on repository constrcut
	public function __construct($repositoryid, $context = SYSCONTEXTID, $options = []) {
    $options['page'] = optional_param('p', 1, PARAM_INT);
    parent::__construct($repositoryid, $context, $options);
    $this->service = new VideoManagerPro();
  }


	// Helper function to route option requests to the correct repository
  public function get_movingimage_option($config = '') {
		return trim(get_config('movingimagepicker', $config));
  }


	// There is no global search for us
  public function global_search() {
    return false;
  }


	// we only serve videos
  public function supported_filetypes() {
    return array('video');
  }


	// we only serve external links
  public function supported_returntypes() {
    return FILE_EXTERNAL;
  }


	// videos can contain private data like names, faces etc.
  public function contains_private_data() {
    return true;
  }


	// list of all option parameters defined by this repository
  public static function get_type_option_names() {
    return array('pluginname','vmproid', 'login', 'password', 'playerid', 'sortby','sortasc','sso','idphint','client',
		             'vmprologin','autocreateuser','usercompanyrole','adminrole','autocreategroup','autocreatechannel','usergrouprole','rootchannel');
  }


	// create config form
  public static function type_config_form($mform, $classname = 'repository') {
    parent::type_config_form($mform);

		// Create new movingimage API instance and try to log in
		$vmpro = new VideoManagerPro();
		if ($vmpro->login(get_config('movingimagepicker', 'login'), get_config('movingimagepicker', 'password'), get_config('movingimagepicker', 'vmproid')) === true) {

			// Get available user roles from API and list them in an array
			$options_roles = ['0' => '** unused **'];
			$roles = $vmpro->getRoles();
			foreach ($roles as $key => $role)
				$options_roles[$key] = $role['name'];

			// Store that we have a valid API conection available, options can be provided as dropdowns
			$validVMPro = true;

		}	else {

			// Store that we do not have a valid API conection available, options need to be provided as ID number
			$validVMPro = false;

		}

		// Provide text input for movingimage EVP admin credentials
    $mform->addElement('text', 'login', get_string('login', 'repository_movingimagepicker'));
    $mform->setType('login', PARAM_RAW_TRIMMED);
		$mform->addRule('login', null, 'required', null, 'client');

		// Provide masked text input for movingimage EVP admin password
    $mform->addElement('password', 'password', get_string('password', 'repository_movingimagepicker'));
    $mform->setType('password', PARAM_RAW_TRIMMED);
		$mform->addRule('password', null, 'required', null, 'client');

		// Provide numeric input for movingimage EVP account ID
    $mform->addElement('text', 'vmproid', get_string('vmproid', 'repository_movingimagepicker'));
    $mform->setType('vmproid', PARAM_INT);
		$mform->addRule('vmproid', null, 'required', null, 'client');
		$mform->addRule('vmproid', null, 'numeric', null, 'client');

		// Provide text input for player ID
    $mform->addElement('text', 'playerid', get_string('playerid', 'repository_movingimagepicker'));
    $mform->setType('playerid', PARAM_RAW_TRIMMED);
		$mform->addRule('playerid', null, 'required', null, 'client');

		// Provide numeric input for video root channel ID
		$mform->addElement('text', 'rootchannel', get_string('rootchannel', 'repository_movingimagepicker'));
    $mform->setType('rootchannel', PARAM_INT);
		$mform->addRule('rootchannel', null, 'numeric', null, 'client');
		$mform->setDefault('rootchannel', 0);

		// Provide text input for sorting field name
    $mform->addElement('text', 'sortby', get_string('sortby', 'repository_movingimagepicker'));
    $mform->setType('sortby', PARAM_RAW_TRIMMED);

		// Provide checkbox for option to sort videos ascending
		$mform->addElement('advcheckbox', 'sortasc', get_string('sortasc', 'repository_movingimagepicker'),'',[],array(0,1));
		$mform->setType('sortasc', PARAM_INT);
    $mform->setDefault('sortasc', 1);

		// Provide text input for login URL for VideoManager Pro
		$mform->addElement('text', 'vmprologin', get_string('vmprologin', 'repository_movingimagepicker'));
    $mform->setType('vmprologin', PARAM_RAW_TRIMMED);
		$mform->setDefault('vmprologin', 'https://vmpro.movingimage.com');

		// Provide checkbox for enabling SSO login
		$mform->addElement('advcheckbox', 'sso', get_string('sso', 'repository_movingimagepicker'),'',[],array(0,1));
		$mform->setType('sso', PARAM_INT);
    $mform->setDefault('sso', 0);

		// Provide text input for SSO IDP hint
		$mform->addElement('text', 'idphint', get_string('idphint', 'repository_movingimagepicker'));
    $mform->setType('idphint', PARAM_RAW_TRIMMED);
		$mform->disabledIf('idphint','sso');

		// Provide text input for SSO client name
		$mform->addElement('text', 'client', get_string('client', 'repository_movingimagepicker'));
    $mform->setType('client', PARAM_RAW_TRIMMED);
		$mform->disabledIf('client','sso');

		// Provide checkbox for auto-creation of non-existent users on SSO login
		$mform->addElement('advcheckbox', 'autocreateuser', get_string('autocreateuser', 'repository_movingimagepicker'),'',[],array(0,1));
		$mform->setType('autocreateuser', PARAM_INT);
    $mform->setDefault('autocreateuser', 0);
		$mform->disabledIf('autocreateuser','sso');

		// Provide user role in company content group for non-existent on SSO login either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'usercompanyrole', get_string('usercompanyrole', 'repository_movingimagepicker'),$options_roles);
		} else {
			$mform->addElement('text', 'usercompanyrole', get_string('usercompanyrole', 'repository_movingimagepicker'));
	    $mform->setType('usercompanyrole', PARAM_INT);
			$mform->addRule('usercompanyrole', null, 'numeric', null, 'client');
		}
		$mform->disabledIf('usercompanyrole','autocreateuser');
		$mform->disabledIf('usercompanyrole','sso');

		// Provide checkbox for auto-creation of non-existent user channels on SSO login
		$mform->addElement('advcheckbox', 'autocreatechannel', get_string('autocreatechannel', 'repository_movingimagepicker'),'',[],array(0,1));
		$mform->setType('autocreatechannel', PARAM_INT);
    $mform->setDefault('autocreatechannel', 0);
		$mform->disabledIf('autocreatechannel','autocreateuser');
		$mform->disabledIf('autocreatechannel','sso');

		// Provide checkbox for auto-creation of non-existent user content groups on SSO login
		$mform->addElement('advcheckbox', 'autocreategroup', get_string('autocreategroup', 'repository_movingimagepicker'),'',[],array(0,1));
		$mform->setType('autocreategroup', PARAM_INT);
    $mform->setDefault('autocreategroup', 0);
		$mform->disabledIf('autocreategroup','autocreateuser');
		$mform->disabledIf('autocreategroup','sso');

		// Provide user role in user content group for non-existent on SSO login either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'usergrouprole', get_string('usergrouprole', 'repository_movingimagepicker'),$options_roles);
		} else {
			$mform->addElement('text', 'usergrouprole', get_string('usergrouprole', 'repository_movingimagepicker'));
	    $mform->setType('usergrouprole', PARAM_INT);
			$mform->addRule('usergrouprole', null, 'numeric', null, 'client');
		}
		$mform->disabledIf('usergrouprole','autocreategroup');
		$mform->disabledIf('usergrouprole','autocreateuser');
		$mform->disabledIf('usergrouprole','sso');

		// Provide admin role in user content group for non-existent on SSO login either as dropdown or as text input
		if ($validVMPro) {
			$mform->addElement('select', 'adminrole', get_string('adminrole', 'repository_movingimagepicker'),$options_roles);
		} else {
			$mform->addElement('text', 'adminrole', get_string('adminrole', 'repository_movingimagepicker'));
	    $mform->setType('adminrole', PARAM_INT);
			$mform->addRule('adminrole', null, 'numeric', null, 'client');
		}
		$mform->disabledIf('adminrole','autocreategroup');
		$mform->disabledIf('adminrole','autocreateuser');
		$mform->disabledIf('adminrole','sso');
	}


	// Validate inputs in config form
	public static function type_form_validation($mform, $data, $errors) {

		// Create new movingimage API instance and try to log in
		$vmpro = new VideoManagerPro();
		if (!$vmpro->login($data['login'], $data['password'], $data['vmproid'])) {

			// Output login error
      $errors['login'] = $errors['password'] = $errors['vmproid'] = get_string('admin_login_error', 'repository_movingimagepicker');
			return $errors;
		}

		// Check if player ID is set to invalid ID and output error message
		$players = $vmpro->getPlayers();
		if (!isset($players[$data['playerid']]))
			$errors['playerid'] = get_string('config_player_error', 'repository_movingimagepicker');

	  // Check if root channel ID is set to invalid ID and output error message
		if ($data['rootchannel'] != 0 && $data['rootchannel'] != null) {
			$result = $vmpro->getChannels($data['rootchannel']);
			if (!is_array($result) || !isset($result['id']) )
				$errors['rootchannel'] = get_string('config_channel_error', 'repository_movingimagepicker');
		}

		// Check if VMPro login URL is set to invalid URL and output error message
		if(filter_var($data['vmprologin'], FILTER_VALIDATE_URL) === FALSE)
			$errors['vmprologin'] = 'Invalid VMPro login URL';

		// Check if IDP hint and client name are not empty if SSO is enabled
		if ($data['sso'] == 1) {
			if (empty($data['idphint']))
				$errors['idphint'] = get_string('config_idphint_error', 'repository_movingimagepicker');
			if (empty($data['client']))
				$errors['client'] = get_string('config_client_error', 'repository_movingimagepicker');
		}

		// Get list of available user roles in movingmage EVP
		$roles = $vmpro->getRoles();

		// Check if user role ID in company content group is set to invalid ID and output error message
		if ($data['autocreateuser'] == 1) {
			if (!isset($roles[$data['usercompanyrole']]))
				$errors['usercompanyrole'] = get_string('config_role_error', 'repository_movingimagepicker');
		}

		// Check if user role ID and admin role ID in user content group is set to invalid ID and output error message
		if ($data['autocreategroup'] == 1) {
			if (!isset($roles[$data['usergrouprole']]))
				$errors['usergrouprole'] = get_string('config_role_error', 'repository_movingimagepicker');
			if (!isset($roles[$data['adminrole']]))
				$errors['adminrole'] = get_string('config_role_error', 'repository_movingimagepicker');
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
			$btn->size = array('width' => 600, 'height' => 800);

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
		return $userID;
	}


	// Display content of repository window (display upload form)
  public function get_listing($path = '', $page = '') {
    global $OUTPUT;
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
				throw new moodle_exception('apierror-login', 'repository_movingimagepicker', get_string('admin_login_error', 'repository_movingimagepicker'), '');
		}

		// Extract single path components
		$pathargs = explode('/',$path);
		$pathtext = array(array('name' => 'movingimage', 'path' => ''));
		$breadcrumb = '';

		// Substitute video channel IDs by real channel names, building a human readable breadcrumb navigation
		for ($i = 1; $i < count($pathargs); $i++)
			$pathtext[] = array('name' => $SESSION->{'channel_'.$pathargs[$i]}, 'path' => $breadcrumb .= '/'.$pathargs[$i]);

		// Set actual video channel ID either to last ID in the path or to the root channel ID if no path is available yet
		if (count($pathargs) > 1)
			$channelid = end($pathargs);
		else
			$channelid = $this->get_movingimage_option('rootchannel');

		// Get list of all video channels from movingimage API and sort them by name
		$subchannels = $this->service->getChannels($channelid);
		uasort($subchannels['children'], function ($a, $b) { return strcmp($a['name'], $b['name']); } );

		// Call helper function to get video list
		$ret = self::_get_collection($channelid,$page);

		// If we are still on the first display page, display the video channel list on top of the videos
		if ($page < 2)	{

			// Create an array that contains all video subchannels
			$folders = array();
			foreach ($subchannels['children'] as $channel) {
				$folders[] = [
							'title'             => $channel['name'],
							'path'				=> $path.'/'.$channel['id'],
							'thumbnail'         => $OUTPUT->pix_url(file_folder_icon(90))->out(false),
							'thumbnail_width'   => 160,
							'thumbnail_height'  => 90,
							'thumbnail_title'   => 'abcdefg',
							'children'          => array()
						];

				// Save the video channel name to a session object for later displaying the correct breadcrumb path
				$subchannelid = $channel['id'];
				$SESSION->{'channel_'.$subchannelid} = $channel['name'];
			}

			// Merge the video channel list together with the video list
			$ret['list'] = array_merge($folders,$ret['list']);
		}

		// Create listing array options to handover content list
    $ret['dynload'] = true;
		$ret['path'] = $pathtext;
		$ret['manage'] = $this->get_movingimage_option('vmprologin');
		$ret['nologin'] = ($this->get_movingimage_option('sso') == 0);
    return $ret;
  }


  // Search for a video via fulltext search
  public function search($searchtext, $page = 0) {
		global $OUTPUT;
		global $SESSION;

		// If we do not have or get a valid access token
		if (!$this->getMiAccessToken()) {

			// Require login if SSO is used or throw error message if technical user cannot log in
			if ($this->get_movingimage_option('sso') == 1)
				return $this->print_login();
			else
				throw new moodle_exception('apierror-login', 'repository_movingimagepicker', get_string('admin_login_error', 'repository_movingimagepicker'), '');
		}

		// Create listing array options to handover search results, received from helper function _get_collection
		$ret = array();
    $ret = self::_get_collection(0, $page, $searchtext);
    $ret['dynload'] = true;
		$ret['manage'] = $this->get_movingimage_option('vmprologin');
		$ret['nologin'] = true;
		$ret['issearchresult'] = true;
    return $ret;
  }


  // Helper function to get video list from movingimage API
  private function _get_collection($channelid, $page = '', $search = '', $folders = 0) {
		global $SESSION;

		// Create return array and set correct page no.
    $ret = array();
		$ret['page'] = (int)$page;
    if ($ret['page'] < 1) {
      $ret['page'] = 1;
    }

		// Set number of videos per page and calculate starting video offset
    $max = self::movingimage_THUMBS_PER_PAGE;
		$ofs = ($ret['page'] - 1) * $max;
    $results = array();

		// Check if access token is available
    if (!empty($SESSION->miAccessToken)) {

			// Get video list from movingimage API
			$videos = $this->service->getVideos($channelid, $max, $ofs, $search, $this->get_movingimage_option('sortby'), false);

			// Check if video list is not empty and valid
			if (is_array($videos) && isset ($videos['videos'])) {

				// Display videos if not in root channel or if root channel is allowed or if in search results
				if ($channelid != $this->get_movingimage_option('rootchannel') || $this->get_movingimage_option('uploadrootchannel') == 1 || $search != '') {

					// Create an array that contains all videos
					foreach ($videos['videos'] as $video) {
						$results[] = array(
								'title' => pathinfo($video['title'], PATHINFO_FILENAME).'.wmv',
								'thumbnail' => $video['thumbnail'],
								'thumbnail_width' => 160,
								'thumbnail_height' => 90,
								'thumbnail_title'   => 'abcdefg',
								'size' => $video['length'] * 400,
								'date' => $video['createdDate'] / 1000,
								'datecreated' => $video['createdDate'] / 1000,
								'datemodified' => $video['modifiedDate'] / 1000,
								'source' => 'https://e.video-cdn.net/video?video-id='.$video['id'].'&player-id='.$this->get_movingimage_option('playerid')
						);
					}

					// Calculate number of remaining pages for further videos
					$ret['pages'] = (int) floor($videos['total'] / $max) + ($videos['total'] % $max > 0 ? 1 : 0);
				}
			} else {

				// movingimage returned empty video list or error, throw error and return
				throw new moodle_exception('apierror-videolist', 'repository_movingimagepicker', get_string('videolist_error', 'repository_movingimagepicker'), '');
				$ret['list'] = [];
				return $ret;

			}
    } else {

			// No access token available, throw error and return
			throw new moodle_exception('apierror-login', 'repository_movingimagepicker', get_string('admin_login_error', 'repository_movingimagepicker'), '');
			$ret['list'] = [];
      return $ret;

    }

    // return video list
		$ret['list'] = $results;
    return $ret;
  }

}
