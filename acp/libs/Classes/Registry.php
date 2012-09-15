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
 * 
 * Beiinhaltet die Klasse Registry.
 * 
 * @author Darvin Mertch
 * @package im.mertsch.annosys
 *
 */

/**
 * Beeinhaltet eine Singleton Registry, welche für eine einfachere Verwaltung der Systemvariablen in der MySQL Datenbank zuständig ist.
 * @author Darvin Mertsch
 *
 */
class Registry {
	
	/**
	 * Instanz der Klasse DataBase. Wird zur Verwaltung der Variablen verwendet.
	 * @var DataBase
	 */
	private $dataBase = NULL;
	
	/**
	 * Singleton Instanz der Registry
	 * @var Registry
	 */
	static private $instance = NULL;
	
	
	
	public function __construct(\DataBase $dataBase)
	{
		if (!($dataBase instanceof  \DataBase)) {
			throw new SystemException('Registry konnte nicht nicht gestartet werden.');
		}
		$this->dataBase = $dataBase;
	}
	
	
	/**
	 * Gibt die Singleton Instanz der Registry zurrück.
	 */
	static public function getInstance() 
	{
		if (self::$instance === NULL) {
			self::$instance = new self(\DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE));
		}
		return self::$instance;
	}
	
	/**
	 * Zurrückgegeben wird der Wert der angeforderten Systemvariablen oder bei Misserfolg <i>false</i>
	 */
	public function __get($name) 
	{
		return $this->dataBase->getValue($name);
	}
	
	/**
	 * Es wird versucht den Wert der Variablen $name in der Datenbank festzulegen mit dem Wert $value.
	 */
	public function __set($name, $value)
	{
		return $this->dataBase->editVariable($name, $value);
	}
	
	/**
	 * Überprüft ob eine Variable in der Datenbank existiert.
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->dataBase->issetVariable($name);
	}
	
	/**
	 * Löscht eine Variable aus der Datenbank.
	 * @return bool
	 */
	public function __unset($name)
	{
		return $this->dataBase->deleteVariable($name);
	}
	
	/**
	 * Fügt eine neue Variable der Datenbank hinzu
	 */
	public function add($name, $value, $description) 
	{
		return $this->dataBase->addVariable($name, $value, $description);
	}
	
	/**
	 * Nimmt ein Array mit Key-Value Paaren entgegen und definiert diese als Globals
	 * @param array $nameValueArrs Array mit Variablen
	 */
	public function defineVariables($variables) 
	{
		$undefinedVariables = array();
		$definedVariables = $this->dataBase->getValues($variables);
		foreach($variables as $variable) {
			if ( !isset($definedVariables[$variable]) ) {
				$undefinedVariables[] = $variable;
			}
			else {
				if ( !defined($variable) ) {
					define($variable, $definedVariables[$variable]);
				}
			}
		}
		return $undefinedVariables;
	}
	
	/**
	 * Registriert alle Variablen in der Datenbank mit dem gewählten Präfix als Globals
	 * @param array $variablePrefix
	 */
	public function defineVariablesByPrefix($namePrefix) 
	{
		$variables = $this->dataBase->getVariablesByPrefix($namePrefix);
		return $this->defineVariables($variables);
	}
	
	/**
	 * Überprüft ob die übergebenen Globalnamen gesetzt sind und gibt ein Array mit allen nicht gesetzten Variablen zurrück
	 * @param array $variables Array mit Variablennamen
	 */
	static public function defined($variables)
	{
		$notDefinedVariables = array();
		
		foreach ($variables as $variable) {
			if ( !defined($variable) ) {
				$notDefinedVariables[] = $variable;
			}
		}
		
		return $notDefinedVariables;
	}
}

?>