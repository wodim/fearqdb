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

require_once('config.php');
require_once(include_dir.'utils.php');

class Quote {
	const READ = 'quotes.id, quotes.permaid, quotes.nick, quotes.date, quotes.ip, quotes.text, quotes.comment, quotes.status, quotes.hidden, UNIX_TIMESTAMP(quotes.date) AS ts, quotes.db, quotes.api, IF(quotes.api > 0, api.name, "") AS name';
	const READ_BY_ID = 'SELECT quotes.id, quotes.permaid, quotes.nick, quotes.date, quotes.ip, quotes.text, quotes.comment, quotes.status, quotes.hidden, UNIX_TIMESTAMP(quotes.date) AS ts, quotes.db, quotes.api, IF(quotes.api > 0, api.name, "") AS name FROM quotes, api WHERE quotes.id = %d AND db = \'%s\'';
	const READ_BY_PERMAID = 'SELECT quotes.id, quotes.permaid, quotes.nick, quotes.date, quotes.ip, quotes.text, quotes.comment, quotes.status, quotes.hidden, UNIX_TIMESTAMP(quotes.date) AS ts, quotes.db, quotes.api, IF(quotes.api > 0, api.name, "") AS name FROM quotes, api WHERE permaid = \'%s\' AND db = \'%s\'';

	var $read = false;
	var $id = 0;
	var $permaid = '';
	var $nick = '';
	var $date = '';
	var $ip = '';
	var $text = '';
	var $comment = '';
	var $upvotes = 0;
	var $downvotes = 0;
	var $reports = 0;
	var $views = 0;
	var $status = 'pending';
	var $hidden = 0;
	var $ts = 0;
	var $db = '';
	var $key = 0;

	// made out by the script (not stoerd in the db)
	var $new = false;
	var $semiip = '';
	var $permalink = '';
	var $timelapse = '';
	var $host = '';
	var $tweet = '';
	var $excerpt = '';
	var $name = '';

	function read($results = null) {
		global $db, $config, $session;

		/* we may already have results (eg. when called from list.php)
			but maybe we do not, so fetch from the db */
		if (!$results) {
			/* if we didn't have $results, no id and no permaid, this was a faulty rqeuest. */
			if (!$this->id && !$this->permaid) {
				$session->log('Using read() with no id or permaid and with no prebaked results');
				return false;
			}
			$query = $this->id ? 
				sprintf(Quote::READ_BY_ID, (int)$this->id, $config['db']['table']) :
				sprintf(Quote::READ_BY_PERMAID, clean($this->permaid, PERMAID_LENGTH, true), $config['db']['table']);
			$results = $db->get_row($query);
		}

		/* still no results? return */
		if (!$results) {
			return false;
		}

		foreach (get_object_vars($results) as $variable => $value) {
			$this->$variable = $value;
		}

		if (preg_match('/^(.*)!/', $this->nick, $matches)) {
			$this->nick = $matches[1];
		}

		// hackish but still right.
		switch (is_bot() ? $config['site']['privacy_level_for_bots'] : $config['site']['privacy_level']) {
			case -1:
				$this->hidden = 0;
				break;
			case 1:
				$this->hidden = 1;
				break;
		}

		$this->new = (date('U') - $this->ts < (60 * 60 * 24));
		$valid = preg_match_all('/(\d+)$/', $this->ip, $hide);
		$hide = $valid ? $hide[1][0] : '';

		if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $this->ip)) {
			if ($config['site']['ip']['host']) {
				$host = gethostbyaddr($this->ip);
				if (!$host || $host == $this->ip) {
					$this->host = $this->semihost = null;
				} else {
					$this->host = $host;
				}
			}

			if ($config['site']['ip']['part']) {
				preg_match_all('/^(\d*\.\d*\.\d*)\.(.*)/', $this->ip, $parts);
				$this->semiip = sprintf('%s.*', $parts[1][0]);
				if ($this->host) {
					$this->semihost = str_replace($parts[2][0], '*', $this->host);
				}
			}
		} else {
			$this->semiip = _('Imported from the bot');
		}

		$this->permalink = sprintf('%s%s', $config['site']['domain'], $this->permaid);
		$date = elapsed_time(date('U') - $this->ts);
		$this->timelapse = ($date == -1) ? false : $date;
		$this->hidden = (bool)$this->hidden;
		$this->excerpt = $this->text_clean($this->text, 'excerpt');
		$this->read = true;
		return true;
	}

	function output($odd = true) {
		global $session;
		if (!$this->read) {
			$session->log('Eeeks! Trying to print a quote that haven\'t been read yet (id=%d)', $this->id);
			die();
		}

		$c = $this;
		$c->style = sprintf('%s%s', $odd ? 'odd' : 'even', ($c->status == 'approved') ? '' : ' unapproved');
		$c->date = date('d/m/Y H:i:s', $c->ts);
		$c->tweet = $this->text_clean($c->text, 'www_tweet');
		$c->tweet = urlencode(sprintf('%s - %s', $c->tweet, $c->permalink));
		$c->tweet = sprintf('https://twitter.com/intent/tweet?text=%s', $c->tweet);
		$c->text = $this->text_clean($c->text, 'www_body');
		$c->comment = $this->text_clean($c->comment, 'www_comment');

		$vars = compact('c');
		Haanga::Load('quote.html', $vars);

		return true;
	}

	function output_rss() {
		global $session;
		if (!$this->read) {
			$session->log('Eeeks! Trying to print a quote that haven\'t been read yet (id=%d)', $this->id);
			die();
		}

		$c = $this;

		$c->title = $this->text_clean($c->text, 'rss_title');
		$c->text = $this->text_clean($c->text, 'rss_body');

		$c->ts = date('r', $c->ts);

		$vars = compact('c');
		Haanga::Load('rss-quote.html', $vars);

		return true;
	}

	private function text_clean($text, $for = 'www_body') {
		// this is real crap.

		global $config, $session;

		// clean only for www. rss uses the CDATA structure which does not require escaping
		if ($for == 'www_body' || $for == 'www_comment') {
			$text = htmlspecialchars($text);
		}

		// mark the search criterium
		if ($for == 'www_body' && $session->search) {			
			$pos = 0;
			$length = mb_strlen($session->search);
			$criteria = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $session->search));
			do {
				// unfortunately we have to do this each time...
				$plain = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $text));
				$offset = mb_strpos($plain, $criteria, $pos);
				if (!$offset) {
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
		}

		// clean special chars from copypasting from irc
		$text = preg_replace('/[\x00-\x09\x0b-\x1F\x7F]/', '', $text);

		// delete timestamps
		$text = preg_replace('/^[\(\[]?\d+:\d+(:\d+)?[\)\]]?\s/m', '', $text);

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'rss_title' || $for == 'excerpt') {
			// add * to mark actions, joins, parts etc
			$text = preg_replace('/^([a-z0-9\-\[\]\{\}_])/smi', '* $1', $text); // :D
		}

		// hack for this db. old quotes came this way. so remove this once it's fixed or remove it if you start with a db from scratch.
		$text = preg_replace("/^\n/", '', $text);

		if ($for == 'www_body') {
			// nicks for the website
			$text = preg_replace_callback('/^&lt;[@+]?([a-z0-9\-\[\]\{\}_]+)&gt;/mi', array($this, 'nick_colour'), $text);
		}

		if ($for == 'rss_body' || $for == 'rss_title') {
			// escape the cdata structure (avoid ijnections) + nicks for rss
			// nicks for rss never use < or &lt; because they DON'T WORK for rss readers
			// < works for some, &lt; for some... so we use ()
			$text = str_replace(']]>', ']]]]><![CDATA[>', $text); // http://en.wikipedia.org/wiki/CDATA#Nesting
			$text = preg_replace('/<[@\+]?([a-z0-9\_\-\[\]\{\}]*)>/msi', '($1)', $text);
		}
		
		if ($for == 'excerpt') {
			$text = preg_replace('/<[@\+]?([a-z0-9\_\-\[\]\{\}]*)>/msi', '<$1>', $text);
		}

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'www_comment') {
			// don't add links to rss titles!
			$text = preg_replace('/(https?:\/\/[a-z0-9\.\-_\?=&,\/;%#]*)/mi', '<a href="$1" rel="nofollow" target="_blank">$1</a>', $text);
			$text = str_replace("\n", '<br />', $text);
			// hashtags
			// $text = preg_replace('/#([a-z0-9\-_\?]*\w)/mi', sprintf('<a href="%ssearch/%%23$1" target="_blank">#$1</a>', $config['site']['domain']), $text);

			// respect \s\s to fix asciis
			$text = str_replace('  ', '&nbsp;&nbsp;', $text);
		} else {
			$text = str_replace("\n", ' ', $text);
		}

		// cut long title
		if ($for == 'rss_title' || $for == 'www_tweet' || $for == 'excerpt') {
			if (mb_strlen($text) > 110) {
				$text = sprintf('%s...', mb_substr($text, 0, 110));
			}
		}

		// fix double utf8 encoding
		if (strpos($text, 'Ã') > 0 || strpos($text, 'Â') > 0) {
			$text = iconv('utf8', 'cp1252', $text);
		}

		return $text;
	}
	
	private function nick_colour($nick) {
		$nick = $nick[1];
		$colour = substr(md5(strtolower($nick)), 0, 1);
		return(sprintf('<strong>&lt;<em class="colour-%s"><a href="/search/%s">%s</a></em>&gt;</strong>', $colour, $nick, $nick));
	}

	// unused? hm
	function save($new = true) {
		global $db, $config;

		if ($new) {
			$result = $db->query(sprintf('INSERT INTO quotes (permaid, nick, date, ip, text, comment, db, hidden, status, api)
				VALUES (\'%s\', \'%s\', NOW(), \'%s\', \'%s\', \'%s\', \'%s\', \'%d\', \'%s\', \'%d\')',
				/* no way of forcing a permaid */
				sprintf('%04x', rand(0, 65535)),
				clean($this->nick, MAX_NICK_LENGTH, true),
				/* date */
				clean($this->ip, MAX_IP_LENGTH, true),
				escape($this->text),
				clean($this->comment, MAX_COMMENT_LENGTH, true),
				$config['db']['table'],
				(int)$this->hidden,
				escape($this->status),
				(int)$this->api));
		} else {
			$result = $db->query(sprintf('UPDATE quotes SET 
				nick = \'%s\', ip = \'%s\', text = \'%s\', comment = \'%s\', 
				db = \'%s\', hidden = %d, status = %s, api = %d
				where id = %d',
				clean($this->nick, MAX_NICK_LENGTH, true),
				clean($this->ip, MAX_IP_LENGTH, true),
				escape($this->text),
				clean($this->comment, MAX_COMMENT_LENGTH, true),
				$config['db']['table'],
				(int)$this->hidden,
				escape($this->status),
				(int)$this->api,
				(int)$this->id));
		}		

		return true;
	}
}
