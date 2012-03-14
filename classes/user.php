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

class User {
	const READ_NICK = 'SELECT id, nick, password, salt, db FROM users WHERE nick = \'%s\' AND db = \'%s\'';
	const READ_ID = 'SELECT id, nick, password, salt, db FROM users WHERE id = \'%s\' AND db = \'%s\'';
	var $id = 0;
	var $nick = '';
	var $password = '';
	var $salt = '';
	var $db = '';

	var $read = false;

	function read() {
		global $db, $config;

		/* prefer id over nick */
		$query = $this->id ? 
			sprintf(User::READ_ID, $this->id, $config['db']['table']) :
			sprintf(User::READ_NICK, $this->nick, $config['db']['table']);
			
		$results = $db->get_row($query);

		if ($results) {
			foreach (get_object_vars($results) as $variable => $value) {
				$this->$variable = $value;
			}
			$this->read = true;
			return $this->read;
		}

		return false;
	}

	// $password is NOT plain, is it?
	function cookie_check($id, $cookie) {
		global $config;

		$this->id = $id;

		if (!$this->read()) {
			return false;
		}

		$expected = sprintf('p%sh%sp', $this->password, $config['site']['key']);

		return($expected == $cookie);
	}

	function login_check($nick, $password) {
		$this->id = $id;

		if (!$this->read()) {
			return false;
		}

		return(md5($password.$this->salt) == $this->password);
	}
}
