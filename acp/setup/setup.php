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

if (isset($_GET['action'])) {
	require_once 'autoloader.inc.php';
	
	$action = $_GET['action'];
	
	if ($action == 'setMySQLConfiguration') {
		if (!parametersAreSet(array( 'post' => array( 'host', 'user', 'pass', 'dbname' ) ))) {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'MISSING_PARAMS' ));
			die();
		}
		
		$host = $_POST['host'];
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		$dbname = $_POST['dbname'];
		
		@$dbh = new MySQLi($host, $user, $pass, $dbname);
		
		if (@$dbh->errno !== 0) {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'DB_CONNECTION_COULD_NOT_BE_ESTABLISHED' ));
			die();
		}
		
		$templateVars = array(
							'MYSQL_HOST'			=> $host,
							'MYSQL_USER'			=> $user,
							'MYSQL_PASS'			=> $pass,
							'MYSQL_DATABASE'		=> $dbname,
						);
		
		$fd = fopen('../libs/config.inc.php', 'w');
		$status = fwrite($fd, \Template::fillTemplate('config.tpl', $templateVars));
		fclose($fd);
		
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'CONFIG_FILE_COULD_NOT_BE_WRITTEN' ));
		}
	}
	
	else if ($action == 'createTables') {
		require_once _INCLUDE_FILES_PATH_.'config.inc.php';
		
		$dbh = new MySQLi(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = true;
		require_once 'annosys_tables.sql.php';
		foreach ($queries as $query) {
			$status = $status and $dbh->query(\Template::fillTemplateString($query, array( 'DBNAME' => MYSQL_DATABASE )));
		}
		
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'NOT_EVERY_TABLE_COULD_BE_CREATED' ));
		}
	}
	
	else if ($action == 'importDefaults') {
		require_once _INCLUDE_FILES_PATH_.'config.inc.php';
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = true;
		
		
		// Benutzergruppen
		$status = $status and $adminGroupid = $dataBase->addUsergroup('Administrator', 'Verwaltet das System und hat alle Rechte');
		$status = $status and $sekGroupid = $dataBase->addUsergroup('Sekretaeriat', 'Verwaltet Extraseiten und Ticker');
		$status = $status and $monitorGroupid = $dataBase->addUsergroup('Monitor', 'Dient der Ausgabe von Vertretungsdaten');
		
		
		// Rechte
		$status = $status and $canEnterAcp = $dataBase->addRight('CAN_ENTER_ACP', 'false', 'Gibt an ob Benutzer dieser Gruppe das ACP betreten k&ouml;nnen');
		$status = $status and $canSeeSubstitutions = $dataBase->addRight('CAN_SEE_SUBSTITUTIONS', 'false', 'Gibt an ob Benutzer dieser Gruppe im ACP die Extraseitenseite betreten k&ouml;nnen');
		$status = $status and $canSeeOutputprofiles = $dataBase->addRight('CAN_SEE_OUTPUTPROFILES', 'false', '	Gibt an ob Benutzer dieser Gruppe im ACP die Ausgabeprofileseite betreten k&ouml;nnen');
		$status = $status and $canSeeUsers = $dataBase->addRight('CAN_SEE_USERS', 'false', 'Gibt an ob Benutzer dieser Gruppe im ACP die Benutzerseite betreten k&ouml;nnen');
		$status = $status and $canSeeUsergroups = $dataBase->addRight('CAN_SEE_USERGROUPS', 'false', 'Gibt an ob Benutzer dieser Gruppe im ACP die Benutzergruppenseite betreten k&ouml;nnen');
		$status = $status and $canSeeSettings = $dataBase->addRight('CAN_SEE_SETTINGS', 'false', '	Gibt an ob Benutzer dieser Gruppe im ACP die Einstellungsseite betreten k&ouml;nnen');
		$status = $status and $canSeeExtrapages = $dataBase->addRight('CAN_SEE_EXTRAPAGES', 'false', '	Gibt an ob Benutzer dieser Gruppe im ACP die Extraseitenseite betreten k&ouml;nnen');
		$status = $status and $canSeeTicker = $dataBase->addRight('CAN_SEE_TICKER', 'false', 'Gibt an ob Benutzer dieser Gruppe im ACP die Tickerseite betreten k&ouml;nnen');
		
		
		
		// Rechte den Gruppen zuordnen
		$status = $status and $dataBase->setRightForGroup($canEnterAcp, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeSubstitutions, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeOutputprofiles, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeUsers, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeUsergroups, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeSettings, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeExtrapages, $adminGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeTicker, $adminGroupid, 'true');
		
		$status = $status and $dataBase->setRightForGroup($canSeeExtrapages, $sekGroupid, 'true');
		$status = $status and $dataBase->setRightForGroup($canSeeTicker, $sekGroupid, 'true');
		
		
		// Themes
		$status = $status and $bluesubstThemeid = $dataBase->addTheme('BlueSubst', 'themes/BlueSubst/');
		$status = $status and $graysubstThemeid = $dataBase->addTheme('GraySubst Students', 'themes/GraySubstStudents/');
		
		// Ausgabeprofile
		$status = $status and $dataBase->addOutputprofile(
											'Schueler-Monitore', 
											implode(',', array($adminGroupid, $monitorGroupid)), 
											'[[{"hl": 1, "width": 33},{"hl": 2, "width": 19},{"hl": 3, "width": 19},{"hl": 10, "width": 29}], [{"hl": 8, "width": 33},{"hl": 5, "width": 19},{"hl": 6, "width": 19},{"hl": 9, "width": 29}]]', 
											$bluesubstThemeid
										);
		$status = $status and $dataBase->addOutputprofile(
											'Lehrer-Monitore', 
											implode(',', array($adminGroupid, $monitorGroupid)), 
											'[[{"hl": 10, "width": 29},{"hl": 2, "width": 19},{"hl": 3, "width": 19},{"hl": 1, "width": 33}], [{"hl": 8, "width": 29},{"hl": 5, "width": 19},{"hl": 6, "width": 19},{"hl": 9, "width": 33}]]', 
											$bluesubstThemeid
										);
		$status = $status and $dataBase->addOutputprofile(
											'Schueler-Online', 
											implode(',', array($adminGroupid, $monitorGroupid)), 
											'[[{"hl": 2, "width": 33},{"hl": 3, "width": 19},{"hl": 4, "width": 19}, {"hl": 7, "width": 29}], [{"hl": 8, "width": 33},{"hl": 5, "width": 19},{"hl": 6, "width": 19},{"hl": 9, "width": 29}]]', 
											$graysubstThemeid
										);
		
		require_once 'defaults.vars.php';
		
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail' ));
		}
	}
	
	else if ($action == 'getSampleTable') {
		
		if (!isset($_GET['tablekind'])) {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'MISSING_TABLE_KIND' ));
			die();
		}
		
		$tableKind = $_GET['tablekind'];
		
		if ($tableKind == 'usf') {
			require_once _INCLUDE_FILES_PATH_.'config.inc.php';
			$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
			$registry = \Registry::getInstance();
			
			// Nötigen Variablen aus der Registry laden.
			$neededVariables = array(
									'GLB_PROGRAMMID_UNTISSUBSTFILE',
									'GLB_IMPORTDIRECTORIES_UNTISSUBSTFILES',
									'USF_FILENAMEPATTERN',
									'GLB_AUTOIMPORT_USF'
								);
			$undefinedVariables = $registry->defineVariables($neededVariables);
			
			// Es ist Fehler aufgetreten
			if ( count($undefinedVariables) > 0 ) {
				json_encode(array( 'status' => 'fail', 'reason' => 'DATABASE_ERROR' ));
			}
			
			$untisProgrammID = GLB_PROGRAMMID_UNTISSUBSTFILE;
			$untisImportDir = '../../'.GLB_IMPORTDIRECTORIES_UNTISSUBSTFILES;
			$untisFilenamepattern = USF_FILENAMEPATTERN;
			$untisSubstitutionFiles = \FileBrowser::searchFilesInDirectory($untisImportDir, $untisFilenamepattern);
			
			// Überprüfen ob  sich Vertretungsplandateien im Verzeichnis befanden
			if (count($untisSubstitutionFiles) > 0) {
				$table = array();
				
				$doc = new DOMDocument();
				@$doc->loadHTMLFile($untisSubstitutionFiles[0]['path']);
				$rows = $doc->getElementsByTagName('table')->item(0)->getElementsByTagName('tr');
				for ($rowIdx = 0; $rowIdx < $rows->length && $rowIdx < 6; $rowIdx++) {
					if ($rowIdx == 0) {
						$cells = $rows->item($rowIdx)->getElementsByTagName('th');
					}
					else {
						$cells = $rows->item($rowIdx)->getElementsByTagName('td');
					}
					
					for ($colIdx = 0; $colIdx < $cells->length; $colIdx++) {
						$table[$rowIdx][$colIdx] = $cells->item($colIdx)->nodeValue;
					}
					
				}
				
				echo json_encode(array( 'status' => 'success', 'table' => $table ));
			}
			else {
				json_encode(array( 'status' => 'fail', 'reason' => 'NO_FILE_FOUND', 'path' => GLB_IMPORTDIRECTORIES_UNTISSUBSTFILES ));
			}
		}
		else {
			json_encode(array( 'status' => 'fail', 'reason' => 'UNEXPECTED_TABLE_KIND' ));
		}
	}
	
	else if ($action == 'setSubstitutionHeaders') {
		if (!parametersAreSet(array( 'get' => array( 'classes', 'hour', 'notice', 'postponement', 'room', 'status', 'subject', 'supply', 'teacher' ) ))) {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'MISSING_PARAMS' ));
			die();
		}
		
		$classes = $_GET['classes'];
		$hour = $_GET['hour'];
		$notice = $_GET['notice'];
		$postponement = $_GET['postponement'];
		$room = $_GET['room'];
		$statusHl = $_GET['status'];
		$subject = $_GET['subject'];
		$supply = $_GET['supply'];
		$teacher = $_GET['teacher'];
		
		require_once _INCLUDE_FILES_PATH_.'config.inc.php';
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$status = true;
		$status = $status and $dataBase->addVariable('USF_HEADLINE_CLASSES', $classes, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die classes Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_HOUR', $hour, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die hour Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_NOTICE', $notice, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die notice Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_POSTPONEMENT', $postponement, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die postponement Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_ROOM', $room, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die room Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_STATUS', $statusHl, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die status Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_SUBJECT', $subject, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die subject Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_SUPPLY', $supply, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die supply Spalte in der Tabelle substitutions');
		$status = $status and $dataBase->addVariable('USF_HEADLINE_TEACHER', $teacher, 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die teacher Spalte in der Tabelle substitutions');
	
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'NOT_EVERY_HEADLINE_COULD_BE_SET' ));
		}
	}
	
	else if ($action == 'setSchoolSettings') {
		require_once _INCLUDE_FILES_PATH_.'config.inc.php';
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$schoolName = $_GET['schoolname'];
		
		$lessonStarts = json_decode(unescape($_GET['lessonStarts']));
		$lessonEnds = json_decode(unescape($_GET['lessonEnds']));
		
		$minGrade = $_GET['minGrade'];
		$maxGrade = $_GET['maxGrade'];
		
		
		$status = true;
		
		$status = $status and $dataBase->addVariable('GLB_SCHOOLNAME', $schoolName, 'Name der Schule');
		
		for ($i = 0; $i < count($lessonStarts); $i++) {
			$status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_'.($i+1), $lessonStarts[$i], 'Die Startzeit der '.($i+1).'. Stunde');
			$status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_'.($i+1), $lessonEnds[$i], 'Die Endzeit der '.($i+1).'. Stunde');
		}
		
		$status = $status and $dataBase->addVariable('GLB_NUMBER-OF-LESSONS', count($lessonStarts), 'Die maximale Anzahl der Schulstunden eines Tages');
		$status = $status and $dataBase->addVariable('GLB_OUTPUT_MAX-GRADE', $maxGrade, 'Der &auml;lteste vorhandene Jahrgang');
		$status = $status and $dataBase->addVariable('GLB_OUTPUT_MIN-GRADE', $minGrade, 'Die j&uuml;ngste vorhandene Jahrgang');
		
		if ($_FILES['iptSchoollogo']) {
			if ( preg_match('/(jpg|png|gif|tga|bmp)/i', substr($_FILES['iptSchoollogo']['name'], -3,3)) ) {
				$targetPath = '../../images/'.$_FILES['iptSchoollogo']['name'];
				if (move_uploaded_file($_FILES['iptSchoollogo']['tmp_name'], $targetPath)) {
					$status = $status and $dataBase->addVariable('GLB_SCHOOLLOGO', $_FILES['iptSchoollogo']['name'], 'Dateiname des Schullogos in /images/.');
				}
				else {
					echo json_encode(array( 'status' => 'fail', 'reason' => 'CANT_MOVE_LOGO' ));
				}
			}
		}
		
		$status = $status and $dataBase->addExtrapage(
											'Versionsinformation', 
											'Setup', 
											\Template::fillTemplate('versioninfo.tpl', array('LOGO' => $dataBase->getValue('GLB_SCHOOLLOGO'), 'SCHOOLNAME' => $dataBase->getValue('GLB_SCHOOLNAME'))), 
											time(), 
											time()+365*24*60*60, 
											time(), 
											'10', 
											array(1,2,3)
										);
										
		$status = $status and $dataBase->addTickermessage(
											array(1,2,3), 
											'AnnoSys wurde erfolgreich eingerichtet', 
											'Setup', 
											time(), 
											time() + 365*24*60*60
										);
		
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'NOT_ALL_SETTINGS_COULD_BE_SET' ));
		}
		
	}
	
	else if ($action == 'setAdministratorLogin') {
		require_once _INCLUDE_FILES_PATH_.'config.inc.php';
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$name = $_GET['adminName'];
		$pass = $_GET['adminPass'];
		
		$status = $dataBase->registerUser($name, $pass, '', 1);
		
		if ($status) {
			echo json_encode(array( 'status' => 'success' ));
		}
		else {
			echo json_encode(array( 'status' => 'fail', 'reason' => 'ADMINISTRATOR_COULD_NOT_BEEN_SAVE' ));
		}
	}
	
	die();
}


/**
 * Überprüft ob ALLE Parameternamen im POST Array gesetzt und nicht leer sind
 * @param array $paramNames
 */
function parametersAreSet($parameters)
{
	$status = true;
	foreach ($parameters as $paramType => $params) {
		foreach ($params as $param) {
			if ($paramType == 'get') {
				$status &= isset($_GET[$param]) && !empty($_GET[$param]);
			}
			else if ($paramType == 'post') {
				$status &= isset($_POST[$param]);
			}
		}
	}
	return $status;
}


/**
 * Entfernt das Escaping
 * @param string $string
 */
function unescape($string)
{
	if (get_magic_quotes_gpc()) {
		$code = stripslashes($string);
	}
	else {
		return $string;
	}
}

?>
<html>
<head>
	<title>AnnoSys Installation</title>
	<link type="text/css" href="../../css/smoothness/jquery-ui.css" rel="stylesheet" />
	<link type="text/css" href="../css/jqueryui-settings.css" rel="stylesheet" />
	<style type="text/css">
		* {
			margin: 0px;
			padding: 0px;
		}
		
		body{
			background-color: #005aa0;
			background-image:url('../images/bg_texture.png');
			background-repeat:repeat-x;
			min-height: 640px;
			min-width: 800px;
		}
	
		#mainFrame {
			position: absolute;
			top: 4%;
			left: 4%;
			width: 90%;
			height: 90%;
			padding: 10px;
			-moz-border-radius: 15px;
			-webkit-border-radius: 15px;
			border-radius: 15px;
			background: #eeeeee;
			min-height: 576px;
			min-width: 720px;
		}
		
		#mainFrame #headline {
			margin-left: 25%;
		}	
		
		#mainFrame #headline h1{
			font-size: 170%;
			
		}
		
		#mainFrame #headline h3 {
			font-size: 140%;
			font-weight: normal;
		}
		
		#mainFrame #content ul {
			list-style: none;
			font-size: 150%;
			margin-left: 30%;
			margin-top: 2%;
		}
		
		#mainFrame #content li {
			color: #bbb;
		}
		
		#mainFrame #content li.active {
			color: #000;
		}
		
		#mainFrame #content li.running {
			list-style-image: url(running.gif);
		}
		
		#mainFrame #content li.success {
			list-style-image: url(success.png);
		}
		
		#mainFrame #content li.fail {
			list-style-image: url(fail.png);
		}
		
		#mainFrame #frmMysqlConnection, #mainFrame #administratorLogin {
			margin-left: 25%;
		}
		
		#mainFrame #frmMysqlConnection label, #mainFrame #administratorLogin label {
			font-size: 120%;
		}
		
		#mainFrame #frmMysqlConnection input, #mainFrame #administratorLogin input {
			width: 60%;
		}
		
		#mainFrame #content #optSubstituionKind {
			margin-top: 3%;
			margin-left: 25%;
		}
		
		#mainFrame #content button {
			margin-left: 1%;
		}
		
		#mainFrame #content #tblPreview {
			margin-left: 2.5%;
			margin-top: 3%;
			width: 95%;
		}
		
		#mainFrame #content #tblPreview td{
			border: 1px solid #000;
			border-collapse: collapse;
		}
		
		#mainFrame #content .dropHeadline {
			width: 100px; 
			height: 100px; 
			padding: 0.5em; 
			font-size: 140%;
			float: left; 
			margin: 10px 10px 10px 0;
		}
		
		#mainFrame #content #schoolSpecificSettings {
			margin-left: 25%;
			margin-top: 2%;
			font-size: 120%;
		}
		
		
		#mainFrame #content #schoolSpecificSettings input, #mainFrame #content #schoolSpecificSettings select{
			width: 45%;
		}
		
		#mainFrame #content #schoolSpecificSettings #iptLessonTimes {
			width: 45%;
			padding: 5px;
			text-align: center;
		}
		
		#mainFrame #content #schoolSpecificSettings #iptLessonTimes input {
			width: 80%;
			margin-left: 10%;
		}
		
		#btnBackward {
			position: absolute;
			bottom: 15px;
			left: 15px;
		}
		
		#btnForward {
			position: absolute;
			bottom: 15px;
			right: 15px;
		}
	</style>
	<script type="text/javascript" src="../../js/sha1.js"></script>
	<script type="text/javascript" src="../../js/libs/jquery.js"></script>
	<script type="text/javascript" src="../../js/libs/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="../js/ValidatingForm.js"></script>
	<script type="text/javascript" src="setup.js"></script>
</head>
<body>
	<div id="mainFrame">
		<div id="headline" class="ui-widget"></div>
		<div id="content">
			<h1>Willkommen bei der AnnoSys Installation</h1>
			<h3>Klicken Sie auf "Weiter" um fortzufahren</h3>
		</div>
		<div id="btnForward">Weiter</div>
	</div>
</body>
</html>