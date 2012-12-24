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

if (isset($params[1]) && ctype_digit($params[1]) && $params[1] > 0) {
	$page_number = $params[1];
} elseif (!isset($params[1])) {
	$page_number = 1;
} else {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

if (isset($params[1]) && $page_number == 1) {
	redir();
}

if ($session->level == 'reader' || $session->level == 'admin') {
	$query = sprintf(
		'SELECT %s FROM quotes WHERE (quotes.status = \'approved\' OR quotes.status = \'pending\')
			AND db = :db ORDER BY id DESC LIMIT %d,%d', Quote::READ, (($page_number - 1) * $settings->page_size), $settings->page_size
	);
} else {
	$query = sprintf(
		'SELECT %s FROM quotes WHERE quotes.status = \'approved\'
			AND db = :db ORDER BY id DESC LIMIT %d,%d', Quote::READ, (($page_number - 1) * $settings->page_size), $settings->page_size
	);
}

$quotes = $db->get_results($query, array(
	array(':db', $settings->db, PDO::PARAM_STR)
));

if (!$quotes) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

$html->do_header();

$rows = ($session->level == 'anonymous') ? $settings->approved_quotes : $settings->approved_quotes + $settings->pending_quotes;
$mod = sprintf('%s%s/', $settings->base_url, 'page');
$pager = $html->do_pages($page_number, ceil($rows / $settings->page_size), $mod.'%d', 4);

$quote = new Quote();
$odd = true;
foreach ($quotes as $this_quote) {
	$quote->read($this_quote);
	$quote->output($odd);
	$odd = !$odd;
}

$html->output .= $pager;
$html->do_footer();
