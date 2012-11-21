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

/* !!TODO */
$query = sprintf('SELECT %s FROM quotes WHERE quotes.db = :db
	AND status = \'approved\' AND hidden = 0 ORDER BY RAND() LIMIT %d',
	Quote::READ, $settings->page_size);
$quotes = $db->get_results($query, array(
	array(':db', $settings->db, PDO::PARAM_STR)
));

if (!$quotes) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

$html->do_header(_('Random quotes'));

$quote = new Quote();
$odd = true;
foreach ($quotes as $this_quote) {
	$quote->read($this_quote);
	$quote->output($odd);
	$odd = !$odd;
}

$html->do_footer();
