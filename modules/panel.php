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

global $settings, $html, $session, $push;

if ($session->level != 'admin') {
	$html->do_sysmsg('Forbidden', null, 403);
}

function result_out($result) {
	$result = htmlentities($result);
	$result = str_replace(' ', '&nbsp;', $result);
	$result = str_replace("\n", '<br />', $result);
	die($result);
}

function required_post($variables) {
	global $session;

	foreach ($variables as $var) {
		if (!isset($_POST[$var])) {
			die('???');
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	required_post(
		array(
			'action'
		)
	);
	
	switch ($_POST['action']) {
		case 'quote':
			required_post(
				array(
					'permaid',
					'quote_action'
				)
			);
			require(classes_dir.'quote.php');
			$quote = new Quote();
			$quote->permaid = $_POST['permaid'];
			if (!$quote->read()) {
				die('no_such_quote');
			}
			switch ($_POST['quote_action']) {
				case 'do_pending':
					$status = 'pending';
					break;
				case 'do_deleted':
					$status = 'deleted';
					break;
				case 'do_approved':
					$status = 'approved';
					break;
				case 'do_show':
					$hidden = 0;
					break;
				case 'do_hide':
					$hidden = 1;
					break;
			}
			if (isset($status)) {
				$quote->status = $status;
			}
			if (isset($hidden)) {
				$quote->hidden = $hidden;
			}
			/* DESTROY DOESN'T WORK ON PURPOSE, this is not the right way of
				doing it and this isn't the right place to do it */
			$quote->save(false);
			die('done');
			break;
		case 'dbq':
			required_post(
				array(
					'query',
					'query_type'
				)
			);
			$query = $_POST['query'];
			switch ($_POST['query_type']) {
				case 'query_type_get_results':
					$results = $db->get_results($query);
					break;
				case 'query_type_get_row':
					$results = $db->get_row($query);
					break;
				case 'query_type_get_var':
					$results = $db->get_var($query);
					break;
				case 'query_type_query':
				default:
					$results = $db->query($query);
					break;
			}
			result_out(print_r($results, true));
			break;
		case 'misc':
			required_post(
				array(
					'misc_action'
				)
			);
			switch ($_POST['misc_action']) {
				case 'approve_all':
					$quotes = $db->get_results('SELECT permaid FROM quotes WHERE status = \'pending\'');
					if (!$quotes) {
						die('no_pending_quotes');
						break;
					}
					echo 'Approving: ';
					foreach ($quotes as $quote) {
						printf('%s ', $quote->permaid);
					}
					$db->query('UPDATE quotes SET status = \'approved\' WHERE status = \'pending\'');
					break;
				case 'privacy_login':
					$db->query(sprintf('UPDATE sites SET privacy_level = 2 WHERE db = \'%s\'', $settings->db));
					break;
				case 'privacy_hide_all':
					$db->query(sprintf('UPDATE sites SET privacy_level = 1 WHERE db = \'%s\'', $settings->db));
					break;
				case 'privacy_unhide_all':
					$db->query(sprintf('UPDATE sites SET privacy_level = 0 WHERE db = \'%s\'', $settings->db));
					break;
				case 'privacy_show_all':
					$db->query(sprintf('UPDATE sites SET privacy_level = -1 WHERE db = \'%s\'', $settings->db));
					break;
				case 'robots_allow':
					$db->query(sprintf('UPDATE sites SET robots = \'allow\' WHERE db = \'%s\'', $settings->db));
					break;
				case 'robots_disallow':
					$db->query(sprintf('UPDATE sites SET robots = \'disallow\' WHERE db = \'%s\'', $settings->db));
					break;
			}
			printf('successful %s', $_POST['misc_action']);
			break;
		case 'push':
			if ($push->hit($_POST['push_text'])) {
				printf('successful');
			} else {
				printf('error');
			}
			break;			
	}
} else {
	$html->do_header(_('Administration'));
	Haanga::Load('panel.html');
	$html->do_footer();
}
