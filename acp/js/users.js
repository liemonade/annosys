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

function markupUsers() {
	$('#users').ready(function(){
		var allFields = $([]).add('#extrapagesName').add('#extrapagesStart').add('#extrapagesEnd').add('#extrapagesCode').add('#extrapagesDuration');
		var lastEdit;
		var currentid;
		var action = 'new' // new oder edit
		
		$('#usersUsergroup').buttonset();
		
		// Autoladen der Extraseitentabelle beim öffnen der Seite
		if ($('#usersTable').html()=='') {
			$('#users').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			$.getJSON('users.php',{action: 'getUsers'}, function(data) {
				if (data.status == 'success') {
					$('#usersTable').qTable({
						headlines: ['Benutzername', 'E-Mail', 'Benutzergruppe', 'Anmeldedatum', 'Letzter Login', 'Optionen'],
						data : data.users,
						rowIDs : 'userid',
						displayRowIDColumn: false,
						tableHeight : '83%',
						insert: function(row) {
							$(row).append($('<td></td>').html('<button class="edit"></button><button class="delete"></button>'));
							$(row).find('td:eq(3), td:eq(4)').each(function() {
								$(this).html(toGerDate($(this).html()));
							});
							$(row).find('td:eq(2)').html(data.usergroups[$(row).find('td:eq(2)').html()]);
						},
						resize: function() {
							setButtons();
						}
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#users').css('visibility', 'visible');", 300);
					
					$('#create-user').button().click(function() {
						action = 'new';
						$('#frmUser').dialog('open');
					});
				}
				
			});
		}
		
		$('#frmUser').dialog({
			autoOpen: false,
			height: 425,
			width: 550,
			modal: true,
			buttons: {
				'+' : function () {
					$('#usersUserdata').append('<tr><td><input type="text" /></td><td><input type="text" /></td><td><div class="removeUserdataBtn"></div></td></tr>');
					$('#usersUserdata tr:gt(0) td:last .removeUserdataBtn').button({icons:{primary:'ui-icon-circle-close'}}).unbind('click').click(function () {
						if ($('#usersUserdata tr').size() == 2) {
							$(this).parent().parent().find('input').each(function() {
								$(this).val('');
							});
						}
						else {
							$(this).parent().parent().remove();
						}
					});
				},
				Eintragen: function() {
					var usernameVal = $('#usersUsername').val();
					var passwordVal = $('#usersPassword').val();
					if (passwordVal != '') {
						if (action == 'edit' && passwordVal == '********') {
							passwordVal = '';
						}
						else {
							passwordVal = SHA1(passwordVal);
						}
					}
					var emailVal = $('#usersEmail').val();
					var usergroupVal = $('#usersUsergroup label.ui-state-active').attr('for');
					var userdataVal = new Object();
					$('#usersUserdata tr:gt(0)').each(function(userdataIdx, userdata) {
						var dataname = $(userdata).find('input:first').val();
						if (dataname != '') {
							var value = $(userdata).find('input:last').val();
							userdataVal[dataname] = value;
						}
					});
					userdataVal = JSON.stringify(userdataVal);
					
					if (usernameVal == '' || usergroupVal == undefined || ((action=='new') ? passwordVal == '' : false) ) {
						alert('Es fehlen Daten');
						return;
					}
					
					lastEdit = {username: usernameVal, password: passwordVal, email: emailVal, usergroup: usergroupVal, userdata: userdataVal};
					
					if (action == 'new') {
						$.post('users.php?action=addUser', lastEdit, function(data) {
							data = $.parseJSON(data);
							if (data.status != 'success') {
								alert('Es gab einen Fehler beim Eintragen des Benutzers');
								return;
							}
							else {
								var user = data.user;
								$('#usersTable').qTable('insert', user);
								$('#frmUser').dialog('close');
							}
						});
					}
					
					if (action == 'edit') {
						lastEdit['userid'] = currentid;
						$.post('users.php?action=editUser', lastEdit, function(data) {
							data = $.parseJSON(data);
							if (data.status != 'success') {
								alert('Es gab einen Fehler beim Bearbeiten des Benutzers');
								return;
							}
							else {
								var user = data.user;
								$('#usersTable').qTable('update', {index: '', value: user});
								$('#frmUser').dialog('close');
							}
						});
					}
				},
				Zuruecksetzen: function() {
					$('#usersUsername, #usersPassword, #usersEmail').val('');
					$('#usersUsergroup label').removeClass('ui-state-active').attr('aria-pressed', 'false');
					$('#usersUserdata').html('<tr align="center"><td>Variablenname</td><td>Wert</td></tr><tr><td><input type="text" /></td><td><input type="text" /></td><td><div class="removeUserdataBtn"></div></td></tr>');
					$('#usersUserdata tr:gt(0) td:last .removeUserdataBtn').button({icons:{primary:'ui-icon-circle-close'}}).unbind('click').click(function () {
						if ($('#usersUserdata tr').size() == 2) {
							$(this).parent().parent().find('input').each(function() {
								$(this).val('');
							});
						}
						else {
							$(this).parent().parent().remove();
						}
					});
				},
				Abbrechen: function() {
					$(this).dialog('close');
				}
			},
			open: function() {
				if (action == 'edit') {
					$('#usersPassword').val('********')
					.unbind('focusin').focusin(function() {
						if ($(this).val() == '********') {
							$(this).val('');
						}
					})
					.unbind('focusout').focusout(function() {
						if ($(this).val() == '') {
							$(this).val('********');
						}
					});
				}
				
				$('#usersUserdata .removeUserdataBtn').button({icons:{primary:'ui-icon-circle-close'}}).unbind('click').click(function () {
					if ($('#usersUserdata tr').size() == 2) {
						$(this).parent().parent().find('input').each(function() {
							$(this).val('');
						});
					}
					else {
						$(this).parent().parent().remove();
					}
				});
			},
			close: function() {
				$('#usersUsername, #usersPassword, #usersEmail').val('');
				$('#usersUsergroup label').removeClass('ui-state-active').attr('aria-pressed', 'false');
				$('#usersUserdata').html('<tr align="center"><td>Variablenname</td><td>Wert</td></tr><tr><td><input type="text" /></td><td><input type="text" /></td><td><div class="removeUserdataBtn"></div></td></tr>');
			}
		});
		
		
		
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
								   .attr('action', 'users.php?action=importcsv')
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
					if (answer.importedUsers) {
						$('#usersTable').qTable('insert', answer.importedUsers);
					}
				}
				$('#frmImportCSV').dialog('close');
			});
		});
		
		
		// Extraseiten mit CSV Datei importieren
		$('#import-users-csv').button().click(function(){
			$('#frmImportCSV').dialog('open');
		});
		
		
		function setButtons() 
		{
			// Editieren-Button
			$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function(){
				var userid = $(this).parent().parent().attr('id');
				currentid = userid;
				$.getJSON('users.php', {action: 'getUser', userid: userid}, function(data) {
					if (data.status == 'success') {
						$('#usersUsername').val(data.user.username);
						$('#usersEmail').val(data.user.email);
						$('#usersUsergroup label').each(function() {
							if ($(this).attr('for') == data.user.usergroup) {
								$(this).addClass('ui-state-active').attr('aria-pressed', 'true');
							}
						});
						if (data.user.userdata) {
							$('#usersUserdata tr:gt(0)').remove();
							$.each(data.user.userdata, function(dataname, value) {
								$('#usersUserdata').append('<tr><td><input type="text" value="'+dataname+'" /></td><td><input type="text"  value="'+value+'" /></td><td><div class="removeUserdataBtn"></div></td></tr>');
							});
						}
						
						
						action ='edit';
						$('#frmUser').dialog('open');
					}
				});
			});
			
			// Löschen-Button
			$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
				var userid = $(this).parent().parent().attr('id');
				$.getJSON('users.php', {action: 'deleteUser', userid: userid}, function(data) {
					if (data.status == 'success') {
						$('#usersTable').qTable('remove', userid);
					}
					else {
						alert('Der Benutzer konnte nicht gelöscht werden.');
					}
				});
			});
		}
	});
}