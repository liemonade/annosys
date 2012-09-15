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

function markupSettings() 
{
	var action = 'new';
	var currentvariable = '';
	var lastEdit = {};
	
	$('#settings').ready(function() {
		
		if ($('#settingsTable').html() == '') 
		{
			$('#settings').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			
			$.getJSON('settings.php', {action: 'getVariables'}, function(data) {
				
				if (data.status != 'success') {
					alert ('Die Variablen konnten nicht abgerufen werden.');
					return;
				}
				else {
					$('#settingsTable').qTable({
						headlines: ['Variablenname', 'Wert', 'Optionen'],
						data : data.variables,
						rowIDs : 'name',
						displayRowIDColumn: true,
						tableHeight : '83%',
						insert: function(row) {
							$(row).append($('<td></td>').html('<button class="edit"></button><button class="delete"></button>'));
						},
						resize: function() {
							setButtons();
						}
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#settings').css('visibility', 'visible');", 300);
				}
				
			});
		}
		
		$("#frmVariable").dialog({
			autoOpen: false,
			height: 430,
			width: 550,
			modal: true,
			buttons: {
				Eintragen: function(){
					var nameVal = $('#variablesName').val();
					var valueVal = $('#variablesValue').val();
					var descriptionVal = $('#variablesDescription').val();
					
					lastEdit = {name: nameVal, value: valueVal, description: descriptionVal};
					
					if (action == 'new') {
						$.post('settings.php?action=addVariable', lastEdit, function(data){
							data = $.parseJSON(data);
							if(data.status != 'success'){
								alert('Es gab einen Fehler beim Einfügen der Variable.');
								return;
							}
							
							var newData = {
								name: lastEdit.name,
								value: lastEdit.value
							};
							$('#settingsTable').qTable('insert', newData);
						});
					}
					else if (action == 'edit') {
						$.post('settings.php?action=editVariable', lastEdit, function(data){
							data = $.parseJSON(data);
							if(data.status != 'success'){
								alert('Es gab einen Fehler beim Bearbeiten der Variable.');
								return;
							}
							
							var newData = {
								name: lastEdit.name,
								value: lastEdit.value
							};
							$('#settingsTable').qTable('update', {index:'', value: newData});
						});
					}
					$(this).dialog('close');
				},
				Zuruecksetzen: function() {
					$('#variablesName, #variablesValue, #variablesDescription').val('');
				},
				Abbrechen: function() {
					$(this).dialog('close');
				}
			},
			open: function() {
				if (action == 'new') {
					$('#frmVariable').dialog( "option", "title", 'Neue Variable hinzuf&uuml;gen' );
					$('#variablesName').attr('disabled', '');
				}
				else if (action == 'edit') {
					$('#frmVariable').dialog( "option", "title", 'Variable editieren' );
					$('#variablesName').attr('disabled', 'disable');
				}
			},
			close: function() {
				$('#variablesName, #variablesValue, #variablesDescription').val('');
			}
		});
		
		
		// Neue Variable-Button
		$('#create-variable').button().click(function(){
			action = 'new';
			$('#frmVariable').dialog('open');
		});
		
	});
	
	
	function setButtons()
	{
		// Editieren-Button
		$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function(){
			var varname = $(this).parent().parent().attr('id');
			currentvariable = varname;
			$.getJSON('settings.php', {action: 'getVariable', name: varname}, function(data) {
				if (data.status == 'success') {
					$('#variablesName').val(data.variable.name);
					$('#variablesValue').val(data.variable.value);
					$('#variablesDescription').val(data.variable.description);
					action ='edit';
					$('#frmVariable').dialog('open');
				}
			});
		});
		
		// Löschen-Button
		$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
			var varname = $(this).parent().parent().attr('id');
			$.getJSON('settings.php', {action: 'deleteVariable', name: varname}, function(data) {
				if (data.status == 'success') {
					$('#settingsTable').qTable('remove', varname);
				}
				else {
					alert('Die Variable konnte nicht gel&ouml;scht werden.');
				}
			});
		});
	}
};