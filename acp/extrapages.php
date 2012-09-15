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


if(isset($_GET['action'])) {
	$action = $_GET['action'];
	
	// Alle Extraseiten wurden angefordert
	if($action == 'getExtrapages') {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$usergroups = $dataBase->getUsergroups();
		$extrapages = $dataBase->getExtrapages();
		
		if ($extrapages) {
			echo json_encode(array( 'status' => 'success', 'extrapages' => $extrapages, 'usergroups' => $usergroups ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	// Es werden die Daten einer spezifischen Extraseite angefordert
	else if($action == 'getExtrapage') {
		if(!isset($_GET['pageid']) || !is_numeric($_GET['pageid'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$extrapage = $dataBase->getExtrapage($_GET['pageid']);
		if ($extrapage) {
			echo json_encode(array('status'=>'success', 'extrapage'=>$extrapage));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	// Es soll eine Extraseite hinzugefügt werden
	else if($action == 'addExtrapage') {
		if (!isset($_POST['name']) || !isset($_POST['start_date']) || !isset($_POST['end_date']) || !isset($_POST['duration']) || !isset($_POST['code']) || !isset($_POST['usergroups'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		$name = $_POST['name'];
		$poster = $_SESSION['USER']->username;
		if (get_magic_quotes_gpc()) {
			$code = stripslashes($_POST['code']);
		}
		else {
			$code = $_POST['code'];
		}
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$edit_date = time();
		$duration = $_POST['duration'];
		$usergroups = json_decode($_POST['usergroups']);
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$pageid = $dataBase->addExtrapage($name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups);
		if($pageid) {
			$dataBase->editVariable('GLB_LASTUPDATE_EXTRAPAGES', time());
			echo json_encode(array('status'=>'success', 'pageid' => $pageid, 'poster'=>$poster));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	
	/// Es soll ein Bild für Extraseiten hochgeladen werden.
	else if ($_GET['action'] == 'uploadpic') {
		if (!$_FILES['extrapagePicture']) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		
		if ( preg_match('/(jpg|png|gif|tga|bmp)/i', substr($_FILES['extrapagePicture']['name'], -3,3)) ) {
			$targetPath = '../uploads/'.$_FILES['extrapagePicture']['name'];
			if (move_uploaded_file($_FILES['extrapagePicture']['tmp_name'], $targetPath)) {
				echo json_encode(array( 'status' => 'success' ));
			}
			else {
				json_encode(array( 'status' => 'fail' ));
			}
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	
	// Es sollen die Dateinamen der zur Verfügung stehenden Bilder ausgegeben werden.
	else if ($action == 'getPictures') {
		$dir = '../uploads/';
		$elements = scandir($dir);
		$images = array();
		foreach ($elements as $filename) {
			if ($filename != '.' && $filename != '..' && preg_match('/(jpg|png|gif|tga|bmp)/i', substr($filename, -3, 3))) {
				$images[] = $filename;
			}
		}
		if ($images) {
			echo json_encode(array( 'status' => 'success', 'images' => $images ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	// Es werden Extraseiten in Form von CSV hochgeladen.
	if ($_GET['action'] == 'importcsv') {
		if (!$_FILES['extrapageCSV']) {
			echo json_encode(array('error'=>'MISSING_DATA'));
			die();
		}
		
		$uploadedCSV = $_FILES['extrapageCSV'];
		
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
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		defineVariables($dataBase->getVariablesByPrefix('EXTRAPAGES_CSV_IMPORT_HEADLINE_'));
		
		// Suchen der Kopfzeilen in der Datei
		$headlines = fgetcsv($fd);
		for ($i = 0; $i < sizeof($headlines); $i++) {
			switch ($headlines[$i]) {
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_NAME:
					$pagenameIdx = $i;
					break;
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_STARTDATE:
					$startdateIdx = $i;
					break;
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_ENDDATE:
					$enddateIdx = $i;
					break;
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_DURATION:
					$durationIdx = $i;
					break;
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_USERGROUPS:
					$usergroupsIdx = $i;
					break;
				case EXTRAPAGES_CSV_IMPORT_HEADLINE_CODE:
					$codeIdx =  $i;
					break;
			}
		}
		
		if (!isset($pagenameIdx) || !isset($startdateIdx) || !isset($enddateIdx) || !isset($durationIdx) || !isset($usergroupsIdx) || !isset($codeIdx)) {
			echo json_encode(array('error' => 'CSV_FORMAT_NOT_CORRECT'));
		}
		else {
			$importedExtrapages = array();
			$everyPageImported = true;
			while (($data = fgetcsv($fd)) != false) {
				$name = $data[$pagenameIdx];
				$poster = $_SESSION['USERNAME'];
				$code = $data[$codeIdx];
				$start_date = strtotime($data[$startdateIdx]);
				$end_date = strtotime($data[$enddateIdx]);
				$edit_date = time();
				$duration = $data[$durationIdx];
				$usergroups = $data[$usergroupsIdx];
				if ($pageid = $dataBase->addExtrapage($name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups)) {
					$importedExtrapages[] = array('pageid'=>$pageid, 'name'=>$name, 'poster'=>$poster, 
												  'start_date'=>$start_date, 'end_date'=>$end_date,  'edit_date'=>$edit_date, 
												  'duration'=>$duration, 'usergroups'=>$usergroups);
				}
				else {
					$everyPageImported = false;
				}
			}
			
			$dataBase->editVariable('GLB_LASTUPDATE_EXTRAPAGES', time());
			if ($everyPageImported) {
				echo json_encode(array('status'=>'IMPORT_SUCCESSFULL', 'importedExtrapages'=>$importedExtrapages));
			}
			else {
				echo json_encode(array('status'=>'IMPORT_NOT_COMPLETE', 'importedExtrapages'=>$importedExtrapages));
			}
		}
	}
	
	
	// Es soll eine Extraseite gelöscht werden
	else if($action == 'deleteExtrapage') {
		if(!isset($_GET['pageid'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
		}
		$pageid = $_GET['pageid'];
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->deleteExtrapage($pageid);
		if($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_EXTRAPAGES', time());
			echo json_encode(array('status'=>'success'));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	// Es soll eine Extraseite editiert werden
	else if($action == 'editExtrapage') {
		if (!isset($_POST['pageid']) || !isset($_POST['name']) || !isset($_POST['start_date']) 
		|| !isset($_POST['end_date']) || !isset($_POST['duration']) || !isset($_POST['code']) 
		|| !isset($_POST['usergroups'])) {
			echo json_encode(array('error'=>'MISSING_DATA'));
		}
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$pageid = $_POST['pageid'];
		$name = $_POST['name'];
		$poster = $_SESSION['USER']->username;
		if (get_magic_quotes_gpc()) {
			$code = stripslashes($_POST['code']);
		}
		else {
			$code = $_POST['code'];
		}
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$edit_date = time();
		$duration = $_POST['duration'];
		$usergroups = json_decode($_POST['usergroups']);
		$status = $dataBase->editExtrapage($pageid, $name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_EXTRAPAGES', time());
			echo json_encode(array('status'=>'success', 'poster'=>$poster));
		}
		else {
			echo json_encode(array('status'=>'fail'));
		}
	}
	
	die();
}


?>
<div id="extrapages">
	
	<!-- Tabelle mit den Extraseiten -->
	<div id="sites-contain" class="ui-widget">
		<table id="pagesTable" class="ui-widget ui-widget-content"></table>
	</div>
	
	<!-- Buttons -->
	<button id="create-site">Neue Seite</button>
	<button id="upload-csv">Import via CSV</button>
	<button id="upload-pic">Bild hochladen</button>
	
	
	
	<!-- Dialog der bei Fehlern aufgerufen wird -->
	<div id="statusDialog" title="">
		<p>
			<span class="" style="float:left; margin:0 7px 50px 0;"></span>
		</p>
	</div>
	
	<!-- Contrainer für den "Ajax"-Upload -->
	<iframe name="upload-frame" id="upload-frame" style="display: none;"></iframe>
	
	
	<!-- Dialog zum erstellen und editieren von Extraseiten -->
	<!-- <div id="frmExtrasite" title="Neue Extraseite hinzuf&uuml;gen">
		<form>
			<fieldset>
				<label for="extrapagesName">Name der Extraseite</label>
				<input type="text" name="extrapagesName" id="extrapagesName" class="text ui-widget-content ui-corner-all" />
				<label for="extrapagesStart">Wird ausgegeben ab</label>
				<input type="text" name="extrapagesStart" id="extrapagesStart" value="" class="text ui-widget-content ui-corner-all" />
				<label for="extrapagesEnd">Bis</label>
				<input type="text" name="extrapagesEnd" id="extrapagesEnd" value="" class="text ui-widget-content ui-corner-all" />
				<label for="extrapagesDuration">Anzeigedauer in Sekunden: </label>
				<div id="extrapagesDuration" class="slider-widget"></div>
				<label for="extrapagesUsergroups">Zielgruppen</label>
				<div id="extrapagesUsergroups" class="checkgroup">
					<?php
						/*$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$usergroups = $dataBase->getUsergroups();
						foreach($usergroups as $groupid=>$groupname) {
							echo "<input type=\"checkbox\" id=\"$groupid\"><label for=\"$groupid\">$groupname</label>";
						}*/
					?>
				</div>
				<label for="extrapagesCode">HTML Code</label>
				<textarea name="extrapagesCode" id="extrapagesCode" value="" cols="62" rows="5" class="text ui-widget-content ui-corner-all"></textarea>
				<button id="extrapagesInsertImage">Bild einf&uuml;gen</button>
			</fieldset>
		</form>
	</div> -->
	
	<!-- Form für das Hochladen von CSV-Datein -->
	<div id="frmImportCSV" title="Importieren von Extraseiten via CSV-Datei">
		<form method="POST" enctype="multipart/form-data">
			<fieldset>
				<label for="extrapageCSV">Pfad zur *.csv-Datei</label>
				<input width="300" type="file" id="extrapageCSV" name="extrapageCSV" class="text ui-widget-content ui-corner-all" />
				<center><div id="submitCSV">Hochladen</div></center>
			</fieldset>
		</form>
	</div>
	
	
	<!-- Form für das Hochladen von Bild-Datein -->
	<div id="frmPictureUpload" title="Bilderupload f&uuml;r Extraseiten">
		<form method="POST" enctype="multipart/form-data">
			<fieldset>
				<label for="extrapagePicture">Pfad zur Bild-Datei</label>
				<input width="300" type="file" id="extrapagePicture" name="extrapagePicture" class="text ui-widget-content ui-corner-all" />
				<center><div id="submitPic">Hochladen</div></center>
			</fieldset>
		</form>
	</div>
	
	<div id="pictureBox" class="ui-widget-content ui-corner-all"></div>

</div>