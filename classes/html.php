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

class HTML {
	var $output = null;

	function do_header($title = null) {
		global $session, $settings;

		header('Content-Type: text/html; charset=utf-8');

		$topic = Array(
			'text' => format_whitespace(format_link($settings->topic_text)),
			'nick' => $settings->topic_nick,
		);
		$vars = compact('title', 'topic', 'session');
		$cached = Haanga::Load('header.html', $vars, true);
		$this->output .= $cached;
	}

	function do_footer() {
		global $start, $db, $session, $settings;

		$timestamp = array();
		$vars = compact('session', 'timestamp');
		$this->output .= Haanga::Load('footer.html', $vars, true);
	}

	function do_pages($page = 1, $total_pages, $query, $adjacents = 2) {
		if ($total_pages < 2) {
			return;
		}

		$dots = false;

		$pager = '<div class="pager">';
		if ($page == 1) {
			$pager .= '<span class="arrow">«</span>';
		} else {
			$pager .= '<a class="arrow" href="'.sprintf($query, $page - 1).'">«</a>';
		}

		for ($i = 1; $i < $total_pages + 1; $i++) {
			if ($i == 1 || $adjacents > abs($page - $i)) {
				if ($i == $page) {
					$pager .= '<span class="current">'.$i.'</span>';
				} else {
					$pager .= '<a href="'.sprintf($query, $i).'">'.$i.'</a>';
				}
				$dots = false;
			} else {
				if (!$dots) {
					$pager .= '<span>...</span>';
				}
				$dots = true;
			}
		}

		if ($page == $total_pages) {
			$pager .= '<span class="arrow">»</span>';
		} else {
			$pager .= '<a class="arrow" href="'.sprintf($query, $page + 1).'">»</a>';
		}
		return $pager.'</div>';
	}

	function do_sysmsg($title, $message, $code) {
		global $session;

		$session->sysmsg = true;

		header('HTTP/1.1 '.$code);
		$this->do_header($title);
		if (!$message) {
			$message = _('Are you lost?');
		}

		$session->log(sprintf('Soft error: (%d) %s - %s', $code, $title, $message));
		$session->hit();

		$vars = compact('title', 'message');
		$this->output .= Haanga::Load('sysmsg.html', $vars, true);
		$this->do_footer();
		echo $this->output;
		die();
	}
}
