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

date_default_timezone_set('Europe/Berlin');
define ('_INCLUDE_FILES_PATH_', 'acp/libs/');

function __autoload($className)
{
	// Namespace Backslashes mit Forslashes ersetzen
	str_replace('\\', '/', $className);
	$pathToClassFile = __DIR__.'/'._INCLUDE_FILES_PATH_.'Classes/'.$className.'.php';
	
	if (file_exists($pathToClassFile) === true) {
		require_once $pathToClassFile;
	}
	else {
		throw new Exception('Klassendatei f&uuml;r "'.$className.'" konnte nicht importiert werden!');
	}
}

?>