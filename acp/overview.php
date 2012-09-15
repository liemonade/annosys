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

$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_ENTER_ACP') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}

if (isset($_GET['action'])) {
	$action = $_GET['action'];
	
	if ($action == 'getOverview') {
		$overview = $dataBase->getOverview();
		$overview['user'] = $_SESSION['USER']->username;
		echo json_encode($overview);
		die();
	}
}
?>
<div id="overview">
	<h1>Guten Tag <font id="acpUsername"></font></h1>
	<div id="monitorOverview"></div>
	<br>
	<div id="statistics">
		<font class="statisticHeadline">Aktuelle Vertretungsdaten:</font> <font class="statisticValue" id="activesubstitutions"></font><br>
		<font class="statisticHeadline">Letzter Vertretungsdatenimport:</font> <font class="statisticValue" id=datetimesubstitutionsimport></font><br>
		<font class="statisticHeadline">Aktuelle Extraseiten:</font> <font class="statisticValue" id="activepages"></font><br>
		<font class="statisticHeadline">Aktuelle Tickernachrichten:</font> <font class="statisticValue" id="activemessages"></font><br>
		<font class="statisticHeadline">Eingeloggte Sch&uuml;ler:</font> <font class="statisticValue" id="schueleronline"></font><br>
		<font class="statisticHeadline">Anzahl der SEK I Sch&uuml;ler, die das System nutzen:</font> <font class="statisticValue" id="numschueler"></font><br>
	</div>
</div>