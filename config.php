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

$start = microtime(true);

$config['site']['root'] = '/';
// used to generate cookies and the site password!
// change it in case of disclosure
$config['site']['key'] = 'thisisakey';
// quotes per page
$config['site']['page_size'] = 10;
$config['site']['lang'] = 'es';
$config['site']['locale'] = 'es_ES.utf8';
// database collation - depends on the language of the quotes.
$config['site']['collate'] = 'utf8_spanish_ci';

// show ips?
$config['site']['ip']['show'] = true;
// resolve ips? REMEMBER, THIS IS NOT STORED IN THE DB
// SO A SLOW DNS COULD MAKE THE WHOLE SITE SLOW!
$config['site']['ip']['host'] = false;
// hide part of the ip/host
$config['site']['ip']['part'] = true;

// we do support google analytics.
$config['site']['analytics']['enabled'] = false;
// UA-xxxxxxxx-x
$config['site']['analytics']['code'] = '';

/* it *must* include /, or even a full path including domain */
$config['core']['statics'] = 'http://localhost/statics/';

$config['db']['user'] = 'qdb';
$config['db']['pass'] = 'qdb';
$config['db']['name'] = 'qdb';
$config['db']['host'] = 'localhost';

$config['site']['snowstorm'] = false;

// per domain config
switch ($_SERVER['HTTP_HOST']) {
	case 'productionqdb.example.com':
		// database "table", IT'S NOT A TABLE BUT A COLUMN!!
		// you don't need to create new tables.
		$config['db']['table'] = 'production';
		$config['site']['irc'] = '#goddammit at EFnet';
		$config['site']['name'] = '#goddammit fun';
		$config['site']['nname'] = 'goddammit';
		$config['core']['domain'] = sprintf('http://%s/', $_SERVER['HTTP_HOST']);
		$config['site']['analytics']['enabled'] = true;
		$config['site']['analytics']['code'] = 'UA-123123123-9';
		$config['site']['snowstorm'] = true;
		
		$config['site']['api']['keys'][] = 'one_api_key';
		$config['site']['api']['keys'][] = 'another_api_key';

		break;
	case 'localhost':
		$config['db']['table'] = 'test';
		$config['site']['irc'] = 'testing';
		$config['site']['name'] = 'testing';
		$config['site']['nname'] = 'general';
		$config['core']['domain'] = sprintf('http://%s/', $_SERVER['HTTP_HOST']);
		$config['site']['analytics']['enabled'] = false;
		break;
}

$config['site']['cookie_name'] = $config['site']['nname'].'_sess';

/* PRIVACY LEVEL
	controls the privacy level of the site.
	-1 = all quotes are public even the hidden ones
	0 = normal (only the hidden quotes are hiddne)
	1 = all quotes are hidden but the user/date are visible
	2 = every access ends up redirected to /login (unless the user is logged, ofc) */
// this setting can be per domain, of course.
$config['site']['privacy_level'] = 0;
$config['site']['privacy_level_for_bots'] = 2;

/* you shouldn't need to configure anything above this point */


$config['core']['include'] = 'include';
$config['core']['modules'] = 'modules';
$config['core']['classes'] = 'classes';

define('include_dir', $config['core']['include'].'/');
define('modules_dir', $config['core']['modules'].'/');
define('classes_dir', $config['core']['classes'].'/');
define('statics_dir', $config['core']['statics'].'/');

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

// initialize the session (cookie, ...)
// require(classes_dir.'session.php');
// $session = new Session();

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
