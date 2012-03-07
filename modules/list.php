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
require_once(classes_dir.'quote.php');

global $params, $config;

if (isset($params[1]) && is_numeric($params[1])) {
	$page_number = (int)$params[1];
} else {
	$page_number = 1;
}

$where = sprintf('WHERE db = \'%s\' %s %s',
	$config['db']['table'],
	$session->logged ? 'AND (approved = 1 OR approved = 0)' : 'AND approved = 1',
	($params[0] == 'hidden') ? 'AND hidden = 1' : '');

$quotes = $db->get_results(sprintf('SELECT %s FROM quotes %s ORDER BY date DESC LIMIT %d,%d',
	Quote::READ, $where, (--$page_number * $config['site']['page_size']), $config['site']['page_size']));

if (!$quotes) { // there are no quotes. but... there are no quotes in this page or no quotes at all?
	if ($page_number != 1) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	} else {
		redir();
	}
}

++$page_number;

$html->do_header(sprintf($page_number == 1 ? _('Latest quotes') : _('Latest quotes - Page %d'), $page_number));
$rows = $db->get_var(sprintf('SELECT SQL_CACHE COUNT(*) FROM quotes %s',
	$where));
//echo $rows;
$mod = sprintf('/%s/', $params[0] != '' ? $params[0] : 'page');
$pager = $html->do_pages($page_number, ceil($rows / $config['site']['page_size']), $mod.'%d', 4);

$quote = new Quote();
$odd = true;
foreach ($quotes as $this_quote) {
	$quote->read(0, $this_quote);
	$quote->output($odd);
	$odd = !$odd;
}

echo($pager);

$html->do_footer();
