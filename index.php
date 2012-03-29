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

require('config.php');

$params = explode('/', trim($_SERVER['REQUEST_URI']));
array_shift($params);

foreach ($params as $k => $v) {
	$params[$k] = urldecode($v);
}

$params[0] = isset($params[0]) ? $params[0] : 'home';

switch ($params[0]) {
	// TODO this should be configurable on config.php, per domain
	case 'robots.txt':
		$module = 'robots';
		require(modules_dir.'leftovers.php');
		break;
	case 'opensearch.xml':
		$module = 'opensearch';
		require(modules_dir.'leftovers.php');
		break;
	case 'admin':
		require(modules_dir.'admin.php');
		break;
	case 'submit':
		require(modules_dir.'submit.php');
		break;
	case 'quote':
		require(modules_dir.'quote.php');
		break;
	case 'random':
		require(modules_dir.'random.php');
		break;
	case 'last':
		require(modules_dir.'last.php');
		break;
	case 'submit':
		require(modules_dir.'submit.php');
		break;
	case 'rss':
		require(modules_dir.'rss.php');
		break;
	case 'api':
		require(modules_dir.'api.php');
		break;
	case 'search':
		require(modules_dir.'search.php');
		break;
	case 'panel':
		require(modules_dir.'panel.php');
		break;
	case 'userlogin':
	case 'login':
	case 'logout':
		$module = 'session';
		require(modules_dir.'login.php');
		break;
	case 'hidden':
	case 'page':
	case 'home':
		$module = 'list';
		require(modules_dir.'list.php');
		break;
	default:
		if (!$params[0]) {
			$module = 'list';
			require(modules_dir.'list.php');
		} else {
			$module = 'quote';
			require(modules_dir.'quote.php');
		}
}

if (!isset($module)) {
	$module = $params[0];
}

$session->hit();
