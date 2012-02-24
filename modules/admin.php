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

global $params, $config;

if (!isset($params[1])) {
	$params[1] = 'index';
}

switch ($params[1]) {
	case 'query':
		require_once(classes_dir.'admin/query.php');
		break;
	case 'dump':
		require_once(classes_dir.'admin/dump.php');
		break;
	case 'edit':
		require_once(classes_dir.'admin/edit.php');
		break;
	case 'index':
	default:
		require_once(classes_dir.'admin/index.php');
}
