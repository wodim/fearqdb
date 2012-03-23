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

class HTML {
	function do_header($title) {
		global $session;

		header('Content-Type: text/html; charset=UTF-8');
		$vars = compact('title', 'session');
		Haanga::Load('header.html', $vars);
	}

	function do_footer() {
		global $start, $db, $session;

		$vars = compact('session');
		Haanga::Load('footer.html', $vars);
		printf('<!-- %.4f - %d -->', (microtime(true) - $start), $db->num_queries);
	}

	function do_pages($page = 1, $total_pages, $query, $adjacents = 3) {
		if ($total_pages < 2) {
			return;
		}

		$dots = false;

		$pager = '<div class="pager">';
		if ($page == 1) {
			$pager .= '<span>'._('« prev').'</span>';
		} else {
			$pager .= '<a href="'.sprintf($query, $page - 1).'">'._('« prev').'</a>';
		}

		for ($i = 1; $i < $total_pages + 1; $i++) {
			if ($i == 1 || $i == $total_pages || $adjacents > abs($page - $i)) {
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
			$pager .= '<span>'._('next »').'</span>';
		} else {
			$pager .= '<a href="'.sprintf($query, $page + 1).'">'._('next »').'</a>';
		}
		return $pager.'</div>';
	}

	function do_sysmsg($title, $message, $code) {
		global $session;

		header('HTTP/1.1 '.$code);
		$this->do_header($title);
		if (!$message) {
			$message = _('Are you lost?');
		}
		
		$session->log(clean(sprintf('Soft error: (%d) %s - %s', $code, $title, $message), 256, true));
		
		$vars = compact('title', 'message');
		Haanga::Load('sysmsg.html', $vars);
		$this->do_footer();
		die();
	}
}
