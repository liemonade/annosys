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
	
	
	
	if (!isset($_SESSION['USER']) && !empty($_COOKIE)) {
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		$status = User::startSubstSession($dataBase, $_COOKIE);
	}
	
	
	// Überprüfen ob die Sitzung korrekt angemeldet ist.
	if (isset($_SESSION['USER']) && isset($_SESSION['IP']) && isset($_SESSION['MINGRADE']) && isset($_SESSION['MAXGRADE']) 
		&& isset($_SESSION['OUTPUTPROFILE']) && $_SESSION['IP'] == $_SERVER['REMOTE_ADDR'] ) {
		
		if ( isset($_GET['action']) ) {
			
			$action = $_GET['action'];
			
			try {
			
				// Testen ob ein Import von Untis-Datein von Nöten ist 
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
					throw new SystemException($_SERVER["PHP_SELF"].': Es konnten nicht alle Variablen aus der Datenbank gelesen werden. Es fehlen: '.implode(', ', $undefinedVariables));
				}
				
				$untisProgrammID = GLB_PROGRAMMID_UNTISSUBSTFILE;
				$untisImportDir = GLB_IMPORTDIRECTORIES_UNTISSUBSTFILES;
				$untisFilenamepattern = USF_FILENAMEPATTERN;
				$untisSubstitutionFiles = \FileBrowser::searchFilesInDirectory($untisImportDir, $untisFilenamepattern);
				
				// Überprüfen ob  sich Vertretungsplandateien im Verzeichnis befanden
				if (count($untisSubstitutionFiles) > 0) {
					$importedUntisFiles = $dataBase->getImportsByProgramm($untisProgrammID);
					$atLeastOneFileIsNew = false;
					
					if ( count($importedUntisFiles) > 0 ) {
						foreach ($untisSubstitutionFiles as $untisSubstitutionFile) {
							$isImportedFile = false;
							$filetime = filemtime($untisSubstitutionFile['path']);
							foreach ($importedUntisFiles as $importedUntisFile) {
								if ($untisSubstitutionFile['filename'] == $importedUntisFile['filename']) {
									$isImportedFile = true;
									if ($filetime > $importedUntisFile['filedate']) {
										$atLeastOneFileIsNew = true;
										break(2);
									}
								}
							}
							
							if (!$isImportedFile) {
								$atLeastOneFileIsNew = true;
								break;
							}
						}
					}
					else {
						$atLeastOneFileIsNew = true;
					}
					// Wenn ja, dann alles importieren
					if ($atLeastOneFileIsNew && GLB_AUTOIMPORT_USF === 'true') {
						$dataBase->importUntisSubstitutionFiles($untisImportDir, $untisFilenamepattern);
					}
				}
				
			}
			catch (\SystemException $se) {
				\Logger\File::write($se);
				\Logger\Push::write($se);
			}
			
			// Der Client fragt, ob er neue Daten benötigt
			if ($action == 'getUpdatestatus' && isset($_GET['lastupdate']) && is_numeric($_GET['lastupdate'])) {
				
				try {
				
					$lastClientUpdate = $_GET['lastupdate'];
					$neededVariables = array( 
										'GLB_LASTUPDATE_EXTRAPAGES', 
										'GLB_LASTUPDATE_SUBSTITUTIONS', 
										'GLB_LASTUPDATE_TICKERMESSAGES', 
										'GLB_LASTUPDATE_OUTPUTPROFILES' 
									);
					$undefinedVariables = $registry->defineVariables($neededVariables);
					
					// Es ist Fehler aufgetreten
					if ( count($undefinedVariables) > 0) {
						throw new SystemException($_SERVER["PHP_SELF"].': Es konnten nicht alle Variablen aus der Datenbank gelesen werden. Es fehlen: '.implode(', ', $undefinedVariables));
					}
					
					// Wenn eines der Updatezeitpunkte aktueller ist, als das Updatedatum des Clients, wird ein Update empfohlen
					if ( (GLB_LASTUPDATE_EXTRAPAGES > $lastClientUpdate) || (GLB_LASTUPDATE_SUBSTITUTIONS > $lastClientUpdate) || 
						(GLB_LASTUPDATE_TICKERMESSAGES > $lastClientUpdate) || (GLB_LASTUPDATE_OUTPUTPROFILES > $lastClientUpdate) ) {
						
						echo json_encode(array('updatestatus' => 'UPDATE_NEEDED'));
					}
					else {
						echo json_encode(array('updatestatus' => 'NO_UPDATE_NEEDED'));
					}
					die();
				}
				catch (\SystemException $se) {
					\Logger\File::write($se);
					\Logger\Push::write($se);
					\Logger\Output::write(new \SystemException('Es gab ein Fehler beim Abfragen von Daten aus der Datenbank.'));
				}
			}
			
			// Der Client möchte alle Daten
			else if ($action == 'getEverything' && isset($_GET['targetgroup']) && ($_GET['targetgroup'] == 'students' || $_GET['targetgroup'] == 'teachers') ) {
				$groupid = $_SESSION['USER']->usergroup;
				$min_grade = $_SESSION['MINGRADE'];
				$max_grade = $_SESSION['MAXGRADE'];
				$targetgroup = $_GET['targetgroup'];
				$outputprofile = $dataBase->getOutputprofile($_SESSION['OUTPUTPROFILE']);
				
				$headlines = json_decode($outputprofile['columns'], true);
				$extrapages = $dataBase->getCurrentExtrapagesByGroupForPresentation($groupid);
				$tickermessages = $dataBase->getCurrentTickermessagesByGroupForPresentation($groupid);
				
				if ($targetgroup == 'students') {
					$substitutions = $dataBase->getCurrentSubstitutions($min_grade, $max_grade, 'students');
				
					if ($substitutions) {
						foreach ($substitutions as $substitution) {
							$substitutionsByDateGrade[$substitution['date']][$substitution['grade']][] = $substitution;
						}
						
						$substitutions = array();
					
						foreach ($substitutionsByDateGrade as $substByGrade) {
							$newSubstByGrade = array();
							foreach ($substByGrade as $substs) {
								$newSubstByGrade[] = $substs;
							}
							$substitutions[] = $newSubstByGrade;
						}
					}
				}
				else if ($targetgroup == 'teachers') {
					$substs = $dataBase->getCurrentSubstitutions($min_grade, $max_grade, 'teachers');
					
					if ($substs) {
						// Substs als assoziatives Array mit dem timestamp des Tages als key
						foreach ($substs as $subst) {
							$substitutionsByDate[$subst['date']][] = $subst;
						}
						
						// Aus dem asso. Array wird ein iterierbares Array
						foreach ($substitutionsByDate as $substsOfDate) {
							$substitutions[] = $substsOfDate;
						}
					}
				}
				
				// Letztes Subst-Update als Text
				$lastUpdate = $dataBase->getValue('GLB_LASTUPDATE_SUBSTITUTIONS');
				$today = strtotime('today');
				if( ($lastUpdate - $today) > 0 ) {
					$lastSubstUpdate = '(Heute) '.date('G:i', $lastUpdate);
				}
				elseif( ($lastUpdate - strtotime('yesterday')) > 0 ) {
					$lastSubstUpdate = '(Gestern) '.date('G:i', $lastUpdate);
				}
				else {
					$wochentage = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');
					$lastSubstUpdate = '('.$wochentage[date('w', $lastUpdate)].') '.date('G:i', $lastUpdate);
				}
				
				
				$headlineSettings = $dataBase->getVariablesByPrefix('GLB_SUBSTTABLE_HEADLINE_');
				foreach ($headlines as $rowIdx => $row) {
					foreach($row as $colIdx => $column) {
						foreach ($headlineSettings as $hlSetting) {
							$hl = json_decode($hlSetting['value'], true);
							if ($column['hl'] == $hl['id']) {
								$headlinesForPresentation[$rowIdx][$colIdx] = array('hl' => $hl, 'width' => $column['width']);
							}
						}
					}
				}
				
				// Stundenenden und -anfï¿½nge bestimmen
				$lessonEnds = $dataBase->getVariablesByPrefix('GLB_LESSONTIMES_ENDS_');
				foreach ($lessonEnds as $lessonEnd) {
					$lessonEndings[] = $lessonEnd['value'];
				}
				$lessonStarttimes = $dataBase->getVariablesByPrefix('GLB_LESSONTIMES_STARTS_');
				foreach ($lessonStarttimes as $lessonStart) {
					$lessonStarts[] = $lessonStart['value'];
				}
				
				
				echo json_encode(array(
						'status' => 'success',
						'headlines' => $headlinesForPresentation,
						'extrapages' => $extrapages,
						'tickermessages' => $tickermessages,
						'substitutions' => $substitutions,
						'lessonstarts' => $lessonStarts,
						'lessonends' => $lessonEndings,
						'lastupdate' => $lastSubstUpdate,
						'servertime' => time()
					));
				
			}
			else {
				echo json_encode(array( 'status' => 'error', 'reason' => 'PARAMETER_MISSING' ));
			}
		}
	}
	else {
		echo json_encode(array('error'=>'NOT_LOGGED_IN'));
	}
?>