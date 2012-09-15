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
	if($action == 'getUsers') {
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$usergroups = $dataBase->getUsergroups();
		$users = $dataBase->getUsers();
		
		// Den usergroupids werden die entsprechenden Gruppennamen zugeordnet
		if ($users) {
			echo json_encode(array('status'=>'success', 'users'=>$users, 'usergroups'=>$usergroups));
		}
		else
			echo json_encode(array('status'=>'fail'));
		die();
	}
	
	// Es werden die Daten eines spezifischen Benutzers angefordert
	if ($action == 'getUser') {
		if (!isset($_GET['userid']) || !is_numeric($_GET['userid'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$user = $dataBase->getUser($_GET['userid']);
		unset($user['password'], $user['lastlogin'], $user['regdate']);
		$user['userdata'] = $dataBase->getDataByUser($_GET['userid']);
		
		if ($user)
			echo json_encode(array('status'=>'success', 'user'=>$user));
		else
			echo json_encode(array('status'=>'fail'));
		die();
	}
	
	// Es soll ein Benutzer hinzugefügt werden
	else if($action == 'addUser') {
		if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email']) || !isset($_POST['usergroup'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$username = $_POST['username'];
		$password = $_POST['password'];
		$email = $_POST['email'];
		$usergroup = $_POST['usergroup'];
		$userdata = json_decode($_POST['userdata']);
		
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$userid = $dataBase->registerUser($username, $password, $email, $usergroup);
		
		if ($userid) {
			if ($userdata) {
				foreach ($userdata as $dataname => $value) {
					$dataBase->addUserdata($userid, $dataname, $value);
				}
			}
			$userinfo = $dataBase->getUser($userid);
			$userinfo['userid'] = $userid;
			unset($userinfo['password']);
			echo json_encode(array('status'=>'success', 'user' => $userinfo));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
		die();
	}
	
	// Es werden Extraseiten in Form von CSV hochgeladen.
	if ($_GET['action'] == 'importcsv') {
		if (!isset($_FILES['usersCSV'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		
		$uploadedCSV = $_FILES['usersCSV'];
		
		if (substr($uploadedCSV['name'], -4, 4) != '.csv') {
			echo json_encode(array('error'=>'NOT_A_CSV_FILE'));
			die();
		}
		
		$fd = @fopen($uploadedCSV['tmp_name'], 'r');
		if(!$fd) {
			echo json_encode(array('error'=>'COULD_NOT_OPEN_FILE'));
			die();
		}

		// Aus der Datenbank auslesen wie die Kopfzeilen in der CSV-Datei heißen sollen
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$registry = \Registry::getInstance();
		
		// Nötigen Variablen aus der Registry laden.
		$neededVariables = array(
								'USERS_CSV_IMPORT_HEADLINE_EMAIL',
								'USERS_CSV_IMPORT_HEADLINE_PASSWORD',
								'USERS_CSV_IMPORT_HEADLINE_USERDATA',
								'USERS_CSV_IMPORT_HEADLINE_USERGROUP',
								'USERS_CSV_IMPORT_HEADLINE_USERNAME'
							);
		$undefinedVariables = $registry->defineVariables($neededVariables);
		
		// Es ist Fehler aufgetreten
		if ( count($undefinedVariables) > 0 ) {
			throw new SystemException($_SERVER["PHP_SELF"].': Es konnten nicht alle Variablen aus der Datenbank gelesen werden. Es fehlen: '.implode(', ', $undefinedVariables));
		}
		
		// Suchen der Kopfzeilen in der Datei
		$headlines = fgetcsv($fd, 1000, ';', '"');
		for ($i = 0; $i < sizeof($headlines); $i++) {
			switch ($headlines[$i]) {
				case USERS_CSV_IMPORT_HEADLINE_USERNAME:
					$usernameIdx = $i;
					break;
				case USERS_CSV_IMPORT_HEADLINE_PASSWORD:
					$passwordIdx = $i;
					break;
				case USERS_CSV_IMPORT_HEADLINE_USERGROUP:
					$usergroupIdx = $i;
					break;
				case USERS_CSV_IMPORT_HEADLINE_EMAIL:
					$emailIdx = $i;
					break;
				case USERS_CSV_IMPORT_HEADLINE_USERDATA:
					$userdataIdx = $i;
					break;
			}
		}
		
		if ( !isset($usernameIdx) || !isset($passwordIdx) || !isset($usergroupIdx) || !isset($emailIdx) || !isset($userdataIdx) ) {
			echo json_encode(array('error' => 'CSV_FORMAT_NOT_CORRECT'));
			die();
		}
		else {
			$importedUsers = array();
			$everyUserIsImported = true;
			while (($data = fgetcsv($fd, 1000, ';', '"')) != false) {
				$username = $data[$usernameIdx];
				$password = $data[$passwordIdx];
				$usergroup = $data[$usergroupIdx];
				$email = $data[$emailIdx];
				$userdata = explode(':', $data[$userdataIdx]);
				
				if ( $userid = $dataBase->registerUser($username, $password, $email, $usergroup) ) {
					if ($userdata) {
						for ($idx = 0; $idx < sizeof($userdata)/2; $idx++) {
							$dataname = $userdata[2*$idx];
							$value = $userdata[2*$idx+1];
							$dataBase->addUserdata($userid, $dataname, $value);
						}
					}
					$importedUsers[] = array('userid' => $userid, 'username' => $username,  'email' => $email, 
											'usergroup' => $usergroup, 'regdate'=>time(),'lastlogin'=> time()
											);
				}
				else {
					$everyUserIsImported = false;
				}
			}
			
			$dataBase->editVariable('GLB_LASTUPDATE_EXTRAPAGES', time());
			if ($everyUserIsImported) {
				echo json_encode(array('status'=>'IMPORT_SUCCESSFULL', 'importedUsers'=>$importedUsers));
				die();
			}
			else {
				echo json_encode(array('status'=>'IMPORT_NOT_COMPLETE', 'importedUsers'=>$importedUsers));
				die();
			}
		}
	}
	
	
	// Es soll eine Extraseite gelöscht werden
	else if($action == 'deleteUser') {
		if (!isset($_GET['userid'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$userid = $_GET['userid'];
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->deleteUser($userid);
		$dataBase->deleteDataByUser($userid);
		
		if($status) {
			echo json_encode(array('status'=>'success'));
		}
		else
			echo json_encode(array('status'=>'fail'));
		die();
	}
	
	// Es soll ein Benutzer editiert werden
	else if($action == 'editUser') {
		if (!isset($_POST['userid']) || !isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email']) || !isset($_POST['usergroup']) || !isset($_POST['userdata'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$userid = $_POST['userid'];
		$username = $_POST['username'];
		$password = $_POST['password'];
		$email = $_POST['email'];
		$usergroup = $_POST['usergroup'];
		$userdata = json_decode($_POST['userdata']);
		
		$status = $dataBase->editUser($userid, $username, $password, $email, $usergroup) && $dataBase->deleteDataByUser($userid);
		
		if ($userdata) {
			foreach ($userdata as $dataname => $value) {
				$status = $status && $dataBase->addUserdata($userid, $dataname, $value);
			}
		}
		
		if ($status) {
			$userinfo = $dataBase->getUser($userid);
			$userinfo['userid'] = $userid;
			unset($userinfo['password']);
			echo json_encode(array('status'=>'success', 'user' => $userinfo));
		}
		else
			echo json_encode(array('status'=>'fail'));
		die();
	}
}


?>
<div id="users">
	
	<!-- Tabelle mit den Extraseiten -->
	<div id="users-contain" class="ui-widget">
		<table id="usersTable"></table>
	</div>
	
	
	<!-- Buttons -->
	<button id="create-user">Neuen Benutzer anlegen</button>
	<button id="import-users-csv">Benutzer via .csv importieren</button>
	
	
	<!-- Dialog zum Erstellen und Editieren von Benutzern -->
	<div id="frmUser" title="Neuen Benutzer hinzuf&uuml;gen">
		<form>
			<fieldset>
				<label for="usersUsername">Benutzername</label>
				<input type="text" name="usersUsername" id="usersUsername" class="text ui-widget-content ui-corner-all" />
				<label for="usersPassword">Passwort</label>
				<input type="password" name="usersPassword" id="usersPassword" value="" class="text ui-widget-content ui-corner-all" />
				<label for="usersEmail">E-Mail</label>
				<input type="text" name="usersEmail" id="usersEmail" value="" class="text ui-widget-content ui-corner-all" />
				<label for="usersUsergroup">Benutzergruppe</label>
				<div id="usersUsergroup" class="checkgroup">
					<?php
						$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$usergroups = $dataBase->getUsergroups();
						foreach($usergroups as $groupid=>$groupname) {
							echo "<input type=\"radio\" id=\"$groupid\" name=\"radio\"><label for=\"$groupid\">$groupname</label>";
						}
					?>
				</div>
				<label for="usersUserdata">Benutzerdaten</label>
				<table id="usersUserdata" name="usersUserdata" style="font-size:10px;" width="97%">
					<tr align="center"><td>Variablenname</td><td>Wert</td></tr>
					<tr><td><input type="text" /></td><td><input type="text" /></td><td><div class="removeUserdataBtn"></div></td></tr>
				</table>
			</fieldset>
		</form>
	</div>
	
	
	
	<!-- Contrainer für den "Ajax"-Upload -->
	<iframe name="upload-frame" id="upload-frame" style="display: none;"></iframe>
	
	<!-- Form für das Hochladen von CSV-Datein -->
	<div id="frmImportCSV" title="Importieren von Benutzern mittels einer CSV-Datei">
		<form method="POST" enctype="multipart/form-data">
			<fieldset>
				<label for="usersCSV">Pfad zur *.csv-Datei</label>
				<input width="300" type="file" id="usersCSV" name="usersCSV" class="text ui-widget-content ui-corner-all" />
				<center><div id="submitCSV">Hochladen</div></center>
			</fieldset>
		</form>
	</div>

</div>