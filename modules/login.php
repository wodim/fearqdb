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

global $params, $session, $html, $settings;

function origin_redir($origin) {
	if (strpos($origin, $settings->base_url) !== 0) {
		$origin = $settings->base_url;
	}
	redir($origin);
}

function user_redir() {
	global $session;

	if ($session->level != 'anonymous') { // it's already logged in, it's not logging out... so what the hell
		redir();
	}
}

$session->origin = '';

switch ($params[0]) {
	case 'userlogin':
		user_redir();
		$params[1] = isset($params[1]) ? $params[1] : '';
		switch ($params[1]) {
			case 'post':
				if (!isset($_POST['nick']) ||
					!isset($_POST['password']) ||
					!isset($_POST['origin'])) {
					$html->do_sysmsg('e.e', null, 403);
				}
				if ($session->create_user($_POST['nick'], $_POST['password'])) {
					origin_redir($origin);
				} else {
					redir(sprintf('%suserlogin/error', $settings->base_url));
				}
				break;
			case 'error':
			default:
				$origin = isset($params[2]) ? urlencode($params[2]) : $settings->base_url;
				$message = ($params[1] == 'error') ? _('Invalid password. Try again.') : '';
				$html->do_header(_('Log in'));
				$vars = compact('origin', 'message');
				Haanga::Load('userlogin.html', $vars);
				$html->do_footer();
		}
		break;
	case 'login':
		user_redir();
		if (isset($params[1]) && !$session->create($params[1])) {
			$html->do_sysmsg(_('Invalid password'), _('The password that that link provided was not valid. Maybe you clicked an outdated link?'), 403);
		}
		$origin = isset($params[2]) ? $params[2] : $settings->base_url;
		origin_redir($origin);
		break;
	case 'logout':
		if (!isset($params[1]) || $params[1] != $session->xsrf || $session->level == 'anonymous') {
			$html->do_sysmsg('e.e', null, 403);
		}
		$session->destroy();
		$origin = isset($params[2]) ? $params[2] : $settings->base_url;
		origin_redir($origin);
		break;
}
