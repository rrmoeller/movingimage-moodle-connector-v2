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
 * @copyright  2019 Rainer Möller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// include all required Moodle and movingimage libs
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/repository/lib.php');


class filter_movingimageembed extends moodle_text_filter {

	// Lifetime of video player token in seconds
	const TOKEN_LIFETIME = 30;


	// Helper function to route option requests to the correct repository
	public function get_movingimage_option($config) {
		return trim(get_config('filter_movingimageembed', $config));
  }


  // Configure filter parameters for movingimage video URL
	public function filter($text, array $options = array()) {
    global $CFG;

		// Define search pattern and callback function
		$search = '#<a [^>]*href="'.$this->get_movingimage_option('movingimageURL').'\?([^"]*)".*</a>#isU';
		$newtext = preg_replace_callback($search, array('filter_movingimageembed', 'callback'), $text);

		// No search pattern hit, return original text
    if (is_null($newtext) or $newtext === $text) {
      return $text;
    }

		// Return substituted text
    return $newtext;
  }


  // Helper function to generate a valid video player token based on shared secret in config options
	private function _generateToken($video_id, $exp_time, $shared_secret)
	{
		// Generate JSON string
	  $data = sprintf("{\"video-id\":\"%s\", \"exp-time\": %s}" , $video_id, $exp_time);

		// Calculate hash code
	  $hash = hash_hmac ( "sha256", $data , hex2bin($shared_secret) );

		// combine timestamp and hash code
	  return sprintf ("%s~%s", $exp_time , $hash);
	}


  // Callback function on search pattern match: Replace movingimage video URL by movingimage video embed code
  private function callback($matches) {

    // Decode URL and store video URL parameters to settings array
		$settings = array();
    $urldecoded = urldecode($matches[1]);
    $entitydecoded = html_entity_decode($urldecoded);
    parse_str($entitydecoded, $settings);

		// Get config settings
		$width = $this->get_movingimage_option('playerwidth');
		$height = $this->get_movingimage_option('playerheight');
		$embedstyle = $this->get_movingimage_option('embedstyle');
		$style = '';

		// Create style tag if width and/or height is set
		if ($width != '0' || $height != '0')	{
			$style = 'style="';
			if ($width != '0')
				$style .= 'max-width:'.$width.'px;';
			if ($height != '0')
				$style .= 'max-height:'.$height.'px;';
			$style .= '"';
		}

    // define token lifetime and calculate movingimage video player token
		$exp_time = time() + filter_movingimageembed::TOKEN_LIFETIME;
		$token = $this->_generateToken($settings['video-id'], $exp_time, $this->get_movingimage_option('secret'));

    // If config settings require iFrame embedding
		if ($embedstyle == '1')		{

			// Return iFrame embed code (token protection not supported)
			$html = '<iframe '.$style.' src="' . $this->get_movingimage_option('movingimageURL').'?video-id=' . $settings['video-id']
				.'&player-id=' . $settings['player-id'] . '&token='.$token.'" allowfullscreen frameborder="0"></iframe>';

		} else {

			// Return div embed code
			$html = '
					<div '.$style.' mi24-video-player video-id="'.$settings['video-id'].'" player-id="'.$settings['player-id'].'"
						config-type="vmpro" flash-path="//e.video-cdn.net/v2/" api-url="//d.video-cdn.net/play"
						token="'.$token.'">
					</div>
					<script src="//e-qa1.video-cdn.net/v2/embed.js"></script>';

		}

		// return substituted HTML embed code
		return $html;
  }

}
