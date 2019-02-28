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
 * repository_movingimageupload
 *
 * @package    repository_movingimageupload v2
 * @copyright  2019 Rainer Möller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['vmproid'] = 'VMPro ID of movingimage account ';
$string['playerid'] = 'Player ID for video embedding';
$string['rootchannel'] = 'Root channel ID for Moodle videos in movingimage EVP';
$string['login'] = 'Admin username to movingimage EVP';
$string['password'] = 'Admin password for authorized access';
$string['adminrole'] = 'Admin role ID in movingimage EVP';
$string['autocreateuser'] = 'Auto-create non-existent user in movingimage EVP';
$string['usercompanyrole'] = 'User role ID in company group in movingimage EVP';
$string['autocreategroup'] = 'Auto-create content group for new movingimage user';
$string['usergrouprole'] = 'User role ID in user group in movingimage EVP';
$string['autocreatechannel'] = 'Auto-create video channel for new movingimage user';
$string['vmprologin'] = 'Login URL to movingimage VideoManager Pro';
$string['sso'] = 'Use SSO authentication to connect to movingimage EVP';
$string['client'] = 'SSO client name for movingimage Auth Service';
$string['idphint'] = 'SSO IDP hint for movingimage Auth Service';
$string['sortby'] = 'Sort video list by "title", "createdDate", "modifiedDate", "views" or "plays"';
$string['sortasc'] = 'Sort video list ascending';

$string['configplugin'] = 'Configuration for movingimage video picker repository';
$string['movingimagepicker:view'] = 'View movingimage repository';
$string['pluginname_help'] = 'Pick movingimage Videos';
$string['pluginname'] = 'movingimage video picker';
$string['search'] = 'Search';

$string['admin_login_error'] = 'movingimage EVP login unsuccessful - check Login/Password & VMPro ID';
$string['config_player_error'] = 'Invalid player ID for movingimage account';
$string['config_channel_error'] = 'Invalid root channel ID for movingimage EVP';
$string['config_idphint_error'] = 'IDP hint must be defined when SSO is activated';
$string['config_client_error'] = 'Client name must be defined when SSO is activated';
$string['config_role_error'] = 'Invalid role ID for movingimage EVP';
$string['videolist_error'] = 'Could not get video list from movingimage EVP';
