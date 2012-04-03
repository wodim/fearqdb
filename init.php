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

$config['site']['include'] = 'include';
$config['site']['modules'] = 'modules';
$config['site']['classes'] = 'classes';

define('include_dir', $config['site']['include'].'/');
define('modules_dir', $config['site']['modules'].'/');
define('classes_dir', $config['site']['classes'].'/');

require(include_dir.'utils.php');
require(include_dir.'defines.php');

require(include_dir.'ezsql/shared/ez_sql_core.php');
require(include_dir.'ezsql/mysql/ez_sql_mysql.php');

$db = new ezSQL_mysql();

if (!@$db->quick_connect($config['db']['user'], $config['db']['pass'],
	$config['db']['name'], $config['db']['host'])) {
	// ?
	header('HTTP/1.1 500 Internal Server Error');
	die('DBE');
}

$db->query('SET NAMES `utf8`');

/* !!FIXME include a fallback $session->log() system? we may want to store
	warnings to review them later, this is WRONG
	the database is already initialised at this moment but html is not because
	it needs some values stored in the sites table to work, such as the
	statics path and it's not a good idea to hardcode it.
	maybe it's a good idea to do a dry query even though $session already does
	it later?
	!!TODO no double query intended!!
	!!TODO the only fired warning inside this is, at this moment, 'HTTP_HOST not
	in range', we are not validating anything but it's not needed */
require(include_dir.'settings.php');

// initialize Haanga
require(include_dir.'Haanga.php');
Haanga::configure(array(
	'template_dir' => 'templates/',
	'cache_dir' => 'templates/compiled/',
	'compiler' => array(
		'global' => array('config', 'session'),
		'strip_whitespace' => false,
		'allow_exec' => false,
		'autoescape' => false
	)
));

// initialize the html engine
require(classes_dir.'html.php');
$html = new HTML();

// initiailze session
require(classes_dir.'session.php');
$session = new Session();
$session->init();

// configure gettext's locale
putenv('LC_ALL='.$config['site']['locale']);
setlocale(LC_ALL, $config['site']['locale']);
bindtextdomain('messages', './locale');
textdomain('messages');

// force https ?
if (isset($_SERVER['HTTPS'])) {
	redir(sprintf('http://%s%s', $_SERVER['HTTP_HOST'], $_GET['q']));
}

// redir to /login if not in ^/login already
if (!is_bot() && ($config['site']['privacy_level'] == 2
	&& $session->level == 'anonymous' && !preg_match('/^\/(login|api)/', $_SERVER['REQUEST_URI']))) {
	$html->do_sysmsg(_('Log in'), _('You must log in to read any quote.'), 403);
	die();
}

if (is_bot() && $config['site']['privacy_level_for_bots'] == 2) {
	header('HTTP/1.1 403 Forbidden');
	die('403 Forbidden'); /* so what */
}
