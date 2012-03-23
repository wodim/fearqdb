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
require_once(classes_dir.'search.php');

global $params, $config, $session;

/* move json functions to utils.php? */
function out($out) {
	global $session;

	if (isset($out['results']['success']) && $out['results']['success'] != 1) {
		header('503 Unavailable');
	}
	echo(json_encode($out));
	$session->hit();
	die();
}

function generic_error($error = 'unspecified') {
	global $session;

	header('503 Unavailable');
	$session->log(clean(sprintf('JSON API error: "%s"', print_r($error, true)), 256, true));
	out(array('error' => $error));
}

function get_last() {
	global $db, $config;

	return $db->get_var(sprintf('SELECT permaid FROM quotes WHERE db = \'%s\' ORDER BY date DESC',
		$config['db']['table']));
}

function sanitize($quote) {
	global $session;

	unset($quote->read);
	unset($quote->id);
	unset($quote->upvotes);
	unset($quote->downvotes);
	unset($quote->reports);
	unset($quote->views);
	unset($quote->status);
	unset($quote->db);
	unset($quote->permalink);
	unset($quote->host);
	unset($quote->tweet);

	if (!check_key()) {
		unset($quote->ip);
	}

	if ($quote->hidden && !check_key()) {
		unset($quote->text);
		unset($quote->comment);
	}

	return $quote;
}

function check_key() {
	global $db, $session, $params, $config;

	if (!isset($params[2])) {
		$session->log('JSON API access with no key');
		return 0;
	}

	$result = $db->get_var(
		sprintf('SELECT id FROM api WHERE `key` = \'%s\' AND approved = 1 AND db = \'%s\' LIMIT 1',
			escape($params[2]), $config['db']['table']));
	
	if ($result) {
		return $result;
	}
	
	$session->log(sprintf('WARNING: JSON API access with invalid key: %s', $params[2]));
	return 0;
}

function required_post($variables) {
	global $session;

	foreach ($variables as $var) {
		if (!isset($_POST[$var])) {
			$session->log(sprintf('JSON API POST with missing variable: %s', $var));
			generic_error('not_enough_parameters');
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	$session->log('JSON API access where METHOD != POST');
	generic_error('post_required');
}

if (!isset($params[1])) {
	$params[1] = '';
}

switch ($params[1]) {
	case 'send':
		required_post(array('nick', 'text'));
		$quote = new Quote();
		$quote->nick = str_replace(' (bot)', '', $_POST['nick']);
		$quote->text = $_POST['text'];
		$quote->comment = isset($_POST['comment']) ? $_POST['comment'] : '';
		if (isset($_POST['hidden'])) {
			switch ($_POST['hidden']) {
				case 1:
				case 'true':
					$quote->hidden = true;
					break;
				case 0:
				case 'false':
				default:
					$quote->hidden = false;
			}
		}
		$key = check_key();
		$quote->status = ($key != 0) ? 'approved' : 'pending';
		$quote->ip = (isset($_POST['ip']) && $key != 0) ? $_POST['ip'] : $session->ip;
		$quote->api = $key;
		$quote->save();
		$last = get_last();
		out(array('results' =>
			array('success' => 1,
				'url' => sprintf('%s%s', $config['site']['domain'], $last),
				'permaid' => $last)));
		break;
	case 'last':
		$last = get_last();
		out(array('results' =>
			array('url' => sprintf('%s%s', $config['site']['domain'], $last),
				'permaid' => $last)));
		break;
	case 'read':
		required_post(array('permaid'));
		$quote = new Quote();
		$quote->permaid = $_POST['permaid'];
		if (!$quote->read()) {
			out(array('results' =>
				array('success' => 0,
					'error' => 'no_such_quote')));
		}
		$quote = sanitize($quote);
		out(array('results' =>
			array('success' => 1,
				'data' => $quote)));
		break;
	case 'search':
		required_post(array('criteria'));
		$search = new Search();
		$search->criteria = clean($_POST['criteria']);
		$search->page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
		$search->page_size = isset($_POST['page_size']) ? (int)$_POST['page_size'] : 0;
		$search->read();
		$results = array();
		if ($search->count) {
			foreach ($search->results as $result) {
				$quote = new Quote();
				$quote->read($result);
				$quotes[] = $quote;
				sanitize($quote);
			}
			out(array('results' =>
				array('success' => 1,
					'data' => $quotes,
					'count' => $search->count)));
		} else {
			out(array('results' =>
				array('success' => 0,
					'error' => 'no_quotes_found',
					'count' => 0)));
		}
		break;
	case 'delete':
		required_post(array('permaid'));
		if (check_key() == 0) {
			out(array('results' =>
				array('success' => 0,
					'error' => 'access_denied')));
		}
		$quote = new Quote();
		$quote->permaid = $_POST['permaid'];
		if ($quote->read()) {
			$quote->status = 'deleted';
			$quote->save(false);
			$session->log(sprintf('JSON API delete successful: %s - %s', $quote->permaid, $params[1]));
			out(array('results' =>
				array('success' => 1)));
		}
		out(array('results' =>
			array('success' => 0,
				'error' => 'no_such_quote')));
		break;
	default:
		$session->log(sprintf('JSON API access with invalid METHOD: %s', $params[1]));
		generic_error('method_not_implemented');
}
