<?php
/*
	fearqdb - quote database system
	Copyright (C) 2012 David Martí <neikokz at gmail dot com>

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

class DB {
	var $user = null;
	var $pass = null;
	var $name = null;
	var $file = null;
	var $host = null;
	var $debug = false;

	var $dbh = null;
	var $num_queries = 0;

	function init() {
		$location = ($this->file != null) ?
			sprintf('unix-socket=%s', $this->file) :
			sprintf('host=%s', $this->host);
		$dsn = sprintf('mysql:dbname=%s;%s;charset=utf8',
			$this->name, $location);

		try {
			$this->dbh = new PDO($dsn, $this->user, $this->pass,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		} catch (PDOException $e) {
			return false;
		}

		return true;
	}

	function query($query, $binds = null) {
		$stmt = $this->dbh->prepare($query);
		$this->run($stmt, $binds);

		return $stmt->rowCount();
	}

	function get_row($query, $binds = null) {
		$stmt = $this->dbh->prepare($query);
		$this->run($stmt, $binds);

		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return isset($results[0]) ? array_to_class($results[0]) : null;
	}

	function get_results($query, $binds = null) {
		$stmt = $this->dbh->prepare($query);
		$this->run($stmt, $binds);

		$results = array_to_class($stmt->fetchAll(PDO::FETCH_ASSOC));
		return $results;
	}

	function get_var($query, $binds = null) {
		$stmt = $this->dbh->prepare($query);
		$this->run($stmt, $binds);

		$results = $stmt->fetch(PDO::FETCH_NUM);
		return isset($results[0]) ? $results[0] : null;
	}

	function run(&$stmt, $binds = null) {
		/* apply binds, if any */
		if (is_array($binds) && count($binds > 0)) {
			foreach ($binds as $bind) {
				$stmt->bindValue($bind[0], $bind[1], $bind[2]);
			}
		}

		try {
			$stmt->execute();
		} catch (PDOException $e) {
			$this->failure();
		}

		$this->num_queries++;
	}

	function failure() {
		die('Oops!');
	}
}