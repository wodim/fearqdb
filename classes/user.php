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

class User {
	const READ_ID = 'SELECT id, nick, password, salt, db FROM users WHERE id = :id AND db = :db';
	const READ_NICK = 'SELECT id, nick, password, salt, db FROM users WHERE nick = :nick AND db = :db';
	var $id = 0;
	var $nick = '';
	var $password = '';
	var $salt = '';
	var $db = '';

	var $read = false;

	function read() {
		global $db, $settings;

		if ($this->id) {
			$results = $db->get_row(User::READ_ID, array(
				array(':id', $this->id, PDO::PARAM_INT),
				array(':db', $settings->db, PDO::PARAM_STR)
			));
		} elseif ($this->nick) {
			$results = $db->get_row(User::READ_NICK, array(
				array(':nick', $this->nick, PDO::PARAM_STR),
				array(':db', $settings->db, PDO::PARAM_STR)
			));
		}

		if ($results) {
			foreach (get_object_vars($results) as $variable => $value) {
				$this->$variable = is_numeric($value) ? (int)$value : $value;
			}
			$this->read = true;
			return $this->read;
		}

		return false;
	}

	// $password is NOT plain, is it?
	function cookie_check($id, $cookie) {
		global $settings;

		$this->id = $id;

		if (!$this->read()) {
			return false;
		}

		$expected = sha512(sprintf('p%sh%sp', $this->password, $settings->site_key));

		return($expected == $cookie);
	}

	function login_check($nick, $password) {
		global $settings;

		$this->nick = $nick;

		if (!$this->read()) {
			return false;
		}

		return((sha512($this->salt.$password) == $this->password) ?
			sha512(sprintf('p%sh%sp', $this->password, $settings->site_key)) :
			false);
	}
}
