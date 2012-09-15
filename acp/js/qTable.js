
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



;(function($) {
	$.fn.extend({
       qTable: function(options,arg) {
            
			defaults = {
				sorting: [[0, 'ASC']], // Die Daten werden nach der Reihefolge im Array sortiert. Also nach sorting[0][0] in Richtung sorting[0][1] usw.
				searchEnabled : true,  // Soll eine Suche eingebaut werden ?
				search : search,	   // Diese Funktion soll ein Array mit allen Reihen liefern, die auf die Suche zutreffen
				searchCompare : compare, // Diese Funktion vergleicht das Suchmuster mit den Daten einer Zeile und entscheidet darüber, ob diese im Array von search zurrückgegeben wird
				tableHeight : $(this).css('height'),  // Gibt die Höhe der Tabelle an
				headlineHeight : '21px',				  // Gibt die Höhe der Kopfzeile an
				rowsHeight : '21px',					  // Gibt die Zeilenhöhe der Einträge an
				datatype : 'array', // table - Die Daten werden aus der vorhandenen Tabelle bezogen, array - Die Daten werden aus einem 2D Array bezogen
				headlines : false,  // Hier muss ein Array mit den Überschriften übergeben werden, ansonsten gibt es keine Kopfzeile
				data : false,	    // Hier werden die Daten gespeichert die verwendet werden sollen
				rowIDs : '',	    // Wenn gesetzt wird in data versucht die entsprechenden IDs zu finden. Bsp: rowIDs:'userid', erhällt Zeile x die id=data[x]['userid']
				displayRowIDColumn : false ,// Soll die Spalte mit den Zeilen IDs angezeigt werden ?
				hightlightSearch : true,
				resize: function() {},
				insert: function() {}, 
				actRowIdx : 0,
				order: false		// Reihenfolge der Daten, falls es sich um ein asso. Array handelt
			};
			
			if (options && typeof(options) == 'object') {
				options = $.extend( {}, defaults, this.data(), options );
            }

			this.each(function() {
                new $.qTable(this, options, arg );
            });
            return;
        }
    });

    $.qTable = function( elem, options, arg ) {

        if (options && typeof(options) == 'string') {
           if (options == 'reset') {
				reset(elem);
           }
           else if (options == 'remove') {
               remove( elem, arg );
           }
		   else if (options == 'insert') {
				insert( elem, arg );
		   }
		   else if (options == 'update') {
				update(elem, arg);
		   }
           return;
        }
	
		var qTable = elem;
		$(elem).data(options);
		var qTableHeightInPixel;
		var dataPerPage;
		var numPages;
		var pageOfRowIdx;
		var isSearching;
		var sortedData = jQuery.parseJSON(JSON.stringify($(qTable).data('data')));
		var searchResults;
		var sortedSearchResults;
		var lastNeedles;
		
		$(qTable).addClass('qTable');
		
		
		// Aufbauene der Tabellenstruktur
		if ($(qTable).data('datatype') == 'array') {
			$(qTable).wrap('<div class="qTableContain"></div>');
			
			$(qTable).before('<div class="qTableSearch" align="right"><input type="text" class="qTableSearchField"/></div>');
			$('.qTableSearchField').val('Suchen...');
			$('.qTableSearchField').toggleClass('qTableSearchFieldEmpty');
			$('.qTableSearchField').focusin(function() {
				if ($(this).val() == 'Suchen...') {
					$(this).val('');
					$(this).toggleClass('qTableSearchFieldEmpty');
				}
			});
			
			$('.qTableSearchField').focusout(function() {
				if ($(this).val() == '') {
					$(this).val('Suchen...');
					$(this).toggleClass('qTableSearchFieldEmpty');
				}
				
			});
			$('.qTableSearchField').keyup(function() {
				if ($(this).val() != '') {
					var needle = $(this).val();
					needle = needle.replace(/\+/g, ' ');
					lastNeedles = needle.split(' ');
					var haystack = $(qTable).data('data');//sortedData;
					var cmpfn = $(qTable).data('searchCompare');
					var results = search(lastNeedles, haystack, cmpfn, $(qTable).data('displayRowIDColumn'), $(qTable).data('rowIDs'));
					if (results.length) {
						isSearching = true;
						searchResults = results;
						sortedSearchResults = jQuery.parseJSON(JSON.stringify(results));
						if ($(qTable).data('hightlightSearch')) {
							sortedSearchResults = highlightStrings(lastNeedles, sortedSearchResults);
						}
						pageOfRowIdx = 1;
						$(qTable).data('actRowIdx', 0);
						$(window).trigger('resizeEnd');
					}
				}
				else {
					isSearching = false;
					sortedData = sortedData = jQuery.parseJSON(JSON.stringify($(qTable).data('data')));
					$(window).trigger('resizeEnd');
				}
			});
			
			$(qTable).html('')
			.append($('<thead class="qTableHead"><tr class="ui-widget-header "></tr></thead>'))
			.append($('<tbody class="qTableBody"></tbody>'))
			.css('height', $(qTable).data('tableHeight'));
			
			qTableHeightInPixel = $(qTable).height();
			$(qTable).css('height', '');
			
			// Eintragen des Tabellenkopfes
			$.each($(qTable).data('headlines'), function(idx, headline) {
				$(qTable).find('.qTableHead tr').append(
					$('<th>'+headline+'<div class="qTableHeadlineSortNot"></div></th>')
					.mouseenter(function() { $(this).toggleClass('qTableHeadlineMouseover'); })
					.mouseout(function() { $(this).toggleClass('qTableHeadlineMouseover'); })
					.mousedown(function() { $(this).toggleClass('qTableHeadlineClick'); })
					.mouseup(function() { $(this).toggleClass('qTableHeadlineClick'); })
					.click(function() {
						var direction = 'not';
						if ($(this).find('div').hasClass('qTableHeadlineSortNot')) {
							$(this).find('div').removeClass('qTableHeadlineSortNot').addClass('qTableHeadlineSortAsc');
							direction = 'asc';
						}
						else if ($(this).find('div').hasClass('qTableHeadlineSortAsc')) {
							$(this).find('div').removeClass('qTableHeadlineSortAsc').addClass('qTableHeadlineSortDesc');
							direction = 'desc';
						}
						else if ($(this).find('div').hasClass('qTableHeadlineSortDesc')) {
							$(this).find('div').removeClass('qTableHeadlineSortDesc').addClass('qTableHeadlineSortNot');
							direction = 'not';
						}
						
						$(this).siblings().find('div').attr('class', 'qTableHeadlineSortNot');
						var actHeadline = $(this).text();
						var sortIndex = '';
						$.each($(qTable).data('headlines'), function(headlineIdx, headline) {
							if (headline == actHeadline) {
								var hlCounter = 0;
								$.each(sortedData[0], function(colName) {
									if (sortIndex != '') {
										return;
									}
									if ($(qTable).data('displayRowIDColumn') == false && colName == $(qTable).data('rowIDs')) {
										return;
									}
									else if (hlCounter == headlineIdx) {
										sortIndex = colName;
									}
									else {
										hlCounter++;
									}
								});
							}
						});
						sortData(sortIndex, direction);
						$(window).trigger('resizeEnd');
					})
				);
			});
			
			// Erstellen der Seitennavigation
			$('.qTableContain').append('<div class="qTablePages"></div>');
			
			// Befüllen der Tabelle und für relative Anpassung an der Höhe sorgen
			$(window).resize(function() {
				if(this.resizeTO) clearTimeout(this.resizeTO);
				this.resizeTO = setTimeout(function() {
					$(this).trigger('resizeEnd');
				}, 100);
			});
			
			$(window).bind('resizeEnd', function() {
				qTableHeightInPixel = getTableHeightInPixel();
				
				if (isSearching) {
					fillTable($(qTable).data('actRowIdx'), sortedSearchResults);
				}
				else {
					fillTable($(qTable).data('actRowIdx'), sortedData);
				}
				fillPageSelection();
				options.resize();
			});
			$(window).trigger('resizeEnd');
			$(window).trigger('resizeEnd');
			
		}
		
		// Befüllt die Tabelle mit den Daten beginnend bei startIdx
		function fillTable(startIdx, data) 
		{
			var rowHeight = $(qTable).find('tr').height();
			$(qTable).find('.qTableBody').html('');
			
			var idx;
			for (idx = startIdx; ($(qTable).parent().height() + rowHeight + 10 <= qTableHeightInPixel && idx < data.length) || idx==startIdx; idx++) {
				$(qTable).find('.qTableBody').append($('<tr></tr>'));
				
				if ($(qTable).data('rowIDs') != '') {
					$(qTable).find('.qTableBody tr:last').attr('id', data[idx][$(qTable).data('rowIDs')]);
				}
				else {
					$(qTable).find('.qTableBody tr:last').attr('id', idx);
				}
				
				if ($(qTable).data('order')) {
					$.each($(qTable).data('order'), function(colIdx, idxKey) {
						if ($(qTable).data('rowIDs') == colIdx && $(qTable).data('displayRowIDColumn') == false && $(qTable).data('rowIDs') != '') {
							return;
						}
						else {
							$(qTable).find('.qTableBody tr:last').append($('<td></td>').html(data[idx][idxKey]));
						}
					});
				}
				else {
					$.each(data[idx], function(colIdx, cell) {
						if ($(qTable).data('rowIDs') == colIdx && $(qTable).data('displayRowIDColumn') == false && $(qTable).data('rowIDs') != '') {
							return;
						}
						else {
							$(qTable).find('.qTableBody tr:last').append($('<td></td>').html(cell));
						}
					});
				}
				
				options.insert($(qTable).find('.qTableBody tr:last'));
			}
			
			if (dataPerPage != 0) {
				pageOfRowIdx = Math.floor($(qTable).data('actRowIdx')/dataPerPage)+1;
			}
			if (startIdx == 0 || pageOfRowIdx != numPages) {
				dataPerPage = idx-startIdx;
			}
			
			$('.qTableBody tr').unbind('mouseover').mouseover(function() {
				$(this).toggleClass('mouseover');
			});
			$('.qTableBody tr').unbind('mouseout').mouseout(function() {
				$(this).toggleClass('mouseover');
			});
			$('.qTableBody tr:even').addClass('even');
		}
		
		
		// Seitenzahlen einfügen
		function fillPageSelection() 
		{
			if (isSearching) {
				numPages = Math.ceil(sortedSearchResults.length/dataPerPage);
			}
			else {
				numPages = Math.ceil(sortedData.length/dataPerPage);
			}
			pageOfRowIdx = Math.floor($(qTable).data('actRowIdx')/dataPerPage)+1;
			
			$('.qTablePages').html('Seite: ');
			
			// Es sind bis zu 10 Seiten vorhanden
			if (numPages <= 10) {
				for (var page = 1; page <= numPages; page++) {
					$('.qTablePages').append(
						$('<input type="radio" id="'+$(qTable).attr('id')+'_page_'+page+'" name="radio"/><label for="'+$(qTable).attr('id')+'_page_'+page+'">'+page+'</label>')
					);
					if (page == pageOfRowIdx) {
						$('.qTablePages input:last').attr('checked', 'checked');
					}
					else {
						$('.qTablePages label:last').addClass('qTablePageSelectable');
					}
				}
			}
			// Es sind mehr vorhanden
			else {
				for (var pageIdx = 1; pageIdx <= 10; pageIdx++) {
					var actPage;
					if (pageIdx <= 2) {
						actPage = pageIdx;
					}
					else if (9 <= pageIdx) {
						actPage = numPages - (10-pageIdx);
					}
					else {
						if (pageOfRowIdx <= 5) {
							if (pageIdx < 8) {
								actPage = pageIdx;
							}
							else if (pageIdx == 8) {
								actPage = '..';
							}
						}
						else if ( (numPages-5) <= pageOfRowIdx ) {
							if (3 < pageIdx) {
								actPage = numPages - (10-pageIdx);
							}
							else if (pageIdx == 3) {
								actPage = '..';
							}
						}
						else {
							if (pageIdx == 3 || pageIdx == 8) {
								actPage = '..';
							}
							else {
								actPage = pageOfRowIdx + (pageIdx-5);
							}
						}
					}
					
					if (actPage != '..') {
						$('.qTablePages').append($('<input type="radio" id="'+$(qTable).attr('id')+'_page_'+actPage+'" name="radio"/><label for="'+$(qTable).attr('id')+'_page_'+actPage+'">'+actPage+'</label>'));
						if (actPage == pageOfRowIdx) { 
							$('.qTablePages input:last').attr('checked', 'checked');
						}
						else { 
							$('.qTablePages label:last').addClass('qTablePageSelectable');
						}
					}
					else {
						$('.qTablePages').append($('<input type="radio" id="'+$(qTable).attr('id')+'_page_point'+pageIdx+'" name="radio"/><label for="'+$(qTable).attr('id')+'_page_point'+pageIdx+'">..</label>'));
					}
				}
			}
			
			$('.qTablePages').buttonset();
			$('.qTablePages').css('width', $(qTable).css('width'));
			$('.qTablePageSelectable').unbind('click').click(function() {
				var actPage =  Math.floor($(qTable).data('actRowIdx')/dataPerPage)+1;
				$(qTable).data('actRowIdx', dataPerPage*(parseInt($(this).find('span').html())-1));
				$(window).trigger('resizeEnd');
			});
		}
		
		
		// Tabellenhöhe in Pixeln ermitteln
		function getTableHeightInPixel() 
		{
			$('.qTableContain').append($('<div class="shadowContain"></div>'));
			$('.shadowContain').css({height: $(qTable).data('tableHeight'), display: 'none'});
			var tableHeightInPixel = $('.shadowContain').height();
			$('.shadowContain').remove();
			return tableHeightInPixel;
		}
		
		// Die aktuell verwendeten Daten nach der Spalte idx in Richtung direction sortieren
		function sortData(idx, direction) 
		{
			if (direction == 'not') {
				if (isSearching) {
					sortedSearchResults = jQuery.parseJSON(JSON.stringify(searchResults));
					if ($(qTable).data('hightlightSearch')) {
						sortedSearchResults = highlightStrings(lastNeedles, sortedSearchResults);
					}
				}
				else {
					sortedData = jQuery.parseJSON(JSON.stringify($(qTable).data('data')));
				}
			}
			else if (direction == 'asc') {
				if (isSearching) {
					sortedSearchResults = jQuery.parseJSON(JSON.stringify(searchResults));
					sortedSearchResults.sort(function(a, b) { a = a[idx]+''; b = b[idx]+''; return a.localeCompare(b); });
					if ($(qTable).data('hightlightSearch')) {
						sortedSearchResults = highlightStrings(lastNeedles, sortedSearchResults);
					}
				}
				else {
					sortedData.sort(function(a, b) { a = a[idx]+''; b = b[idx]+''; return a.localeCompare(b); });
				}
			}
			else if (direction == 'desc') {
				if (isSearching) {
					sortedSearchResults = jQuery.parseJSON(JSON.stringify(searchResults));
					sortedSearchResults.sort(function(a, b) { a = a[idx]+''; b = b[idx]+''; return -1*(a.localeCompare(b)); });
					if ($(qTable).data('hightlightSearch')) {
						sortedSearchResults = highlightStrings(lastNeedles, sortedSearchResults);
					}
				}
				else {
					sortedData.sort(function(a, b) { a = a[idx]+''; b = b[idx]+''; return -1*(a.localeCompare(b)); });
				}
			}
		}
		
		
		function highlightStrings(needles, data) 
		{
			$.each(data, function(rowIdx, row) {
				$.each(row, function(colName, col) {
					var colStr = col+'';
					$.each(needles, function(needleIdx, needle) {
						if (needle != '') {
							var matches = colStr.match(RegExp(needle, 'i'));
							if (matches) {
								$.each(matches, function(matchIdx, match) {
									row[colName] = colStr.replace(RegExp(match), '<font class="qTableSearchHighlight'+(needleIdx%5)+'">'+match+'</font>');
								});
							}
						}
					});
				});
			});
			return data;
		}
		
		///////////////////////////
		// Methoden der qTable   //
		///////////////////////////
		
        function reset(qTable) 
		{
			$(qTable).data('actRowIdx', 0);
			$(window).trigger('resizeEnd');
        }
		

        function remove(qTable, elemIdx) 
		{
			var oldData = $(qTable).data('data');
			var arrIdx = false;
			
			if ($(qTable).data('rowIDs') != '') {
				$.each(oldData, function(idx, row) {
					if (row[$(qTable).data('rowIDs')] == elemIdx) {
						arrIdx = idx;
					}
				});
			}
			else {
				arrIdx = elemIdx;
			}
			if (arrIdx) {
				oldData.splice(arrIdx, 1);
				$(qTable).data('data', oldData);
				refreshTable(qTable);
			}
        }
		
		
		function insert(qTable, newData) 
		{
			var oldData = $(qTable).data('data');
			if (newData.constructor == Array) {
				oldData = oldData.concat(newData);
			}
			else {
				oldData.push(newData);
			}
			$(qTable).data('data', oldData);
			refreshTable(qTable);
		}
		
		
		function update(qTable, updatedData) 
		{
			var oldData = $(qTable).data('data');
			
			if ($(qTable).data('rowIDs') != '') {
				var key = $(qTable).data('rowIDs');
				$.each(oldData, function(idx, row) {
					if (row[key] == updatedData.value[key]) {
						oldData[idx] = updatedData.value;
					}
				});
			}
			else {
				var idx = updatedData.index;
				var value = updatedData.value;
				oldData[idx] = value;
			}
			
			$(qTable).data('data', oldData);
			refreshTable(qTable);
		}
		
		
		function refreshTable(qTable) 
		{
			$(qTable).parent().find('.qTableSearchField').trigger('keyup');
			$(qTable).find('.qTableHead th:first div').attr('class', 'qTableHeadlineSortDesc');
			$(qTable).find('.qTableHead th:first').trigger('click');
		}

    };
	
	
	
})(jQuery);

// Funktion für das Durchsuchen der Daten
function search(needles, haystack, cmpfn, shouldDisplayRowIDColumn, rowIDs) 
{
	var foundArray = new Array();
	$.each(haystack, function(rowIdx, row) {
		var foundInRow = false;
		var newRow = jQuery.extend({}, row);
		$.each(newRow, function(colName, col) {
			if (shouldDisplayRowIDColumn == false && colName == rowIDs) {
				return;
			}
			var colStr = col+'';
			$.each(needles, function(needleIdx, needle) {
				if (needle != '' && compare(needle.toLowerCase(), colStr.toLowerCase()) == true) {
					foundInRow = true;
				}
			});
		});
		if (foundInRow) {
			foundArray.push(newRow);
		}
	});
	return foundArray;
}


function compare(pattern, str) 
{
	pattern = new RegExp(pattern);
	return pattern.test(str);
}