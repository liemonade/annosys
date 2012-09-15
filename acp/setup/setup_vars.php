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

	require_once('../libs/db.class.php');
	require_once('../libs/config.inc.php');
	$db = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	$lines = file('defaults.var');
	foreach($lines as $line) {
		$pieces = explode(' : ', $line);
		$name = $pieces[0];
		$value = $pieces[1];
		$description = $pieces[2];
		$db->addVariable($name, $value, $description);
	}
?>