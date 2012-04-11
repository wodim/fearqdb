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
require_once(include_dir.'utils.php');
require_once(classes_dir.'quote.php'); // Quote::READ

class Search {
	const SEARCH = 'SELECT %s FROM quotes, api WHERE quotes.status = \'approved\' AND quotes.text LIKE \'%s\' COLLATE %s AND quotes.db = \'%s\' AND api.id = quotes.api ORDER BY quotes.date DESC LIMIT %d,%d';
	const COUNT = 'SELECT SQL_CACHE COUNT(*) FROM quotes WHERE quotes.status = \'approved\' AND text LIKE \'%s\' COLLATE %s AND quotes.db = \'%s\'';
	
	/* whether a search has been done with this class; called $read for consistance */
	var $read = false;
	var $criteria = '';
	var $results = array();
	var $count = 0;
	
	var $page = 1;
	var $page_size = 0;
	
	function read() {
		global $settings, $db;

		if (!$this->page_size || $this->page_size > 50) {
			$this->page_size = $settings->page_size;
		}

		/* do not modify $this->criteria, the script that called us may want to access
			it later and we don't want to send garbage back */
		$criteria = $this->clean_criteria($this->criteria);

		/* this may look like a double query but it's not:
			1) we will need to store the number of results anyway;
			2) we want to know whether $page is out of bounds */
		$this->count = $db->get_var(sprintf(Search::COUNT,
			$criteria, $settings->collate, $settings->db));

		if (!$this->count) {
			return false;
		}

		--$this->page;

		/* out of bounds */
		if ($this->count < ($this->page * $this->page_size)) {
			return false;
		}

		$this->results = $db->get_results(sprintf(Search::SEARCH,
			Quote::READ, 
			$criteria, 
			$settings->collate,
			$settings->db,
			($this->page * $this->page_size), 
			$this->page_size));

		$this->read = true;
		return true;
	}
		
	function clean_criteria($criteria) {
		$criteria = escape($criteria);
		$criteria = preg_replace('/^\*|\*$/', '', $criteria);
		$criteria = preg_replace('/^\?|\?$/', '', $criteria);
		$criteria = str_replace('%', '\%', $criteria);
		$criteria = str_replace('_', '\_', $criteria);
		$criteria = str_replace('*', '%', $criteria);
		$criteria = str_replace('?', '_', $criteria);
		$criteria = ($criteria == '') ? '%' : sprintf('%%%s%%', $criteria);
		
		return $criteria;
	}
}
