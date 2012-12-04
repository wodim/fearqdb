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

$selected = new stdClass();
$beacon = 'class="selected" ';

if (!isset($params[1]) ||
	!isset($params[2]) && $params[1] != 'all') {
	redir(sprintf('%srandom/all', $settings->base_url));
}
$range = $params[1];
$lapse = isset($params[2]) ? $params[2] : null;
$seconds = 0;

switch ($range) {
	case 'week':
		if ($lapse != 1) {
			redir(sprintf('%srandom/week/1', $settings->base_url));
		}
		$seconds = 604800; /* 3600 * 24 * 7 */
		break;
	case 'month':
		if ($lapse != 1 && $lapse != 3 && $lapse != 6) {
			redir(sprintf('%srandom/month/1', $settings->base_url));
		}
		$seconds = $lapse * 2419200; /* 604800 * 4 */
		break;
	case 'year':
		if ($lapse != 1) {
			redir(sprintf('%srandom/year/1', $settings->base_url));
		}
		$seconds = 29030400; /* 2419200 * 12 */
		breaK;
}

$selected->oneweek = ($seconds == 604800) ? $beacon : null;
$selected->onemonth = ($seconds == 2419200) ? $beacon : null;
$selected->threemonths = ($seconds == 3 * 2419200) ? $beacon : null;
$selected->sixmonths = ($seconds == 6 * 2419200) ? $beacon : null;
$selected->oneyear = ($seconds == 29030400) ? $beacon : null;
$selected->all = ($seconds == 0) ? $beacon : null;

$vars = compact('selected');
$filterer = Haanga::Load('random.html', $vars, true);

$diff = ($seconds == 0) ? 0 : (time() - $seconds);

/* !!TODO */
if ($db->type == 'mysql') {
	$query = sprintf('SELECT %s FROM quotes WHERE quotes.db = :db
		AND status = \'approved\' AND hidden = 0 AND timestamp > :timestamp
		ORDER BY RAND() LIMIT %d',
		Quote::READ, $settings->page_size);
} elseif ($db->type == 'sqlite') {
	$query = sprintf('SELECT %s FROM quotes WHERE quotes.db = :db
		AND status = \'approved\' AND hidden = 0 AND timestamp > :timestamp
		ORDER BY RANDOM() LIMIT %d',
		Quote::READ, $settings->page_size);
}

$quotes = $db->get_results($query, array(
	array(':db', $settings->db, PDO::PARAM_STR),
	array(':timestamp', $diff, PDO::PARAM_INT)
));

if (!$quotes) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

$html->do_header(_('Random quotes'));
$html->output .= $filterer;
$quote = new Quote();
$odd = true;
$shown = array();
foreach ($quotes as $this_quote) {
	$quote->read($this_quote);
	if (in_array($quote->id, $shown)) {
		continue;
	}
	$shown[] = $quote->id;
	$quote->output($odd);
	$odd = !$odd;
}

$html->do_footer();
