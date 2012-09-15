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
       ValidatingForm: function(options,arg) {
            
			defaults = {
				fields:									[], 	// Ein Array mit allen in der Form zu verwendenen Feldern
				shouldPutTooltipsOnUnvalidatedFields:	true, 	// Ob Tooltips mit der Information, was an der Eingabe falsch ist, neben den Feldern angezeigt werden sollen
				
				// Callbacks
				allFieldsAreValid: false,
				fieldsBecameInvalid: false,
			};
			
			if (options && typeof(options) == 'object') {
				options = $.extend( {}, defaults, this.data(), options );
            }
			else if (typeof(options) != 'string'){
				options = $.extend( {}, defaults, this.data() );
			}
			
			// Der Benutzer erwartet Rückgabewerte, die normale Instanzzierung des Plug-Ins wird also ausgesetzt
			if (options == 'values' || options == 'value') {
				
				var returnVal = {};
				
				this.each(function() {
					if ($(this).hasClass('ValidatingFormContainer')) {
						var result = $.ValidatingForm($(this), options, arg);
						
						if (options == 'values') {
							returnVal = $.extend( {}, returnVal, result );
						}
						else if (options == 'value') {
							returnVal = result;
						}
					}
				});
				
				return returnVal;
			}

			// Die Elemente werden als ValidatingForms instanzziert
			this.each(function() {
				new $.ValidatingForm(this, options, arg );
            });
			
            return;
        }
    });
	

    $.ValidatingForm = function( elem, options, arg ) {
	
		
		if (options && typeof(options) == 'string') {
           if (options == 'values') {
				return values(elem);
		   }
		   if (options == 'value') {
				return value(elem, arg);
		   }
		   if (options == 'reset') {
				reset(elem);
           }
           else if (options == 'setValues') {
               setValues( elem, arg );
           }
		   else if (options == 'update') {
				update(elem, arg);
		   }
           return;
        }
		
		// validatingForm als Container des Formulars
		var validatingForm = elem;		
		
		// Das field Object mit den Standard des field.type's mergen
		var fieldTypeDefaults = this.fieldTypes;
		
		$.each(options.fields, function(fieldIdx, field) {
			if (fieldTypeDefaults[field.type] !== undefined) {
				options.fields[fieldIdx] = $.extend({}, fieldTypeDefaults[field.type], field);
			}
			else {
				options.fields[fieldIdx] = $.extend({}, fieldTypeDefaults['text'], field);
			}
		});
		
		$(validatingForm).data(options);
		
		var shouldPutTooltipsOnUnvalidatedFields = $(validatingForm).data('shouldPutTooltipsOnUnvalidatedFields');
		
		// Formular Container setzen
		$(validatingForm).addClass('ValidatingFormContainer');
		
		// Formular vorbereiten
		$(validatingForm).append('<form><fieldset></fieldset></form>');
		
		
		// Felder in das Formular einfügen
		$.each(options.fields, function(fieldidx, field) {
			insertField(validatingForm, field);
		});
		
		
		if (options.appendix !== undefined) {
			$(validatingForm).find('form > fieldset').append(options.appendix);
		}
		
		
		/**
		 * Einfügen eines Feldes in das Formular
		 * @param jQuery validatingForm Fieldsetelement im Formcontainer
		 * @param Object field Object mit mind. den Feldern "name" und "type"
		 */
		function insertField(validatingForm, field)
		{
			
			var fieldsetElem = $(validatingForm).find('form fieldset');
			
			if (field.label !== undefined) {
				$(fieldsetElem).append('<label for="'+field.name+'">'+field.label+'</label>');
			}
			
			$(fieldsetElem).append(field.fieldMarkup);
			
			
			var fieldElem = $(fieldsetElem).find(':last');
			
			
			$(fieldElem).attr({
				'id': field.name,
				'internaltype': field.type
			});
			
			
			// Falls noch zusätzliche Attribute gesetzt sind, diese setzen
			if (field.attributes !== undefined) {
				$(fieldElem).attr(field.attributes);
			}
			
						
			// Gewünschte Klassen hinzufügen
			if (field.classes !== undefined) {
				$.each(field.classes, function(idx, className) {
					$(fieldElem).addClass(className);
				});
			}
			
			
			// Falls gewünscht, eine jQuery Methode aufrufen
			if (field.jquery !== undefined) {
				var params = {};
				if (field.jqueryParams !== undefined) {
					params = field.jqueryParams;
				}
				jQuery.fn[field.jquery] && jQuery(fieldElem)[field.jquery](params);
			}
			
			
			// Falls vorhanden Platzhalter in das Feld einf�gen
			if (field.placeholder !== undefined) {
				$(fieldElem).attr('placeholder', field.placeholder);
				$(fieldElem).val(field.placeholder);
				
				$(fieldElem).focusin(function() {
					if ($(fieldElem).val() == field.placeholder) {
						$(fieldElem).val('');
					}
				});
				
				$(fieldElem).focusout(function() {
					if ($(fieldElem).val() == '') {
						$(fieldElem).val(field.placeholder);
					}
				});
			}
			
			
			// Wenn schon ein Wert verfügbar ist, diesen setzen
			if (field.value !== undefined) {
				$(fieldElem).val(field.value);
			}
			
			
			// Es wurde ein eigener Callback für die Validierung angegeben
			if (typeof field.validation === 'function') {
				$(fieldElem).change(function() { 
					validate(validatingForm, field.name, field.validation, shouldPutTooltipsOnUnvalidatedFields);
				});
				if (field.optional === false) {
					markFieldForValidation(validatingForm, field.name);
				}
			}
			// Es soll anhand eines Regul�ren Ausdruckes validiert werden
			else if (field.validation instanceof RegExp) {
				$(fieldElem).change(function() { 
					validate(validatingForm, field.name, function(value) { return value.match(field.validation) !== null; }, shouldPutTooltipsOnUnvalidatedFields);
				});
				if (field.optional === false) {
					markFieldForValidation(validatingForm, field.name);
				}
			}
			
			
			// Es wurde ein Callback angegeben, der nach Erzeugen des Felds aufgerufen werden soll
			if (typeof field.onCreate === 'function') {
				field.onCreate(fieldElem, field);
			}
		}
		
		
		/**
		 * Festlegen, dass das Feld zu validieren ist
		 * @param jQuery validatingForm
		 * @param string fieldName
		 */
		function markFieldForValidation(validatingForm, fieldName)
		{
			var toValidateFields = $(validatingForm).data('toValidateFields');
			if (toValidateFields === undefined) {
				toValidateFields = new Object();
			}
			toValidateFields[fieldName] = false;
			$(validatingForm).data('toValidateFields', toValidateFields);
		}
		
		
		/**
		 * Allgemeine Validierungsroutine, welche die Validierungsfunktion für das spezifische Feld auswertet
		 * @param jQuery fieldElem
		 * @param function validationFunction Funktion mit dem Prototype validationFunction(value)
		 * @param boolean shouldPutTooltipsOnUnvalidatedFields
		 */
		function validate(validatingForm, fieldName, validationFunction, shouldPutTooltipsOnUnvalidatedFields)
		{
			// Das jQuery-Object des aktuell zu untersuchenden Feldes 
			var fieldElem = $(validatingForm).find('#'+fieldName);
			
			var fields = $(validatingForm).data('fields');
			var field = {};
			$(fields, function() {
				if (this.name === fieldName) {
					field = this;
				}
			});
			
			// Validierungsstatus des Feldes
			var isValidated = validationFunction( value(validatingForm, fieldName), field );
			
			// Alle zu validierenden Felder
			var toValidateFields = $(validatingForm).data('toValidateFields');
			
			if (isValidated === true) {
				$(fieldElem).data('validated', true);
			}
			else {
				$(fieldElem).data('validated', false);
				
				if (toValidateFields[fieldName] !== undefined) {
					toValidateFields[fieldName] = false;
					$(validatingForm).data('toValidateFields', toValidateFields);
				}
				// Falls gewünscht, wird nun neben dem Feld ein Tooltip mit der Fehlerbeschreibung angezeigt
				if (shouldPutTooltipsOnUnvalidatedFields == true) {
					console.log('Fehler soll angezeigt werden');		// !!!!!!!!!!!!!!!!
				}
			}
			
			// Falls es ein zu validierendes Feld war, auf den neuen Validierungsstatus setzen
			if (toValidateFields[fieldName] !== undefined) {
				toValidateFields[fieldName] = isValidated === true;
				$(validatingForm).data('toValidateFields', toValidateFields);
				
				var allFieldsAreValid = true;
				console.log(toValidateFields);
				$.each(toValidateFields, function(fieldIdx, fieldIsValid) {
					allFieldsAreValid = allFieldsAreValid && fieldIsValid;
				});
				
				var allFieldsWereValid = $(validatingForm).data('allFieldsWereValid');
				
				if (allFieldsAreValid && !allFieldsWereValid) {
					var allFieldsAreValidCallback = $(validatingForm).data('allFieldsAreValid');
					if (typeof allFieldsAreValidCallback === 'function') { 
						allFieldsAreValidCallback($(validatingForm));
					}
					$(validatingForm).data('allFieldsWereValid', true);
				}
				
				else if (!allFieldsAreValid && allFieldsWereValid) {
					var fieldsBecameInvalidCallback = $(validatingForm).data('fieldsBecameInvalid');
					if (typeof fieldsBecameInvalidCallback === 'function') { 
						fieldsBecameInvalidCallback($(validatingForm));
					}
					$(validatingForm).data('allFieldsWereValid', false);
				}
			}
		}
		
		
		//////////////////////////////////
		///////		Methoden	//////////
		//////////////////////////////////
		
		/**
		 * Liest alle Werte der Felder aus und gibt sie als Objekt zur�ck
		 * @param jQuery validatingForm
		 */
		function values(validatingForm)
		{
			var fields = $(validatingForm).data('fields');
			var values = {};
			
			$.each(fields, function(fieldIdx, field) {
				if (typeof field.getValue === 'function') {
					values[field.name] = field.getValue($(validatingForm).find('#'+field.name), field);
				}
				else {
					values[field.name] = $(validatingForm).find('#'+field.name).val();
				}
			});
			
			return values;
		}
		
		
		/**
		 * Liest ein bestimmtes Feld aus und gibt den Wert zur�ck
		 * @param jQuery validatingForm
		 * @param string fieldName
		 */
		function value(validatingForm, fieldName)
		{
			var value = null;
			
			$.each($(validatingForm).data('fields'), function(fieldIdx, field) {
				if (field.name == fieldName) {
					
					if (typeof field.getValue === 'function') {
						value = field.getValue($(validatingForm).find('#'+field.name), field);
					}
					else {
						value = $(validatingForm).find('#'+field.name).val();
					}
				}
			});
			
			return value;
		}
		
		
		/**
		 * Setzt den Wert alle im Parameter fields übergebenen Felder
		 * @param jQuery validatingForm
		 * @param Object fieldValues
		 */
		function setValues(validatingForm, fieldValues)
		{
			var fields = $(validatingForm).data('fields');
			
			if (fieldValues instanceof Object) {
				$.each(fieldValues, function(fieldName, newValue) {
					$.each(fields, function(fieldIdx, field) {
						if (field.name == fieldName) {
							if (typeof field.setValue === 'function') {
								field.setValue($(validatingForm).find('#'+field.name), field, newValue);
							}
							else {
								$(validatingForm).find('#'+field.name).val(newValue);
							}
						}
					});
				});
			}
			// Alle Felder leeren
			else if (fieldValues === '') {
				$.each(fields, function(fieldIdx, field) {
					if (typeof field.setValue === 'function') {
							field.setValue($(validatingForm).find('#'+field.name), field, '');
					}
					else {
						$(validatingForm).find('#'+field.name).val('');
					}
				});
			}
		}
		
		
	};
	
	
	
	// Es ermöglichen neue Feldtypen hinzuzufügen, um neues Standartverhalten für Felder zu erhalten.
	// Und Standartverhalten für inputs vom type text usw. bereits hinzufügen.
	
	var defaultFieldType = {
		fieldMarkup: '<input type="text" />',
		validation: false,
		optional: false,
		value: undefined,
		placeholder: undefined,
		jquery: undefined,
		jqueryParams: undefined,
		getValue: undefined,
		onCreate: undefined
	};
	
	$.extend($.ValidatingForm.prototype, {
		fieldTypes: {
				'text': $.extend({}, defaultFieldType),
				'textarea': $.extend({}, defaultFieldType, {
					fieldMarkup: '<textarea></textarea>'
				}),
				'email': $.extend({}, defaultFieldType, {
					validation: _validateEmail
				}),
				'url': $.extend({}, defaultFieldType, {
					validation: _validateURL
				}),
		},
		addFieldType: function(fieldName, settings) {
			this.fieldTypes[fieldName] = $.extend({}, defaultFieldType, settings);
		}
	});
	
	
	/**
	 * Validierungsfunktion für E-Mail Adressen
	 * @param string value
	 */
	function _validateEmail(value)
	{
		if (value.match(/^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i )) {
			return true;
		}
		else {
			return {
				errors: ["Keine gültige E-Mail Adresse"]
			};
		}
	}
	
	
	/**
	 * Validierungsfunktion für URLs
	 * @param string value
	 */
	function _validateURL(value)
	{
		if (value.match(/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/)) {
			return true;
		}
		else {
			return {
				errors: ["Keine gültige URL"]
			};
		}
	}
	
})(jQuery);