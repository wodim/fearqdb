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

global $params, $session;

$nick = $session->nick ? $session->nick : '';

/* create a new session using %url%/submit/%nick%/%password%, this is, autologin and then redir to /submit */
if (isset($params[2])) {
	if ($session->level != 'reader') {
		$session->create($params[2]); // if it can't be created, who cares? redir anyway, we don't want the pwd to stay in the url bar...
	}
	redir(sprintf('/submit/%s', $params[1]));
}

if (isset($params[1])) {
	switch ($params[1]) {
		case 'post':
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				redir('/submit');
			}
			$quote = new Quote();
			$quote->nick = $_POST['nick'];
			$quote->ip = $session->ip;
			$quote->text = $_POST['text'];
			$quote->comment = $_POST['comment'];
			$quote->hidden = (isset($_POST['hidden']) && $_POST['hidden'] == 'on');
			$quote->status = ($session->level == 'admin') ? 'approved' : 'pending';
			$quote->api = 1; // web
			$permaid = $quote->save();
			if ($permaid === false) {
				redir('/submit/invalid');
			}
			if ($quote->status == 'approved') {
				$quote->generate();
				$push->hit(sprintf(_('New quote: %s - %s'), $quote->permalink, $quote->excerpt));
				redir(sprintf('/%s', $permaid));
			} else {
				$push->hit(sprintf(_('%s has sent a quote and it is pending approval.'), $quote->nick));
				redir('/submit/sent');
			}
			break;
		case 'invalid':
			$html->do_sysmsg(_('Oops!'), _('Your quote has not been sent. Maybe it was too short?'), 200);
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
