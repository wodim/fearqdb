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

require(classes_dir.'quote.php');

global $params, $db;

if (!isset($params[1])) {
	$params[1] = 'index';
}

function all_quotes($all_dbs = true) {
	global $db, $settings;

	if ($all_dbs) {
		$results = $db->get_results('SELECT id FROM quotes');
	} else {
		$results = $db->get_results('SELECT id FROM quotes WHERE db = :db', array(
			array(':db', $settings->db, PDO::PARAM_STR)
		));
	}

	foreach ($results as $result) {
		$return[] = $result->id;
	}

	return $return;
}

header('HTTP/1.1 403 Forbidden');
header('Content-Type: text/plain');
die('Access denied');

switch ($params[1]) {
	/* example modules. */
	case 'utf8fix':
		/* fix double utf-8'd quotes */
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
		$lines = file('quotes.txt');
		foreach ($lines as $line) {
			$db->query('INSERT INTO quotes (permaid, ip, nick, date, text, db, status)
				VALUES (:permaid, :ip, :nick, NOW(), :text, :db, :status)', array(
				array(':permaid', sprintf('%04x', rand(0, 65535)), PDO::PARAM_STR),
				array(':ip', 'kobaz', PDO::PARAM_STR),
				array(':nick', 'Imported', PDO::PARAM_STR),
				/* NOW() */
				array(':text', $line, PDO::PARAM_STR),
				array(':db', 'default', PDO::PARAM_STR),
				array(':status', 'approved', PDO::PARAM_STR)
			));
			printf("Inserted line.\n");
		}
		break;
	case 'recount':
		/* recount all quotes,
			for approved_quotes pending_quotes */
		$settings->recount();
		printf("End\n");
		break;
	case 'sort':
		/* sort all quotes by date, REGARDLESS OF db.
		this will fuck all ids.
		it copies them to another table.
		CREATE TABLE quotes2 LIKE quotes
		it does not use Quote::new() to make it possible to force a permaid
		pray */
		$quotes = $db->get_results('SELECT * FROM quotes ORDER BY date');
		foreach ($quotes as $quote) {
			$db->query('INSERT INTO quotes2 (permaid, nick, date, ip, text, comment, db, status, hidden, api)
				VALUES (:permaid, :nick, :date, :ip, :text, :comment, :db, :status, :hidden, :api)', array(
				array(':permaid', $quote->permaid, PDO::PARAM_STR),
				array(':nick', $quote->nick, PDO::PARAM_STR),
				array(':date', $quote->date, PDO::PARAM_STR),
				array(':ip', $quote->ip, PDO::PARAM_STR),
				array(':text', $quote->text, PDO::PARAM_STR),
				array(':comment', $quote->comment, PDO::PARAM_STR),
				array(':db', $quote->db, PDO::PARAM_STR),
				array(':status', $quote->status, PDO::PARAM_STR),
				array(':hidden', $quote->hidden, PDO::PARAM_INT),
				array(':api', $quote->api, PDO::PARAM_INT)
			));
			printf("Inserted %s\n", $quote->permaid);
		}
		printf("End after %d queries\n", $db->num_queries);
		break;
}