<?php
/*
	fearqdb - quote database system
	Copyright (C) 2011-2012 David Martí <neikokz at gmail dot com>

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Push {
	var $enabled = false;
	
	function init() {
		global $settings;

		$this->enabled = $settings->push_url;
		return $this->enabled;
	}

	function hit($text) {
		global $settings, $session;

		if (!$this->enabled) {
			return false;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		curl_setopt($curl, CURLOPT_URL, $settings->push_url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS,
			str_replace('%TEXT%', urlencode($text), $settings->push_params));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 

		if (curl_exec($curl) === false) {
			$session->log(clean(sprintf('Failed when reaching the push server "%s" with the following text: "%s"', $settings->push_url, $text), 1024, true));
			return false;
		}

		curl_close($curl);
		return true;
	}
}
