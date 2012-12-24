<?php
/*
	fearqdb - quote database system
	Copyright (C) 2012 David Martí <neikokz at gmail dot com>

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

$statics = array('padlock_odd', 'padlock_even', 'medal_odd', 'medal_even');

if (count($params) < 3) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

if (!preg_match('/^[a-z_]+$/', $params[2])) {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

/* css */
if ($params[1] == 'css') {
	if (!file_exists(sprintf('templates/%s.css', $params[2]))) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	}

	foreach ($statics as $static) {
		/* we don't control whether these files exist, the list is hardcoded ^ */
		$timestamp[$static] = md5(sprintf('%s%s', filemtime(sprintf('statics/%s.png', $static)), $settings->site_key));
	}

	header('Cache-Control: public, max-age=31536000');
	header('Expires: Thu, 01 Jan 2099 00:00:00 GMT');
	header('Content-Type: text/css; charset=utf-8');
	$vars = compact('timestamp');
	Haanga::Load('core.css', $vars);
}

if ($params[1] == 'image') {
	if (!file_exists(sprintf('statics/%s.png', $params[2]))) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	}

	header('Cache-Control: public, max-age=31536000');
	header('Expires: Thu, 01 Jan 2099 00:00:00 GMT');
	header('Content-Type: image/png');
	readfile(sprintf('statics/%s.png', $params[2]));
}

if ($params[1] == 'js') {
	if (!file_exists(sprintf('statics/%s.js', $params[2]))) {
		$html->do_sysmsg(_('Page not found'), null, 404);
	}

	header('Cache-Control: public, max-age=31536000');
	header('Expires: Thu, 01 Jan 2099 00:00:00 GMT');
	header('Content-Type: application/x-javascript; charset=utf-8');
	readfile(sprintf('statics/%s.js', $params[2]));
}

die(); /* otherwise, the queries counter would be shown. */