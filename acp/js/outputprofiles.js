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

function markupOutputprofiles() {
	$('#outputprofiles').ready(function(){
		var allFields = $([]).add('#outputprofileName').add('#outputprofileTheme').add('#outputprofileOutputelements').add('#outputprofileUsergroups');
		var lastEdit;
		var trNode;
		var usergroups = null;
		var themes = null;
		var currentid;
		var action = 'new';		// new oder edit
		var item;
		var placeholder;
		var entireWidth = false;
		var headlineButton;
		var numHeadlines;
		var originalRowNum;
		var originalHeadlineConstellation = new Array();
		
		// Autoladen der Ausgabeprofiltabelle beim Öffnen der Seite
		if($('#outputprofilesTable').html()=='') {
			$('#outputprofiles').css('visibility', 'hidden');
			$('#loading').css('visibility', 'visible');
			$.getJSON('outputprofiles.php',{action: 'getOutputprofiles'}, function(data) {
				
				if (data.status == 'success') {
				
					usergroups = data.usergroups;
					themes = data.themes;
					var outputprofilesForqTable = new Array();
					
					$.each(data.outputprofiles, function(profileIdx, profile) {
						$.each(profile.usergroups, function(groupIdx, groupid) {
							profile.usergroups[groupIdx] = usergroups[groupid];
						});
						profile.usergroups = profile.usergroups.join(',');
						profile.theme = themes[profile.theme];
						outputprofilesForqTable.push(profile);
					});
					
					// Eine qTable mit den existierenden Benutzergruppen erstellen
					$('#outputprofilesTable').qTable({
						headlines: ['Profilname', 'Benutzer Theme', 'Zielgruppen', 'Optionen'],
						data : outputprofilesForqTable,
						rowIDs : 'profileid',
						displayRowIDColumn: false,
						tableHeight : '83%',
						insert: function(row) {
							$(row).append($('<td></td>').html('<button class="edit"></button><button class="delete"></button>'));
						},
						resize: setButtons
					});
					setTimeout("$('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('.qTablePageSelectable:eq(1)').trigger('click');  $('.qTablePageSelectable:eq(0)').trigger('click'); $('#loading').css('visibility', 'hidden'); $('#outputprofiles').css('visibility', 'visible');", 300);
					
					// Herausfinden wieviele Kopfzeilen es gibt
					numHeadlines = $(".outputprofileOutputelements li").size();
					
					// Button zum Anlegen einer neuen Seite
					$('#create-outputprofile').button().click(function(){
						action = 'new';
						$('#frmOutputprofiles').dialog('open');
					});
					
					
						
					
					// Bei Doppelklick die Kopfzeile zu den versteckten hinzufügen
					$(".outputprofileOutputelements li").live('dblclick', function(){
						$('#hidden-headlines').append($(this));
						$(this).parent().remove(this);
						realignHeadlines();
						updateHiddenHeadlines();
					});
					
					// Die Originalreihenfolge der Kopfzeilen im Dialog speichern
					$('.outputprofileOutputelements').each(function(idx) {
						originalHeadlineConstellation[idx] = new Array();
						$(this).find('li').each(function(liIdx) {
							originalHeadlineConstellation[idx][liIdx] = $(this);
						});
					});
					
					originalRowNum = $('.outputprofileOutputelements').size();
					
					
					
					// Dialog zum Anlegen einer Tickernachricht
					$('#frmOutputprofiles').dialog({
						autoOpen: false,
						height: 365,
						width: 560,
						modal: true,
						resizable: false,
						buttons: {
							'+': addHeadlineRow,
							'-': removeHeadlineRow,
							'ausgeblendet': function(){
								updateHiddenHeadlines();
								if ($('#hidden-headlines').css('visibility')=='visible') {
									$('#hidden-headlines').css('visibility', 'hidden');
									headlineButton.button({
										icons: { secondary: "ui-icon-triangle-1-n"}
									});
								}
								else {
									$('#hidden-headlines').css('visibility', 'visible');
									headlineButton.button({
										icons: { secondary: "ui-icon-triangle-1-s"}
									});
								}
							},
							Eintragen: function(){
								var profilenameVal = $('#outputprofileName').val();
								var choosedTheme = $('#outputprofileTheme').val();
								var choosedThemename = $('#outputprofileTheme option:selected:eq(0)').html();
								var usergroupids = '';
								var usergroupnames = '';
								$('#outputprofileUsergroups label.ui-state-active').each(function(){
									if (usergroupids == '')
										usergroupids = $(this).attr('for');
									else
										usergroupids += ',' + $(this).attr('for');
								});
								$('#outputprofileUsergroups label.ui-state-active span').each(function(){
									if (usergroupnames == '')
										usergroupnames = $(this).html();
									else
										usergroupnames += ',' + $(this).html();
								});
								var headlineSorting = new Array();
								$('.outputprofileOutputelements').each(function() {
									if ($(this).children().size()){
										var curIndex = headlineSorting.length;
										headlineSorting[curIndex] = new Array();
										$(this).children('li').each(function(idx){
											headlineSorting[curIndex][idx] = {
												hl: $(this).attr('id'),
												width: Math.floor(parseInt($(this).css('width')) / 420 * 100)
											};
										});
									}
								});
								headlineSortingJSON = JSON.stringify(headlineSorting);
								
								lastEdit = {profilename: profilenameVal, theme: choosedTheme, usergroups: usergroupids, columns: headlineSortingJSON, usergroupnames: usergroupnames};
								
								if (action == 'new') {
									$.post('outputprofiles.php?action=addOutputprofile', lastEdit, function(data){
										data = $.parseJSON(data);
										if (data.status == 'MISSING_DATA') {
											alert('Sie haben nicht alle Felder ausgefüllt');
										}
										else if (data.status != 'success') {
											alert('Es ist ein Fehler beim Eintragen der Daten aufgetreten');
											$(this).dialog('close');
										}
										else {
											var newData = {
												profileid: data.profileid,
												profilename: lastEdit.profilename,
												theme: themes[lastEdit.theme],
												usergroup: usergroupnames
											};
											$('#outputprofilesTable').qTable('insert', newData);
											$('#frmOutputprofiles').dialog('close');
										}
									});
								}
								else if (action == 'edit') {
									lastEdit.profileid = currentid;
									$.post('outputprofiles.php?action=editOutputprofile', lastEdit, function(data){
										data = $.parseJSON(data);
										if (data.status == 'MISSING_DATA') {
											alert('Sie haben nicht alle Felder ausgefüllt');
										}
										else if (data.status != 'success') {
											alert('Es ist ein Fehler beim Eintragen der Daten aufgetreten');
											$(this).dialog('close');
										}
										else {
											var newData = {
												profileid: lastEdit.profileid,
												profilename: lastEdit.profilename,
												theme: themes[lastEdit.theme],
												usergroup: usergroupnames
											};
											$('#outputprofilesTable').qTable('update', {index: '', value: newData});
											$('#frmOutputprofiles').dialog('close');
										}
									});
								}
							},
							Zuruecksetzen: function() {
								allFields.val('').removeClass('ui-state-error');
								$('#outputprofileUsergroups label').removeClass('ui-state-active').attr('aria-pressed', 'false');
								while ($(".outputprofileOutputelements").size() < originalRowNum) {
									addHeadlineRow();
								}
								restoreHeadlineConstellation();
							},
							Abbrechen: function() {
								$(this).dialog('close');
							}
						},
						open: function() {
							if (action == 'edit') {
								while ($('.outputprofileOutputelements').size() > profile.columns.length) {
									removeHeadlineRow();
								}
							}
						},
						close: function() {
							allFields.val('').removeClass('ui-state-error');
							$('#outputprofileUsergroups label').removeClass('ui-state-active').attr('aria-pressed', 'false');
							while ($(".outputprofileOutputelements").size() < originalRowNum) {
								addHeadlineRow();
							}
							restoreHeadlineConstellation();
						}
					});
					
					// Finden des ausgeblendet Button
					$('.ui-dialog-buttonset').children().each(function(){
						if ($(this).children('.ui-button-text').html() != 'ausgeblendet')
							return;
						$(this).button({
							icons: { secondary: "ui-icon-triangle-1-n"}
						});
						headlineButton = $(this);
					});
					
					// Forms setzen
					$('#outputprofileUsergroups').buttonset();
					realignHeadlines();
					makeSortable();
					makeResizable();
					
					
				}
			});
		}
		
		
		// Die Positionen und Eventhandler der ausgeblendeten Kopfzeilen updaten
		function updateHiddenHeadlines() {
			var numElem = $('#hidden-headlines li').size();
			var listHeight = numElem * 20;
			var leftButtom = $(headlineButton).offset();
			
			$('#hidden-headlines').css({
				backgroundColor: '',
				height: listHeight,
				width: headlineButton.outerWidth(),
				left: parseInt(leftButtom.left) - 28,
				top: parseInt(leftButtom.top) - parseInt(headlineButton.outerHeight()) - 4 - numElem * 20
			});
			
			$('#hidden-headlines li')
			.css('width', headlineButton.outerWidth())
			.resizable({disabled: true});
			
			
			
			$('#hidden-headlines li').live('dblclick', function(){
				$(this).resizable('enable');
				$('.outputprofileOutputelements:last').append($(this));
				$(this).parent().remove(this);
				if ( !$('#hidden-headlines li').size()) {
					$('#hidden-headlines').css('visibility', 'hidden');
					headlineButton.button({
						icons: { secondary: "ui-icon-triangle-1-n"}
					});
				}
				realignHeadlines();
				updateHiddenHeadlines();
			});
		}
		
		// Die Kopfzeilen sortierbar machen
		function makeSortable() {
			$(".outputprofileOutputelements").sortable({
				connectWith: ".outputprofileOutputelements",
				placeholder: "ui-state-highlight",
				scroll: false,
				cursor: 'move',
				stop: function(e, ui){
					$('.outputprofileOutputelements').each(function(){
						$(this).children().css('width', 420 / $(this).children().size() );
					});
				},
				sort: function(e, ui){
					item = ui.item;
					placeholder = ui.placeholder;
					$('.outputprofileOutputelements').each(function(){
						if ($(this).attr('id') == $(item).parent().attr('id'))
							$(this).children().css('width', 420/ ($(this).children().size() - 1) );
						else
							$(this).children().css('width', 420 / $(this).children().size() );
					});
					$(item).css('width', $(placeholder).css('width'));
				}			
			}).disableSelection();
		}
		
		// Die Kopfzeilen in der Breite veränderbar machen
		function makeResizable() {
			$(".outputprofileOutputelements li").resizable({
				minWidth: 21,
				maxWidth: 420,
				minHeight: 20,
				maxHeight: 20,
				grid: 1,
				start: function(e, ui) {
					if (!entireWidth && $(this).next().size()) {
						entireWidth = parseInt($(this).css('width')) + parseInt($(this).next().css('width'));
					}
				},
				resize: function(e, ui) {
					if (!$(this).next().size()) {
						$(this).css('width', ui.originalSize.width);
						$(this).resizable('cancel');
						return;
					}
					
					var actWidth = parseInt($(this).css('width'));
					var nextHeadline = $(this).next();
					var minWidth = parseInt($(this).resizable('option', 'minWidth'));
					
					var newWidth = entireWidth - actWidth;
					if (newWidth < minWidth) {
						$(this).css('width', entireWidth - minWidth);
						nextHeadline.css('width', minWidth);
					}
					else
						nextHeadline.css('width', newWidth);
				},
				stop: function(e, ui) {
					if ($(this).next().size()) {
						var actWidth = parseInt($(this).css('width'));
						var newWidth = entireWidth - actWidth;
						$(this).next().css('width', newWidth);
						entireWidth = false;
					}
				}
			});
		}
		
		
		// Alle Kopfzeilen wieder in den Ursprungszustand versetzen
		function restoreHeadlineConstellation() {
			$('#hidden-headlines li').each(function() {
				$(this).resizable('enable');
				$('.outputprofileOutputelements:last').append($(this));
				$(this).parent().remove(this);
				if ( !$('#hidden-headlines li').size()) {
					$('#hidden-headlines').css('visibility', 'hidden');
					headlineButton.button({
						icons: { secondary: "ui-icon-triangle-1-n"}
					});
				}
			});
			
			realignHeadlines();
			updateHiddenHeadlines();
		}
		
		// Alle Kopfzeilen in einer Reihe auf dieselbe Breite bringen
		function realignHeadlines(){
			$('.outputprofileOutputelements').each(function(){
				$(this).children().css('width', 420 / $(this).children().size() );
			});
		}
		
		
		function addHeadlineRow()
		{
			if ($(".outputprofileOutputelements").size() < numHeadlines) {
				var id = 'outputprofileOutputelements' + ($(".outputprofileOutputelements").size() + 1);
				$(".outputprofileOutputelements:last").after(
					$('<ul class="outputprofileOutputelements ui-widget-content ui-corner-all"></ul>')
					.attr('id', id)
					.attr('name', id)
				);
				
				var newHeight = parseInt($('#frmOutputprofiles').dialog('option', 'height')) + 21;
				$('#frmOutputprofiles').dialog({
					height: newHeight
				});
				makeSortable();
				updateHiddenHeadlines();
			}
		}
		
		// Entfernt die unterste Kopfzeilereihe
		function removeHeadlineRow() {
			if ($(".outputprofileOutputelements").size() > 1) {
				var needRealign = $('.outputprofileOutputelements:last li').size() > 0;
				$(".outputprofileOutputelements:last").prev().append(
					$(".outputprofileOutputelements:last").html()
				);
				$(".outputprofileOutputelements:last").remove();
				var newHeight = parseInt($('#frmOutputprofiles').dialog('option', 'height')) - 21;
				$('#frmOutputprofiles').dialog({
					height: newHeight
				});
				if (needRealign) {
					realignHeadlines();
				}
				updateHiddenHeadlines();
			}
		}
		
		// Funktion zum Setzen der Optionenbuttons
		function setButtons() {
			// Editieren-Button
			$('.edit').button({icons: {primary: 'ui-icon-pencil'}}).click(function() {
				trNode = $(this).parent().parent();
				currentid = trNode.attr('id');
				$.getJSON('outputprofiles.php', {action: 'getOutputprofile', profileid: currentid}, function(data) {
					if (data.status != 'success') {
						alert('Die Daten des zu bearbeitenden Ausgabeprofils konnten nicht aufgerufen werden');
						return;
					}
					
					profile = data.outputprofile;
					profile.columns = $.parseJSON(profile.columns);
					
					
					$('#outputprofileName').val(profile.profilename);
					$('#outputprofileTheme').val(profile.theme).children('option').each(function(){
						if ($(this).val() == profile.theme)
							$(this).attr('selected', 'selected');
					});
					var selectedUsergroups = profile.usergroups.split(',');
					$('#outputprofileUsergroups label').each(function(){
						if ($.inArray( $(this).attr('for'), selectedUsergroups) != -1)
							$(this).addClass('ui-state-active').attr('aria-pressed', 'true');
					});
					
					// Alle zur Verfügung stehenden Headlines in ein Array einordnen
					var headlines = new Array();
					$('.outputprofileOutputelements li').each(function(){
						var idx = $(this).attr('id');
						headlines[idx] = $(this);
						$(this).remove();
					});
					
					// Alle Headlines aus der JSON antwort in der entsprechenden Reihenfolge und Breite einordnen
					$.each(profile.columns, function(rowIdx, row){
						var targetUL = $('.outputprofileOutputelements').eq(rowIdx);
						$.each(row, function(columnIdx, cell){
							var width = (cell.width/100) * 420;
							targetUL.append(
								$(headlines[cell.hl]).css('width', width)
							);
							headlines[cell.hl] = '';
						});
					});
					
					// Die nicht verwendenten Headlines ausbleden
					$.each(headlines, function(hlIdx, headlineElem){
						if (headlineElem != '') {
							$('#hidden-headlines').append(headlineElem);
						}
					});
					
					makeSortable();
					makeResizable();
					updateHiddenHeadlines();
					
					
					action = 'edit';
					$('#frmOutputprofiles').dialog('open');
				});
				
			});
			
			// Löschen-Button
			$('.delete').button({icons: {primary: 'ui-icon-circle-close'}}).click(function() {
				trNode = $(this).parent().parent();
				var profileidVal = trNode.attr('id');
				$.getJSON('outputprofiles.php?action=deleteOutputprofile', {profileid: profileidVal}, function(data) {
					if(data.status != 'success')
						alert('Die Seite konnte nicht gelöscht werden.');
					else
						trNode.remove();
				});
			});
		}
		
	});
}