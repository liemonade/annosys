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

require_once 'autoloader.inc.php';

require_once _INCLUDE_FILES_PATH_.'config.inc.php';
require_once _INCLUDE_FILES_PATH_.'sessionmanagement.inc.php';
session_destroy();
?>
<html>
<head>
	<title>Logout</title>
	<meta http-equiv="refresh" content="2; URL=index.php">
</head>
<body>
	Ihre Sitzung wurde erfolgreich beendet.
	<br />
	Klicken sie <a href="index.php">HIER</a> falls sie nicht automatisch weitergeleitet werden.
</body>
</html>