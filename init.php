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
if (file_exists('local.php')) {
	require('local.php');
}

define('include_dir', 'include/');
define('modules_dir', 'modules/');
define('classes_dir', 'classes/');

require(include_dir.'utils.php');
require(include_dir.'defines.php');

/* initialise db */
require(classes_dir.'db.php');
$db = new DB();
$db->type = $config['db']['type'];
$db->debug = $config['db']['debug'];
$db->persistent = $config['db']['persistent'];
$db->file = $config['db']['file'];
$db->user = $config['db']['user'];
$db->pass = $config['db']['pass'];
$db->name = $config['db']['name'];
$db->socket = $config['db']['socket'];
$db->host = $config['db']['host'];
if (!$db->init()) {
	header('HTTP/1.1 500 Internal Server Error');
	die('DBE');
}

/* read settings, determine virtual host  */
require(classes_dir.'settings.php');
$settings = new Settings();
if (!$settings->init() &&
	!preg_match('/^\/_\//', $_SERVER['REQUEST_URI'])) {
	header('HTTP/1.1 500 Internal Server Error');
	die('VHE');
}

/* initialise memcache */
require(classes_dir.'memcache.php');
$memcache = new Memcache();
$memcache->enabled = $config['memcache']['enabled'];
$memcache->server = $config['memcache']['server'];
$memcache->port = $config['memcache']['port'];
$memcache->prefix = $config['memcache']['prefix'] ? $config['memcache']['prefix'] : 'fearqdb';
$memcache->debug = $config['memcache']['debug'];
$memcache->init();

/* encoding */
if ($db->type == 'mysql') {
	$db->query(sprintf('SET NAMES utf8 COLLATE %s', $settings->collate));
}
mb_internal_encoding('utf8');

// initialize Haanga
require(include_dir.'Haanga.php');
Haanga::configure(array(
	'template_dir' => 'templates/',
	'cache_dir' => 'templates/compiled/',
	'compiler' => array(
		'global' => array('settings', 'session'),
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

// initialize push engine
require(classes_dir.'push.php');
$push = new Push();
$push->init();

// configure gettext's locale
putenv('LC_ALL='.$settings->locale);
setlocale(LC_ALL, $settings->locale);
bindtextdomain('messages', './locale');
textdomain('messages');

// redir to /login if not in ^/login already
if (!is_bot() && ($settings->privacy_level == 2
	&& $session->level == 'anonymous'
	&& !preg_match(sprintf('/^%s(login|api|userlogin|_)/', preg_quote($settings->base_url, '/')), $_SERVER['REQUEST_URI']))) {
	$html->do_sysmsg(_('Log in'), _('You must log in to read any quote.'), 403);
}

/* make privacy_level_for_bots effective */
if (is_bot()
	&& $settings->privacy_level_for_bots == 2
	&& !preg_match(sprintf('/^%s(rss|robots\.txt)/', preg_quote($settings->base_url, '/')), $_SERVER['REQUEST_URI'])) {
	header('HTTP/1.1 403 Forbidden');
	die('403 Forbidden'); /* so what */
}
