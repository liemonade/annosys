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

function markupExtrapages() {
	$('#extrapages').ready(function(){
		var currentid = null;
		var usergroups = null;
		var currentAction = 'new' // new oder edit
		
		// Autoladen der Extraseitentabelle beim öffnen der Seite
		if($('#pagesTable').html()=='')
		{
			$('#extrapages').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			$.getJSON('extrapages.php',{action: 'getExtrapages'},function(data) {
				if (data.status == 'success') {
					// Globale usergroups Variable mit den Benutzergruppen setzen
					usergroups = data.usergroups;
					
					// Die Benutzergruppen innerhalb des Extrapages Arrays joinen um Sie in de qTable eintragbar zu machen
					var extrapagesForqTable = [];
					$.each(data.extrapages, function(idx, extrapage) {
						$.each(extrapage.usergroups, function(idx, groupid) {
							extrapage.usergroups[idx] = usergroups[groupid];
						});
						extrapage.usergroups = extrapage.usergroups.join(',');
						extrapagesForqTable.push(extrapage);
					});
					
					
					$('#pagesTable').qTable({
						headlines: ['Name', 'Ersteller', 'Ausgabe von', 'Ausgabe bis', 'Letzte Bearbeitung', 'Anzeigedauer', 'Zielgruppen', 'Optionen'],
						data : extrapagesForqTable,
						rowIDs : 'pageid',
						displayRowIDColumn: false,
						tableHeight : '83%',
						insert: function(row) {
							// Buttons einfügen
							$(row).append($('<td></td>').html('<button class="preview"></button><button class="edit"></button><button class="delete"></button>'));
							// Die Timestamps in normales Datum umwandeln
							$(row).find('td:eq(2), td:eq(3), td:eq(4)').each(function() {
								$(this).html(toGerDateTime($(this).html()));
							});
							// Die Einheit Sekunde (s) an Anzeigedauerspalte anhängen
							$(row).find('td:eq(5)').append('s');
						},
						resize: function() {
							setButtons();
						}
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#extrapages').css('visibility', 'visible');", 300);
				
				
					// Div für den Dialog erstellen
					$('#extrapages').append('<div id="frmExtrapage" title="Neue Extraseite hinzuf&uuml;gen"></div>');
					
					// Formular innerhalb des Divs erstellen lassen
					$('#frmExtrapage').ValidatingForm({
						fields: [
							{ label: "Name der Extraseite", name: "name", type: "text", validation: function(value) { return value.length > 0; } },
							{ label: "Wird ausgegeben ab", name: "start_date", type: "datetimepicker" },
							{ label: "Bis", "name": "end_date", type: "datetimepicker" },
							{ label: "Anzeigedauer in Sekunden: ", name: "duration", type: "slider" },
							{ label: "Zielgruppen", name: "usergroups", type: "segmentedcontroll", segments: usergroups },
							{ label: "HTML Code", name: "code", type: "textarea", attributes: {cols: "62", rows: "5"}, classes: ["text", "ui-widget-content", "ui-corner-all"], onCreate: function(fieldElem) {
								$(fieldElem).dblclick(function() {
									openEditor();
								});
							} }
						],
						appendix: '<button id="extrapagesInsertImage">Bild einf&uuml;gen</button><button id="extrapagesCodeEditor">Editor</button>'
					});
					
					// Aus dem Div ein jQuery-UI Dialog erstellen
					$('#frmExtrapage').dialog({
						autoOpen: false,
						height: 530,
						width: 560,
						modal: true,
						buttons: {
							'Eintragen': function() {
								var values = $(this).ValidatingForm('values');
								var usergroupNames = values.usergroups.htmlValues.join(',');
								values.usergroups = JSON.stringify(values.usergroups.idValues);
								
								console.log(values, currentAction);
								if (currentAction == 'new') {
									$.post('extrapages.php?action=addExtrapage', values, function(data) {
										data = $.parseJSON(data);
										if (data.status != 'success') {
											alert('Das Hinzufuegen der Extraseite ist fehlgeschlagen.');
										}
										else {
											var newData = {
												pageid: data.pageid,
												name: values.name,
												poster: data.poster,
												start_date: values.start_date,
												end_date: values.end_date,
												edit_date: Date.parse(Date())/1000,
												duration: values.duration,
												usergroups: usergroupNames
											};
											$('#pagesTable').qTable('insert', newData);
										}
										$('#frmExtrapage').dialog('close');
									});
								}
								
								else if (currentAction == 'edit') {
									values.pageid = currentid;
									$.post('extrapages.php?action=editExtrapage', values, function(data) {
										data = $.parseJSON(data);
										if (data.status != 'success') {
											alert('Das Editieren der Extraseite ist fehlgeschlagen.');
										}
										else {
											var newData = {
												pageid: currentid,
												name: values.name,
												poster: data.poster,
												start_date: values.start_date,
												end_date: values.end_date,
												edit_date: Date.parse(Date())/1000,
												duration: values.duration,
												usergroups: usergroupNames
											};
											$('#pagesTable').qTable('update', {index: '', value: newData});
										}
										$('#frmExtrapage').dialog('close');
									});
								}
							},
							'Zuruecksetzen': function() {
								$(this).ValidatingForm('setValues', '');
							},
							'Abbrechen': function() {
								$(this).dialog('close');
							}
						},
						close: function() {
							$(this).ValidatingForm('setValues', '');
						}
					});
					
					
					// Button um ein Bild in den Code-Bereich einzufügen
					$('#extrapagesInsertImage').button().unbind('click').click(function(event) {
						event.preventDefault();
						var btnInsertImage = this;
						$.getJSON('extrapages.php', {action: 'getPictures'}, function(data){
							if (data.status != 'success') {
								alert('Es sind keine Bilder vorhanden');
								return;
							}
							$('#pictureBox').css({top: $(btnInsertImage).offset().top-340, left: $(btnInsertImage).offset().left-35, visibility: 'visible'});
							
							$('#pictureBox').html('');
							
							$('#pictureBox').append('<div id="closeBox"><div>');
							$('#closeBox').button({icons: {primary: 'ui-icon-circle-close'}})
							.unbind('click').click(closeImageview)
							.css({height: 20, width: 20, marginRight:10, left:135, paddingRight:5});
							
							$.each(data.images, function(imageIdx, image) {
								$('#pictureBox').append($('<img class="uploadedImage" />').attr('src', '../uploads/'+image).data('filename', image));
							});
							
							$('#pictureBox img.uploadedImage').mouseover(function() {
								$(this).toggleClass('uploadedImageMouseover');
							});
							
							$('#pictureBox img.uploadedImage').mouseout(function() {
								$(this).toggleClass('uploadedImageMouseover');
							});
							
							
							$('#pictureBox img.uploadedImage').unbind('click').click(function() {
								$('#code').val('<img src="uploads/'+$(this).data('filename')+'" height="100%" />');
								closeImageview();
							});
						});
					});
					
					
					
					function closeImageview() 
					{
						$('#pictureBox').html('');
						$('#pictureBox').css({visibility: 'hidden'});
					}
					
					
					
					// Den WYSIWYG-Editor öffnen
					$('#extrapagesCodeEditor').button().unbind('click').click(function(event) {
						event.preventDefault();
						openEditor();
					});
					
					
					function openEditor() 
					{
						var destination = 'preview.php?action=edit&dest=code';
						if (currentAction == 'edit') {
							destination = 'preview.php?action=edit&dest=code&pageid='+currentid;
						}
						fenster = window.open(destination, "Popupfenster", "width=1280,height=720,resizable=no");
						fenster.focus();
					}
					
					
					
					// Modal-Dialog für den Upload von CSV Datein
					$('#frmImportCSV').dialog({
						autoOpen: false,
						modal: true,
						width: 430,
						height: 210,
						buttons: {
							Schliessen: function() { 
								$('#frmImportCSV').dialog('close');
							}
						}
					});
					
					
					// Senden-Button der CSV-Datei für den Import
					$('#submitCSV').button().click(function(){
						var uploadFrame = $('#upload-frame');
						// Die Form abschickbereit machen - Antwort werden an upload - frame geschickt
						$('#frmImportCSV form').attr('target', 'upload-frame')
											   .attr('action', 'extrapages.php?action=importcsv')
											   .submit();
						uploadFrame.one('load', function(){
							var answer = $(this).contents().find('body').html();
							
							answer = $.parseJSON(answer);
							if (answer.status == 'NOT_A_CSV_FILE') {
								alert('Die gesendete Datei ist keine CSV-Datei');
							}
							else if (answer.status == 'COULD_NOT_OPEN_FILE') {
								alert('Die Datei konnte nicht geöffnet werden');
							}
							else if (answer.status == 'CSV_FORMAT_NOT_CORRECT') {
								alert('Das Format der CSV-Datei ist nicht korrekt');
							}
							else if (answer.status == 'IMPORT_NOT_COMPLETE' || answer.status == 'IMPORT_SUCCESSFULL') {
								if (answer.status == 'IMPORT_NOT_COMPLETE')
									alert('Es konnten nicht alle Elemente der CSV-Datei importiert werden');
								if (answer.importedExtrapages) {
									$('#pagesTable').qTable('insert', answer.importedExtrapages);
								}
							}
							$('#frmImportCSV').dialog('close');
						});
					});
					
					
					
					
					// Modal-Dialog für den Upload von Bild Datein
					$('#frmPictureUpload').dialog({
						autoOpen: false,
						modal: true,
						width: 430,
						height: 210,
						buttons: {
							Schliessen: function() { 
								$('#frmPictureUpload').dialog('close');
							}
						}
					});
					
					
					// Senden-Button der CSV-Datei für den Import
					$('#submitPic').button().click(function(){
						var uploadFrame = $('#upload-frame');
						// Die Form abschickbereit machen - Antwort werden an upload - frame geschickt
						$('#frmPictureUpload form').attr('target', 'upload-frame')
											   .attr('action', 'extrapages.php?action=uploadpic')
											   .submit();
						uploadFrame.one('load', function(){
							var answer = $(this).contents().find('body').html();
							answer = $.parseJSON(answer);
							if (answer.status != 'success') {
								alert('Die gesendete Bild-Datei konnte nicht gespeichert werden');
							}
							$('#frmPictureUpload').dialog('close');
						});
					});
					
					
					
					// Neue Extraseite-Button
					$('#create-site').button().click(function(){
						currentAction = 'new';
						$('#frmExtrapage').dialog('open');
					});
					
					// Extraseiten mit CSV Datei importieren
					$('#upload-csv').button().click(function(){
						$('#frmImportCSV').dialog('open');
					});
					
					
					// Extraseiten mit CSV Datei importieren
					$('#upload-pic').button().click(function(){
						$('#frmPictureUpload').dialog('open');
					});
					
					
				}
			});
		}
		
		
		
		// Funktion zum Setzen der Optionenbuttons
		function setButtons() {
			// Vorschau-Button
			$('.preview').button({icons: {primary: 'ui-icon-circle-zoomin'}}).click(function(){
				currentid = $(this).parent().parent().attr('id');
				fenster = window.open('preview.php?action=preview&pageid='+currentid, "Popupfenster", "width=1280,height=720,resizable=no");
				fenster.focus();
			});
			
			// Editieren-Button
			$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function(){
				currentid = $(this).parent().parent().attr('id');
				$.getJSON('extrapages.php', {action: 'getExtrapage', pageid: currentid}, function(data){
					if (data.status != 'success') {
						alert('Die Daten der zu bearbeitenden Seite konnten nicht aufgerufen werden');
						return;
					}
					$('#frmExtrapage').ValidatingForm('setValues', data.extrapage);
					currentAction = 'edit';
					$('#frmExtrapage').dialog('open');
				});
				
			});
			
			// Löschen-Button
			$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
				var pageid = $(this).parent().parent().attr('id');
				$.getJSON('extrapages.php?action=deleteExtrapage', {pageid: pageid},function(data){
					if(data.status != 'success')
						alert('Die Seite konnte nicht gelöscht werden.');
					else
						$('#pagesTable').qTable('remove', pageid);
				});
			});
		}
		
	});
}