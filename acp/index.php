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

$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_ENTER_ACP') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}
?>
<html>
<head>
	<title>Administration Control Panel</title>
	
	<link type="text/css" href="../css/smoothness/jquery-ui.css" rel="stylesheet" />
	<link type="text/css" href="css/jqueryui-settings.css" rel="stylesheet" />
	<link type="text/css" href="css/index.css" rel="stylesheet" />
	
	<script type="text/javascript" src="../js/sha1.js"></script>
	<script type="text/javascript" src="../js/libs/jquery.js"></script>
	<script type="text/javascript" src="../js/libs/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/libs/json2.js"></script>
	<link type="text/css" href="css/qTable.css" rel="stylesheet" />
	<script type="text/javascript" src="js/qTable.js"></script>
	<link type="text/css" href="css/ValidatingForm.css" rel="stylesheet" />
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="js/ValidatingForm.js"></script>

	<script type="text/javascript" src="js/index.js"></script>
	<script type="text/javascript" src="js/overview.js"></script>
	<link type="text/css" href="css/overview.css" rel="stylesheet" />
	<?php
	if ($_SESSION['USER']->right('CAN_SEE_EXTRAPAGES') == 'true') {
		echo '<script type="text/javascript" src="js/extrapages.js"></script>';
		echo '<link type="text/css" href="css/extrapages.css" rel="stylesheet" />';
	}
	if ($_SESSION['USER']->right('CAN_SEE_SUBSTITUTIONS') == 'true') {
		echo '<script type="text/javascript" src="js/substitutions.js"></script>';
	}
	if ($_SESSION['USER']->right('CAN_SEE_OUTPUTPROFILES') == 'true') {
		echo '<script type="text/javascript" src="js/outputprofiles.js"></script>';
	}
	if ($_SESSION['USER']->right('CAN_SEE_USERS') == 'true') {
		echo '<script type="text/javascript" src="js/users.js"></script>';
	}
	if ($_SESSION['USER']->right('CAN_SEE_USERGROUPS') == 'true') {
		echo '<script type="text/javascript" src="js/usergroups.js"></script>';
	}
	if ($_SESSION['USER']->right('CAN_SEE_TICKER') == 'true') {
		echo '<script type="text/javascript" src="js/ticker.js"></script>';
	}
	if ($_SESSION['USER']->right('CAN_SEE_SETTINGS') == 'true') {
		echo '<script type="text/javascript" src="js/settings.js"></script>';
	}
	?>

</head>
<body class="main">
	<div id="mainFrame">
		<div id="tabs">
			<ul>
				<li><a href="overview.php">&Uuml;berblick</a></li>
				
				<?php
				if ($_SESSION['USER']->right('CAN_SEE_SUBSTITUTIONS') == 'true') {
					echo '<li><a href="substitutions.php">Vertretungen</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_EXTRAPAGES') == 'true') {
					echo '<li><a href="extrapages.php">Extraseiten</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_OUTPUTPROFILES') == 'true') {
					echo '<li><a href="outputprofiles.php">Ausgabeprofile</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_USERS') == 'true') {
					echo '<li><a href="users.php">Benutzer</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_USERGROUPS') == 'true') {
					echo '<li><a href="usergroups.php">Benutzergruppen</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_TICKER') == 'true') {
					echo '<li><a href="ticker.php">Ticker</a></li>';
				}
				if ($_SESSION['USER']->right('CAN_SEE_SETTINGS') == 'true') {
					echo '<li><a href="settings.php">Einstellungen</a></li>';
				}
				?>

				<li><a href="logout.php">Ausloggen</a></li>
			</ul>
		</div>
	</div>
	<div id="loading"><img src="images/ajax-loader.gif" /></div>
</body>
</html>