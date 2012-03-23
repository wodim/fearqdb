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

function all_quotes() {
	global $db;

	$results = $db->get_results('SELECT id FROM quotes');
	
	foreach ($results as $result) {
		$return[] = $result->id;
	}
	
	return $return;
}

switch ($params[1]) {
	/* example modules. */
	case 'utf8fix':
		/* fix double utf-8'd quotes */
		header('Content-Type: text/plain');
		die('Double UTF-8 already fixed');
		foreach (all_quotes() as $i) {
			$quote = new Quote();
			$quote->id = $i;
			if ($quote->read()) {
				printf("Read %d!\n", $i);
				if (strpos($quote->text, 'Ã') > 0 || strpos($quote->text, 'Â') > 0) {
					printf("Fixing %s\n", $quote->permaid);
					$quote->text = iconv('utf8', 'cp1252', $quote->text);
					$quote->save(false);
				}
			}
			unset($quote);
		}
		printf("End\n");
		break;
	case 'bot':
		/* delete (bot) and assign an api key */
		header('Content-Type: text/plain');
		die('API keys already assigned');
		foreach (all_quotes() as $i) {
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
		foreach (all_quotes() as $i) {
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
			$db->query(sprintf('INSERT INTO quotes (permaid, ip, nick, date, text, db, status)
				VALUES (\'%s\', \'kobaz\', \'Import\', NOW(), \'%s\', \'default\', \'approved\')',
				sprintf('%04x', rand(0, 65535)),
				escape($line)));
			printf("Inserted line.\n");
		}
		break;
}
