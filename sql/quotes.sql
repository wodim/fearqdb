-- fearqdb - quote database system
-- Copyright (C) 2011-2012 David Martí <neikokz at gmail dot com>

-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.

-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.

-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- key: the API key. we recommend this to be a md5 or something similar but
--   nothing stops you from using any other value such a word or a password.
-- name: the name of the API, eg: 'Mobile', the name of your IRC bot, etc.
-- approved: if 0, the API key won't work.
-- db: the name of the db this API key works on, or empty if it will work on
--   all dbs
-- "1 - web" comes by default, DO NOT DELETE IT!
CREATE TABLE IF NOT EXISTS `api` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL,
  `name` varchar(20) NOT NULL,
  `approved` tinyint(1) NOT NULL,
  `db` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  `url` varchar(256) NOT NULL,
  `redir` varchar(256) NOT NULL,
  `module` varchar(8) NOT NULL,
  `search` varchar(256) NOT NULL,
  `db` varchar(16) NOT NULL,
  `level` enum('anonymous','reader','user') NOT NULL,
  `user` int(11) NOT NULL DEFAULT '0',
  `referer` varchar(256) NOT NULL,
  `user_agent` varchar(256) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  `time` datetime NOT NULL,
  `url` varchar(256) NOT NULL,
  `db` varchar(10) NOT NULL,
  `text` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permaid` varchar(4) NOT NULL,
  `nick` varchar(20) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `db` varchar(16) NOT NULL DEFAULT '',
  `status` enum('pending','approved','deleted') NOT NULL DEFAULT 'pending',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `api` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- domain: the domain (and only the domain) of your quote database.
--   eg: qdb.example.net
-- site_key: your site key; used to generate passwords, cookies and so on.
--   in case of disclosure, just change it.
-- lang: your language, for gettext.
--   eg: es
-- locale: your locale, for gettext.
--   eg: es_ES.UTF-8
-- collate: database collation for the search module.
--   eg: utf8_spanish_ci
--   This way if you do a search with "que", it will find "que" and "qué", which
--   is useful.
-- ip_show: whether ip addresses will be shown on the website.
-- ip_host: whether ip addresses will be resolved before being shown on the
--   website. Take into account that a slow DNS will slow down page generation
--   since we don't cache this.
-- ip_part: whether the last block of ip addresses and hosts will be hidden.
--   ** authorised API key owners can read all IP addresses, it doesn't matter **
--   **                 whether you set ip_show or ip_part                     **
-- analytics_enabled: include Google Analytics javascript code
-- analytics_code: your GA account
--   eg: UA-13371488-1
-- url: your quote database url, WITH the leading /
--   eg: http://qdb.example.net/
-- statics_url: the url that includes your static content, WITH the leading /
--   eg: http://qdb.example.net/statics/
--   eg: http://qdbstatic.net/
-- snowstorm: snow storm!
-- db: matches the 'db' field in all the other tables. allows you to have several
--   domains running in the same database/tables and using the same code.
-- irc: a brief description of where your irc channel is.
--   eg: "#goddammit at EFnet"
-- name: the name of your site used in the header, in the <title>, etc
--   eg: "#goddammit quote database"
-- nname: short name of your site, one word
--   eg: gddmtqdb
-- cookie: the name of the cookie your site will use
--   eg: qdb_session
-- privacy_level: privacy level for quotes for normal web users.
--   -1: all quotes are shown, even the hidden ones
--    0: normal
--    1: all quotes are hidden, but nick/date are visible
--    2: you have to be logged in to actually read any quote
-- privacy_level_for_bots: same but for bots, don't rely on this since a normal
--   user can change his/her user agent and make him/her look like a bot!
-- page_size: quotes per page
-- robots: 'disallow' to block robots via robots.txt

CREATE TABLE IF NOT EXISTS `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(32) NOT NULL,
  `site_key` varchar(32) NOT NULL,
  `lang` varchar(5) NOT NULL,
  `locale` varchar(16) NOT NULL,
  `collate` varchar(24) NOT NULL,
  `ip_show` tinyint(1) NOT NULL,
  `ip_host` tinyint(1) NOT NULL,
  `ip_part` tinyint(1) NOT NULL,
  `analytics_enabled` tinyint(1) NOT NULL,
  `analytics_code` varchar(16) NOT NULL,
  `url` varchar(32) NOT NULL,
  `statics_url` varchar(64) NOT NULL,
  `snowstorm` tinyint(1) NOT NULL,
  `db` varchar(10) NOT NULL,
  `irc` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `nname` varchar(10) NOT NULL,
  `cookie` varchar(16) NOT NULL,
  `privacy_level` int(11) NOT NULL,
  `privacy_level_for_bots` int(11) NOT NULL,
  `page_size` int(11) NOT NULL,
  `robots` enum('allow','disallow') NOT NULL DEFAULT 'allow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(16) NOT NULL,
  `password` varchar(128) NOT NULL,
  `salt` varchar(8) NOT NULL,
  `db` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

INSERT INTO `api` (`id`, `key`, `name`, `approved`, `db`) VALUES
(1, '', 'web', 0, '');
