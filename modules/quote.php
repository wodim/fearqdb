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

global $params, $session, $settings;

if (isset($params[0]) && strlen($params[0]) == 4) {
	$quote = new Quote();
	$quote->permaid = $params[0];
} else {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

if (!$quote->read() || ($session->level == 'anonymous' && $quote->status != 'approved') || $quote->status == 'deleted') {
	$html->do_sysmsg(_('No such quote'), null, 404);
}

if (isset($params[1]) && $params[1] == $quote->password) {
	$quote->forceshow = true;
} elseif (isset($params[1]) && $params[1] != $quote->password) {
	redir(sprintf('%s%s', $settings->base_url, $quote->permaid));
	die();
}

$html->do_header(sprintf(_('Quote #%s'), $quote->permaid));
$quote->output();
$html->do_footer();