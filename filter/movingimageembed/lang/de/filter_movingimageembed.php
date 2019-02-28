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
 * movingimage Video filter plugin.
 *
 * @package    filter_movingimageembed
 * @copyright  2017 Rainer M�ller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['filtername'] = 'movingimage Video-Einbettung';
$string['filtername_help'] = 'Ersetze movingimage Video-URL durch eingebetteten Video-Player';
$string['configfilter'] = 'Konfiguration von movingimage Video Filter';
$string['embedstyle'] = 'Einbettungs-Methode:';
$string['embedstyle_desc'] = 'Definieren Sie die Einbettungs-Methode von Videos in Moodle. Einbettungen per "div" können zu JavaScript-Konflikten führen.';
$string['iframe'] = 'iFrame';
$string['div'] = '<div>-Element';
$string['playerwidth'] = 'Breite';
$string['playerwidth_desc'] = 'Breite des Players in Pixeln (0 = automatisch)';
$string['playerheight'] = 'Höhe';
$string['playerheight_desc'] = 'Höhe des Players in Pixeln (0 = automatisch)';
$string['movingimageURL'] = 'URL';
$string['movingimageURL_desc'] = 'Base-URL der movingimage Video-Links, z.B. https://e.video-cdn.net/video';
$string['secret'] = 'Shared-Secret';
$string['secret_desc'] = 'Shared-Secret für tokengeschützte Videoauslieferung (optional). Nur verfügbar bei Einbettung als "div"-Element';
