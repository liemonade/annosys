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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_USERS') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}


if (isset($_GET['action'])) {
	$action = $_GET['action'];
	
	// Alle Benutzer wurden angefordert
	if($action == 'getUsergroups') {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$_usergroups = $dataBase->getUsergroups(true);
		$rights = $dataBase->getRights();
		
		$usergroups = array();
		foreach ($_usergroups as $id => $groupdata) {
			$usergroups[] = array( 'groupid' => $id, 'groupname' => $groupdata['groupname'], 'description' => $groupdata['description']);
		}
		
		if ($usergroups && $rights) {
			echo json_encode(array( 'status' => 'success', 'usergroups' => $usergroups, 'rights' => $rights ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	else if ($action == 'getUsergroup' && is_numeric($_GET['usergroupid'])) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$usergroup = $dataBase->getUsergroup($_GET['usergroupid']);
		$usergroup['rights'] = $dataBase->getRightsForGroup($_GET['usergroupid'], isset($_GET['rightsById']) && $_GET['rightsById'] === 'true');
		
		if ($usergroup) {
			echo json_encode(array( 'status' => 'success', 'usergroup' => $usergroup ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	
	else if ($action == 'addUsergroup' && isset($_POST['groupname']) && isset($_POST['description']) && isset($_POST['rights'])) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$groupname = $_POST['groupname'];
		$description = $_POST['description'];
		
		$selectedRights = json_decode($_POST['rights']);
		$rights = $dataBase->getRights();
		
		$groupid = $dataBase->addUsergroup($groupname, $description);
		
		if ($groupid) {
			foreach ($selectedRights as $rightid) {
				$dataBase->setRightForGroup($rightid, $groupid, 'true');
			}
			
			$usergroup = $dataBase->getUsergroup($groupid);
			$usergroup['groupid'] = $groupid;
			echo json_encode(array( 'status' => 'success', 'usergroup' => $usergroup ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	
	else if ($action == 'editUsergroup' && isset($_POST['groupid']) && isset($_POST['groupname']) && isset($_POST['description']) && isset($_POST['rights'])) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$groupid = $_POST['groupid'];
		$groupname = $_POST['groupname'];
		$description = $_POST['description'];
		
		$selectedRights = json_decode($_POST['rights']);
		$rights = $dataBase->getRights();
		
		$status = $dataBase->deleteRightsForGroup($groupid);
		foreach ($selectedRights as $rightid) {
			$status &= $dataBase->setRightForGroup($rightid, $groupid, 'true');
		}
		
		$status &= $dataBase->editUsergroup($groupid, $groupname, $description);
		
		if ($status) {
			$usergroup = $dataBase->getUsergroup($groupid);
			$usergroup['groupid'] = $groupid;
			echo json_encode(array( 'status' => 'success', 'usergroup' => $usergroup ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		
	}
	
	
	else if ($action == 'deleteUsergroup' && isset($_GET['groupid']) && is_numeric($_GET['groupid'])) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$groupid = $_GET['groupid'];
		
		$status = $dataBase->deleteUsergroup($groupid);
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	
	else if ($action == 'deleteUsersByUsergroup' && isset($_GET['groupid']) && is_numeric($_GET['groupid'])) {
	$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$groupid = $_GET['groupid'];
		
		$status = $dataBase->deleteUsersByUsergroup($groupid);
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	die();
}

?>
<div id="usergroups">

	<!-- Tabelle mit den Benutzergruppen -->
	<div  class="table-contain ui-widget">
		<table id="usergroupsTable" class="ui-widget ui-widget-content"></table>
	</div>


	<!-- Buttons -->
	<button id="create-usergroup">Neue Benutzergruppe anlegen</button>
	<button id="delete-users-by-usergroup">Alle Benutzer einer Gruppe l&ouml;schen</button>

</div>