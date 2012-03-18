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

require_once('config.php');
require_once(classes_dir.'user.php');

class Session {
	var $level = 'anonymous';
	var $user = 0;
	var $ip = '';
	var $date = '';
	var $search = '';
	var $unapproved = 0;
	var $origin = '';
	var $expected_cookie = '';
	var $xsrf = '';
	var $type = '';
	var $hit = false;

	function init() {
		global $config, $db, $params;

		// $this->unapproved = $db->get_var('SELECT COUNT(*) FROM quotes WHERE approved = 0');
		
		$this->date = strftime('%d/%m');
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->origin = urlencode($_SERVER['REQUEST_URI']);

		/* today's lesson: the more bullshit you get into a cookie, the more secure it is. */
		$this->expected_cookie = md5(sprintf('ni%sna%snu%sne', $this->password(), $config['site']['key'], date('YdmYdYmdYmdY')));
		$this->xsrf = substr(md5(sprintf('el%sek%str%so', $this->expected_cookie, $this->ip, $config['site']['key'])), 0, 8);

		if (!isset($_COOKIE[$config['site']['cookie_name']])) {
			return false;
		}

		$tmp = base64_decode($_COOKIE[$config['site']['cookie_name']]);
		$tmp = explode('!', $tmp);

		if (count($tmp) < 2) {
			// garbage; destroy
			$this->destroy();
			return false;
		}
		
		if ((int)$tmp[0] == 0) {
			if ($this->expected_cookie == $tmp[1]) {
				$this->level = 'reader';
				/* return already */
				return true;
			}
			return false;
		} else {
			$user = new User();
			if ($user->cookie_check((int)$tmp[0], $tmp[1])) {
				$this->level = $user->level;
				$this->user = (int)$tmp[0];
			}
			$this->destroy();
			return false;
		}

		return false;
	}

	// store a hit
	function hit($is_redir = false, $location) {
		if ($this->hit) {
			$this-log('Tried to store a hit twice.');
			return;
		}

		global $config, $module, $db;

		$ip = $this->ip;
		$url = clean($_SERVER['REQUEST_URI'], 256, true);
		$redir = $is_redir ? clean($location, 256, true) : '';
		$search = clean($this->search, 256, true);
		/* $module = $module; */
		$db_table = $config['db']['table'];
		$level = $this->level;
		$user = $this->user;
		$referer = isset($_SERVER['HTTP_REFERER']) ? clean($_SERVER['HTTP_REFERER'], 256, true) : '';
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? clean($_SERVER['HTTP_USER_AGENT'], 256, true) : '';
		/* $time = NOW(); */

		$db->query(sprintf('INSERT INTO hits (ip, url, redir, module, search, db, level, user, referer, user_agent, time)
			VALUES(\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%d\', \'%s\', \'%s\', NOW())',
			$ip,
			$url,
			$redir,
			$module,
			$search,
			$db_table,
			$level,
			$user,
			$referer,
			$user_agent));
		$this->hit = true;
	}
	
	function log($text) {
		$ip = $this->ip;
		$url = clean($_SERVER['REQUEST_URI'], 256, true);
		$db_table = $config['db']['table'];
		$text = clean($text, 256, true);

		$db->query(sprintf('INSERT INTO logs (ip, time, url, db, text)
			VALUES(\'%s\', NOW(), \'%s\', \'%s\', \'%s\')',
			$ip,
			/* NOW() */
			$url,
			$db_table,
			$text));
	}

	function create($password) {
		global $config;

		if ($password != $this->password()) {
			return false;
		}

		// the password is generated daily soooo exp time = 24 hours
		setcookie($config['site']['cookie_name'], base64_encode(sprintf('0!%s', $this->expected_cookie)), time() + 86400, '/');
		return true;
	}

	// we DO NOT ESCAPE
	function create_user($nick, $password) {
		global $config;

		$user = new User();
		$tmp = $user->login_check($nick, $password);
		
		if (!$tmp) {
			return false;
		}

		setcookie($config['site']['cookie_name'], base64_encode(sprintf('%d!%s', $user->id, $tmp)), time() + 86400, '/');
		return true;
	}

	function destroy() {
		global $config;

		setcookie($config['site']['cookie_name'], '', time() - 3600, '/');
		return true;
	}

	/* current password, it's generated every day. in case of disclosure, change the site key. */
	function password() {
		global $config;

		return(substr(md5(date('d/m/Y').$config['site']['key']), 0, 8));
	}
}
