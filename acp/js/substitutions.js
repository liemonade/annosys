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

function markupSubstitutions() 
{
	var action = 'new';
	var currenthash = '';
	var lastEdit = {};
	
	$('#substitutions').ready(function() {
		
		if ($('#substitutionsTable').html() == '') 
		{
			$('#substitutions').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			
			$.getJSON('substitutions.php', {action: 'getSubstitutions'}, function(data) {
				
				if (data.status != 'success') {
					alert ('Die Vertretungsdaten konnten nicht abgerufen werden.');
					return;
				}
				else {
					$('#substitutionsTable').qTable({
						headlines: ['Datum', 'Stunde', 'Jahrgang', 'Klassen', 'Fach', 'Lehrer', 'Art', 'Raum', 'Vertr. Lehrer', 'Verlegung', 'Anmerkung', 'Erstellt durch', 'Optionen'],
						data : data.substitutions,
						displayRowIDColumn: true,
						tableHeight : '83%',
						insert: function(row) {
							var rowText = '';
							$(row).find('td').each(function(idx, content){ rowText += $(content).html(); });
							$(row).data('hash', SHA1(rowText));
							$(row).append($('<td></td>').html('<button class="delete"></button>'));
							$(row).find('td:eq(0)').html(toGerDate($(row).find('td:eq(0)').html()));
						},
						resize: function() {
							setButtons();
						}
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#substitutions').css('visibility', 'visible');", 300);
				}
				
			});
		}
		
		$('#substitutionsDate').datepicker({dateFormat: 'dd.mm.yy'});
		$('#substitutionsHour, #substitutionsGrade').buttonset();
		
		$("#frmSubstitution").dialog({
			autoOpen: false,
			height: 430,
			width: 550,
			modal: true,
			buttons: {
				Eintragen: function(){
					var dateVal = toUnixtime($('#substitutionsDate').val());
					var hourVal = '';
					$('#substitutionsHour label.ui-state-active span').each(function(){ hourVal = $(this).html(); });
					var gradeVal = '';
					$('#substitutionsGrade label.ui-state-active span').each(function(){ gradeVal = $(this).html(); });
					var classesVal = $('#substitutionsClasses').val();
					var subjectVal = $('#substitutionsSubject').val();
					var teacherVal = $('#substitutionsTeacher').val();
					var statusVal = $('#substitutionsStatus').val();
					var roomVal = $('#substitutionsRoom').val();
					var supplyVal = $('#substitutionsSupply').val();
					var postponementVal = $('#substitutionsPostponement').val();
					var noticeVal = $('#substitutionsNotice').val();
					
					lastEdit = {
									date: dateVal, 
									hour: hourVal, 
									grade: gradeVal,
									classes: classesVal,
									subject: subjectVal,
									teacher: teacherVal,
									status: statusVal,
									room: roomVal,
									supply: supplyVal,
									postponement: postponementVal,
									notice: noticeVal
								};
					
					
					if (action == 'new') {
						$.post('substitutions.php?action=addSubstitution', lastEdit, function(data){
							data = $.parseJSON(data);
							if(data.status != 'success'){
								alert('Es gab einen Fehler beim Einfügen der Vertretungsregelung.');
								return;
							}
							
							var newData = {
									date: lastEdit.date, 
									hour: lastEdit.hour, 
									grade: lastEdit.grade,
									classes: lastEdit.classes,
									subject: lastEdit.subject,
									teacher: lastEdit.teacher,
									status: lastEdit.status,
									room: lastEdit.room,
									supply: lastEdit.supply,
									postponement: lastEdit.postponement,
									notice: lastEdit.notice,
									proramm: data.programm
							};
							$('#settingsTable').qTable('insert', newData);
						});
					}
					
					
					$(this).dialog('close');
				},
				Zuruecksetzen: function() {
					$('#substitutionsDate, #substitutionsClasses, #substitutionsSubject, #substitutionsTeacher, #substitutionsStatus, #substitutionsRoom, #substitutionsSupply, #substitutionsPostponement, #substitutionsNotice').val('');
					$('#substitutionsHour label, #substitutionsGrade label').removeClass('ui-state-active').attr('aria-pressed', 'false');
				},
				Abbrechen: function() {
					$(this).dialog('close');
				}
			},
			open: function() {
				if (action == 'new') {
					$('#frmVariable').dialog( "option", "title", 'Neue Vertretungsregelung hinzuf&uuml;gen' );
				}
				else if (action == 'edit') {
					$('#frmSubstitutions').dialog( "option", "title", 'Vertretungsregelung editieren' );
				}
			},
			close: function() {
				$('#substitutionsDate, #substitutionsHour, #substitutionsGrade, #substitutionsClasses, #substitutionsSubject, #substitutionsTeacher, #substitutionsStatus, #substitutionsRoom, #substitutionsSupply, #substitutionsPostponement, #substitutionsNotice').val('');
				$('#substitutionsHour label, #substitutionsGrade label').removeClass('ui-state-active').attr('aria-pressed', 'false');
			}
		});
		
		
		// Neue Variable-Button
		$('#create-substitution').button().click(function(){
			action = 'new';
			$('#frmSubstitution').dialog('open');
		});
		
	});
	
	
	function setButtons()
	{
		// Löschen-Button
		$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function(){
			var hash = $(this).parent().parent().data('hash');
			var dataIdx = $(this).parent().parent().attr('id');
			$.getJSON('substitutions.php', {action: 'deleteSubstitution', hash: hash}, function(data) {
				if (data.status == 'success') {
					$('#substitutionsTable').qTable('remove', dataIdx);
				}
				else {
					alert('Die Vertretungsregelung konnte nicht gel&ouml;scht werden.');
				}
			});
		});
	}
}