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

define('MAX_URL_LENGTH', 256);
define('MAX_REDIR_LENGTH', MAX_URL_LENGTH);
define('MAX_MODULE_LENGTH', 8);
define('MAX_SEARCH_LENGTH', MAX_URL_LENGTH);
define('MAX_DB_LENGTH', 16);
define('MAX_REFERER_LENGTH', MAX_URL_LENGTH);
define('MAX_UA_LENGTH', MAX_URL_LENGTH);
define('MAX_USER_LENGTH', 16);

define('MAX_LOG_LENGTH', MAX_URL_LENGTH);

define('PERMAID_LENGTH', 4);
define('MAX_QUOTE_LENGTH', -1);
define('MAX_NICK_LENGTH', 20);
define('MAX_COMMENT_LENGTH', 1000);
define('MAX_IP_LENGTH', 64);

define('MAX_DOMAIN_LENGTH', 32);
define('MAX_TOPIC_LENGTH', MAX_COMMENT_LENGTH);
