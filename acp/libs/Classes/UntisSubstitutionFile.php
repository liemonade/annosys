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
 * Enth‰llt die Definition einer Klasse zum Auslesen von Untis-HTML-Vertretungsplan-Dateien
 * 
 * In dieser Datei findet sich die Klasse
 * UntisSubstitutionFile, welche f¸r das Auslesen
 * und Verarbeiten der von Untis
 * erzeugten Vertretungspl‰ne verantwortlich ist.
 * 
 * @package im.mertsch.annosys
 * @copyright 2011 by Darvin Mertsch , all rights reserved
 * @author Darvin Mertsch
 */


// Setzen der Timezone
date_default_timezone_set('Europe/Berlin');


/**
 * Bietet die Mˆglichkeit die von Untis genertierten Vertretungspl‰ne auszulesen und in Array-Form zu bringen.
 * @author Darvin Mertsch
 */
class UntisSubstitutionFile implements Iterator {
	//////////////////////////////////////////////
	////////////	Eigenschaften	//////////////
	//////////////////////////////////////////////
	
	
	private $Filename;
	private $Filetime;
	private $numColumns;
	private $numRows;
	private $DOMObject;
	private $Timestamp = NULL;
	
	/**
	 * Beinhaltet den aktuellen Reihenindex des foreach Iterators
	 * @var int
	*/
	private $currentRow;
	
	/**
	 * Beinhaltet ein assoziatives Array, in welchem die Indizes den entsprechenden Tabellen¸berschriften zugeordnet werden
	 * @var array
	 */
	private $columnIndices = array();
	
	/**
	 * Beinhaltet die Referenz zur Tabelle mit den Vertretungsdaten.
	 * @var DOMNode
	 */
	private $substitutionTable = NULL;
	
	//////////////////////////////////////////////
	//////////////	Methoden	//////////////////
	//////////////////////////////////////////////

	/**
	 * @param string $linkToSite Pfad zur Vertretungsplandatei
	 * @param array $columns Array mit den auszulesenden Kopfzeilen
	*/
	public function __construct($linkToSite, $columns) 
	{
		try {
			if (!file_exists($linkToSite)) {
				throw new SystemException(__CLASS__.": Datei '$linkToSite' konnte nicht geladen werden.");
			}
			$this->Filename = $linkToSite;					// Speicherort d. einzulesenden Stundenplan
			$this->Filetime = filemtime($linkToSite);
			
			$this->DOMObject = new DOMDocument();
			@$this->DOMObject->loadHTMLFile($linkToSite);	// Domobjekt d. Stundenplan
			
			// Finden der Tabelle mit den Vertretungsdaten.
			$tables = $this->DOMObject->getElementsByTagName("table");
			for ($tableIdx = 0; $tableIdx < $tables->length; $tableIdx++) {
				if ($tables->item($tableIdx)->getAttribute('class') == 'mon_list') {
					$this->substitutionTable = $tables->item($tableIdx);
				}
			}
			
			if ($this->substitutionTable == NULL) {
				throw new SystemException(__CLASS__.": Konnte Vertretungsplantabelle nicht finden.");
			}
			
			$this->initTableProportions();					// Ausmassen d. Planes ausrechnen
			$this->initTimestamp();							// Auslesen des Datums und umrechnen in einen Timestamp
			
			foreach($columns as $column) {
				$this->columnIndices[$column] = -1;
			}
			
			$this->findColumns();
		} 
		catch (SystemException $se) {
			\Logger\File::write($se);
			\Logger\Push::write($se);
			throw new SystemException(__CLASS__.": Objekt konnte nicht instanziiert werden");
		}
	}
	
	/**
	 * Ausmaﬂe der Tabelle auslesen.
	 * @return <i>true</i> Wenn das ermitteln der Tabellenausmaﬂe erfolgreich war
	 */
	private function initTableProportions() 
	{
		$this->numRows = $this->substitutionTable->childNodes->length;
		$this->numColumns = $this->substitutionTable->childNodes->item(0)->childNodes->length;
		if (!empty($this->numRows) && !empty($this->numColumns) ) {
			return true;
		}
		else {
			throw new SystemException(__CLASS__.": Konnte Tabellenausmaﬂe nicht ermitteln.");
		}
	}
	
	/**
	 * Auslesen und Speichern, des von Untis zugewiesenen Datums des Stundenplanes.
	 * @return void
	 * @throws SystemException
	 */
	private function initTimestamp() 
	{
		$divs = $this->DOMObject->getElementsByTagName("div");
		for ($divIdx = 0; $divIdx < $divs->length; $divIdx++) {
			if ($divs->item($divIdx)->getAttribute('class') == 'mon_title') {
				$untisTime = $divs->item($divIdx)->nodeValue;
				$tmpTimeVar = explode(" ", $untisTime);
				$tmpDate = explode(".", $tmpTimeVar[0]);
				$this->Timestamp = mktime(0, 0, 0, $tmpDate[1], $tmpDate[0], $tmpDate[2]);
			}
		}
		
		// Das Datum konnte nicht ausgelesen werden
		if ($this->Timestamp === NULL) {
			throw new SystemException(__CLASS__.": Konnte den Tag, f¸r welchen die Vertretungsdaten gelten, nicht ermitteln.");
		}
	}
	
	/**
	 * Auslesen eines Zelleninhaltes
	 * @param int $row Zeile
	 * @param int $column Spalte
	 * @return string $cellcontent
	 */
	public function getCellcontent($row, $column) 
	{
		$cellcontent = htmlentities($this->substitutionTable->childNodes->item($row)->childNodes->item($column)->nodeValue, ENT_NOQUOTES, "UTF-8");
		if ($cellcontent == '&nbsp;') {
			return '';
		}
		else {
			return $cellcontent;
		}
	}
	
	/**
	 * 
	 * Gibt eine Reihe in Form eines assoziativen Arrays zurr¸ck, mit den ‹berschriften als Keys
	 * @param int $row Auszulesende Reihe
	 * @return array $columns 
	 */
	public function getRow($row) 
	{
		$columns = array();
		foreach($this->columnIndices as $colName => $index) {
			$columns[$colName] = $this->getCellcontent($row, $index);
		}
		return $columns;
	}
	
	/**
	 * Versucht alle Spalten, deren Kopfzeile in $columnIndices enthalten sind, zu finden.
	 * 
	 * Anschlieﬂend werden die Spaltenindizies den entsprechenden Kopfzeilen in $columnIndicies zugewiesen.
	 * Wirft, wenn nicht alle ‹berschriften gefunden werden sollten, eine SystemException
	 * @return bool
	 * @throws SystemException
	 */
	public function findColumns() 
	{
		for($i = 0; $i < $this->numColumns; $i++) {
			$columnName = $this->getCellcontent(0, $i);
			if ( array_key_exists($columnName, $this->columnIndices) ) {
				$this->columnIndices[$columnName] = $i;
			}
		}
		
		if (array_search(-1, $this->columnIndices) === false) {
			return true;
		} 
		else {
			$missingHeadlines = array();
			foreach ($this->columnIndices as $headline => $index) {
				if ($index === -1) {
					$missingHeadlines[] = $headline;
				}
			}
			$headlines = implode(', ', $missingHeadlines);
			
			throw new SystemException(__CLASS__.": Es konnten nicht alle auszulesenden Spalten gefunden werden. Folgende Spalten wurden nicht gefunden: $headlines");
		}
	}
	
	
	// Gibt die Anzahl an Spalten pro Reihe zurr¸ck.
	public function getnumColumns() { return $this->numColumns; }
	
	// Gibt die Anzahl Eintr‰ge in der Tabelle zurr¸ck.
	public function getNumRows() { return $this->numRows; }
	
	// Gibt das (undformatierte) Datei‰nderungsdatum der verwendeten Untis HTML zurr¸ck.
	public function getFiletime() { return $this->Filetime; }
	
	public function getTimestamp() { return $this->Timestamp; }
	
	
	//////////////////////////////////////////////
	///////	   Iterator Implementierung	  ////////
	//////////////////////////////////////////////
	
	public function next() 
	{
		$this->currentRow += 1;
	}
	
	public function rewind() 
	{
		$this->currentRow = 1;
	}
	
	public function key() 
	{
		return $this->currentRow;
	}
	
	public function current() 
	{
		$row = $this->getRow($this->currentRow);
		return $row;
	}
	
	public function valid() 
	{
		return $this->currentRow < $this->getNumRows();
	}
}


?>