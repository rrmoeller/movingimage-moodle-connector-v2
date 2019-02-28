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

$string['filtername'] = 'movingimage video embedding';
$string['filtername_help'] = 'Substitute movingimage video URLs to embed video players';
$string['configfilter'] = 'Configuration for movingimage video filter';
$string['embedstyle'] = 'Embed method:';
$string['embedstyle_desc'] = 'Specify how the video player shall be embedded into your Moodle elements. Embedding via "div" can cause JavaScript conflicts.';
$string['iframe'] = 'iFrame';
$string['div'] = '<div> element';
$string['playerwidth'] = 'Width';
$string['playerwidth_desc'] = 'Width of player in px (0 = automatic)';
$string['playerheight'] = 'Height';
$string['playerheight_desc'] = 'Height of player in px (0 = automatic)';
$string['movingimageURL'] = 'URL';
$string['movingimageURL_desc'] = 'Base URL of movingimage video links, e.g. https://e.video-cdn.net/video';
$string['secret'] = 'Shared secret';
$string['secret_desc'] = 'Shared secret for token protected video delivery (optional). Only available for embedding via "div" element';
