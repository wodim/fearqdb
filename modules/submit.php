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

global $params, $config, $session;

// needed
$nick = '';

if (isset($params[2])) {
	if (!$session->logged) {
		$session->create($params[2]); // if it can't be created, who cares? redir anyway, we don't want the pwd to stay in the url bar...
	}
	redir(sprintf('/submit/%s', $params[1]));
}

if (isset($params[1])) {
	switch ($params[1]) {
		case 'post':
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				redir();
			}
			$quote = new Quote();
			$quote->nick = clean($_POST['nick'], 16);
			$quote->ip = $session->ip;
			$quote->text = clean($_POST['text'], 10000);
			$quote->comment = clean($_POST['comment'], 1000);
			$quote->hidden = (isset($_POST['hidden']) && $_POST['hidden'] == 'on') ? '1' : '0';
			$quote->approved = $session->logged ? '1' : '0';
			$quote->save();
			if ($session->logged) {
				redir('/last');
			} else {
				redir('/submit/sent');
			}
			break;
		case 'sent':
			$html->do_sysmsg(_('Quote sent!'), _('Your quote has been submitted and is now pending approval!'), 200);
			break;
		default:
			$nick = htmlspecialchars($params[1]);
	}
}

$html->do_header(_('Submit new quote'));

$vars = compact('session', 'nick');

Haanga::Load('submit.html', $vars);

$html->do_footer();
