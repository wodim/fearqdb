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

class Memcache {
	var $enabled = false;
	var $server = null;
	var $port = 0;
	var $prefix = null;
	var $debug = false;

	var $mch = null;

	function init() {
		global $settings;

		$mch = new Memcached();
		if (!$mch->addServer($this->server, $this->port)) {
			debug('error when connecting to the memcache server');
			$this->enabled = false;
			return false;
		}
		$mch->setOption(Memcached::OPT_PREFIX_KEY,
			sprintf('%s_%s_', $this->prefix, $settings->db));

		$this->debug(sprintf('connected successfully to %s:%s', $this->server, $this->port));
		return true;
	}

	function get($key) {
		$this->debug(sprintf('get key %s'));
		$return = $mch->get($key);
		$result = $m->getResultCode();
		if ($result != Memcached::RES_SUCCESS) {
			debug('error fetching key %s: %d', $key, $result);
			return false;
		}
		return true;
	}

	function set($key, $value, $expiration = 0) {
		$this->debug(sprintf('set key %s=%s exp %ds'));
		$return = $mch->set($key, $value, $expiration);
		$result = $m->getResultCode();
		if ($result != Memcached::RES_SUCCESS) {
			debug('error storing key %s: %d', $key, $result);
			return false;
		}
		return true;
	}

	function delete($key) {
		$this->debug(sprintf('delete key %s'));
		$return = $mch->delete($key);
		$result = $m->getResultCode();
		if ($result != Memcached::RES_SUCCESS) {
			debug('error deleting key %s: %d', $key, $result);
			return false;
		}
		return true;
	}

	function flush() {
		$this->debug(sprintf('flushing all keys'));
		$return = $mch->flush();
		$result = $m->getResultCode();
		if ($result != Memcached::RES_SUCCESS) {
			debug('error flushing keys: %d', $result);
			return false;
		}
		return true;
	}

	function debug($message) {
		if ($this->debug) {
			debug($message);
		}
	}
}