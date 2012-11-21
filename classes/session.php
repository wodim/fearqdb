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

require(classes_dir.'user.php');

class Session {
	var $level = 'anonymous';
	var $user = 0;
	var $ip = '';
	var $date = '';
	var $search = '';
	var $origin = '';
	var $expected_cookie = '';
	var $xsrf = '';
	var $type = '';
	var $hit = false;
	var $sysmsg = false;
	var $nick = '';

	function init() {
		global $settings, $db, $params;

		$this->date = strftime('%d/%m');
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->origin = urlencode($_SERVER['REQUEST_URI']);

		/* today's lesson: the more bullshit you get into a cookie, the more secure it is. */
		$this->expected_cookie = sha512(sprintf('ni%sna%sne', $settings->site_key, date('YdmYdYmdYmdY')));
		$this->xsrf = substr(sha512(sprintf('el%sek%str%so', $this->expected_cookie, $this->ip, $settings->site_key)), 0, 8);

		if (!isset($_COOKIE[$settings->cookie])) {
			return false;
		}

		$tmp = base64_decode($_COOKIE[$settings->cookie]);
		$tmp = explode('!', $tmp);

		if (count($tmp) < 2) {
			// garbage; destroy
			$this->log(clean(sprintf('Garbage cookie: %s', $_COOKIE[$settings->cookie]), 256, true));
			$this->destroy();
			return false;
		}

		if ((int)$tmp[0] == 0) {
			if ($this->expected_cookie == $tmp[1]) {
				$this->level = 'reader';
				/* return already */
				return true;
			}
			$this->log(clean(sprintf('Invalid cookie: %s', $_COOKIE[$settings->cookie]), 256, true));
			$this->destroy();
			return false;
		} else {
			$user = new User();
			if ($user->cookie_check((int)$tmp[0], $tmp[1])) {
				$this->user = (int)$tmp[0];
				$this->level = 'admin';
				$this->nick = $user->nick;
			} else {
				$this->destroy();
			}
			return false;
		}

		return false;
	}

	// store a hit
	function hit($is_redir = false, $location = null) {
		if ($this->hit) {
			$this->log('Tried to store a hit twice.');
			return;
		}

		global $settings, $module, $db;

		$db->query('INSERT INTO hits (ip, url, redir, module, search, db, level, user, referer, user_agent, time)
			VALUES (:ip, :url, :redir, :module, :search, :db, :level, :user, :referer, :user_agent, NOW())', array(
			array(':ip', $this->ip, PDO::PARAM_STR),
			array(':url', $_SERVER['REQUEST_URI'], PDO::PARAM_STR),
			array(':redir', $is_redir ? $location : null, PDO::PARAM_STR),
			array(':search', $this->search, PDO::PARAM_STR),
			array(':module', $module, PDO::PARAM_STR),
			array(':db', $settings->db, PDO::PARAM_STR),
			array(':level', $this->level, PDO::PARAM_STR),
			array(':user', $this->user, PDO::PARAM_INT),
			array(':referer', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null, PDO::PARAM_STR),
			array(':user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null, PDO::PARAM_STR)
			/* NOW() */
		));

		$this->hit = true;
	}

	function log($text) {
		global $settings, $db;

		$db->query('INSERT INTO LOGS (ip, time, url, db, text)
			VALUES (:ip, NOW(), :url, :db, :text)', array(
			array(':ip', $this->ip, PDO::PARAM_STR),
			/* NOW() */
			array(':url', $_SERVER['REQUEST_URI'], PDO::PARAM_STR),
			array(':db', $settings->db, PDO::PARAM_STR),
			array(':text', $text, PDO::PARAM_STR)
		));
	}

	function create($password) {
		global $settings, $db;

		$results = $db->get_results('SELECT id, `key` FROM api WHERE approved = 1');

		foreach ($results as $result) {
			if ($password == $this->password($result->key)) {
				$this->log(sprintf('Created session using API key %d', $result->id));
				setcookie($settings->cookie, base64_encode(sprintf('0!%s', $this->expected_cookie)), time() + 86400, '/');
				return true;
			}
		}

		return false;
	}

	// we DO NOT ESCAPE
	function create_user($nick, $password) {
		global $settings;

		$user = new User();
		$tmp = $user->login_check($nick, $password);

		if (!$tmp) {
			return false;
		}

		setcookie($settings->cookie, base64_encode(sprintf('%d!%s', $user->id, $tmp)), time() + 86400, '/');
		return true;
	}

	function destroy() {
		global $settings;

		$this->log('Destroyed session');

		setcookie($settings->cookie, '', time() - 3600, '/');
		return true;
	}

	private function password($seed) {
		return substr(md5(date('d/m/Y').$seed), 0, 8);
	}
}
