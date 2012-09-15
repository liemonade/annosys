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

$(document).ready(function() {
	var steps = ['setMySQLConfiguration', 'createTables', 'importDefaults', 'getSampleTable', 'setSubstitutionHeaders', 'setSchoolSettings', 'setAdministratorLogin'];
	var currentAction = -1;
	
	
	$('#btnForward').button().click(nextStep);
	
	$('#btnForward').trigger('click');
	
	function nextStep()
	{
		
		if (currentAction == -1) {
			$('#mainFrame > #headline').html('<h1>Datenbankverbindung</h1><h3>Bitte geben Sie die Verbindungsdaten für die MySQL Datenbank an.')
			$('#mainFrame > #content').html('<div id="frmMysqlConnection" class="ui-widget"></div>');
			$('#frmMysqlConnection').ValidatingForm({
				fields: [
					{ label: "Datenbankhost", name: "host" },
					{ label: "Benutzername", name: "user" },
					{ label: "Passwort", name: "pass", type: "password" },
					{ label: "Datenbankname", name: "dbname" }
				]
			});
			
			currentAction++;
		}
		else if (currentAction == 0) {
			var values = $('#frmMysqlConnection').ValidatingForm('values');

			$.post('setup.php?action='+steps[currentAction], values, function(data) {
				data = $.parseJSON(data);
				if (data.status != 'success') {
					if (data.reason == 'MISSING_PARAMS') {
						alert('Die Daten konnten nicht gespeichert werden, da eine oder mehrere Angaben fehlen.');
					}
					else if (data.reason == 'DB_CONNECTION_COULD_NOT_BE_ESTABLISHED') {
						alert('Die Datenbankverbindung konnte mit den angegebenen Daten nicht hergestellt werden.');
					}
					else if (data.reason == 'CONFIG_FILE_COULD_NOT_BE_WRITTEN') {
						alert('Es gab einen Fehler beim Schreiben der Konfigurationsdatei.');
					}
					else {
						alert('Ein unbekannter Fehler ist aufgetreten.');
					}
					return;
				}
				else {
					$('#mainFrame > #headline').html('<h1>Einrichten der Datenbank</h1><h3>Das Einrichten erfolgt automatisch. Sie werden anschliessend zum n&auml;chsten Schritt weitergeleitet.')
					$('#mainFrame > #content').html(
						$('<ul id="listDatabaseSteps" class="ui-widget"></ul>')
							.append('<li id="liCreateTables">Anlegen der Tabellen</li>')
							.append('<li id="liImportDefaults">Importieren der Voreinstellungen</li>')
					);
					
					currentAction++;
					
					$('#btnForward span').html('Installieren');
					
					nextStep();
				}
			});
		}
		
		else if (currentAction == 1) {
			
			$('#btnForward').button('disable');
			
			$('#liCreateTables').addClass('running').addClass('active');
			
			$.getJSON('setup.php?action='+steps[currentAction], {}, function(data) {
				if (data.status != 'success') {
					$('#liCreateTables').removeClass('running').addClass('fail');
					alert('Es gab einen Fehler beim Erstellen der Tabellen.');
				}
				else {
					$('#liCreateTables').removeClass('running').addClass('success');
					currentAction++;
					nextStep();
				}
			});
		}
		
		else if (currentAction == 2) {
			$('#liImportDefaults').addClass('running').addClass('active');
			
			$.getJSON('setup.php?action='+steps[currentAction], {}, function(data) {
				if (data.status != 'success') {
					$('#liImportDefaults').removeClass('running').addClass('fail');
					alert('Es gab einen Fehler beim Erstellen der Tabellen.');
				}
				else {
					$('#liImportDefaults').removeClass('running').addClass('success');
					currentAction++;
					
					$('#btnForward').button('enable');
					$('#btnForward span').html('Weiter');
				}
			});
		}
		
		else if (currentAction == 3) {
			$('#btnForward').button('disable');
			$('#mainFrame > #headline').html('<h1>Art der Vertretungsplan-Datein</h1><h3>W&auml;hlen Sie aus, woraus die Vertretungsdaten automatisch importiert werden sollen.</h3>')
			$('#mainFrame > #content').html('<select id="optSubstituionKind"><option value="usf">Untis-Vertretungsplan-Dateien</option></select><button id="btnSubstitutionKind">W&auml;hlen</button>');
			$('#btnSubstitutionKind').button().click(function() {
				$.getJSON('setup.php?action=getSampleTable', {tablekind: $('#optSubstituionKind').val()}, function(data) {
					if (data.status == 'success') {
						$('#mainFrame > #headline').html('<h1>Zuordnen der Spalten</h1><h3>Ordnen Sie die &Uuml;berschriften den Spalten der Tabelle zu.</h3>')
					
						$('#mainFrame > #content').html('<table id="tblPreview"></table>');
						$.each(data.table, function(rowIdx, row) {
							if (rowIdx <= 1) {
								$('#tblPreview').append('<tr></tr>');
							}
							$.each(row, function(colIdx, cell) {
								if (rowIdx == 0) {
									$('#tblPreview tr:last').append('<th>'+cell+'</th>');
								}
								if (rowIdx == 1) {
									$('#tblPreview tr:last').append(
										$('<td>'+cell+'<br /></td>').data('colIdx', colIdx)
									);
								}
								else {
									$('#tblPreview tr:last td:eq('+colIdx+')').append(cell+'<br />');
								}
							});
						});
					
						$('#mainFrame > #content').append(
							'<div id="dropClasses" class="dropHeadline ui-widget-content">Klasse(n)</div>',
							'<div id="dropHour" class="dropHeadline ui-widget-content">Stunde</div>',
							'<div id="dropSubject" class="dropHeadline ui-widget-content">Fach</div>',
							'<div id="dropSupply" class="dropHeadline ui-widget-content">Aktueller Lehrer</div>',
							'<div id="dropRoom" class="dropHeadline ui-widget-content">Raum</div>',
							'<div id="dropPostponement" class="dropHeadline ui-widget-content">Verlegung von ... nach ...</div>',
							'<div id="dropTeacher" class="dropHeadline ui-widget-content">Eigentlicher Lehrer</div>',
							'<div id="dropStatus" class="dropHeadline ui-widget-content">Art der Vertretung</div>',
							'<div id="dropNotice" class="dropHeadline ui-widget-content">Anmerkung</div>'
						);
						
						$('.dropHeadline').draggable();
						
						$('#tblPreview tr:last td').droppable({
							activeClass: "ui-state-hover",
							hoverClass: "ui-state-active",
							drop: function( event, ui ) {
								$(this).addClass( "ui-state-highlight" );
								var colIdx = $(this).data('colIdx');
								$(ui.draggable).data('colIdx', colIdx);
								
								// Testen ob alle Überschriften verteilt sind.
								var currentIndices = new Array();
								var allHeadlinesDropped = true;
								
								$('.dropHeadline').each(function() {
									currentIndices.push($(this).data('colIdx'));
									allHeadlinesDropped = allHeadlinesDropped && $(this).data('colIdx') !== undefined;
								});
								
								if (allHeadlinesDropped) {
									var allHeadlinesDroppedInDifferentColumns = true;
									
									$.each(currentIndices, function(idx, currentIdx) {
										var controllIndices = currentIndices.slice();
										controllIndices.splice(idx, 1);
										
										$.each(controllIndices, function(idx2, controllIdx) {
											if (controllIdx == currentIdx) {
												allHeadlinesDroppedInDifferentColumns = false;
											}
										});
									});
									
									if (allHeadlinesDroppedInDifferentColumns) {
										$('#btnForward').button('enable');
									}
									else {
										$('#btnForward').button('disable');
									}
								}
								
								
							},
							out: function(  event, ui ) {
								$(this).removeClass("ui-state-highlight");
								$(ui.draggable).data('colIdx', undefined);
							}
						});
						currentAction++;
					}
				});
			});
		}
		
		else if (currentAction == 4) {
			
			
			var params = {};
			
			$('.dropHeadline').each(function() {
				var paramName = $(this).attr('id').substr(4).toLowerCase();
				params[paramName] = $('#tblPreview th').eq($(this).data('colIdx')).html();
			});
			
			$.getJSON('setup.php?action='+steps[currentAction], params, function(data) {
				if (data.status == 'success') {
					currentAction++;
					
					
					$('#mainFrame > #headline').html('<h1>Schulspezifische Einstellungen</h1><h3>Konfigurieren Sie das System f&uuml;r Ihre Schule.</h3>');
					$('#mainFrame > #content').html(
						$('<div id="schoolSpecificSettings" class="ui-widget"></div>').append(
							'<label for="iptSchoolname">Schulname</label><input id="iptSchoolname" name="iptSchoolname" />',
							'<form id="uploadForm" method="POST" enctype="multipart/form-data"><label for="iptSchoollogo">Schullogo</label><input type="file" id="iptSchoollogo" name="iptSchoollogo" /></form>',
							'<label for="iptNumLessons">Anzahl der Schulstunden</label><select id="iptNumLessons" name="iptNumLessons"></select>',
							'<label for="iptLessonTimes">Stunden Start- und Endzeitpunkte</label><table id="iptLessonTimes"><tr><th>Stunde</th><th>Start</th><th>Ende</th></tr></table>',
							'<label for="selVon">Minimal Jahrgang</label><select id="selVon" name="selVon"></select>',
							'<label for="selBis">Maximal Jahrgang</label><select id="selBis" name="selBis" ></select>'
						)
					);
					
					for (var grade = 1; grade <= 13; grade++) {
						$('#selVon').append( $('<option></option>').html(grade) );
						$('#selBis').append( $('<option></option>').html(grade) );
					}
					
					$('#selVon option:first').attr('selected', 'selected');
					$('#selBis option:last').attr('selected', 'selected');
					
					$('#selVon').live('click', function(){
						var selectedVon = parseInt($('#selVon').val());
						var selectedBis = parseInt($('#selBis').val());
						var options = '';
						for (i = selectedVon; i <= 13; i++) {
							options += '<option>'+i+'</options>';
						}
						$('#selBis').html(options);
						if (selectedBis > selectedVon) {
							var toSelectIdx = selectedBis - selectedVon;
							$('#selBis option:eq('+toSelectIdx+')').attr('selected', 'selected');
						}
					});
					
					for (var i = 1; i <= 12; i++) {
						$('#iptNumLessons').append(
							$('<option>'+i+'</option>')
						);
					}
					
					$('#iptNumLessons').change(function() {
						var numLessons = $(this).val();
						
						$('#iptLessonTimes tr:gt(0)').remove();
						
						for (var i = 1; i <= numLessons; i++) {
							$('#iptLessonTimes').append(
								$('<tr></tr>').append(
									'<td>'+i+'</td>',
									'<td><input class="lessonTime startTime"></td>',
									'<td><input class="lessonTime endTime"></td>'
								)
							)
						}
						
						$('.lessonTime').timepicker({
							timeFormat: 'hh:mm', 
							timeText: 'Uhrzeit', 
							hourText: 'Stunden', 
							minuteText: 'Minute', 
							secondText: 'Sekunde', 
							currentText: 'Jetzt', 
							closeText: 'Fertig'
						});
					});
					
					$('#iptNumLessons').trigger('change');
				}
				else {
					alert('Es gab einen Fehler beim Eintragen der Überschriften in die Datenbank.');
					return;
				}
			});
		}
		
		else if (currentAction == 5) {
			var lessonStarts = new Array();
			var lessonEnds = new Array();
			
			var schoolName = $('#iptSchoolname').val();
			
			if (schoolName === '') {
				alert('Sie haben keinen Schulnamen vergeben.');
				return;
			}
			
			var minGrade = parseInt($('#selVon').val());
			var maxGrade = parseInt($('#selBis').val());
			
			if (minGrade > maxGrade) {
				alert('Der Maximal- darf nicht kleiner als der Minimaljahrgang sein.');
				return;
			}
			
			var allTimesAreValid = true;
			
			$('.lessonTime').each(function() {
				allTimesAreValid = allTimesAreValid && $(this).val() !== '';
				if ($(this).hasClass('startTime')) {
					lessonStarts.push($(this).val());
				}
				else if ($(this).hasClass('endTime')) {
					lessonEnds.push($(this).val());
				}
			});
			
			if (allTimesAreValid) {
				var allTimesAreAscading = true;
				for (var i = 0; i < lessonStarts.length; i++) {
					allTimesAreAscading = allTimesAreAscading && lessonStarts[i] <= lessonEnds[i];
					if (i != 0) {
						allTimesAreAscading = allTimesAreAscading && lessonEnds[i-1] <= lessonStarts[i];
					}
				}
				
				if (allTimesAreAscading) {
					
					
					if ($('#iptSchoollogo').val()) {
						$('#content').append('<iframe name="upload-frame" id="upload-frame" style="display: none;"></iframe>');
						var uploadFrame = $('#upload-frame');
						
						// Die Form abschickbereit machen - Antwort werden an upload - frame geschickt
						$('#uploadForm').attr('target', 'upload-frame')
									   .attr('action', 'setup.php?action='+steps[currentAction]+'&schoolname='+schoolName+'&lessonStarts='+JSON.stringify(lessonStarts)+'&lessonEnds='+JSON.stringify(lessonEnds)+'&minGrade='+minGrade+'&maxGrade='+maxGrade)
									   .submit();
						
						uploadFrame.one('load', function() {
							
							var answer = $(this).contents().find('body').html();
							
							answer = $.parseJSON(answer);
							
							uploadFrame.remove();
							
							if (answer.status == 'success') {
								currentAction++;
								
								$('#mainFrame > #headline').html('<h1>Administrator</h1><h3>Legen Sie den Namen und das Passwort des Administrators fest.</h3>');
								$('#mainFrame > #content').html('<div id="administratorLogin" class="ui-widget"></div>');
								$('#content #administratorLogin').ValidatingForm({
									fields: [
										{ label: "Benutzername", name: "adminName" },
										{ label: "Passwort", name: "adminPass", type: "password"}
									]
								});
								$('#btnForward span').html('Fertigstellen');
							}
							else {
								if (answer.reason == 'CANT_MOVE_LOGO') {
									alert('Die Bilddatei konnte nicht in den Zielordner verschoben werden.');
								}
								else if (answer.reason == 'NOT_ALL_SETTINGS_COULD_BE_SET') {
									alert('Nicht alle Einstellungen konnten erfolgreich in der Datenband gespeichert werden.');
								}
								else {
									alert('Es gab einen unbekannten Fehler beim Eintragen der Daten in die Datenbank.');
								}
								return;
							}
						});
					}
					
					else {
						var params = {
							schoolname: schoolName,
							lessonStarts: JSON.stringify(lessonStarts),
							lessonEnds: JSON.stringify(lessonEnds),
							minGrade: minGrade,
							maxGrade: maxGrade
						};
						
						$.getJSON('setup.php?action='+steps[currentAction], params, function(data) {
							if (data.status == 'success') {
								currentAction++;
								
								$('#mainFrame > #headline').html('<h1>Administrator</h1><h3>Legen Sie den Namen und das Passwort des Administrators fest.</h3>');
								$('#mainFrame > #content').html('<div id="administratorLogin" class="ui-widget"></div>');
								$('#content #administratorLogin').ValidatingForm({
									fields: [
										{ label: "Benutzername", name: "adminName" },
										{ label: "Passwort", name: "adminPass", type: "password"}
									]
								});
								$('#btnForward span').html('Fertigstellen');
							}
							else {
								if (data.reason == 'CANT_MOVE_LOGO') {
									alert('Die Bilddatei konnte nicht in den Zielordner verschoben werden.');
								}
								else if (data.reason == 'NOT_ALL_SETTINGS_COULD_BE_SET') {
									alert('Nicht alle Einstellungen konnten erfolgreich in der Datenband gespeichert werden.');
								}
								else {
									alert('Es gab einen unbekannten Fehler beim Eintragen der Daten in die Datenbank.');
								}
								return;
							}
						});
					}
				}
				else {
					alert('Die Stundenanfangs- und/oder Stundenendzeiten sind nicht in linearer Reihenfolge.');
					return;
				}
			}
			else {
				alert('Füllen Sie bitte Alle Uhrzeitenfelder aus.');
				return;
			}
		}
		
		else if (currentAction == 6) {
			var values = $('#content #administratorLogin').ValidatingForm('values');
			values.adminPass = SHA1(values.adminPass);
			
			$.getJSON('setup.php?action='+steps[currentAction], values, function(data) {
				if (data.status == 'success') {
					self.location.href="../index.php";
				}
				else {
					// fehlercode
				}
			});
		}
	}
});