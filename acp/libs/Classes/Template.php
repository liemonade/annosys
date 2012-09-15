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

class Template {
	
	/**
	 * Nimmt den Pfad zu einem Template entgegen und ersetzt alle Platzhalter mit den Werten aus dem Array. 
	 * Bei Erfolg wird das gefüllte Template, bei Missefolg false zurrückgegeben
	 */
	static public function fillTemplate($templatePath, $varArray) {
		$template = file_get_contents($templatePath);
		if (!$template) {
			return false;
		}
		
		return self::fillTemplateString($template, $varArray);
	}
	
	static public function fillTemplateString($template, $varArray)
	{
		return preg_replace('/\{%%(([_a-zA-Z0-9])*)%%\}/e', '((isset(\$varArray[\'$1\'])) ? \$varArray[\'$1\'] : \'{%%$1%%}\')', $template);
	}
}

?>