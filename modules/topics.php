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

if ($session->level != 'admin' && $session->level != 'reader') {
	$html->do_sysmsg('Forbidden', null, 403);
}

if (isset($params[1]) && ctype_digit($params[1]) && $params[1] > 0) {
	$page_number = $params[1];
} elseif (!isset($params[1])) {
	$page_number = 1;
} else {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

$page_size = $settings->page_size * 2;

$rows = $db->get_var('SELECT COUNT(1) from topics WHERE db = :db', array(
	array(':db', $settings->db, PDO::PARAM_STR)
));

$query = sprintf(
	'SELECT * FROM topics WHERE db = :db ORDER BY id DESC LIMIT %d,%d',
		(($page_number - 1) * $page_size), $page_size
);

$topics = $db->get_results($query, array(
	array(':db', $settings->db, PDO::PARAM_STR)
));

if (!$topics) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

$html->do_header();

$mod = sprintf('%stopics/%%d', $settings->base_url, 'page');
$pager = $html->do_pages($page_number, ceil($rows / $page_size), $mod, 4);

$odd = true;
foreach ($topics as $topic) {
	$odd = !$odd;
	$topic['style'] = $odd ? 'odd' : 'even';
	$topic['date'] = date('d/m/Y H:i:s', $topic['timestamp']);
	$topic['timelapse'] = elapsed_time(date('U') - $topic['timestamp']);

	$vars = compact('topic');
	$html->output .= Haanga::Load('topic.html', $vars, true);
}

$html->output .= $pager;
$html->do_footer();
