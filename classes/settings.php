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
	const READ = 'SELECT id, site_key, lang, locale, `collate`,
		ip_show, analytics_enabled, analytics_code,
		url, base_url, full_url, statics_url, snowstorm, db, irc, name, nname, cookie,
		privacy_level, privacy_level_for_bots, page_size, robots,
		topic_text, topic_nick, push_enabled, push_url, push_params, extra_css,
		approved_quotes, hidden_quotes
		FROM sites WHERE url = :url';
	/* we are entitled to add reasonable defaults here!! */
	var $id = 0;
	var $url = '';
	var $base_url = '';
	var $full_url = '';
	var $site_key = '';
	var $lang = '';
	var $locale = '';
	var $collate = '';
	var $ip_show = false;
	var $analytics_enabled = false;
	var $analytics_code = '';
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
	var $robots = 'allow';
	var $topic_text = '';
	var $topic_nick = '';
	var $push_enabled = '';
	var $push_url = '';
	var $push_params = '';
	var $extra_css = null;
	var $approved_quotes = 0;
	var $hidden_quotes = 0;

	var $no_rewrite = false;
	var $read = false;

	function init() {
		global $db;

		$url = sprintf('%s%s', $_SERVER['HTTP_HOST'], preg_replace('/index\.php$/', '', $_SERVER['SCRIPT_NAME']));
		$results = $db->get_row(Settings::READ, array(
			array(':url', $url, PDO::PARAM_STR)
		));
		if (!$results) {
			return $this->read;
		}

		foreach (get_object_vars($results) as $variable => $value) {
			$this->$variable = ctype_digit($value) ? (int)$value : $value;
		}

		/* does the user have mod_rewrite? */
		$this->no_rewrite = preg_match('/\?m=$/', $this->base_url);
		$this->read = true;
		return true;
	}

	function recount() {
		global $db;

		$approved_quotes = $db->get_var('SELECT COUNT(1) FROM quotes WHERE status = \'approved\' AND db = :db', array(
			array(':db', $this->db, PDO::PARAM_STR)
		));
		$hidden_quotes = $db->get_var('SELECT COUNT(1) FROM quotes WHERE status = \'approved\' AND hidden = 1 AND db = :db', array(
			array(':db', $this->db, PDO::PARAM_STR)
		));
		$db->query('UPDATE sites
			SET approved_quotes = :approved_quotes, hidden_quotes = :hidden_quotes WHERE db = :db', array(
			array(':approved_quotes', $approved_quotes, PDO::PARAM_INT),
			array(':hidden_quotes', $hidden_quotes, PDO::PARAM_INT),
			array(':db', $this->db, PDO::PARAM_STR)
		));
	}
}