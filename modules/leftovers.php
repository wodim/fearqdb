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

/* this isn't possible if base_url is not /. the user would have to include these
	files by hand. */
if ($settings->base_url != '/') {
	$html->do_sysmsg(_('Page not found'), null, 404);
}

switch ($params[0]) {
	case 'robots.txt':
		header('Content-Type: text/plain');
		switch ($settings->robots) {
			case 'disallow':
				$allow = "User-agent: *\nDisallow: /";
				break;
			case 'allow':
			default:
				$allow = "User-agent: *\nAllow: /\nDisallow: /_/";
		}
		echo $allow;
		break;
	case 'opensearch.xml':
		header('Content-Type: application/xml');
		Haanga::Load('opensearch.html');
		break;
	case 'favicon.ico':
		header('HTTP/1.1 301 Moved Permanently');
		header(sprintf('Location: %s_/image/fearqdb', $settings->statics_url));
}

die();