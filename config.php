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

/* ---------- DB ---------- */
/* mysql or sqlite */
$config['db']['type'] = null;

/* show all queries */
$config['db']['debug'] = false;

/* use a persistent connection. could not work,
	but it is preferable. */
$config['db']['persistent'] = false;

/* for sqlite */
$config['db']['file'] = null;

/* for mysql */
$config['db']['user'] = null;
$config['db']['pass'] = null;
$config['db']['name'] = null;
/* 'socket' takes precedence over 'host'
	(it is obviously not possible to use both at once) */
$config['db']['socket'] = null;
$config['db']['host'] = null;

/* ---------- MEMCACHE ---------- */
$config['memcache']['enabled'] = false;
$config['memcache']['server'] = null;
$config['memcache']['port'] = null;
$config['memcache']['prefix'] = 'fearqdb';
$config['memcache']['debug'] = false;