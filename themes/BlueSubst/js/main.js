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

	var headlines;
	var extrapages;
	var tickermessages;
	var substitutions;
	var lessonends;
	var lessonstarts;
	var lastupdate = 0;
	var servertimeDifference;
	var updateTimeout = 1*10*1000;		// Zeit bis zur nächsten Updateanfrage
	
	// Vars für den Durchlauf
	var actDayIdx = 0;		// Aktueller Tag
	var actGradeIdx = 0;	// Aktueller Startjahrgang / Nur für targetgroup == 'students'
	var actIdx = 0;			// aktuelle Index. Für Vertretung oder Extraseiten
	var action = 'subst';	// subst oder extrapages
	var increment = 5;
	var presentationtimer = false;

$(document).ready(function(){
	loadEverything();
	$('#ticker').liScroll();
});

function loadEverything() {
	$.ajax({
		url: 'request.php?action=getEverything&targetgroup='+targetgroup,
		dataType: 'json',
		success: function(data) {
			if (data.status != 'success') {
				setTimeout("loadEverything()", 5000);
			}
			else {
				
				lastupdate = time();
				headlines = data.headlines;
				extrapages = data.extrapages;
				tickermessages = data.tickermessages;
				substitutions = data.substitutions;
				
				// Durchlauf resetten
				actDayIdx = 0;
				actGradeIdx = 0;
				actIdx = 0;
				if (substitutions.length) {
					action = 'subst';
				}
				else {
					action = 'extrapages';
				}
				
				// Herausfinden um wieviel die Lokalzeit sich von der Serverzeit unterscheidet
				servertimeDifference = time() - parseInt(data.servertime);
				
				// Start- und Endzeitpunkte der Stunden
				lessonends = data.lessonends;
				lessonstarts = data.lessonstarts;
				
				clock(lessonstarts, lessonends);
				
				autoupdate();
				if (presentationtimer == false) {
					showPresentation();
				}

				var tickerstring = concatenateTickermessages(tickermessages, ' +++ ');
				$('#ticker li span').html(tickerstring);
				
				$('#lastUpdate').html(data.lastupdate);
			}
		},
		error: function() {
			setTimeout("loadEverything()", 5000);
		}
	});
}

// Sorgt für das automatische Updaten der Daten
function autoupdate() {
	$.ajax({
		url: 'request.php?action=getUpdatestatus',
		dataType: 'json',
		data: {lastupdate: lastupdate - servertimeDifference},
		success: function(data){
			if (data.updatestatus == 'UPDATE_NEEDED') {
				loadEverything();
			}
			else {
				if (lastupdate+5*60 < time()) {
					loadEverything()
				}
				else {
					setTimeout("autoupdate()", updateTimeout);
				}
			}
		},
		error: function(){
			setTimeout("autoupdate()", 3000);
		}
	});
}


// Zeigt nacheinander alle Vertretungsplanseiten
function showPresentation() {
	if (!substitutions.length && !extrapages.length) {
		// Es sind gar keine anzeigbaren Daten vorhanden
		return;
	}
	// Vertretungsdaten werden angezeigt
	if (action == 'subst') {
	
		// Es gibt keine Vertretungsdaten
		if (!substitutions.length) {
			action = 'extrapages';
			presentationtimer = setTimeout("showPresentation()", 3000);
			return;
		}
		
		if (targetgroup == 'teachers') {
		
			// Der anzuzeigende Tag existiert nicht
			if (actIdx >= substitutions[actDayIdx].length) {
				action = 'extrapages';
			}
			else {
				var actTimeout = 3000;
				$('#mainFrame #mainFrameContent').animate({opacity:'0.0'}, 250, function() {
					// Daten in die Tabelle einfügen
					putSubstitutions(actDayIdx, actIdx, increment);
					$('#mainFrame #mainFrameContent').animate({opacity:'1.0'}, 250, function() {
						// Timeout und neuen Index setzen
						actTimeout += parseInt($('.entry').size())*1000;
						actIdx += increment;
						
						// Der neue Index ist außerhalb der Arraygrenzen des Tages
						if (actIdx >= substitutions[actDayIdx].length) {
							
							// Nächste Tag und Index wieder auf 0
							actDayIdx++;
							actIdx = 0;
							
							// Der Tag existiert nicht
							if (actDayIdx >= substitutions.length) {
								
								// Alles wieder auf Anfang und falls vorhanden als nächsten Extraseiten anzeigen
								actDayIdx = 0;
								actIdx = 0;
								if (extrapages.length) {
									action = 'extrapages';
								}
							}
						}
						presentationtimer = setTimeout("showPresentation()", actTimeout);
					});
				});
			}
		
		}
		else if (targetgroup == 'students') {
		
			if (actGradeIdx > substitutions[actDayIdx].length) {
				action = 'extrapages';
			}
			else {
				var actTimeout = 3000;
				if ( actIdx < substitutions[actDayIdx][actGradeIdx].length ) {
					$('#mainFrame #mainFrameContent').animate({opacity:'0.0'}, 250, function() {
						putSubstitutions(actDayIdx, actIdx, increment, actGradeIdx);
						$('#mainFrame #mainFrameContent').animate({opacity:'1.0'}, 250, function() {
							actTimeout += parseInt($('.entry').size())*1000;
							actIdx += increment;
							if (actIdx >= substitutions[actDayIdx][actGradeIdx].length) {
								actGradeIdx++;
								actIdx = 0;
								if (actGradeIdx >= substitutions[actDayIdx].length) {
									actGradeIdx = 0;
									actDayIdx++;
									if (actDayIdx >= substitutions.length) {
										actDayIdx = 0;
										if (extrapages.length)
											action = 'extrapages';
									}
								}
							}
							presentationtimer = setTimeout("showPresentation()", actTimeout);
						});
					});
				}
			}
			
		}
	}
	// Extraseiten werden angezeigt
	else if (action == 'extrapages') {
		var actTimeout = 3000;
		if (extrapages.length) {
			actTimeout = extrapages[actIdx]['duration'] * 1000;
			$('#mainFrameContent').html(extrapages[actIdx]['code']);
			
			// Falls nichts außer der einen Seite vorhanden ist, kann man sich das Flackern auch sparen
			if (extrapages.length == 1 && !substitutions.length) {
				return;
			}
			
			actIdx++;
			
			// Falls der neue Extraseitenindex nicht existiert, den Index wieder auf 0 stellen und wenn vorhanden nun Vertretungspläne anzeigen
			if (actIdx >= extrapages.length) {
				actIdx = 0;
				if (substitutions.length) {
					action = 'subst';
				}
			}
		}
		presentationtimer = setTimeout("showPresentation()", actTimeout);
	}
}


// Setzt Vertretungsdaten in den Mainbereich der Seite
function putSubstitutions(day, startidx, maxnum, grade) {
	
	// Hauptdiv aufräumen
	$('#mainFrameContent').html('');
	$('#mainFrameContent').append($('<div id="mainContent"></div>'));
	
	// Die Informationszeile über den Vertretungsdaten einfügen
	var timeToday = toUnixtime(getTimestring());
	if (targetgroup == 'teachers') {
		var timeSubstPage = substitutions[day][startidx].date;
	}
	else if (targetgroup == 'students') {
		var timeSubstPage = substitutions[day][grade][startidx].date;
	}
	
	// Fertiger String für die Informationszeile
	if (timeToday == timeSubstPage) {
		var dayHTML = 'Heute: '+getWeekday(timeSubstPage)+'. '+toGerDate(timeSubstPage);
	}
	else {
		var dayHTML = 'Vorschau: '+getWeekday(timeSubstPage)+'. '+toGerDate(timeSubstPage);
	}
	
	var maxPages = undefined;
	if (targetgroup == 'teachers') {
		maxPages = Math.ceil(substitutions[day].length / maxnum);
	}
	else if (targetgroup == 'students') {
		maxPages = Math.ceil(substitutions[day][grade].length / maxnum);
	}
	
	var actPage = startidx / maxnum + 1;
	var siteHTML = 'Seite ('+actPage+'|'+maxPages+')';
	
	if (targetgroup == 'teachers') {
		var startTeacher = substitutions[day][startidx];
		var endTeacher = endTeacher = ( (substitutions[day].length-1 < startidx+increment-1) ? substitutions[day][substitutions[day].length-1] : substitutions[day][startidx+increment-1] );
		
		if (startTeacher.supply) {
			startTeacher = startTeacher.supply;
		}
		else {
			startTeacher = startTeacher.teacher;
		}
		if (endTeacher.supply) {
			endTeacher = endTeacher.supply;
		}
		else {
			endTeacher = endTeacher.teacher;
		}
		
		
		var gradeHTML = startTeacher+' - '+endTeacher;
	}
	else if (targetgroup == 'students') {
		var gradeHTML = 'Jahrgang '+substitutions[day][grade][startidx].grade;
	}
	
	
	// Die ermittelten Daten in die Informationszeile einfügen
	$('#mainContent').append(
		$('<div class="informations"></div>')
		.append(
			$('<p class="left"></p>').html( dayHTML )
		)
		.append(
			$('<p class="middle"></p>').html( siteHTML )
		)
		.append(
			$('<p class="right"></p>').html( gradeHTML )
		)
	);
	
	
	
	
	// Die Kopfzeile der Tabelle erzeugen
	$('#mainContent').append('<table class="head"></table>');
	$.each(headlines, function(rowIdx, row){
		$('.head').append(
			$('<tr></tr>')
		);
		$.each(row, function(colIdx, column){
			$('.head tr').eq(rowIdx).append(
				$('<td></td>').attr('class', 'cell'+column.hl.id).html(column.hl.alias)
			);
		});
	});
	
	// Die eigentlichen Daten eintragen
	$('#mainContent').append('<div id="substTable"></div>');
	
	if (targetgroup == 'teachers') {
		var numSubstitutions = substitutions[day].length;
	}
	else if (targetgroup == 'students') {
		var numSubstitutions = substitutions[day][grade].length;
	}
	
	for (var i = startidx; i < startidx+maxnum && i != numSubstitutions; i++) {
		$('#substTable').append('<table class="entry"></table>');
		$.each(headlines, function(rowIdx, row){
			$('.entry:last').append(
				$('<tr></tr>')
			);
			$.each(row, function(colIdx, column) {
				// Die atkuellen Vertretungsdaten
				if (targetgroup == 'teachers') {
					var substdata = substitutions[day][i];
				}
				else if (targetgroup == 'students') {
					var substdata = substitutions[day][grade][i];
				}
				
				var template = column.hl.template;
				
				if (template == '__supply__ (__teacher__)' && substdata['supply'] == '') {
					template = substdata['teacher'];
				}
				else if (template == '__supply__ (__teacher__)' && substdata['supply'] == substdata['teacher']) {
					template = substdata['teacher'];
				}
				else if (template == '__supply__ (__teacher__)' && substdata['supply'] != '' && substdata['teacher'] == '') {
					template = substdata['supply'];
				}
				else {
					var matches = template.match(/__\w*__/g);
					$.each(matches, function(){
						template = template.replace(this, substdata[this.replace(/__/g, '')]);
					});
				}
				if (template == '') {
					template = '&nbsp;';
				}
				$('.entry:last tr').eq(rowIdx).append(
					$('<td></td>').attr('class', 'cell'+column.hl.id).html(template)
				);
			});
		});
	}
	
	
	// Setzen einiger CSS Eigenschaften
	var classRowOneColOne = $('.head tr:eq(0) td:eq(0)').attr('class');
	var classRowOneColTwo = $('.head tr:eq(0) td:eq(1)').attr('class');
	var classRowOneColThree = $('.head tr:eq(0) td:eq(2)').attr('class');
	var classRowOneColFour = $('.head tr:eq(0) td:eq(3)').attr('class');
	
	$('.'+classRowOneColOne).css({width: headlines[0][0].width+'%', borderBottom: '2px dashed #a9a9a9'});
	$('.'+classRowOneColTwo).css({width: headlines[0][1].width+'%', borderBottom: '2px dashed #a9a9a9'});
	$('.'+classRowOneColThree).css({width: headlines[0][2].width+'%', borderBottom: '2px dashed #a9a9a9'});
	$('.'+classRowOneColFour).css({width: headlines[0][3].width+'%', borderBottom: '2px dashed #a9a9a9'});
	
	$('td').each(function(){
		if($(this).text() == 'Entfall')
			$(this).parent().css({color: '#ff0c0c'});
		if($(this).text() == 'Vertretung')
			$(this).parent().css({color: '#003aa5'});
		if($(this).text() == 'Raum-Vtr.')
			$(this).parent().css({color: '#1ba305'});
		if($(this).text() == 'Statt-Vertretung')
			$(this).parent().css({color: '#a517e0'});
	});
	
	$('table:gt(1):even').each(function(){
		$(this).css('background-color', '#e0e0e0');
	});
	
	$('.entry:odd').css({backgroundColor: '#e0e0e0'});
	
	
	// Die Größe der Schrift so anpassen, dass der Platz ausgenutzt wird
	$('body').css('fontSize', '1%');

	var originalTableEntryHeight = $('.entry:eq(0)').height();
	var bodyFontSizePercent = 80;
	
	var currentMaxHeight = 0;
	
	
	
	while (originalTableEntryHeight >= currentMaxHeight) {
		bodyFontSizePercent *= 1.05;
		$('body').css('fontSize', bodyFontSizePercent+'%');
		$('.entry').each(function() {
			var height = $(this).height();
			if (height > currentMaxHeight) {
				currentMaxHeight = height;
			}
		});
	}
	
	$('body').css('fontSize', (0.9*bodyFontSizePercent)+'%');
}



// Erzeugen des Tickerstrings
function concatenateTickermessages(tickermessages, glue) {
	if (tickermessages.length == 0)
		return '';
	var tickerstring = '';
	$.each(tickermessages, function(idx, message){
		tickerstring += glue + message.message;
		if ( (idx+1) == tickermessages.length )		// Letzte Tickernachricht
			tickerstring += glue;
	});
	return tickerstring;
}


// Liefert die aktuelle UnixZeit
function time() {
	return Date.parse(Date()) / 1000;
}

// Funktion zum liefern des aktuellen Datums in dd.mm.yyyy
function getTimestring() {
	var timestring = '';
	var time = new Date();
	var day = String(time.getDate());
		day = ((day.length==1)?'0'+day:day);
	var month = String(time.getMonth()+1);
		month = ((month.length==1)?'0'+month:month);
	var year = String(time.getFullYear());
	timestring = day+'.'+month+'.'+year;
	return timestring;
}

function getWeekday(unixtime){
	var weekdays = new Array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
	var date = new Date();
	date.setTime(unixtime*1000);
	day = date.getDay();
	return weekdays[day];
}


// Funktion zum Konvertieren von Unixzeit zum Datumsformat dd.mm.yyyy
function toGerDate(unixtime){
	var date = new Date();
	date.setTime(unixtime*1000);
	var tag = date.getDate();
	var monat = date.getMonth() + 1;
	var jahr = date.getYear()+1900;
	var stunde = date.getHours();
	var minute = date.getMinutes();
	var sekunde = date.getSeconds();
	return ((tag < 10) ? '0'+tag : tag)+'.'+
		((monat < 10) ? '0'+monat : monat)+'.'+
		jahr;
}

function toUnixtime(strGerDate) {
	var dateParts = strGerDate.split('.');
	var date = new Date(dateParts[2], dateParts[1]-1, dateParts[0]);
	return (Date.parse(date))/1000;
}
function clock(stundenAnfangszeiten, stundenEndzeiten) {
	var zeit = new Date();
	var year = zeit.getFullYear();
	var month = zeit.getMonth();
	var day = zeit.getDate();
	var hours = zeit.getHours();
	var minutes = zeit.getMinutes();
	var seconds = zeit.getSeconds();
	var weekday = zeit.getDay();
	var beginnDesTages = new Date(month+1+" "+ day+", "+year+" "+"0:00"+":00");
	var endeDesTages = new Date(month+1+" "+ day+", "+year+" "+"23:59"+":59");
	var schulBeginn = new Date(month+1+" "+ day+", "+year+" "+"8:00"+":00");
	var schulEnde = new Date(month+1+" "+ day+", "+year+" "+"16:00"+":00");
	//var stundenZeiten = new Array("8:00", "8:45", "8:55", "9:40", "10:00", "10:45", "10:45", "11:30", "11:50", "12:35", "12:45", "13:30", "14:30", "15:15", "15:15", "16:00");
	var schulzustand = "Pause";
	if(weekday == 6 || weekday == 0)
		schulzustand = "Wochenende";
	else if(beginnDesTages <= zeit && zeit < schulBeginn)
		schulzustand = "vor Schulbeginn";
	else if(schulEnde < zeit && zeit <= endeDesTages)
		schulzustand = "Schulschluss";
	else
	{
		for(i = 0; i < stundenAnfangszeiten.length; i++)
		{
			var stundenAnfang = new Date(month+1+" "+ day+", "+year+" "+stundenAnfangszeiten[i]+":00");
			var stundenEnde = new Date(month+1+" "+ day+", "+year+" "+stundenEndzeiten[i]+":00");
			if(stundenAnfang <= zeit && zeit < stundenEnde)
			{
				schulzustand = "Unterricht: "+(i+1)+". Stunde";
				break;
			}
		}
	}
	if(hours < 10)
		hours = "0"+hours;
	if(minutes < 10)
		minutes = "0"+minutes;
	if(seconds < 10)
		seconds = "0"+seconds;
	$('#timeNow').html(hours+":"+minutes+":"+seconds+" ("+schulzustand+")");
	setTimeout(function() { clock(stundenAnfangszeiten, stundenEndzeiten); }, 1000);
}