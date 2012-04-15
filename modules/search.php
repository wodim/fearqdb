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

require(classes_dir.'quote.php');
require(classes_dir.'search.php');

global $params, $db, $session;

if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
	isset($_POST['query'])) {
	redir(sprintf('/search/%s', urlencode($_POST['query'])));
}

if (!isset($params[1]) || (trim($params[1]) == '')) {
	redir();
}

$page_number = (isset($params[2]) ? (((int)$params[2] < 1) ? 1 : (int)$params[2]) : 1);

$search = new Search();
$search->criteria = urldecode($params[1]);
$search->page = $page_number;
$search->show_hidden = ($session->level != 'anonymous');
$search->read();

$session->search = htmlspecialchars(urldecode($params[1]));

if (!$search->results) {
	if ($search->page_size * ($search->page - 1) < $search->count) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	} else {
		$html->do_sysmsg(_('No quotes found'), _('There are no quotes matching your criteria.'), 404);
	}
} else {
	$html->do_header(sprintf(_('Search results for "%s"'), htmlspecialchars($params[1])));

	$pager = $html->do_pages(($search->page + 1), ceil($search->count / $search->page_size), 
		sprintf('/search/%s/%%d', str_replace('%', '%%', urlencode($params[1]))), 4);

	$quote = new Quote();
	$odd = true;
	foreach ($search->results as $this_quote) {
		$quote->read($this_quote);
		$quote->output($odd);
		$odd = !$odd;
	}

	echo($pager);

	$html->do_footer();
}
