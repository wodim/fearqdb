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

$query = sprintf('SELECT %s FROM quotes
	WHERE status = \'approved\' AND quotes.db = :db AND hidden = 0
	ORDER BY id DESC LIMIT %d', Quote::READ, $settings->page_size * 5);
$quotes = $db->get_results($query, array(
	array(':db', $settings->db, PDO::PARAM_STR)
));

if (!$quotes) {
	header('HTTP/1.1 404');
	die();
}

header('Content-Type: application/rss+xml');

$rss['date'] = date('r', $quotes[0]->ts);

$vars = compact('rss');
Haanga::Load('rss-header.html', $vars);

$quote = new Quote();
foreach ($quotes as $this_quote) {
	$quote->read($this_quote);
	$quote->output_rss();
}

Haanga::Load('rss-footer.html');
