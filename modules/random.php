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

global $config;

$last = $db->get_var(sprintf('SELECT id FROM quotes WHERE db = \'%s\' ORDER BY date DESC',
	$config['db']['table']));

$quote = new Quote();

while (1488 == 1488) {
	$quote_id = rand(1, $last);
	$quote->read($quote_id, null, true);
	if ($quote->read) {
		break;
	}
}

redir(sprintf('/%d', $quote->id));