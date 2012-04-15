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

class Settings {
   const READ = 'SELECT id, domain, site_key, lang, locale, `collate`,
	ip_show, ip_host, ip_part, analytics_enabled, analytics_code,
	url, statics_url, snowstorm, db, irc, name, nname, cookie,
	privacy_level,	privacy_level_for_bots, page_size
	FROM sites WHERE domain = \'%s\'';
	var $id = 0;
	var $domain = '';
	var $site_key = '';
	var $lang = '';
	var $locale = '';
	var $collate = '';
	var $ip_show = false;
	var $ip_host = false;
	var $ip_part = false;
	var $analytics_enabled = false;
	var $analytics_code = '';
	var $url = '';
	var $statics_url = '';
	var $snowstorm = false;
	var $db = '';
	var $irc = '';
	var $name = '';
	var $nname = '';
	var $cookie = '';
	var $privacy_level = 0;
	var $privacy_level_for_bots = '';
	var $page_size = 10;

	var $read = false;

	function init() {
		global $db;

		$results = $db->get_row(
			sprintf(Settings::READ,
				clean($_SERVER['HTTP_HOST'], MAX_DOMAIN_LENGTH, true)));

		if (!$results) {
			return $this->read;
		}

		foreach (get_object_vars($results) as $variable => $value) {
			$this->$variable = $value;
		}

		$this->read = true;
		return true;
	}
}
