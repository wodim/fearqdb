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

global $params, $config, $q;

if (isset($params[1])) {
	$redirhack = true;
	$quote_no = (int)$params[1];
} elseif ((int)$params[0]) {
	$quote_no = (int)$params[0];
} else {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

if ($_GET['q'] != sprintf('/quote/%d', $quote_no)) {
	/* breaks analytics */
	// redir(sprintf('/quote/%d', $quote_no));
}

if (isset($redirhack) && $redirhack) {
	redir(sprintf('/%s', $params[1]));
}

$quote = new Quote();
$quote->read($quote_no);

if (!$quote->read) {
	$html->do_sysmsg(_('No such quote'), null, 404);
}

$html->do_header(sprintf(_('Quote %d'), $quote_no));

$quote->output();

$html->do_footer();
