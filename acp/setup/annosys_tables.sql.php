<?php

/*
	Copyright (C) 2012  Darvin Mertsch

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

$queries = array();
$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`extrapages` (
				`pageid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`name` VARCHAR( 100 ) NOT NULL ,
				`poster` VARCHAR( 50 ) NOT NULL ,
				`code` VARCHAR( 10000 ) NOT NULL ,
				`start_date` INT( 10 ) NOT NULL ,
				`end_date` INT( 10 ) NOT NULL ,
				`edit_date` INT( 10 ) NOT NULL ,
				`duration` INT( 3 ) NOT NULL ,
				`usergroups` VARCHAR( 500 ) NOT NULL ,
				UNIQUE (`name`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`extrapages`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`imports` (
				`filename` VARCHAR( 15 ) NOT NULL ,
				`filedate` INT( 10 ) NOT NULL ,
				`programm` INT( 2 ) NOT NULL ,
				UNIQUE (`filename`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`imports`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`messages` (
				`messageid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`messagetype` INT( 3 ) NOT NULL ,
				`senderid` INT NOT NULL ,
				`recipentid` INT NOT NULL ,
				`read` BOOL NOT NULL ,
				`subject` VARCHAR( 100 ) NOT NULL ,
				`message` VARCHAR( 10000 ) NOT NULL ,
				`send_date` INT( 10 ) NOT NULL
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`messages`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`outputprofiles` (
				`profileid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`profilename` VARCHAR( 50 ) NOT NULL ,
				`usergroups` VARCHAR( 500 ) NOT NULL ,
				`columns` VARCHAR( 1000 ) NOT NULL ,
				`theme` INT NOT NULL ,
				UNIQUE (`profilename`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`outputprofiles`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`rights` (
				`rightid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`rightname` VARCHAR( 50 ) NOT NULL ,
				`defaultValue` VARCHAR( 10000 ) NOT NULL ,
				`description` VARCHAR( 10000 ) NOT NULL ,
				UNIQUE (`rightname`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`rights`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`righttogroup` (
				`groupid` INT NOT NULL ,
				`rightid` INT NOT NULL ,
				`value` VARCHAR( 10000 ) NOT NULL
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`righttogroup`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`sessions` (
				`id` VARCHAR( 32 ) NOT NULL ,
				`data` TEXT NOT NULL ,
				`lastAccess` VARCHAR( 14 ) NOT NULL ,
				PRIMARY KEY (  `id` )
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`sessions`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`substitutions` (
				`date` INT( 10 ) NOT NULL ,
				`grade` INT( 2 ) NOT NULL ,
				`classes` VARCHAR( 30 ) NOT NULL ,
				`hour` VARCHAR( 7 ) NOT NULL ,
				`subject` VARCHAR( 20 ) NOT NULL ,
				`teacher` VARCHAR( 4 ) NOT NULL ,
				`status` VARCHAR( 20 ) NOT NULL ,
				`room` VARCHAR( 4 ) NOT NULL ,
				`supply` VARCHAR( 4 ) NOT NULL ,
				`postponement` VARCHAR( 50 ) NOT NULL ,
				`notice` VARCHAR( 100 ) NOT NULL ,
				`programm` INT( 2 ) NOT NULL
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`substitutions`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`themes` (
				`themeid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`themename` VARCHAR( 50 ) NOT NULL ,
				`path` VARCHAR( 100 ) NOT NULL ,
				UNIQUE (`themename`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`themes`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`tickermessages` (
				`tickerid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`usergroups` VARCHAR( 200 ) NOT NULL ,
				`message` VARCHAR( 500 ) NOT NULL ,
				`poster` VARCHAR( 50 ) NOT NULL ,
				`start_date` INT( 10 ) NOT NULL ,
				`end_date` INT( 10 ) NOT NULL
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`tickermessages`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`userdata` (
				`userid` INT NOT NULL,
				`dataname` VARCHAR ( 200 ) NOT NULL,
				`val` VARCHAR ( 5000 ) NOT NULL
				) ENGINE = MYSIAM;';
				
$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`userdata`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`usergroups` (
				`groupid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`groupname` VARCHAR( 50 ) NOT NULL ,
				`description` VARCHAR( 1000 ) NULL ,
				UNIQUE (`groupname`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`usergroups`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`users` (
				`userid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`username` VARCHAR( 50 ) NOT NULL ,
				`password` VARCHAR( 64 ) NOT NULL ,
				`email` VARCHAR( 100 ) NULL ,
				`usergroup` INT NOT NULL ,
				`regdate` INT( 10 ) NOT NULL ,
				`lastlogin` INT( 10 ) NOT NULL ,
				UNIQUE (`username`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`users`;';


$queries[] = 	'CREATE TABLE IF NOT EXISTS `{%%DBNAME%%}`.`variables` (
				`name` VARCHAR( 50 ) NOT NULL ,
				`value` VARCHAR( 10000 ) NOT NULL ,
				`description` VARCHAR( 1000 ) NULL ,
				UNIQUE (`name`)
				) ENGINE = MYISAM ;';

$queries[] = 	'TRUNCATE TABLE `{%%DBNAME%%}`.`variables`;';