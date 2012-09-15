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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_SETTINGS') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}

if(isset($_GET['action'])) {
	$action = $_GET['action'];
	
	if ($action == 'getVariables') {
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$variables = $dataBase->getVariables(false);
		if ($variables) {
			echo json_encode(array( 'status' => 'success', 'variables' => $variables ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	
	if ($action == 'getVariable' && isset($_GET['name'])) {
		$name = $_GET['name'];
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$variable = $dataBase->getVariable($name);
		if ($variable) {
			echo json_encode(array('status' => 'success', 'variable' => $variable));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	
	if ($action == 'addVariable' && isset($_POST['name']) && isset($_POST['value']) ) {
		$name = $_POST['name'];
		$value = $_POST['value'];
		$description = '';
		if ($_POST['description']) {
			$description = $_POST['description'];
		}
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->addVariable($name, $value, $description);
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	
	if ($action == 'editVariable' && isset($_POST['name']) && isset($_POST['value']) ) {
		$name = $_POST['name'];
		$value = $_POST['value'];
		$description = false;
		if ($_POST['description']) {
			$description = $_POST['description'];
		}
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->editVariable($name, $value, $description);
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	if ($action == 'deleteVariable' && isset($_GET['name'])) {
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$variablename = $_GET['name'];
		$status = $dataBase->deleteVariable($variablename);
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
}
?>
<div id="settings">
	<!-- Tabelle mit den Extraseiten -->
	<div  class="table-contain ui-widget">
		<table id="settingsTable" class="ui-widget ui-widget-content"></table>
	</div>
	
	<!-- Buttons -->
	<button id="create-variable">Neue Variable</button>
	
	
	<!-- Dialog zum Erstellen und Editieren von Variablen -->
	<div id="frmVariable" title="Neue Variable hinzuf&uuml;gen">
		<form>
			<fieldset>
				<label for="variablesName">Name</label>
				<input type="text" name="variablesName" id="variablesName" class="text ui-widget-content ui-corner-all" />
				<label for="variablesValue">Wert</label>
				<textarea name="variablesValue" id="variablesValue" value="" cols="62" rows="5" class="text ui-widget-content ui-corner-all"></textarea>
				<label for="variablesDescription">Beschreibung</label>
				<textarea name="variablesDescription" id="variablesDescription" value="" cols="62" rows="5" class="text ui-widget-content ui-corner-all"></textarea>
			</fieldset>
		</form>
	</div>
	
</div>