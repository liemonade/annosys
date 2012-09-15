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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_TICKER') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}


if (isset($_GET['action'])) {
	$action = $_GET['action'];
	
	// Alle Tickernachrichten werden angefordert
	if ($action == 'getTickermessages') {
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$tickermessages = $dataBase->getTickermessages();
		$usergroups = $dataBase->getUsergroups();
		
		if ($tickermessages && $usergroups) {
			echo json_encode(array( 'status' => 'success', 'tickermessages' => $tickermessages, 'usergroups' => $usergroups ));
		}
		else {
			echo json_encode(array('status' => 'fail'));
		}
		
	}
	
	// Es wird eine Tickernachricht angefordert
	if ( $action == 'getTickermessage' && isset($_GET['tickerid']) && is_numeric($_GET['tickerid']) ) {
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$tickermessage = $dataBase->getTickermessage($_GET['tickerid']);
		
		if ($tickermessage) {
			echo json_encode(array('status' => 'success', 'tickermessage'=>$tickermessage));
		}
		else {
			echo json_encode(array('status' => 'fail'));
		}
	}
	
	// Eine neue Tickernachricht soll eingetragen werden
	if ($action == 'addTickermessage') {
		if (!isset($_POST['usergroups']) || !isset($_POST['message']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])
			|| empty($_POST['usergroups']) || empty($_POST['message']) || empty($_POST['start_date']) || empty($_POST['end_date'])
		) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$usergroups = json_decode($_POST['usergroups']);
		if (get_magic_quotes_gpc()) {
			$message = stripslashes($_POST['message']);
		}
		else {
			$message = $_POST['message'];
		}
		$poster = $_SESSION['USER']->username;
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$tickerid = $dataBase->addTickermessage($usergroups, $message, $poster, $start_date, $end_date);
		if ($tickerid) {
			$dataBase->editVariable('GLB_LASTUPDATE_TICKERMESSAGES', time());
			$tickermessage = $dataBase->getTickermessage($tickerid);
			$tickermessage['tickerid'] = $tickerid;
			echo json_encode(array('status'=>'success', 'tickermessage' => $tickermessage));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	// Es soll eine Tickernachricht editiert werden
	if ($action == 'editTickermessage') {
		if (!isset($_POST['tickerid']) || !isset($_POST['usergroups']) || !isset($_POST['message']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$tickerid = $_POST['tickerid'];
		$usergroups = json_decode($_POST['usergroups']);
		if (get_magic_quotes_gpc()) {
			$message = stripslashes($_POST['message']);
		}
		else {
			$message = $_POST['message'];
		}
		$poster = $_SESSION['USER']->username;
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->editTickermessage($tickerid, $usergroups, $message, $poster, $start_date, $end_date);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_TICKERMESSAGES', time());
			$tickermessage = $dataBase->getTickermessage($tickerid);
			$tickermessage['tickerid'] = $tickerid;
			echo json_encode(array('status'=>'success', 'tickermessage' => $tickermessage));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	// Eine Tickernachricht soll gelöscht werden
	if ($action == 'deleteTickermessage' && isset($_GET['tickerid']) && is_numeric($_GET['tickerid']) ) {
		$tickerid = $_GET['tickerid'];
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->deleteTickermessage($tickerid);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_TICKERMESSAGES', time());
			echo json_encode(array('status'=>'success'));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	die();
}
?>

<div id="ticker">
	
	<!-- MAIN Tabelle -->
	<div id="sites-contain" class="ui-widget">
		<table id="tblTicker" class="ui-widget ui-widget-content"></table>
	</div>
	
	<!-- Buttons -->
	<button id="create-tickermessage">Neue Tickernachricht</button>
	
	
</div>