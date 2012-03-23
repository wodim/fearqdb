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

$config['site']['include'] = 'include';
$config['site']['modules'] = 'modules';
$config['site']['classes'] = 'classes';

define('include_dir', $config['site']['include'].'/');
define('modules_dir', $config['site']['modules'].'/');
define('classes_dir', $config['site']['classes'].'/');
define('statics_dir', $config['site']['statics'].'/');

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

// initialize Haanga
require(include_dir.'Haanga.php');
Haanga::configure(array(
	'template_dir' => 'templates/',
	'cache_dir' => 'templates/compiled/',
	'compiler' => array(
		'global' => array('config', 'session'),
		'strip_whitespace' => true,
		'allow_exec' => false,
		'autoescape' => false
	)
));

// initialize the html engine
require(classes_dir.'html.php');
$html = new HTML();

/* this has to be initialized right after HTML and Session,
	since we need HTML to display errors and Session to store them */
// !!TODO nvm about session, store them later? We already die inside settings
require(include_dir.'settings.php');

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
