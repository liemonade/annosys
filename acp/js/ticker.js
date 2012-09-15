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

function markupTicker() {
	$('#ticker').ready(function(){
		var currentid;
		var usergroups = null;
		var action = 'new';		// new oder edit
		
		// Autoladen der Tickermessagetabelle beim Öffnen der Seite
		if($('#tblTicker').html()=='') {
			$('#ticker').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			
			$('#tickerUsergroups label').each(function () {
				usergroups[$(this).attr('for')] = $(this).html();
			});
			
			$.getJSON('ticker.php', {action: 'getTickermessages'}, function(data) {
				
				if (data.status === 'success') {
					
					usergroups = data.usergroups;
					
					// Die Benutzergruppen innerhalb des Tickermessages Arrays joinen um Sie in die qTable eintragbar zu machen
					var tickermessagesForqTable = [];
					$.each(data.tickermessages, function(idx, tickermessage) {
						$.each(tickermessage.usergroups, function(idx, groupid) {
							tickermessage.usergroups[idx] = usergroups[groupid];
						});
						tickermessage.usergroups = tickermessage.usergroups.join(',');
						tickermessagesForqTable.push(tickermessage);
					});
					
					
					$('#tblTicker').qTable({
						headlines: ['Tickertext', 'Eintr&auml;ger(in)', 'Ausgabe von', 'Ausgabe bis', 'Zielgruppen', 'Optionen'],
						order: ['message', 'poster', 'start_date', 'end_date', 'usergroups'],
						data : tickermessagesForqTable,
						rowIDs : 'tickerid',
						displayRowIDColumn: false,
						tableHeight : '83%',
						insert: function(row) {
							$(row).append($('<td></td>').html('<button class="edit"></button><button class="delete"></button>'));
							$(row).find('td:eq(2), td:eq(3)').each(function() {
								$(this).html(toGerDateTime($(this).html()));
							});
						},
						resize: function() {
							setButtons();
						}
					});
					
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#ticker').css('visibility', 'visible');", 300);
				
					$('#ticker').append('<div id="frmTickermessage" title="Neue Tickernachricht hinzuf&uuml;gen"></div>');
					$('#frmTickermessage').ValidatingForm({
						fields: [
							{ label: 'Tickertext', name: 'message', type: 'text', validation: function(value) { return value.length > 1; } },
							{ label: 'Wird ausgegeben ab', name: 'start_date', type: 'datetimepicker' },
							{ label: 'Bis', name: 'end_date', type: 'datetimepicker' },
							{ label: 'Zielgruppen', name: 'usergroups', type: 'segmentedcontroll', segments: usergroups }
						]
					});
					
					
					// Dialog zum Anlegen einer Tickernachricht
					$('#frmTickermessage').dialog({
						autoOpen: false,
						height: 350,
						width: 550,
						modal: true,
						buttons: {
							Eintragen: function(){
								
								var values = $('#frmTickermessage').ValidatingForm('values');
								var usergroupNames = values.usergroups.htmlValues.join(',');
								values.usergroups = JSON.stringify(values.usergroups.idValues);
								
								if (action == 'new') {
									$.post('ticker.php?action=addTickermessage', values, function(data){
										data = $.parseJSON(data);
										if (data.status == 'success') {
											var newData = {
												tickerid: data.tickermessage.tickerid,
												message: data.tickermessage.message,
												usergroups: usergroupNames,
												poster: data.tickermessage.poster,
												start_date: data.tickermessage.start_date,
												end_date: data.tickermessage.end_date
											};
											$('#tblTicker').qTable('insert', newData);
											$('#frmTickermessage').dialog('close');
										}
									});
								}
								else if (action == 'edit') {
									values.tickerid = currentid;
									
									$.post('ticker.php?action=editTickermessage', values, function(data){
										data = $.parseJSON(data);
										if (data.status == 'success') {
											var newData = {
												tickerid: data.tickermessage.tickerid,
												message: data.tickermessage.message,
												usergroups: usergroupNames,
												poster: data.tickermessage.poster,
												start_date: data.tickermessage.start_date,
												end_date: data.tickermessage.end_date
											};
											$('#tblTicker').qTable('update', {index:'', value: newData});
											$('#frmTickermessage').dialog('close');
										}
									});
								}
							},
							Zuruecksetzen: function() {
								$('#frmTickermessage').ValidatingForm('setValues', '');
							},
							Abbrechen: function() {
								$(this).dialog('close');
							}
						},
						open: function() {
							
						},
						close: function() {
							$('#frmTickermessage').ValidatingForm('setValues', '');
						}
					});
					
					
					
					// Button zum Anlegen einer neuen Seite
					$('#create-tickermessage').button().click(function(){
						action = 'new';
						$('#frmTickermessage').dialog('open');
					});
					
				}
			});
		}
		
		
		
		// Funktion zum Setzen der Optionenbuttons
		function setButtons() {
			// Editieren-Button
			$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function(){
				currentid = $(this).parent().parent().attr('id');
				$.getJSON('ticker.php', {action: 'getTickermessage', tickerid: currentid}, function(data){
					if (data.status != 'success') {
						alert('Die Daten der zu bearbeitenden Seite konnten nicht aufgerufen werden');
						return;
					}
					$('#frmTickermessage').ValidatingForm('setValues', data.tickermessage);
					
					action = 'edit';
					$('#frmTickermessage').dialog('open');
				});
				
			});
			
			// Löschen-Button
			$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
				var tickerid = $(this).parent().parent().attr('id');
				$.getJSON('ticker.php?action=deleteTickermessage', {tickerid: tickerid},function(data){
					if(data.status != 'success')
						alert('Die Seite konnte nicht gelöscht werden.');
					else {
						$('#tblTicker').qTable('remove', tickerid);
					}
				});
			});
		}
	});
}