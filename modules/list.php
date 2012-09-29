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

global $params, $settings;

if (isset($params[1]) && is_numeric($params[1]) && $params[1] > 0) {
	$page_number = (int)$params[1];
} else {
	$page_number = 1;
}

$status = '';

// i'd like to use a switch for this :(
if ($session->level == 'anonymous') {
	$status = 'AND quotes.status = \'approved\'';
} elseif ($session->level == 'admin' && $params[0] == 'deleted') {
	$status = 'AND quotes.status = \'deleted\'';
} else {
	$status = 'AND (quotes.status = \'approved\' OR quotes.status = \'pending\')';
}

$subpage = '';

switch ($params[0]) {
	case 'pending':
		$subpage = 'AND quotes.status = \'pending\'';
		break;
	case 'hidden':
		$subpage = 'AND quotes.hidden = 1';
		break;
	case 'deleted':
		if ($session->level == 'admin') {
			$subpage = 'AND quotes.status = \'deleted\'';
		} else {
			$subpage = 'AND 1 = 0';
		}
		break;
}

$where = sprintf('WHERE quotes.db = \'%s\' %s %s',
	$settings->db, $status, $subpage);

$where_api = sprintf('%s AND api.id = quotes.api', $where);

$quotes = $db->get_results(sprintf('SELECT %s FROM quotes, api %s ORDER BY date DESC LIMIT %d,%d',
	Quote::READ, $where_api, (--$page_number * $settings->page_size), $settings->page_size));

if (!$quotes) { // there are no quotes. but... there are no quotes in this page or no quotes at all?
	if ($page_number != 1) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	} else {
		redir();
	}
}

++$page_number;

$html->do_header();
$rows = $db->get_var(sprintf('SELECT SQL_CACHE COUNT(*) FROM quotes %s',
	$where));

$mod = sprintf('/%s/', $params[0] != '' ? $params[0] : 'page');
$pager = $html->do_pages($page_number, floor($rows / $settings->page_size), $mod.'%d', 4);

$quote = new Quote();
$odd = true;
foreach ($quotes as $this_quote) {
	$quote->read($this_quote);
	$quote->output($odd);
	$odd = !$odd;
}

echo($pager);

$html->do_footer();
