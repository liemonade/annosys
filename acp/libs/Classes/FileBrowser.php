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

class FileBrowser {
	
	/**
	 * Der aktuelle Ordner des FileBrowsers
	 * @var string
	 */
	private $currentDirectory;
	
	/**
	 * @param string $directory
	 */
	public function __construct($directory)
	{
		if (is_dir($directory)) {
			$this->currentDirectory = $directory;
		}
	}
	
	/**
	 * Findet alle Datein die dem Dateimuster entsprechen im Ordner
	 * @param string $dir Zu durchsuchender Ordner
	 * @param string $filenamePattern Regex für die zu suchenden Dateien
	 * @return array Alle Dateien in dem Ordner die zum Suchmuster passen
	 */
	static public function searchFilesInDirectory($dir, $filenamePattern) {
		$elements = scandir($dir);
		
		$files = array();
		
		foreach ($elements as $element) {
			$path = $dir.$element;
			if (!is_dir($path) && preg_match($filenamePattern, $element)) {
				$files[] = array('path' => $path, 'filename' => $element);
			}
		}
		return $files;
	}
}

?>