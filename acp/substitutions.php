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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_SUBSTITUTIONS') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}

if(isset($_GET['action'])) {
	$action = $_GET['action'];
	
	if ($action == 'getSubstitutions') {
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$substitutions = $dataBase->getSubstitutions();
		if ($substitutions) {
			echo json_encode(array( 'status' => 'success', 'substitutions' => $substitutions ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	if ($action == 'addSubstitution') {
		if ( !isset($_POST['date']) &&  !isset($_POST['hour']) && !isset($_POST['grade']) && !isset($_POST['classes']) &&
			 !isset($_POST['subject']) && !isset($_POST['teacher']) && !isset($_POST['status']) && !isset($_POST['room'])
		) {
			echo json_encode(array( 'status' => 'missing_data' ));
			die();
		}
		
		$date = $_POST['date'];
		$hour = $_POST['hour'];
		$grade = $_POST['grade'];
		$classes = $_POST['classes'];
		$subject = $_POST['subject'];
		$teacher = $_POST['teacher'];
		$status = $_POST['status'];
		$room = $_POST['room'];
		$supply = $_POST['supply'];
		$postponement = $_POST['postponement'];
		$notice = $_POST['notice'];
		
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = $dataBase->addSubstitution(
									$date, 
									$grade, 
									$classes, 
									$hour, 
									$subject, 
									$teacher, 
									$status, 
									$room, 
									$supply, 
									$postponement, 
									$notice, 
									$dataBase->getValue('GLB_PROGRAMMID_MANUALLY')
								); 
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_SUBSTITUTIONS', time());
			echo json_encode(array( 'status' => 'success', 'programm' => $dataBase->getValue('GLB_PROGRAMMID_MANUALLY') ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	if ($action == 'editSubstitution') {
		if ( !isset($_POST['date']) &&  !isset($_POST['hour']) && !isset($_POST['grade']) && !isset($_POST['classes']) &&
			 !isset($_POST['subject']) && !isset($_POST['teacher']) && !isset($_POST['status']) && !isset($_POST['room'])
		) {
			echo json_encode(array( 'status' => 'missing_data' ));
			die();
		}
		
		$date = $_POST['date'];
		$hour = $_POST['hour'];
		$grade = $_POST['grade'];
		$classes = $_POST['classes'];
		$subject = $_POST['subject'];
		$teacher = $_POST['teacher'];
		$status = $_POST['status'];
		$room = $_POST['room'];
		$supply = $_POST['supply'];
		$postponement = $_POST['postponement'];
		$notice = $_POST['notice'];
		
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = $dataBase->editSubstitution(
									$hash,
									$date, 
									$grade, 
									$classes, 
									$hour, 
									$subject, 
									$teacher, 
									$status, 
									$room, 
									$supply, 
									$postponement, 
									$notice, 
									$dataBase->getValue('GLB_PROGRAMMID_MANUALLY')
								); 
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_SUBSTITUTIONS', time());
			echo json_encode(array( 'status' => 'success', 'programm' => $dataBase->getValue('GLB_PROGRAMMID_MANUALLY') ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
	
	if ($action == 'deleteSubstitution') {
		if (!isset($_GET['hash'])) {
			echo json_encode(array( 'status' => 'missing_data' ));
			die();
		}
		$hash = $_GET['hash'];
		$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = $dataBase->deleteSubstitution($hash);
		if ($status) {
			$dataBase->editVariable('GLB_LASTUPDATE_SUBSTITUTIONS', time());
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
		die();
	}
}


?>
<div id="substitutions">
	<!-- Tabelle mit den Vertretungen -->
	<div  class="table-contain ui-widget">
		<table id="substitutionsTable" class="ui-widget ui-widget-content"></table>
	</div>
	
	<!-- Buttons -->
	<button id="create-substitution">Neue Vertretungsregelung</button>
	
	
	<!-- Dialog zum Erstellen und Editieren von Vertretungsregelungen -->
	<div id="frmSubstitution" title="Neue Vertretungsregelung hinzuf&uuml;gen">
		<form>
			<fieldset>
				<label for="substitutionsDate">Datum</label>
				<input type="text" name="substitutionsDate" id="substitutionsDate" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsHour">Stunde</label>
				<div id="substitutionsHour" class="checkgroup">
					<?php
						$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$numberOfLessons = $dataBase->getValue('GLB_NUMBER-OF-LESSONS');
						for ($lessonIdx = 1; $lessonIdx <= $numberOfLessons; $lessonIdx++) {
							echo "<input type=\"radio\" id=\"lesson$lessonIdx\" name=\"radioLesson\"><label for=\"lesson$lessonIdx\">$lessonIdx</label>";
						}
					?>
				</div>
				<label for="substitutionsGrade">Jahrgang</label>
				<div id="substitutionsGrade" class="checkgroup">
					<?php
						$dataBase = new DataBase(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
						$minGrade = $dataBase->getValue('GLB_OUTPUT_MIN-GRADE');
						$maxGrade = $dataBase->getValue('GLB_OUTPUT_MAX-GRADE');
						for ($gradeIdx = $minGrade; $gradeIdx <= $maxGrade; $gradeIdx++) {
							echo "<input type=\"radio\" id=\"grade$gradeIdx\" name=\"radioGrade\"><label for=\"grade$gradeIdx\">$gradeIdx</label>";
						}
					?>
					
				</div>
				<label for="substitutionsClasses">Klasse(n)</label>
				<input type="text" name="substitutionsClasses" id="substitutionsClasses" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsSubject">Fach</label>
				<input type="text" name="substitutionsSubject" id="substitutionsSubject" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsTeacher">Lehrer</label>
				<input type="text" name="substitutionsTeacher" id="substitutionsTeacher" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsStatus">Art</label>
				<input type="text" name="substitutionsStatus" id="substitutionsStatus" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsRoom">Raum</label>
				<input type="text" name="substitutionsRoom" id="substitutionsRoom" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsSupply">Vertretungslehrer</label>
				<input type="text" name="substitutionsSupply" id="substitutionsSupply" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsPostponement">Verlegung</label>
				<input type="text" name="substitutionsPostponement" id="substitutionsPostponement" class="text ui-widget-content ui-corner-all" />
				<label for="substitutionsNotice">Anmerkung</label>
				<input type="text" name="substitutionsNotice" id="substitutionsNotice" class="text ui-widget-content ui-corner-all" />
			</fieldset>
		</form>
	</div>
	
</div>