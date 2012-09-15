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

$(document).ready(function(){
	$("#tabs").tabs({
		ajaxOptions: {
			error: function(xhr, status, index, anchor) {
				$(anchor.hash).html("Tab konnte nicht geladen werden.");
			}
		},
		cache: false,
		load: function() {
			$(window).unbind();
			if ($('div#overview').size())
				markupOverview();
			if ($('div#substitutions').size())
				markupSubstitutions();
			if ($('div#extrapages').size())
				markupExtrapages();
			if ($('div#outputprofiles').size())
				markupOutputprofiles();
			if ($('div#users').size())
				markupUsers();
			if ($('div#usergroups').size())
				markupUsergroups();
			if ($('div#ticker').size())
				markupTicker();
			if ($('div#settings').size())
				markupSettings();
		},
		fx: { opacity: 'toggle' },
		select: function(event,ui) {
			var selected = $(this).tabs('option', 'selected');
			$(this).children('div').eq(selected).html('');
		}
	});
	
	$('a:last').click(function(){
		setTimeout("self.location.href='index.php'", 2000);
		$('#tabs').tabs('disable');
	});
});

// Datepicker für Verwendung mit dem deutschen Datumsformat konfigurieren
$.datepicker.regional['de'] = {
	closeText: 'Schließen',
	prevText: '&#x3c;Zurück',
	nextText: 'Vor&#x3e;',
	currentText: 'Heute',
	monthNames: ['Januar','Februar','März','April','Mai','Juni', 'Juli','August','September','Oktober','November','Dezember'],
	monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun', 'Jul','Aug','Sep','Okt','Nov','Dez'],
	dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
	dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
	dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
	weekHeader: 'Wo',
	dateFormat: 'dd.mm.yy',
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: ''
};
$.datepicker.setDefaults($.datepicker.regional['de']);


// Die ValidatingForm um einen Type namens datetimepicker erweitern, welches einen
// datepicker mit Uhrzeit darstellt
$.ValidatingForm.prototype.addFieldType('datetimepicker', {
	jquery: "datetimepicker", 
	jqueryParams: {
		timeFormat: 'hh:mm', 
		timeText: 'Uhrzeit', 
		hourText: 'Stunden', 
		minuteText: 'Minute', 
		secondText: 'Sekunde', 
		currentText: 'Jetzt', 
		closeText: 'Fertig'
	},
	getValue: function(fieldElem) {
		var dateObject = $(fieldElem).datetimepicker('getDate');
		return Math.round(Date.parse(dateObject) / 1000);
	},
	validation: function(value) {
		return typeof value == 'number';
	},
	setValue: function(fieldElem, field, value) {
		if (value == '') {
			$(fieldElem).val('');
		}
		else {
			$(fieldElem).val(toGerDateTime(value));
		}
	}
});


// Die ValidatingForm um einen Typ namens slider erweitern, welcher einen UISlider
// in die Form einfügt
$.ValidatingForm.prototype.addFieldType('slider', {
	defaultValue: 10,
	minValue: 1,
	maxValue: 60,
	fieldMarkup: '<div class="slider-widget"></div>',
	onCreate: function(fieldElem, field) {
		$('label[for="'+field.name+'"]').val(field.defaultValue);
		$('label[for="'+field.name+'"]').html('Anzeigedauer: <b>' + field.defaultValue + ' Sekunden</b>');
		$(fieldElem).slider({
			range: "min", value: 10, min: 1, max: 60, slide: function(event, ui) {
				$('label[for="'+field.name+'"]').val(ui.value);
				$('label[for="'+field.name+'"]').html('Anzeigedauer: <b>' + ui.value + ' Sekunden</b>');
			}
		});
	},
	getValue: function(fieldElem) {
		return $(fieldElem).slider('value');
	},
	setValue: function(fieldElem, field, value) {
		if (value === '') {
			value = field.defaultValue;
		}
		$(fieldElem).slider("option", "value", value);
		$('label[for="'+field.name+'"]').val(value);
		$('label[for="'+field.name+'"]').html('Anzeigedauer: <b>' + value + ' Sekunden</b>');
	},
	validation: function(value) {
		if (typeof value == 'number') {
			return true;
		}
		else {
			return ['Schieben Sie den Regler auf den gewünschten Wert'];
		}
	}
});

// Die ValidatingForm um einen Typ namens segmentedcontroll erweitern, welches
// Checkboxes in Form von Buttons darstellt
$.ValidatingForm.prototype.addFieldType('segmentedcontroll', {
	minSelection: undefined,
	maxSelection: undefined,
	value: [],
	fieldMarkup: '<div class="checkgroup"></div>',
	onCreate: function(fieldElem, field) {
		$.each(field.segments, function(segmentIdx, segment) {
			$(fieldElem).append('<input type="checkbox" id="'+segmentIdx+'"><label for="'+segmentIdx+'">'+segment+'</label>');
		});
		$(fieldElem).buttonset();
		
		if (field.value.length && field.value instanceof Array) {
			
			$(fieldElem).find('label ').each(function() {
				if ($.inArray($(this).attr('for'), field.value) !== -1) {
					$(this).addClass('ui-state-active').attr('aria-pressed', 'true');
				}
			});
			
		}
	},
	getValue: function(fieldElem, field) {
		var idValues = new Array;
		var htmlValues = new Array;
		
		$(fieldElem).find('label.ui-state-active').each(function(){
			idValues.push($(this).attr('for'));
		});
		
		$(fieldElem).find('label.ui-state-active span').each(function(){
			htmlValues.push($(this).html());
		});
		
		return { 'idValues': idValues, 'htmlValues': htmlValues };
	},
	validation: function(value, field) {
		var valid = true;
		var errors = new Array;
		
		if (field.minSelection !== undefined && value.idValues.length < field.minSelection) {
			valid = false;
			errors.push('Es muss mindestens '+field.minSelection+' Feld ausgewählt werden');
		}
		if (field.maxSelection !== undefined && value.idValues.length > field.maxSelection) {
			valid = false;
			errors.push('Es dürfen maximal '+field.maxSelection+' Felder ausgewählt werden');
		}
		
		if (valid) {
			return true;
		}
		
		else {
			return errors;
		}
	},
	setValue: function(fieldElem, field, fieldValue) {
		if (fieldValue === '') {
			$(fieldElem).find('label').removeClass('ui-state-active').attr('aria-pressed', 'false');
		}
		else if (fieldValue instanceof Array) {
			$(fieldElem).find('label').removeClass('ui-state-active').attr('aria-pressed', 'false');
			
			$(fieldElem).find('label ').each(function() {
				if ($.inArray($(this).attr('for'), fieldValue) !== -1) {
					$(this).addClass('ui-state-active').attr('aria-pressed', 'true');
				}
			});
		}
	},
	reset: function(fieldElem, field) {
		$(fieldElem).find('label').removeClass('ui-state-active').attr('aria-pressed', 'false');
		
		if (field.value.length && field.value instanceof Array) {
			$(fieldElem).find('label ').each(function() {
				if ($.inArray($(this).attr('for'), field.value) !== -1) {
					$(this).addClass('ui-state-active').attr('aria-pressed', 'true');
				}
			});
		}
	}
});


// Funktion zum liefern des aktuellen Datums in dd.mm.yyyy
function getTimestring()
{
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

//Funktion zum Konvertieren von Unixzeit zum Datumsformat dd.mm.yyyy hh:mm
function toGerDateTime(unixtime){
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
		jahr+' '+
		((stunde < 10) ? '0'+stunde : stunde)+':'+
		((minute < 10) ? '0'+minute : minute);
}

function toUnixtime(strGerDate) {
	var dateParts = strGerDate.split('.');
	var date = new Date(dateParts[2], dateParts[1]-1, dateParts[0]);
	return (Date.parse(date))/1000;
}