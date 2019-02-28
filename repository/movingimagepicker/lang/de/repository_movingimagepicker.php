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

$string['configplugin'] = 'Konfiguration für movingimage Video-Picker Repository';
$string['movingimagepicker:view'] = 'View movingimage Video-Picker Repository';
$string['pluginname_help'] = 'Pick movingimage Videos';
$string['pluginname'] = 'movingimage Video-Picker';
$string['search'] = 'Suche';

$string['vmproid'] = 'VMPro-ID Ihres movingimage-Accounts ';
$string['playerid'] = 'Player-ID für Videoeinbettung';
$string['rootchannel'] = 'Root-Channel-ID für Moodle-Videos im movingimage EVP-Account';
$string['login'] = 'Admin-Username in movingimage EVP';
$string['password'] = 'Admin-Password für autorisierten Zugriff';
$string['adminrole'] = 'Admin-Rollen-ID in movingimage EVP';
$string['autocreateuser'] = 'Auto-Erstellen nicht-existenter Benutzer in movingimage EVP';
$string['usercompanyrole'] = 'Benutzer-Rollen-ID in Unternehmens-Gruppe in movingimage EVP';
$string['autocreategroup'] = 'Auto-Erstellen von Content-Gruppen für neue movingimage-Benutzer';
$string['usergrouprole'] = 'Benutzer-Rollen-ID in Benutzer-Gruppe in movingimage EVP';
$string['autocreatechannel'] = 'Auto-Erstellen von Video-Channels für neue movingimage-Benutzer';
$string['vmprologin'] = 'Login-URL für movingimage VideoManager Pro';
$string['sso'] = 'Aktiviere SSO Authentifizierung für movingimage EVP';
$string['client'] = 'SSO Client-Nanem für movingimage Auth Service';
$string['idphint'] = 'SSO IDP-Hhint für movingimage Auth Service';
$string['sortby'] = 'Sortiere Videoliste nach "title", "createdDate", "modifiedDate", "views" or "plays"';
$string['sortasc'] = 'Sortiere Videoliste aufsteigend';

$string['admin_login_error'] = 'movingimage EVP-Login fehlgeschlagen - Überprüfen Sie Login/Password sowie VMPro-ID';
$string['config_player_error'] = 'Ungültiger Player ID für movingimage-Account';
$string['config_channel_error'] = 'Ungültige Root-Channel-ID für movingimage EVP';
$string['config_idphint_error'] = 'IDP-Hint muss definiert sein wenn SSO aktiviert ist';
$string['config_client_error'] = 'Client-Name muss definiert sein wenn SSO aktiviert ist';
$string['config_role_error'] = 'Ungültige Rollen-ID für movingimage EVP';
$string['videolist_error'] = 'Videoliste der movingimage EVP nicht abrufbar';
