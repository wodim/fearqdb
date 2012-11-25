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

require_once(classes_dir.'quote.php'); // Quote::READ

class Search {
	const SEARCH = 'SELECT %s FROM quotes WHERE status = \'approved\' AND (text LIKE :text OR comment LIKE :comment %s) AND db = :db AND quotes.hidden = 0 ORDER BY id DESC';
	const SEARCH_HIDDEN = 'SELECT %s FROM quotes WHERE status = \'approved\' AND (text LIKE :text OR comment LIKE :comment %s) AND db = :db ORDER BY id DESC';
	const COUNT = 'SELECT COUNT(1) FROM quotes WHERE status = \'approved\' AND (text LIKE :text OR comment LIKE :comment %s) AND db = :db AND quotes.hidden = 0';
	const COUNT_HIDDEN = 'SELECT COUNT(1) FROM quotes WHERE status = \'approved\' AND (text LIKE :text OR comment LIKE :comment %s) AND db = :db';

	/* whether a search has been done with this class; called $read for consistance */
	var $read = false;
	var $criteria = '';
	var $results = array();
	var $count = 0;

	var $page = 1;
	var $page_size = 0;
	var $show_hidden = false;

	function read() {
		global $settings, $db, $session;

		if (!$this->page_size || $this->page_size > 50) {
			$this->page_size = $settings->page_size;
		}

		/* do not modify $this->criteria, the script that called us may want to access
			it later and we don't want to send garbage back */
		$criteria = $this->clean_criteria($this->criteria);

		$escape = $db->type == 'sqlite' ? 'ESCAPE \'\\\'' : '';
		$query = $this->show_hidden ?
			sprintf(Search::SEARCH_HIDDEN, Quote::READ, $escape) :
			sprintf(Search::SEARCH, Quote::READ, $escape);
		$query_count = $this->show_hidden ?
			sprintf(Search::COUNT_HIDDEN, $escape) :
			sprintf(Search::COUNT, $escape);

		/* this may look like a double query but it's not:
			1) we will need to store the number of results anyway;
			2) we want to know whether $page is out of bounds */
		$this->count = $db->get_var($query_count, array(
			array(':text', $criteria, PDO::PARAM_STR),
			array(':comment', $criteria, PDO::PARAM_STR),
			array(':db', $settings->db, PDO::PARAM_STR)
		));

		if (!$this->count) {
			return false;
		}

		$this->page--;

		/* out of bounds */
		if ($this->count < ($this->page * $this->page_size)) {
			return false;
		}

		/* unfortunately this becomes tricky */
		$query = sprintf('%s LIMIT %d, %d', $query, ($this->page * $this->page_size), $this->page_size);
		$this->results = $db->get_results($query, array(
			array(':text', $criteria, PDO::PARAM_STR),
			array(':comment', $criteria, PDO::PARAM_STR),
			array(':db', $settings->db, PDO::PARAM_STR)
		));

		$this->read = true;
		return true;
	}

	function clean_criteria($criteria) {
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
