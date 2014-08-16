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
	const READ = 'SELECT id, db, topic_text, topic_nick,
		approved_quotes, pending_quotes
		FROM sites WHERE db = :db';
	var $id = 0;

	/* in config.php */
	var $domain = '';
	var $db = '';
	var $base_url = '';
	var $full_url = '';
	var $site_key = '';
	var $lang = '';
	var $locale = '';
	var $collate = '';
	var $ip_show = false;
	var $analytics_code = '';
	var $statics_url = null;
	var $title = '';
	var $footer = '';
	var $privacy_level = 0;
	var $privacy_level_for_bots = '';
	var $page_size = 10;
	var $robots = 'allow';
	var $push_enabled = '';
	var $push_url = '';
	var $push_params = '';
	var $extra_css = null;

	/* in the database */
	var $topic_text = '';
	var $topic_nick = '';
	var $approved_quotes = 0;
	var $pending_quotes = 0;

	/* automatically generated */
	var $cookie = '';

	var $no_rewrite = false;
	var $read = false;

	function init() {
		global $db, $sites;

		$site = null;
		foreach ($sites as $this_site) {
			if ($this_site['domain'] == $_SERVER['HTTP_HOST']) {
				$site = $this_site;
			}
		}
		if (!$site) {
			return $this->read;
		}

		$results = $db->get_row(Settings::READ, array(
			array(':db', $site['db'], PDO::PARAM_STR)
		));
		if (!$results) {
			return $this->read;
		}

		foreach ($site as $variable => $value) {
			$this->$variable = ctype_digit($value) ? (int)$value : $value;
		}
		foreach ($results as $variable => $value) {
			$this->$variable = ctype_digit($value) ? (int)$value : $value;
		}

		$this->cookie = $this->db.'_session';
		if (!$this->full_url) {
			$this->full_url = "http://{$this->domain}{$this->base_url}";
		}
		if (!$this->statics_url) {
			$this->statics_url = $this->base_url;
		}

		/* does the user have mod_rewrite? */
		$this->no_rewrite = preg_match('/\?m=$/', $this->base_url);
		$this->read = true;
		return true;
	}

	function recount() {
		global $db, $memcache;

		$approved_quotes = $db->get_var('SELECT COUNT(*) FROM quotes WHERE status = \'approved\' AND db = :db', array(
			array(':db', $this->db, PDO::PARAM_STR)
		));
		$pending_quotes = $db->get_var('SELECT COUNT(*) FROM quotes WHERE status = \'pending\' AND db = :db', array(
			array(':db', $this->db, PDO::PARAM_STR)
		));
		$db->query('UPDATE sites
			SET approved_quotes = :approved_quotes, pending_quotes = :pending_quotes WHERE db = :db', array(
			array(':approved_quotes', $approved_quotes, PDO::PARAM_INT),
			array(':pending_quotes', $pending_quotes, PDO::PARAM_INT),
			array(':db', $this->db, PDO::PARAM_STR)
		));

		if ($memcache->enabled) {
			/* flush memcached pages */
			$levels = array('anonymous', 'user');
			foreach ($levels as $level) {
				$pages = $memcache->page_list($level);
				foreach ($pages as $page) {
					$memcache->delete(sprintf('page_%d_level_%s', $page, $level));
				}
			}
		}
	}
}