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

class Quote {
	const READ = 'id, permaid, nick, timestamp, ip, text, comment, status, hidden AS ts, db, api';
	const READ_BY_ID = 'SELECT id, permaid, nick, timestamp, ip, text, comment, status, hidden, db, api FROM quotes WHERE id = :id AND db = :db';
	const READ_BY_PERMAID = 'SELECT id, permaid, nick, timestamp, ip, text, comment, status, hidden, db, api FROM quotes WHERE permaid = :permaid AND db = :db';

	var $read = false;
	var $id = 0;
	var $permaid = '';
	var $nick = '';
	var $date = '';
	var $ip = '';
	var $text = '';
	var $comment = '';
	var $status = 'pending';
	var $hidden = 0;
	var $ts = 0;
	var $db = '';
	var $api = 0;

	// made out by the script (not stoerd in the db)
	var $new = false;
	var $semiip = '';
	var $permalink = '';
	var $timelapse = '';
	var $host = '';
	var $tweet = '';
	var $excerpt = '';
	var $name = '';
	var $password = '';
	var $forceshow = '';

	function read($results = null) {
		global $db, $settings, $session;

		/* we may already have results (eg. when called from list.php)
			but maybe we do not, so fetch from the db */
		if (!$results) {
			/* if we didn't have $results, no id and no permaid, this was a faulty rqeuest. */
			if (!$this->id && !$this->permaid) {
				$session->log('Using read() with no id or permaid and with no prebaked results');
				return false;
			}
			if ($this->id) {
				$results = $db->get_row(Quote::READ_BY_ID, array(
					array(':id', $this->id, PDO::PARAM_INT),
					array(':db', $settings->db, PDO::PARAM_STR)
				));
			} elseif ($this->permaid) {
				$results = $db->get_row(Quote::READ_BY_PERMAID, array(
					array(':permaid', $this->permaid, PDO::PARAM_STR),
					array(':db', $settings->db, PDO::PARAM_STR)
				));
			}
		}

		/* still no results? return */
		if (!$results) {
			return false;
		}

		foreach ($results as $variable => $value) {
			$this->$variable = ctype_digit($value) ? (int)$value : $value;
		}

		if (preg_match('/^(.*)!/', $this->nick, $matches)) {
			$this->nick = $matches[1];
		}

		// hackish but still right.
		switch (is_bot() ? $settings->privacy_level_for_bots : $settings->privacy_level) {
			case -1:
				$this->hidden = 0;
				break;
			case 1:
				$this->hidden = 1;
				break;
		}

		$this->generate();
		$this->read = true;
		return true;
	}

	function generate() {
		global $settings;

		$this->new = (date('U') - $this->timestamp < (60 * 60 * 24));
		$this->permalink = sprintf('%s%s', $settings->full_url, $this->permaid);
		$date = elapsed_time(date('U') - $this->timestamp);
		$this->timelapse = ($date == -1) ? false : $date;
		$this->hidden = (bool)$this->hidden;
		$this->excerpt = $this->text_clean($this->text, 'excerpt');
		$this->password = substr(md5(sprintf('a%sb%sc%sd', $settings->site_key, $this->permaid, date('d/m:H'))), 0, 8);
	}

	function output($odd = true) {
		global $session;
		if (!$this->read) {
			$session->log('Eeeks! Trying to print a quote that haven\'t been read yet (id=%d)', $this->id);
			die();
		}

		$c = $this;
		$c->style = sprintf('%s%s', $odd ? 'odd' : 'even', ($c->status == 'approved') ? '' : ' unapproved');
		$c->date = date('d/m/Y H:i:s', $c->timestamp);
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

		$c->ts = date('r', $c->timestamp);

		$vars = compact('c');
		Haanga::Load('rss-quote.html', $vars);

		return true;
	}

	private function text_clean($text, $for = 'www_body') {
		// this is real crap.

		global $settings, $session;

		// clean only for www. rss uses the CDATA structure which does not require escaping
		if ($for == 'www_body' || $for == 'www_comment') {
			$text = htmlspecialchars($text);
		}

		// mark the search criterium
		if ($session->search && ($for == 'www_body' || $for == 'www_comment')) {
			$text = highlight($text, $session->search);
		}

		// clean special chars from copypasting from irc
		$text = preg_replace('/[\x00-\x09\x0b-\x1F\x7F]/', '', $text);

		// delete timestamps
		$text = preg_replace('/^[\(\[]?\d+:\d+(:\d+)?[\)\]]?\s/m', '', $text);

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'rss_title' || $for == 'excerpt') {
			// add * to mark actions, joins, parts etc
			$text = preg_replace('/^([a-z0-9\[\]\{\}_])/smi', '* $1', $text); // :D
		}

		// hack for this db. old quotes came this way. so remove this once it's fixed or remove it if you start with a db from scratch.
		$text = preg_replace("/^\n/", '', $text);

		if ($for == 'www_body') {
			// nicks for the website
			$text = preg_replace_callback('/^&lt;[@\+\s]?([a-z0-9\-\[\]\{\}_`~]+)&gt;/mi', array($this, 'nick_colour'), $text);
		}

		if ($for == 'rss_body' || $for == 'rss_title') {
			// escape the cdata structure (avoid injections) + nicks for rss
			// nicks for rss never use < or &lt; because they DON'T WORK for rss readers
			// < works for some, &lt; for some... so we use ()
			$text = str_replace(']]>', ']]]]><![CDATA[>', $text); // http://en.wikipedia.org/wiki/CDATA#Nesting
			$text = preg_replace('/<[@\+\s]?([a-z0-9\-\[\]\{\}_`~]+)>/msi', '($1)', $text);
		}

		if ($for == 'excerpt') {
			$text = preg_replace('/<[@\+\s]?([a-z0-9\-\[\]\{\}_`~]+)>/msi', '<$1>', $text);
		}

		if ($for == 'www_body' || $for == 'rss_body' || $for == 'www_comment') {
			// don't add links to rss titles!
			$text = format_link($text);
			$text = str_replace("\n", '<br />', $text);

			// respect \s\s to fix asciis
			$text = format_whitespace($text);
		} else {
			$text = str_replace("\n", ' ', $text);
		}

		// cut long title
		if ($for == 'rss_title' || $for == 'www_tweet' || $for == 'excerpt') {
			if (mb_strlen($text) > 110) {
				$text = sprintf('%s...', mb_substr($text, 0, 110));
			}
		}

		return $text;
	}

	private function nick_colour($nick) {
		global $settings;

		$nick = $nick[1];
		$colour = substr(md5(strtolower($nick)), 0, 1);
		return sprintf('<strong>&lt;<em class="colour-%s"><a href="%ssearch/%s">%s</a></em>&gt;</strong>', $colour, $settings->base_url, urlencode($nick), $nick);
	}

	function save($new = true) {
		global $db, $settings, $push;

		foreach (array($this->nick, $this->text) as $check) {
			if (mb_strlen($check) < 3) {
				return false;
			}
		}

		if ($new) {
			do {
				$quote = new Quote();
				$permaid = $quote->permaid = sprintf('%04x', rand(0, 65535));
			} while ($quote->read());
			$result = $db->query('INSERT INTO quotes (permaid, nick, date, ip, text, comment, db, hidden, status, api)
				VALUES (:permaid, :nick, NOW(), :ip, :text, :comment, :db, :hidden, :status, :api)', array(
				array(':permaid', $permaid, PDO::PARAM_STR),
				array(':nick', $this->nick, PDO::PARAM_STR),
				/* NOW() */
				array(':ip', $this->ip, PDO::PARAM_STR),
				array(':text', $this->text, PDO::PARAM_STR),
				array(':comment', $this->comment, PDO::PARAM_STR),
				array(':db', $settings->db, PDO::PARAM_STR),
				array(':hidden', $this->hidden, PDO::PARAM_INT),
				array(':status', $this->status, PDO::PARAM_STR),
				array(':api', $this->api, PDO::PARAM_INT)
			));
			$this->permaid = $permaid;
			$this->generate();
			if ($this->status == 'approved') {
				$push->hit(sprintf(_('New quote: %s - %s'), $this->permalink, $this->excerpt));
			} else {
				$push->hit(sprintf(_('%s has sent a quote and it is pending approval.'), $this->nick));
			}
			return $this->permaid;
		} else {
			$result = $db->query('UPDATE quotes SET nick = :nick, ip = :ip, text = :text,
				comment = :comment, hidden = :hidden, status = :status, api = :api where id = :id', array(
				array(':nick', $this->nick, PDO::PARAM_STR),
				array(':ip', $this->ip, PDO::PARAM_STR),
				array(':text', $this->text, PDO::PARAM_STR),
				array(':comment', $this->comment, PDO::PARAM_STR),
				array(':hidden', $this->hidden, PDO::PARAM_INT),
				array(':status', $this->status, PDO::PARAM_STR),
				array(':api', $this->api, PDO::PARAM_INT),
				array(':id', $this->id, PDO::PARAM_INT)
			));
		}

		$settings->recount();
		return (bool)$result;
	}
}