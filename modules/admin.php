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
require_once(classes_dir.'quote.php');

global $params, $config, $db;

if (!isset($params[1])) {
	$params[1] = 'index';
}

switch ($params[1]) {
	/* example modules. */
	case 'bot':
		/* delete (bot) and assign an api key */
		header('Content-Type: text/plain');
		die('API keys already assigned');
		for ($i = 2; $i < 650; $i++) {
			printf("Assigning API key (or not) to %d...\n", $i);
			$quote = new Quote();
			$quote->id = $i;
			if ($quote->read()) {
				printf("Read %d!\n", $i);
				if (strpos($quote->nick, ' (bot)') > 0) {
					printf("%s - %d was added using the bot - setting key\n", $quote->permaid, $i);
					$quote->nick = str_replace(' (bot)', '', $quote->nick);
					$quote->api = 1;
					$quote->save(false);
					printf("Key on %d set\n", $i);
				}
			} else {
				printf("Unreadable %d\n", $i);
			}
			unset($quote);
		}
		printf("End\n");
		break;
	case 'assign':
		/* assign permaids to all quotes. 
			useful if you had no permaids... */
		header('Content-Type: text/plain');
		die('Permaids already assigned');
		for ($i = 2; $i < 650; $i++) {
			printf("Creating permaid for %d...\n", $i);
			$quote = new Quote();
			$quote->id = $i;
			if ($quote->read()) {
				printf("Read %d!\n", $i);
				$quote->permaid = sprintf('%04x', rand(0, 65535));
				printf("New permaid for %d is %s\n", $i, $quote->permaid);
				$quote->save(false);
				printf("Saved %d\n", $i);
			} else {
				printf("Unreadable %d\n", $i);
			}
			unset($quote);
		}
		printf("End\n");
		break;
	case 'massimport':
		/* import all quotes from a text file.
			one line per quote. */
		header('Content-Type: text/plain');
		die('Importing done');
		$lines = file('quotes.txt');
		foreach ($lines as $line) {
			$db->query(sprintf('INSERT INTO quotes (permaid, ip, nick, date, text, db, approved)
				VALUES (\'%s\', \'kobaz\', \'Import\', NOW(), \'%s\', \'default\', 1)',
				sprintf('%04x', rand(0, 65535)),
				escape($line)));
			printf("Inserted line.\n");
		}
		break;
/*
	case 'query':
		require_once(classes_dir.'admin/query.php');
		break;
	case 'dump':
		require_once(classes_dir.'admin/dump.php');
		break;
	case 'edit':
		require_once(classes_dir.'admin/edit.php');
		break;
	case 'index':
	default:
		require_once(classes_dir.'admin/index.php');
*/
}
