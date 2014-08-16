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

/* Important: it's recommended to make a copy of this file called "local.php"
	and edit that file instead of this one, just in case. */

/* ---------- DB ---------- */
/* mysql or sqlite */
$config['db']['type'] = null;

/* show all queries */
$config['db']['debug'] = false;

/* use a persistent connection. could not work,
	but it is preferable. */
$config['db']['persistent'] = false;

/* for sqlite */
$config['db']['file'] = null;

/* for mysql */
$config['db']['user'] = null;
$config['db']['pass'] = null;
$config['db']['name'] = null;
/* 'socket' takes precedence over 'host'
	(it is obviously not possible to use both at once) */
$config['db']['socket'] = null;
$config['db']['host'] = null;

$sites = Array();

$sites[0] = Array();
/* domain. Example: qdb.es */
$sites[0]['domain'] = '';
/* base url. Most probably, / (don't touch it if unsure) */
$sites[0]['base_url'] = '/';
/* a private site key, used to generate cookies and other things.
   use a random string. */
$sites[0]['site_key'] = '';
/* language of the site, such as 'en' or 'es' */
$sites[0]['lang'] = 'en';
/* locale of the site */
$sites[0]['locale'] = 'es_ES.utf8';
/* collation for the database */
$sites[0]['collate'] = 'utf8_english_ci';
/* show the ip address of those who sent the quotes to all users.
   if it's false, they will be shown to registered users anyway. */
$sites[0]['ip_show'] = '';
/* google analytics code. leave blank if you don't use ga */
$sites[0]['analytics_code'] = '';
/* a different url for the statics folder. leave blank if you don't need it */
$sites[0]['statics_url'] = '';
/* the title of this site */
$sites[0]['title'] = '';
/* text shown at the footer */
$sites[0]['footer'] = '';
/* privacy level of the quotes. possible values:
   -1: all quotes are shown, even the hidden ones
    0: normal
    1: all quotes are hidden, but nick/date are visible
    2: you have to be logged in to actually read any quote
   the second variable applies to bots such as the google crawler */
$sites[0]['privacy_level'] = 0;
$sites[0]['privacy_level_for_bots'] = 0;
/* amount of quotes per page */
$sites[0]['page_size'] = 10;
/* allow or disallow robots with robots.txt */
$sites[0]['robots'] = 'allow';
/* push server used for sending topics from irc to the site */
$sites[0]['push_enabled'] = false;
$sites[0]['push_url'] = '';
$sites[0]['push_params'] = '';
/* extra css to insert in all pages */
$sites[0]['extra_css'] = '';