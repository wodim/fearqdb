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
	const READ = 'id, permaid, nick, date, ip, text, comment, approved, hidden, UNIX_TIMESTAMP(date) as ts';
	const READ_SPEC = 'SELECT id, permaid, nick, date, ip, text, comment, approved, hidden, UNIX_TIMESTAMP(date) AS ts, db FROM quotes WHERE id = %d AND db = \'%s\'';
	const READ_SPEC_GUESS = 'SELECT id, permaid, nick, date, ip, text, comment, approved, hidden, UNIX_TIMESTAMP(date) AS ts, db FROM quotes WHERE id >= %d AND db = \'%s\'';

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
	var $approved = 0;
	var $hidden = 0;
	var $ts = 0;
	var $db = 0;

	// made out by the script (not stoerd in the db)
	var $new = false;
	var $semiip = '';
	var $permalink = '';
	var $timelapse = '';
	var $host = '';
	var $tweet = '';

	function read_permaid($permaid) {
		global $db, $config;

		$result = $db->get_var(sprintf('SELECT id FROM quotes WHERE permaid = \'%s\' AND db = \'%s\'',
			mysql_read_escape_string($permaid), $config['db']['table']));
			
		if ($result) {
			$this->read($result);
			return true;
		}
		
		return false;
	}

	function read($id = 0, $results = null, $guess = false, $permaid = false) {
		global $db, $config;

		if ($id && !$results) {
			$results = $db->get_row(sprintf($guess ? Quote::READ_SPEC_GUESS : Quote::READ_SPEC, (int)$id, $config['db']['table']));
		}

		// what a lose of time this shit is, especially when already having $obj
		// rework?
		if ($results) {
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

			if ($this->ip != 'kobaz') {
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

			$this->permalink = sprintf('%s%s', $config['core']['domain'], $this->permaid);
			$date = elapsed_time(date('U') - $this->ts);
			$this->timelapse = ($date == -1) ? false : $date;
			$this->read = true;
			return true;
		}
		return false;
	}

	function output($odd = true) {
		if (!$this->read) {
			printf('Eeeks! Trying to print a quote that haven\'t been read yet (id=%d)', $this->id);
			die();
		}

		$c = $this;
		$c->style = sprintf('%s%s', $odd ? 'odd' : 'even', $c->approved ? '' : ' unapproved');
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
		if (!$this->read) {
			printf('Eeeks! Trying to print a quote that haven\'t been read yet (id=%d)', $this->id);
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

		global $config;

		// clean only for www. rss uses the CDATA structure which does not require escaping
		if ($for == 'www_body' || $for == 'www_comment') {
			$text = htmlspecialchars($text);
		}

		// clean special chars from copypasting from irc
		$text = preg_replace('/[\x00-\x09\x0b-\x1F\x7F]/', '', $text);

		// delete timestamps
		$text = preg_replace('/^[\(\[]?\d+:\d+(:\d+)?[\)\]]?\s/m', '', $text);

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'rss_title') {
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

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'www_comment') {
			// don't add links to rss titles!
			$text = preg_replace('/(https?:\/\/[a-z0-9\.\-_\?=&,\/;%#]*)/mi', '<a href="$1" rel="nofollow" target="_blank">$1</a>', $text);
			$text = str_replace("\n", '<br />', $text);
			// hashtags
			// $text = preg_replace('/#([a-z0-9\-_\?]*\w)/mi', sprintf('<a href="%ssearch/%%23$1" target="_blank">#$1</a>', $config['core']['domain']), $text);
		} else {
			$text = str_replace("\n", ' ', $text);
		}

		// respect \s\s to fix asciis
		$text = str_replace('  ', '&nbsp;&nbsp;', $text);

		// cut long title
		if ($for == 'rss_title' || $for == 'www_tweet') {
			if (mb_strlen($text) > 110) {
				$text = sprintf('%s...', mb_substr($text, 0, 110));
			}
		}

		// fix double utf8 encoding
		if (strpos($text, 'Ã') !== false || strpos($text, 'Â') !== false) {
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
	function save($new = 'true') {
		global $db, $config;

		if ($new) {
			$result = $db->query(sprintf('INSERT INTO quotes (permaid, nick, date, ip, text, comment, db, hidden, approved)
				VALUES (\'%s\', \'%s\', NOW(), \'%s\', \'%s\', \'%s\', \'%s\', \'%d\', \'%d\')',
				/* no way of forcing a permaid */
				sprintf('%04x', rand(0, 65535)),
				mysql_real_escape_string($this->nick),
				/* date */
				mysql_real_escape_string($this->ip),
				mysql_real_escape_string($this->text),
				mysql_real_escape_string($this->comment),
				$config['db']['table'],
				(int)$this->hidden,
				(int)$this->approved));
		} else {
			$result = $db->query(sprintf('UPDATE quotes SET 
				nick = \'%s\', permaid = \'%s\', ip = \'%s\', text = \'%s\', comment = \'%s\', 
				db = \'%s\', hidden = %d, approved = %d
				where id = %d',
				mysql_real_escape_string($this->nick),
				$this->permaid,
				$this->ip,
				mysql_real_escape_string($this->text),
				mysql_real_escape_string($this->comment),
				$config['db']['table'],
				(int)$this->hidden,
				(int)$this->approved,
				(int)$this->id));
		}		

		return true;
	}
}
