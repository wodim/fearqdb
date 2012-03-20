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
	const SEARCH = 'SELECT %s FROM quotes WHERE approved = 1 AND text LIKE \'%s\' COLLATE %s AND db = \'%s\' ORDER BY date DESC LIMIT %d,%d';
	const COUNT = 'SELECT SQL_CACHE COUNT(*) FROM quotes WHERE approved = 1 AND text LIKE \'%s\' COLLATE %s AND db = \'%s\'';
	
	/* whether a search has been done with this class; called $read for consistance */
	var $read = false;
	var $criteria = '';
	var $results = array();
	var $count = 0;
	
	var $page = 0;
	var $page_size = 0;
	
	function search() {
		global $config, $db;

		if (!$this->page_size || $this->page_size > 50) {
			$this->page_size = $config['site']['page_size'];
		}
	
		$criteria = $this->clean_criteria($criteria);

		/* this may look like a double query but it's not:
			1) we will need to store the number of results anyway;
			2) we want to know whether $page is out of bounds */
		$this->count = $db->get_var(sprintf(Search::COUNT,
			$search, $config['site']['collate'], $config['db']['table']));

		if (!$this->count) {
			return false;
		}

		/* TODO: detect whether $page is out of bounds and don't actually do the query */

		$quotes = $db->get_results(sprintf(Search::SEARCH,
			Quote::READ, 
			$criteria, 
			$config['site']['collate'],
			$config['db']['table'], 
			(($this->page - 1) * $this->page_size), 
			$this->page_size));
	
		return $quotes;
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
