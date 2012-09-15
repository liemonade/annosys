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


class User {

	/**
	 * Instanz der Klasse DataBase. Wird zur Verwaltung der Benutzer verwendet.
	 * @var DataBase
	 */
	private $dataBase = NULL;
	
	private $userid = NULL;
	private $userInfos = NULL;
	private $rights = NULL;

	
	/**
	 * Konstruktor des User Objekts
	 * @param \DataBase $dataBase
	 * @param int $userid
	 * @throws SystemException
	 */
	public function __construct(\DataBase $dataBase, $userid)
	{
		if (!($dataBase instanceof  \DataBase)) {
			throw new SystemException('User konnte nicht nicht instanzziert werden.');
		}
		$this->dataBase = $dataBase;
		$this->userid = $userid;
		$this->dataBase->setLastLogin($this->userid);
	}
	
	
	/**
	 * Überprüft ob der Benutzer existiert mit den übergebenen Angaben und gibt im Erfolgsfall eine User Instanz zurück
	 * @param string $username
	 * @param string $password
	 */
	static public function login(\DataBase $dataBase, $username, $password)
	{
		$userid = $dataBase->checkLogin($username, $password);
		if ($userid === false) {
			return false;
		}
		
		return new self($dataBase, $userid);
	}

	/**
	 * ZurÃ¼ckgegeben wird der Wert der angeforderten Benutzerdaten oder bei Misserfolg <i>false</i>
	 */
	public function __get($name)
	{
		if ($name == 'username' || $name == 'password' || $name == 'usergroup' || $name == 'email' || $name == 'regdate' || $name == 'lastlogin') {
			if ($this->userInfos == NULL) {
				$this->userInfos = $this->dataBase->getUser($this->userid);
			}
			return $this->userInfos[$name];
		}
		
		return $this->dataBase->getUserdataValue($this->userid, $name);
	}

	/**
	 * Es wird versucht den Wert der Variablen $name in der Datenbank festzulegen mit dem Wert $value.
	 */
	public function __set($name, $value)
	{
		if ($name == 'username' || $name == 'password' || $name == 'usergroup' || $name == 'email' || $name == 'regdate' || $name == 'lastlogin') {
			if ($this->userInfos == NULL) {
				$this->userInfos = $this->dataBase->getUser($this->userid);
			}
			$this->userInfos[$name] = $value;
			return $this->dataBase->editUser($this->userid, $this->userInfos['username'], $this->userInfos['password'], $this->userInfos['email'], $this->userInfos['usergroup']);
		}
		else {
			if ($this->dataBase->issetUserdata($this->userid, $name)) {
				return $this->dataBase->editUserdata($this->userid, $name, $value);
			}
			else {
				return $this->dataBase->addUserdata($this->userid, $name, $value);
			}
		}
	}
	
	
	/**
	 * Interteptor für Serialisierung des User Objects
	 */
	public function __sleep()
	{
		return array( 'userid' );
	}
	
	
	/**
	 * Interzeptor für die Unserialsierung des User Objects
	 */
	public function __wakeup()
	{
		$this->dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	}
	
	
	/**
	 * Gibt den für die Benutzergruppe des Users gesetzten Wert des Rechtes zurück
	 */
	public function right($rightname) 
	{
		$usergroup = $this->usergroup;
		
		if ($usergroup === false) {
			return false;
		}
		if ($this->rights == NULL) {
			$this->rights = $this->dataBase->getRightsForGroup($usergroup);
		}
		return $this->rights[$rightname];
	}
	
	/**
	 * Versucht auf Basis der Daten aus einem Array einen Benutzer im 
	 * System einzuloggen und gibt bei Erfolg true zurück, ansonsten false
	 */
	static public function startSubstSession($dataBase, $cookieData)
	{
		if (isset($cookieData['USER']) && isset($cookieData['PASS']) && isset($cookieData['MINGRADE']) && isset($cookieData['MAXGRADE']) && isset($cookieData['OUTPUTPROFILE'])) 
		{
			$username = $cookieData['USER'];
			$password = $cookieData['PASS'];
			$user = User::login($dataBase, $username, $password);
			$minGrade = $dataBase->getValue('GLB_OUTPUT_MIN-GRADE');
			$maxGrade = $dataBase->getValue('GLB_OUTPUT_MAX-GRADE');
			$min = $cookieData['MINGRADE'];
			$max = $cookieData['MAXGRADE'];
			$outputprofile = $cookieData['OUTPUTPROFILE'];
			
			$outputprofiles = $dataBase->getOutputprofilesByGroup($user->usergroup);
			$allowedToUseProfile = false;
			foreach ($outputprofiles as $profile) 
			{
				if ($profile['profileid'] == $outputprofile)
				{
					$allowedToUseProfile = true;
				}
			}
			
			if ($user && $min >= $minGrade && $min <= $maxGrade && $max >= $minGrade && $max <= $maxGrade && $min <= $max && $allowedToUseProfile) 
			{
				$_SESSION['USER'] = $user;
				$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['MINGRADE'] = $min;
				$_SESSION['MAXGRADE'] = $max;
				$_SESSION['OUTPUTPROFILE'] = $outputprofile;
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
}

?>