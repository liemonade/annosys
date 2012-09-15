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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_EXTRAPAGES') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}


if (isset($_GET['action'])) {
	$action = $_GET['action'];
	
	// Alle Ausgabeprofile werden angefordert
	if ($action == 'getOutputprofiles') {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$outputprofiles = $dataBase->getOutputprofiles();
		$usergroups = $dataBase->getUsergroups();
		$_themes = $dataBase->getThemes();
		
		$themes = array();
		foreach ($_themes as $theme) {
			$themes[$theme['themeid']] = $theme['themename'];
		}
		
		if ($outputprofiles && $usergroups && $themes) {
			echo json_encode(array( 'status' => 'success', 'outputprofiles' => $outputprofiles, 'usergroups' => $usergroups, 'themes' => $themes ));
		}
		else
			echo json_encode(array( 'status'=>'fail' ));
		die();
	}
	
	// Es wird ein Ausgabeprofil angefordert
	else if ($action == 'getOutputprofile' && isset($_GET['profileid']) && is_numeric($_GET['profileid']) ) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$profileid = $_GET['profileid'];
		$profile = $dataBase->getOutputprofile($profileid);
		if ($profile)
			echo json_encode(array('status'=>'success', 'outputprofile'=>$profile));
		else
			echo json_encode(array('status'=>'fail'));
	}
	
	// Es soll ein neues Ausgabeprofil eingetagen werden
	else if ($action == 'addOutputprofile') {
		if (!isset($_POST['profilename']) || !isset($_POST['theme']) || !isset($_POST['usergroups']) || !isset($_POST['columns'])
			|| empty($_POST['profilename']) || empty($_POST['theme']) || empty($_POST['usergroups']) || empty($_POST['columns'])
		) {
			echo json_encode(array('status'=>'MISSING_DATA'));
			die();
		}
		$profilename = $_POST['profilename'];
		$usergroups = $_POST['usergroups'];
		$columns = $_POST['columns'];
		$theme = $_POST['theme'];
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$profileid = $dataBase->addOutputprofile($profilename, $usergroups, $columns, $theme);
		if ($profileid) {
			$dataBase->editVariable('GLB_LASTUPDATE_OUTPUTPROFILES', time());
			echo json_encode(array('status'=>'success', 'profileid'=>$profileid));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	else if ($action == 'editOutputprofile') {
		if (!isset($_POST['profileid']) || !isset($_POST['profilename']) || !isset($_POST['theme']) || !isset($_POST['usergroups']) || !isset($_POST['columns'])
			|| empty($_POST['profileid']) || empty($_POST['profilename']) || empty($_POST['theme']) || empty($_POST['usergroups']) || empty($_POST['columns'])
		) {
			echo json_encode(array('status'=>'MISSING_DATA'));
			die();
		}
		$profileid  = $_POST['profileid'];
		$profilename = $_POST['profilename'];
		$usergroups = $_POST['usergroups'];
		$columns = $_POST['columns'];
		$theme = $_POST['theme'];
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = $dataBase->editOutputprofile($profileid, $profilename, $usergroups, $columns, $theme);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_OUTPUTPROFILES', time());
			$profile = $dataBase->getOutputprofile($profileid);
			echo json_encode(array('status' => 'success', 'profile' => $profile));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	// Es soll ein Ausgabeprofil gelöscht werden
	else if ($action == 'deleteOutputprofile' && isset($_GET['profileid']) && is_numeric($_GET['profileid']) ) {
		$profileid = $_GET['profileid'];
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->deleteOutputprofile($profileid);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_OUTPUTPROFILES', time());
			echo json_encode(array('status'=>'success'));
		}
		else
			echo json_encode(array('status'=>'fail'));
	}
	
	die();
}
?>

<div id="outputprofiles">
	
	<!-- MAIN Tabelle -->
	<div  class="table-contain ui-widget">
		<table id="outputprofilesTable" class="ui-widget ui-widget-content"></table>
	</div>
	
	<!-- Buttons -->
	<button id="create-outputprofile">Neues Ausgabeprofil</button>
	
	<!-- Diagog zum Erstellen und Editieren von Ausgabeprofilen -->
	<div id="frmOutputprofiles" title="Neues Ausgabeprofil hinzuf&uuml;gen">
		<form>
			<fieldset>
				<label for="outputprofileName">Ausgabeprofilname</label>
				<input type="text" name="outputprofileName" id="outputprofileName" class="text ui-widget-content ui-corner-all" />
				<label for="outputprofileTheme">Zu benutzender Theme</label>
				<select id="outputprofileTheme" name="outputprofileTheme" class="text ui-widget-content ui-corner-all" size="1" style="width: 100px;">
					<?php
						$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$themes = $dataBase->getThemes();
						if ($themes) {
							foreach ($themes as $theme) {
								echo '<option value="'.$theme['themeid'].'">'.$theme['themename'].'</option>';
							}
						}
					?>
				</select>
				<label for="outputprofileUsergroups">Zielgruppen</label>
				<div id="outputprofileUsergroups" class="checkgroup">
					<?php
						$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$usergroups = $dataBase->getUsergroups();
						foreach($usergroups as $groupid=>$groupname) {
							echo "<input type=\"checkbox\" id=\"$groupid\"><label for=\"$groupid\">$groupname</label>";
						}
					?>
				</div>
				<label for="outputprofileOutputelements1">Auszugebene Daten und Sortierung</label>
				<ul id="outputprofileOutputelements1" name="outputprofileOutputelements1" class="outputprofileOutputelements ui-widget-content ui-corner-all">
				<?php
					$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
					$headlineVars = $dataBase->getVariablesByPrefix('GLB_SUBSTTABLE_HEADLINE_');
					if ($headlineVars) {
						foreach ($headlineVars as $headlineVar) {
							$headline = json_decode($headlineVar['value'], true);
							$headlines[$headline['id']] = $headline['alias'];
						}
						for ($i = 1; $i <= sizeof($headlines); $i++) {
							echo '<li class="ui-state-default" id="'.$i.'">'.$headlines[$i].'</li>';
							if ($i % 4 == 0)
								echo '</ul><ul id="outputprofileOutputelements'.($i/4+1).'" name="outputprofileOutputelements'.($i/4+1).'" class="outputprofileOutputelements ui-widget-content ui-corner-all">';
						}
					}
				?>
				</ul>
				
			</fieldset>
		</form>
	</div>
	
	<!-- Hier landen die nicht verwendeten Überschriften -->
	<ul id="hidden-headlines" style="visibility:hidden;"></ul>
</div>