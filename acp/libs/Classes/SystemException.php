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

/**
 * Beinhaltet die Klasse SystemException
 * 
 * @author Darvin Mertsch
 * @package im.mertsch.annosys
 */

class SystemException extends Exception {
	
	const Error = 0;
	const Warning = 1;
	const Info = 2;
	
	private $errorLevels = array(
								'Error',
								'Warning',
								'Info'
							);
	public $level = '';
	
	/**
	 * Erstellt eine neue SystemException und initialisiert die von Exception geerbten Member
	 * @param string $message Fehlernachricht
	 * @param int $code Mögliche Wert
	 *   - <var>self::Error</var>(default)
	 *   - <var>self::Warning</var>
	 *   - <var>self::Info</var>
	 */
	public function __construct($message, $code = self::Error)
	{
		parent::__construct($message, $code);
		if (array_key_exists($code, $this->errorLevels)) {
			$this->level = $this->errorLevels[$code];
		}
		else {
			$this->level = $this->errorLevels[self::Error];
		}
	}
	
	public function __toString() 
	{
		$date = date('d.m.Y H:i:s', time());
		return "[$this->level] <$date> - $this->message\n";
	}
}
?>