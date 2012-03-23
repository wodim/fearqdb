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

global $db, $config;

$query = 'SELECT id, domain, site_key, lang, locale, `collate`,
	ip_show, ip_host, ip_part, analytics_enabled, analytics_code,
	url, statics_url, snowstorm, db, irc, name, nname, cookie,
	privacy_level,	privacy_level_for_bots, page_size
	FROM sites WHERE domain = \'%s\'';
	
$results = $db->get_row(
	sprintf($query, 
		clean($_SERVER['HTTP_HOST'], MAX_DOMAIN_LENGTH, true)));

if (!$results) {
	$html->do_sysmsg('e.e', 'e.e', 404);
}

$config['site']['key'] = $results->site_key;
$config['site']['page_size'] = $results->page_size;
$config['site']['lang'] = $results->lang;
$config['site']['locale'] = $results->locale;
$config['site']['collate'] = $results->collate;
$config['site']['ip']['show'] = $results->ip_show;
$config['site']['ip']['host'] = $results->ip_host;
$config['site']['ip']['part'] = $results->ip_part;
$config['site']['analytics']['enabled'] = $results->analytics_enabled;
$config['site']['analytics']['code'] = $results->analytics_code;
$config['site']['domain'] = $results->url;
$config['site']['statics'] = $results->statics_url;
$config['db']['table'] = $results->db;
$config['site']['irc'] = $results->irc;
$config['site']['name'] = $results->name;
$config['site']['nname'] = $results->nname;
$config['site']['snowstorm'] = $results->snowstorm;
$config['site']['cookie_name'] = $results->cookie;
$config['site']['privacy_level'] = $results->privacy_level;
$config['site']['privacy_level_for_bots'] = $results->privacy_level_for_bots;
