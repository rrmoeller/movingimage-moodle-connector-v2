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
 * movingimage video filter plugin.
 *
 * @package    filter_movingimageembed
 * @copyright  2017 Rainer M�ller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

  // Provide text input for movingimage video base URL
  $settings->add(new admin_setting_configtext('filter_movingimageembed/movingimageURL',
 				         get_string('movingimageURL', 'filter_movingimageembed'),
                 get_string('movingimageURL_desc', 'filter_movingimageembed'),
				         'https://e.video-cdn.net/video',
                 PARAM_RAW_TRIMMED));

  // Provide dropdown selection for embedding method (iFrame or div)
  $settings->add(new admin_setting_configselect('filter_movingimageembed/embedstyle',
				         get_string('embedstyle', 'filter_movingimageembed'),
                 get_string('embedstyle_desc', 'filter_movingimageembed'),
				         '1',
        				 array(
        				 	 '0' => get_string('div', 'filter_movingimageembed'),
        				 	 '1' => get_string('iframe', 'filter_movingimageembed')
        				 )));

  // Provide numeric input for player width
  $settings->add(new admin_setting_configtext('filter_movingimageembed/playerwidth',
				         get_string('playerwidth', 'filter_movingimageembed'),
                 get_string('playerwidth_desc', 'filter_movingimageembed'),
			           '640',
				         PARAM_RAW_TRIMMED));

  // Provide numeric input for player height
  $settings->add(new admin_setting_configtext('filter_movingimageembed/playerheight',
				         get_string('playerheight', 'filter_movingimageembed'),
                 get_string('playerheight_desc', 'filter_movingimageembed'),
				         '360',
				         PARAM_RAW_TRIMMED));

  // Provide masked text input for shared secret for player token calculation
  $settings->add(new admin_setting_configpasswordunmask('filter_movingimageembed/secret',
				         get_string('secret', 'filter_movingimageembed'),
                 get_string('secret_desc', 'filter_movingimageembed'),
				         '',
				         PARAM_RAW_TRIMMED));
}
