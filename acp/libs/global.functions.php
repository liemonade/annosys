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
	
	
	// Rekursiver Durchlauf eines Arrays und utf8 encoden aller Elemente
	function utf8json($inArray) {
		static $depth = 0;
		$depth++;
		if($depth >= '1000')
			return false;

		foreach($inArray as $key=>$value) {
			if(is_array($value)) {
				$newArray[$key] = utf8json($value);
			} else {
				$newArray[$key] = utf8_encode($value);
			}
		}
		
		return $newArray;
	}
	
	// Erzeugen eines JSON Objekts aus einem Array 
	// mit vorherigen UTF8 encoden aller Elemente
	function jsonEncode($array) {
		return json_encode(utf8json($array));
	}
	
	

	
	
	
?>