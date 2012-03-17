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

/* move json functions to utils.php? */
function out($out) {
	echo(json_encode($out));
}

function generic_error($error = 'unspecified') {
	header('503 Unavailable');
	out(array('error' => $error));
}

function enforce_post() {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		generic_error('post_required');
	}
}

function get_last() {
	global $db, $config;

	return $db->get_var(sprintf('SELECT permaid FROM quotes WHERE db = \'%s\' ORDER BY date DESC',
		$config['db']['table']));
}

if (isset($params[2]) && $params[2] == $session->password()) {
	$session->level = 'reader';
}

if (!isset($params[1])) {
	$params[1] = '';
}

switch ($params[1]) {
	case 'send':
		enforce_post();
		$quote = new Quote();
		$quote->nick = clean($_POST['nick'], 20);
		$quote->ip = $session->ip;
		$quote->text = clean($_POST['text'], 10000);
		$quote->comment = clean($_POST['comment'], 1000);
		$quote->hidden = (isset($_POST['hidden']) && ((int)$_POST['hidden'] == 0 || (int)$_POST['hidden'] == 1)) ? (int)$_POST['hidden'] : '0';
		$quote->approved = ($session->level != 'anonymous') ? '1' : '0';
		$quote->save();
		$last = get_last();
		out(array('results' =>
			array('success' => 1,
				'url' => sprintf('%s%s', $config['core']['domain'], $last),
				'id' => $last)));
		break;
	case 'last':
		$last = get_last();
		out(array('results' =>
			array('url' => sprintf('%s%s', $config['core']['domain'], $last),
				'id' => $last)));
		break;
	case 'read':
		if (!isset($params[2])) {
			generic_error('not_enough_parameters');
		}
		$quote = new Quote();
		$quote->read_permaid($params[2]);
		if ($quote->read) {
			if (!$quote->hidden) {
				out(array('results' =>
					array('success' => 1,
						'data' => $quote)));
			} else {
				out(array('results' =>
					array('success' => 0,
						'error' => 'hidden_quote')));
			}
		} else {
			out(array('results' =>
				array('success' => 0,
					'error' => 'no_such_quote')));
		}
		break;
	default:
		generic_error('method_not_implemented');
}
