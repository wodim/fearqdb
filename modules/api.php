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

function hide_sensitive($quote) {
	unset($quote->read);
	unset($quote->id);
	$quote->ip = $quote->semiip;
	unset($quote->semiip);
	unset($quote->upvotes);
	unset($quote->downvotes);
	unset($quote->reports);
	unset($quote->views);
	unset($quote->approved);
	unset($quote->db);
	unset($quote->permalink);
	unset($quote->host);
	unset($quote->tweet);

	return $quote;
}

function check_key($key) {
	global $config;
	
	if (!isset($config['site']['api']['keys']) 
		|| count($config['site']['api']['keys']) == 0) {
		return false;
	}
	
	foreach ($config['site']['api']['keys'] as $api_key) {
		if ($key == $api_key) {
			return true;
		}
	}
	
	return false;
}

function required_post($variables) {
	foreach ($variables as $var) {
		if (!isset($_POST[$var])) {
			generic_error('not_enough_parameters');
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	generic_error('post_required');
}

if (isset($params[2]) && check_key($params[2])) {
	$session->level = 'reader';
}

if (!isset($params[1])) {
	$params[1] = '';
}

switch ($params[1]) {
	case 'send':
		required_post(array('nick', 'text'));
		$quote = new Quote();
		$quote->nick = $_POST['nick'];
		$quote->ip = $session->ip;
		$quote->text = $_POST['text'];
		$quote->comment = $_POST['comment'];
		$quote->hidden = (isset($_POST['hidden']) && ((int)$_POST['hidden'] == 0 || (int)$_POST['hidden'] == 1)) ? (int)$_POST['hidden'] : '0';
		$quote->approved = ($session->level != 'anonymous') ? '1' : '0';
		$quote->save();
		$last = get_last();
		out(array('results' =>
			array('success' => 1,
				'url' => sprintf('%s%s', $config['core']['domain'], $last),
				'permaid' => $last)));
		break;
	case 'last':
		$last = get_last();
		out(array('results' =>
			array('url' => sprintf('%s%s', $config['core']['domain'], $last),
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
		$quote = hide_sensitive($quote);
		if ($quote->hidden && $session->level == 'anonymous') {
			unset($quote->text);
			unset($quote->comment);
			unset($quote->ip);
		}
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
				if ($result->hidden) {
					$result->text = '';
					$result->comment = '';
				}
				$result->semiip = '';
				$result = hide_sensitive($result);
				unset($result->ip);
				$results[] = $result;
			}
			out(array('results' =>
				array('success' => 1,
					'data' => $results,
					'count' => $search->count)));
		} else {
			out(array('results' =>
				array('success' => 0,
					'error' => 'no_quotes_found',
					'count' => 0)));
		}		
		break;
	default:
		generic_error('method_not_implemented');
}
