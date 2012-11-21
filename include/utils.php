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
	global $session, $settings;

	if (!$location) {
		$location = $settings->base_url;
	}

	$session->hit(true, $location);

	header('HTTP/1.0 302 Found');
	header('Location: '.$location);
	die();
}

function escape($string) {
	debug(sprintf('escape("%s")', $string));
	return ($string);
}

function clean($string, $maxlen = 0, $escape = false) {
	$string = $maxlen ? substr(trim($string), 0, $maxlen) : trim($string);

	if ($escape) {
		$string = escape($string);
	}

	return $string;
}

function is_bot() {
	return isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|slurp/i', $_SERVER['HTTP_USER_AGENT']);
}

function elapsed_time($time) {
	$elapsed = $time;

	if ($time > 29030400) {
		$elapsed /= 29030400;
		return(($elapsed < 2) ? _('one year ago') : sprintf(_('%d years ago'), $elapsed));
	} elseif ($time > 2419200) {
		$elapsed /= 2419200;
		return(($elapsed < 2) ? _('one month ago') : sprintf(_('%d months ago'), $elapsed));
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

function sha512($string) {
	return hash('sha512', $string);
}

function format_link($string) {
	global $settings;

	$string = preg_replace_callback('/(https?:\/\/[a-z0-9\.\-_\?=&,\/;%#:]+)/mi', 'format_link_shorten', $string);
	$string = preg_replace('/#([a-f0-9]{4})/mi', sprintf('<a href="%s$1">#$1</a>', $settings->base_url), $string);
	return $string;
}

function format_link_shorten($match) {
	$link = htmlspecialchars_decode($match[1]);
	$text = (strlen($link) > 45) ? sprintf('%s...', substr($link, 0, 45)) : $link;
	$text = preg_replace('/^https?:\/\//', '', $text);
	return sprintf('<a href="%s" rel="nofollow" target="_blank">%s</a>', htmlspecialchars($link), htmlspecialchars($text));
}

function format_whitespace($string) {
	$string = str_replace('  ', '&nbsp;&nbsp;', $string);
	return $string;
}

function highlight($text, $highlight) {
	$pos = 0;
	$length = mb_strlen($highlight);
	$criteria = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $highlight));
	do {
		// unfortunately we have to do this each time...
		$plain = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', translit_fix($text)));
		$offset = mb_strpos($plain, $criteria, $pos);
		if ($offset === false) {
			break;
		}
		$first = '<strong class="criteria">';
		$last = '</strong>';

		$text = sprintf('%s%s%s%s%s',
			mb_substr($text, 0, ($offset)),
			$first,
			mb_substr($text, $offset, $length),
			$last,
			mb_substr($text, ($offset + $length)));
		$pos = $offset + strlen($first) + $length + strlen($last);
	} while (1);

	return $text;
}

function translit_fix($text) {
	return str_replace(
		array('«', '»', '€'),
		array('<', '>', 'e'),
		$text);
}

function array_to_class($input) {
	if (is_array($input)) {
		return (object)array_map(__FUNCTION__, $input);
	} else {
		return $input;
	}
}

function debug($message) {
	printf('<span style="border: 1px solid white; background: red; font-weight: bold; font-size: 9pt; color: white; padding: 3px 5px; display: inline-block;">%s</span>%s', $message, "\n");
}