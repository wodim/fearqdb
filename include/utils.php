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

function redir($location = null) {
	global $config;

	if (!$location) {
		$location = $config['site']['root'];
	}

	header('HTTP/1.0 302 Found');
	header('Location: '.$location);
	die();
}

/* START LEGACY, ie not being used anymroe */
// you could call this function with no arguments to get a mm.
function get_avatar_url($email = '') {
	return('http://www.gravatar.com/avatar/'.md5(trim(strtolower($email))).'/?d=mm');
}

function is_valid_nick($nick) {
	return(preg_match('/^[a-zA-Z0-9]{3,12}$/', $nick));
}

function is_valid_email($email) {
	return(preg_match('/^[a-z0-9\.\-_]+(\+[a-z0-9\.\-_]+)*@[a-z0-9\-\.]+\.[a-z]{2,4}$/i', strtolower($email)));
}

function system_message($code, $message) {
	header('HTTP/1.0 '.$code);
	die('<h3>'.$code.' - '.$message.'</h3>');
}
/* END LEGACY */

function escape($string) {
	global $db;
	
	return $db->escape($string);
}

function clean($string, $maxlen = 0, $escape = false) {
	$string = $maxlen ? substr(trim($string), 0, $maxlen) : trim($string);

	if ($escape) {
		escape($string);
	}

	return $string;
}

function is_bot() {
	return(preg_match('/bot|slurp/i', $_SERVER['HTTP_USER_AGENT']));
}

function elapsed_time($time) {
	$elapsed = $time;

	if ($time > 29030400) {
		$elapsed /= 29030400;
		return(($elapsed < 2) ? _('one year ago') : sprintf(_('%d years ago'), $elapsed));
	} elseif ($time > 2419200) {
		$elapsed /= 2419200;
		return(($elapsed < 2) ? _('one month ago') : sprintf(_('%d months ago'), $elapsed));
	} elseif ($time > 345600) {
		$elapsed /= 345600;
		return(($elapsed < 2) ? _('one week ago') : sprintf(_('%d weeks ago'), $elapsed));
	} elseif ($time > 86400) {
		$elapsed /= 86400;
		return(($elapsed < 2) ? _('one day ago') : sprintf(_('%d days ago'), $elapsed));
	} elseif ($time > 3600) {
		$elapsed /= 3600;
		return(($elapsed < 2) ? _('one hour ago') : sprintf(_('%d hours ago'), $elapsed));
	} elseif ($time > 60) {
		$elapsed /= 60;
		return(($elapsed < 2) ? _('one minute ago') : sprintf(_('%d minutes ago'), $elapsed));
	} else {
		return(($elapsed < 30) ? _('just now') : sprintf(_('%d seconds ago'), $elapsed));
	}
}
