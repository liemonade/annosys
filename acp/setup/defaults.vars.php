<?php

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

$status = $status and $dataBase->addVariable('ACP_INDEX_OVERVIEW_MONITORE', '3', 'Userids der User, deren Onlinezustand auf der Indexseite angezeigt werden sollen.');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_CODE', 'HTML_CODE', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Inhalten der Extraseiten innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_DURATION', 'Anzeigedauer', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Anzeigedauern innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_ENDDATE', 'Ende_der_Ausgabe', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Daten der Ausgabeende innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_NAME', 'Extraseitenname', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Extraseitennamen innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_STARTDATE', 'Beginn_der_Ausgabe', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Daten der Ausgabeanfänge innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('EXTRAPAGES_CSV_IMPORT_HEADLINE_USERGROUPS', 'Benutzergruppen', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Benutzergruppen innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('GLB_AUTOIMPORT_USF', 'true', 'Wenn der Wert gleich "true" ist, werden Vertretungsdaten aus neuen Untis-Subst-Files automatisch importiert');
$status = $status and $dataBase->addVariable('GLB_IMPORTDIRECTORIES_UNTISSUBSTFILES', 'import/', 'Der Ort wo die Untis Datein liegen, welche importiert werden sollen.');
$status = $status and $dataBase->addVariable('GLB_LASTUPDATE_EXTRAPAGES', '0', 'Der letzte Zeitpunkt, als Unix-Timecode, an welchem die Tabelle extrapages verändert wurde');
$status = $status and $dataBase->addVariable('GLB_LASTUPDATE_OUTPUTPROFILES', '0', 'Der letzte Zeitpunkt, als Unix-Timecode, an welchem die Tabelle outputprofiles verändert wurde');
$status = $status and $dataBase->addVariable('GLB_LASTUPDATE_SUBSTITUTIONS', '0', 'Der letzte Zeitpunkt, als Unix-Timecode, an welchem die Tabelle substitutions verändert wurde');
$status = $status and $dataBase->addVariable('GLB_LASTUPDATE_TICKERMESSAGES', '0', 'Der letzte Zeitpunkt, als Unix-Timecode, an welchem die Tabelle tickermessages verändert wurde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_1', '8:45', 'Die Endzeit der ersten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_2', '9:40', 'Die Endzeit der zweiten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_3', '10:45', 'Die Endzeit der dritten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_4', '11:30', 'Die Endzeit der vierten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_5', '12:35', 'Die Endzeit der f&uuml;nften Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_6', '13:30', 'Die Endzeit der sechsten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_7', '15:15', 'Die Endzeit der siebten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_ENDS_8', '16:00', 'Die Endzeit der achten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_1', '8:00', 'Der Startzeit der ersten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_2', '8:55', 'Der Startzeit der zweiten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_3', '10:00', 'Der Startzeit der dritten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_4', '10:45', 'Der Startzeit der vierten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_5', '11:50', 'Der Startzeit der f&uuml;nften Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_6', '12:45', 'Der Startzeit der sechsten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_7', '14:30', 'Der Startzeit der siebten Stunde');
// $status = $status and $dataBase->addVariable('GLB_LESSONTIMES_STARTS_8', '15:15', 'Der Startzeit der achten Stunde');
// $status = $status and $dataBase->addVariable('GLB_NUMBER-OF-LESSONS', '8', 'Die maximale Anzahl der Schulstunden eines Tages');
// $status = $status and $dataBase->addVariable('GLB_OUTPUT_MAX-GRADE', '12', 'Der &auml;lteste vorhandene Jahrgang');
// $status = $status and $dataBase->addVariable('GLB_OUTPUT_MIN-GRADE', '5', 'Die j&uuml;ngste vorhandene Jahrgang');
$status = $status and $dataBase->addVariable('GLB_PROGRAMMID_MANUALLY', '0', 'Die ProgrammID die beim Manuellen Eintragen einer Vertretung in der Datenbank verwendet wird');
$status = $status and $dataBase->addVariable('GLB_PROGRAMMID_UNTISSUBSTFILE', '1', 'Die ProgrammID die beim Import von UntisSubstFile\'s in die Datenbank eingetragen wird');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_CLASSES', '{"id":"1", "alias":"Klasse(n)", "template":"__classes__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Klassen ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_HOUR', '{"id":"2", "alias":"Stunde", "template":"__hour__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Stunde ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_NOTICE', '{"id":"9", "alias":"Anmerkung", "template":"__notice__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Notizen ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_POSTPONEMENT', '{"id":"6", "alias":"Verlegung", "template":"__postponement__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Datumsverlegungen von Unterricht ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_ROOM', '{"id":"5", "alias":"Raum", "template":"__room__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die (Vertr.) R&auml;um ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_STATUS', '{"id":"8", "alias":"Art", "template":"__status__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Art des Vertretungsplaneintrages (Entfall, Vertretung,...) ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_SUBJECT', '{"id":"3", "alias":"Fach", "template":"__subject__"} ', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher das Fach ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_SUPPLY ', '{"id":"7", "alias":"Vertr. Lehrer", "template":"__supply__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die Vertretungslehrer ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_TEACHER', '{"id":"4", "alias":"Lehrer", "template":"__teacher__"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die normalen Lehrer der Klassen ausgegeben werden');
$status = $status and $dataBase->addVariable('GLB_SUBSTTABLE_HEADLINE_TEACHER-AND-SUPPLY', '{"id":"10", "alias":"Vert. Lehrer (f&uuml;r)", "template":"__supply__ (__teacher__)"}', 'Kopfzeile der Spalte innerhalb der Ausgabe, in welcher die normalen Lehrer der Klassen und die Vertretungskraft (in Klammern) ausgegeben werden');
$status = $status and $dataBase->addVariable('OUTPUTPROFILE_LEHRER-MONITORE_TARGETGROUP', 'teachers', 'Templatevariable f&uuml;r das Ausgabeprofil Lehrer-Monitor');
$status = $status and $dataBase->addVariable('OUTPUTPROFILE_SCHUELER-MONITORE_TARGETGROUP', 'students', 'Templatevariable f&uuml;r das Ausgabeprofil Schueler-Monitor');
$status = $status and $dataBase->addVariable('THM_BLUESUBST_SPECIAL_CASE_TEACHER', 'Entfall, Raum-Vtr., Trotz Absenz, Verlegung', 'Sonderfälle bei denen Vertretungslehrer und Lehrer identisch sind, und daher nur der Lehrer angezeigt wird');
$status = $status and $dataBase->addVariable('USERS_CSV_IMPORT_HEADLINE_EMAIL', 'Email', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den E-Mails innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('USERS_CSV_IMPORT_HEADLINE_PASSWORD', 'Passwort_SHA1', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den SHA1 verschl&uuml;sselten Passw&ouml;rtern innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('USERS_CSV_IMPORT_HEADLINE_USERDATA', 'Benutzerdaten', 'BenutzerdatDie Bezeichnung, welche in der Kopfzeile der Spalte mit den Benutzerdaten innerhalb der zu importierenden *.csv-Datei steht en');
$status = $status and $dataBase->addVariable('USERS_CSV_IMPORT_HEADLINE_USERGROUP', 'Benutzergruppe', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Benutzergruppen innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('USERS_CSV_IMPORT_HEADLINE_USERNAME', 'Benutzername', 'Die Bezeichnung, welche in der Kopfzeile der Spalte mit den Benutzernamen innerhalb der zu importierenden *.csv-Datei steht');
$status = $status and $dataBase->addVariable('USF_FILENAMEPATTERN', '/subst_[0-9]{3}.htm/', 'Der reguläre Ausdruck für die Untis-Datein');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_CLASSES', 'Klasse(n)', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die classes Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_HOUR', 'Stunde', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die hour Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_NOTICE', 'Vertretungs-Text', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die notice Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_POSTPONEMENT', 'Vertr. von', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die postponement Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_ROOM', 'Raum', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die room Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_STATUS', 'Art', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die status Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_SUBJECT', 'Fach', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die subject Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_SUPPLY', 'Lehrer', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die supply Spalte in der Tabelle substitutions');
// $status = $status and $dataBase->addVariable('USF_HEADLINE_TEACHER', '(Lehrer)', 'Auszulesende Kopfzeile der subst_xxx.htm Datei f&uuml;r die teacher Spalte in der Tabelle substitutions');