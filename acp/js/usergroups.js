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

function markupUsergroups() {
	$('#usergroups').ready(function(){
		
		var currentid = null;
		var currentAction = 'new';
		
		var rights = [];
		var usergroups = [];
		
		// Autoladen der Extraseitentabelle beim öffnen der Seite
		if ($('#usergroupsTable').html()=='') {
			$('#usergroups').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			
			// Die bereits existierenden Benutzergruppen laden			
			$.getJSON('usergroups.php',{action: 'getUsergroups'}, function(data) {
				if (data.status == 'success') {
					
					usergroups = data.usergroups;
					rights = data.rights;
					
					// Eine qTable mit den existierenden Benutzergruppen erstellen
					$('#usergroupsTable').qTable({
						headlines: ['Gruppenname', 'Beschreibung', 'Optionen'],
						data : data.usergroups,
						rowIDs : 'groupid',
						displayRowIDColumn: false,
						tableHeight : '83%',
						insert: function(row) {
							$(row).append($('<td></td>').html('<button class="edit"></button><button class="delete"></button>'));
						},
						resize: setButtons
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#usergroups').css('visibility', 'visible');", 300);
					
					
					// Das Rechteobjekt für das Formular zum Erstellen neuer Benutzergruppen und editieren bereits bestehender anpassen
					var rightsForForm = {};
					var rightsTrueByDefault = [];
					
					$.each(rights, function(idx, right) {
						rightsForForm[right.rightid] = right.rightname;
						if (right.defaultValue === 'true') {
							rightsTrueByDefault.push(right.rightid);
						}
					});
					
					
					// Div für den Dialog erstellen
					$('#usergroups').append('<div id="frmUsergroup" title="Neuen Benutzer hinzuf&uuml;gen"></div>');
					
					// Formular innerhalb des Divs erstellen lassen
					$('#frmUsergroup').ValidatingForm({
						fields: [
							{ "label": "Gruppenname", "name": "groupname", "type": "text", validation: function(value) { return value.length > 0; } },
							{ "label": "Gruppenbeschreibung", "name": "description", "type": "textarea", validation: function(value) { return true; } },
							{ "label": "Rechte", "name": "rights", "type": "segmentedcontroll", "segments": rightsForForm, "value": rightsTrueByDefault}
						]
					});
					
					// Aus dem Div ein jQuery-UI Dialog erstellen
					$('#frmUsergroup').dialog({
						autoOpen: false,
						height: 425,
						width: 550,
						modal: true,
						buttons: {
							'Eintragen': function() {
								var values = $(this).ValidatingForm('values');
								values.rights = JSON.stringify(values.rights.idValues);
								
								if (currentAction == 'new') {
									$.post('usergroups.php?action=addUsergroup', values, function(data) {
										data = $.parseJSON(data);
										if (data.status != 'success') {
											alert('Das Hinzufuegen der Benutzergruppe ist fehlgeschlagen.');
										}
										else {
											$('#usergroupsTable').qTable('insert', data.usergroup);
										}
										$('#frmUsergroup').dialog('close');
									});
								}
								
								else if (currentAction == 'edit') {
									values.groupid = currentid;
									$.post('usergroups.php?action=editUsergroup', values, function(data) {
										data = $.parseJSON(data);
										if (data.status != 'success') {
											alert('Das Editieren der Benutzergruppe ist fehlgeschlagen.');
										}
										else {
											$('#usergroupsTable').qTable('update', {index: '', value: data.usergroup});
										}
										$('#frmUsergroup').dialog('close');
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
					
					// Button zum Erstellen  neuer Benutzergruppen den Dialog öffnen lassen
					$('#create-usergroup').button().click(function() {
						currentAction = 'new';
						$('#frmUsergroup').dialog('open');
					});
					
					// Button, welcher einen Dialog erstellt, in welchem man eine Gruppe auswählen kann, deren Benutzer gelöscht werden sollen.
					$('#delete-users-by-usergroup').button().click(function() {
						$('#usergroups').append('<div id="frmDeleteUsers" title="Alle Benutzer einer bestimmten Gruppe l&ouml;schen"><form><fieldset></fieldset></form></div>');
						
						$('#frmDeleteUsers').dialog({
							autoOpen: true,
							height: 200,
							width: 350,
							modal: true,
							buttons: {
								'Loeschen': function() {
									$.getJSON('usergroups.php', {action: 'deleteUsersByUsergroup', groupid: $('#toDeleteGroup').val()}, function(data) {
										if (data.status !== 'success') {
											alert('Das loeschen der Benutzer der Gruppe ist fehlgeschlagen.');
										}
										$('#frmDeleteUsers').dialog('close');
									});
								},
								'Abbrechen': function() {
									$(this).dialog('close');
								}
							},
							close: function() {
								$(this).remove();
							},
							open: function() {
								$(this).find('fieldset').append('<label for="toDeleteGroup">Gruppe</label><select id="toDeleteGroup" size="1"></select>');
								$.each(usergroups, function(idx, usergroup) {
									$('#toDeleteGroup').append('<option value="'+usergroup.groupid+'">'+usergroup.groupname+'</option>');
								});
							}
						});
					});
				}
			});
		}
		
		
		// Aktionen für das Drücken der Editier- und Löschenbuttons setzen
		function setButtons() 
		{
			// Editieren-Button
			$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function(){
				var usergroupid = $(this).parent().parent().attr('id');
				currentid = usergroupid;
				
				// Die Werte für die schon existierende Benutzergruppe laden
				$('#loading').css('visibility', 'visible');
				$.getJSON('usergroups.php', {action: 'getUsergroup', usergroupid: usergroupid, rightsById: 'true'}, function(data) {
					$('#loading').css('visibility', 'hidden');
					if (data.status == 'success') {
						currentAction ='edit';
						
						// Rechte so umwandeln, dass ValidatingForms sie setzen kann
						var rightsSelected = [];
						$.each(data.usergroup.rights, function(rightId, rightValue) {
							if (rightValue == 'true') {
								rightsSelected.push(rightId);
							}
						});
						
						$('#frmUsergroup').ValidatingForm('setValues', {
							'groupname': data.usergroup.groupname,
							'description': data.usergroup.description,
							'rights': rightsSelected
						});
						$('#frmUsergroup').dialog('open');
					}
				});
			});
			
			// Löschen-Button
			$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
				var usergroupid = $(this).parent().parent().attr('id');

				$.getJSON('usergroups.php', {action: 'deleteUsergroup', groupid: usergroupid}, function(data) {
					if (data.status == 'success') {
						$('#usergroupsTable').qTable('remove', usergroupid);
					}
					else {
						alert('Die Benutzergruppe konnte nicht gelöscht werden.');
					}
				});
			});
		}

	});
}