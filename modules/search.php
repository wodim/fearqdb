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

global $params, $config, $db;

if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
	isset($_POST['query'])) {
	redir(sprintf('/search/%s', urlencode($_POST['query'])));
}

if (!isset($params[1]) || (trim($params[1]) == '')) {
	redir();
}

$page_number = (isset($params[2]) ? (((int)$params[2] < 1) ? 1 : (int)$params[2]) : 1);

$search = urldecode($params[1]);
$session->search = htmlspecialchars($search);
$search = mysql_real_escape_string($search); // y tu madre la chupa
$search = preg_replace('/^\*|\*$/', '', $search);
$search = preg_replace('/^\?|\?$/', '', $search);
$search = str_replace('%', '\%', $search);
$search = str_replace('_', '\_', $search);
$search = str_replace('*', '%', $search);
$search = str_replace('?', '_', $search);
$search = $search == '' ? '%' : sprintf('%%%s%%', $search);

$quotes = $db->get_results(sprintf('SELECT %s FROM quotes WHERE approved = 1 AND text LIKE \'%s\' COLLATE %s AND db = \'%s\' ORDER BY date DESC LIMIT %d,%d',
	Quote::READ, $search, $config['site']['collate'], $config['db']['table'], (--$page_number * $config['site']['page_size']), $config['site']['page_size']));

if (!$quotes) {
	if ($page_number) {
		$html->do_sysmsg(_('Page not found'), sprintf('<!-- Busqueda convertida a: %s -->', $search), 404);
	} else {
		$html->do_sysmsg(_('No quotes found'), _('There are no quotes matching your criteria.').sprintf('<!-- Busqueda convertida a: %s -->', $search), 404);
	}
}

$html->do_header(sprintf(_('Search results for "%s"'), htmlspecialchars($params[1])));

$rows = $db->get_var(sprintf('SELECT SQL_CACHE COUNT(*) FROM quotes WHERE approved = 1 AND text LIKE \'%s\' COLLATE %s AND db = \'%s\'',
	$search, $config['site']['collate'], $config['db']['table']));
$pager = $html->do_pages(++$page_number, ceil($rows / $config['site']['page_size']), sprintf('/search/%s/%%d', htmlspecialchars(urldecode($params[1]))), 4);

$quote = new Quote();
$odd = true;
foreach ($quotes as $this_quote) {
	$quote->read(0, $this_quote);
	$quote->output($odd);
	$odd = !$odd;
}

echo($pager);

$html->do_footer();
