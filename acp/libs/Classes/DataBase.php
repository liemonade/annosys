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
 * Beinhaltet die zentrale Datenbankklasse, für die Verwaltung des Systems.
 * 
 * @author Darvin Mertsch
 * @package im.mertsch.annosys
 *
 */
class DataBase
{
    private $mysqli;
    static private $instance;
    
    // Verbinden der Datenbank
    public function __construct($host, $user, $pass, $table) 
    {
		$this->mysqli = new MySQLi($host, $user, $pass, $table);
    }

    // Schließen der Datenbankverbindung
    public function  __destruct() 
    {
    	session_write_close();
		$this->mysqli->close();
    }
    
    static public function getInstance($host, $user, $pass, $table) 
    {
    	if (self::$instance == null) 
    	{
    		self::$instance = new self($host, $user, $pass, $table);
    	}
    	
    	return self::$instance;
    }
    
    static public function closeInstance()
    {
    	unset(self::$instance);
    }
	
	// Gibt ein Array zurrück mit Daten Über den Zustand einiger Tabellen der DB
	// Wie z.B. Onlinestatus von Monitoren, Usern, Anzahl der aktuellen Extraseiten usw.
	public function getOverview()
	{
		$overview = array('onlinestatus' => array(), 'extrapages' => array(), 'tickermessages' => array(), 'users' => array(), 'substitutions' => array());
		$actTime = time();
		
		// Anzahl der SchÃ¼ler ermitteln, die Online sind
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM `sessions` WHERE `data` like '%USERGROUP|i:6;%'");
		$stmt->execute();
		$stmt->bind_result($overview['onlinestatus']['schueleronline']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln, welche der Monitore welchen Onlinezustand haben
		$monitorIds = $this->getValue('ACP_INDEX_OVERVIEW_MONITORE');
		if ($monitorIds) {
			$monitorIds = explode(',', $monitorIds);
			
			// Ermitteln wann der letzte Login war
			$sql = 'SELECT username, lastlogin FROM users WHERE userid=';
			foreach ($monitorIds as $idx => $id) {
				if ($idx == 0) {
					$sql .= $id;
				}
				else {
					$sql .= ' OR userid='.$id;
				}
			}
			$stmt = $this->mysqli->prepare($sql);
			$stmt->execute();
			$stmt->bind_result($username, $lastlogin);
			while ($stmt->fetch()) {
				$overview['onlinestatus']['monitor'][$username]['lastlogin'] = $lastlogin;
			}
			$stmt->close();
			unset($stmt);
			
			// Ermitteln welche online sind und wann der letzte Aufruf war
			$sql = "SELECT users.username, sessions.lastAccess FROM sessions, users WHERE ";
			foreach ($monitorIds as $idx => $id) {
				$stmt = $this->mysqli->prepare($sql."sessions.data LIKE '%USERID|i:$id;%' AND users.userid=$id");
				$stmt->execute();
				$stmt->bind_result($username, $lastAccess);
				$stmt->fetch();
				if ($lastAccess and $username) {
					$overview['onlinestatus']['monitor'][$username]['lastaccess'] = $lastAccess;
				}
				$stmt->close();
				unset($stmt);
			}
		}
		
		// Ermitteln wieviele Extraseiten aktuell ausgegeben werden kÃ¶nnten
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM extrapages WHERE start_date <= ? AND ? <= end_date");
		$stmt->bind_param('ii', $actTime, $actTime);
		$stmt->execute();
		$stmt->bind_result($overview['extrapages']['activepages']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln wieviele Tickernachrichten ausgegeben werden kÃ¶nnten
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM tickermessages WHERE start_date <= ? AND ? <= end_date");
		$stmt->bind_param('ii', $actTime, $actTime);
		$stmt->execute();
		$stmt->bind_result($overview['tickermessages']['activemessages']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln wieviel SchÃ¼ler insgesamt im System sind
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM users WHERE usergroup=6");
		$stmt->execute();
		$stmt->bind_result($overview['users']['numschueler']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln wieviel SchÃ¼ler das System schon nutzen
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM users WHERE usergroup=6 AND lastlogin != regdate");
		$stmt->execute();
		$stmt->bind_result($overview['users']['schuelerusingsystem']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln wieviele Vertretungsdaten, die ausgegeben werden kÃ¶nnten, sich im System befinden
		$stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM substitutions WHERE (date <= ? AND ? <= (date+60*60*24)) OR ? < date");
		$stmt->bind_param('iii', $actTime, $actTime, $actTime);
		$stmt->execute();
		$stmt->bind_result($overview['substitutions']['activesubstitutions']);
		$stmt->fetch();
		$stmt->close();
		unset($stmt);
		
		// Ermitteln wann das letzte mal ein Vertretungsplandatei importiert wurde
		$overview['substitutions']['lastimport'] = $this->getValue('GLB_LASTUPDATE_SUBSTITUTIONS');
		
		return $overview;
	}

    /************************\
    |		 extrapages	     |
    \************************/

	// Hinzufügen einer Extrapage
	public function addExtrapage($name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups) 
	{
		$usergroups = implode(',', $usergroups);
		$sql = "INSERT INTO extrapages(name, poster, code, start_date, end_date, edit_date, duration, usergroups)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssiiiis", $name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return $stmt->insert_id;
		else
			return false;
	}

	// ZurrÃ¼ckliefern aller Werte einer Extrapage
	public function getExtrapage($pageid) 
	{
		$sql = "SELECT name, poster, code, start_date, end_date, edit_date, duration, usergroups
				FROM extrapages WHERE pageid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $pageid);
		$stmt->execute();
		$stmt->bind_result($name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups);
		if($stmt->fetch())
			return array(
							'name' => $name,
							'poster' => $poster,
							'code' => $code,
							'start_date' => $start_date,
							'end_date' => $end_date,
							'edit_date' => $edit_date,
							'duration' => $duration,
							'usergroups' => explode(',', $usergroups)
						);
		else
			return false;
	}

	// ZurrÃ¼ckliefern aller Extrapages
	public function getExtrapages() 
	{
		$sql = "SELECT pageid, name, poster, start_date, end_date, edit_date, duration, usergroups FROM extrapages ORDER BY edit_date ASC";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($pageid, $name, $poster, $start_date, $end_date, $edit_date, $duration, $usergroups);
		while($stmt->fetch())
			$extrapages[] = array(
									'pageid'=>$pageid,
									'name' => $name,
									'poster' => $poster,
									'start_date' => $start_date,
									'end_date' => $end_date,
									'edit_date' => $edit_date,
									'duration' => $duration,
									'usergroups' => explode(',', $usergroups)
								);
		if(!empty ($extrapages))
			return $extrapages;
		else
			return false;
	}
	
	// Alle Extrapages einer Gruppe zurrückliefern
	public function getCurrentExtrapagesByGroupForPresentation($groupid) 
	{
		$sql = "SELECT pageid, code, end_date, duration FROM extrapages
				WHERE FIND_IN_SET(?, usergroups) > 0 AND start_date <= ? AND ? <= end_date
				ORDER BY start_date DESC";
		$stmt = $this->mysqli->prepare($sql);
		$currentTime = time();
		$stmt->bind_param('iii', $groupid, $currentTime, $currentTime);
		$stmt->execute();
		$stmt->bind_result($pageid, $code, $end_date, $duration);
		
		$extrapages = array();
		
		while ($stmt->fetch()) {
			$extrapages[] = array(
									'pageid' => $pageid,
									'code' => $code,
									'end_date' => $end_date,
									'duration' => $duration
								);
		}
		
		return $extrapages;
	}

	// Bearbeiten einer Extrapage
	public function editExtrapage($pageid, $name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups) 
	{
		$usergroups = implode(',', $usergroups);
		$sql = "UPDATE extrapages SET name=?, poster=?, code=?, start_date=?, end_date=?, edit_date=?, duration=?, usergroups=?
				WHERE pageid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssiiiisi", $name, $poster, $code, $start_date, $end_date, $edit_date, $duration, $usergroups, $pageid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

    // LÃ¶schen einer Extrapage
	public function deleteExtrapage($pageid) 
	{
		$sql = "DELETE FROM extrapages WHERE pageid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $pageid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}
	
	// LÃ¶schen einer Extrapage anhand des Namens
	public function deleteExtrapageByName($pagename) 
	{
		$sql = "DELETE FROM extrapages WHERE name=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $pagename);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	/************************\
    |		 imports	     |
    \************************/

	// Einsetzen/Updaten eines Imports
	public function setImport($filename, $filedate, $programm) 
	{
		$sql = "INSERT INTO imports(filename, filedate, programm) VALUES(?, ?, ?)
				ON DUPLICATE KEY UPDATE filedate=?, programm=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("siiii", $filename, $filedate, $programm, $filedate, $programm);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// Liefert das letzte Updatedatum einer Importdatei zurÃ¼ck
	public function getImport($filename) 
	{
		$sql = "SELECT filedate FROM imports WHERE filename=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $filename);
		$stmt->execute();
		$stmt->bind_result($filedate);
		if($stmt->fetch())
			return $filedate;
		else
			return false;
	}

	// Liefert alle Imports eines Programms zurück
	public function getImportsByProgramm($programmid) 
	{
		$sql = "SELECT filename, filedate FROM imports WHERE programm=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $programmid);
		$stmt->execute();
		$stmt->bind_result($filename, $filedate);
		$imports = array();
		while($stmt->fetch()) {
			$imports[] = array('filename' => $filename, 'filedate' => $filedate);
		}
		return $imports;
	}
	
	// Löscht alle Imports eines Programms
	public function clearImportsByProgramm($programmid) 
	{
		$sql = "DELETE FROM imports WHERE programm=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $programmid);
		$stmt->execute();
		if ($stmt->affected_rows > 0)
			return true;
		else
			return false;
	}

    /************************\
    |	 outputprofiles	     |
    \************************/

	// Hinzufügen eines Ausgabeprofiles
	public function addOutputprofile($profilename, $usergroups, $columns, $theme) 
	{
		$sql = "INSERT INTO outputprofiles(profilename, usergroups, columns, theme) VALUES(?, ?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("ssss", $profilename, $usergroups, $columns, $theme);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return $stmt->insert_id;
		else
			return false;
	}
	
	// Liefert alle Ausgabeprofile zurrück
	public function getOutputprofiles() 
	{
		$sql = "SELECT profileid, profilename, usergroups, theme FROM outputprofiles";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($profileid, $profilename, $usergroups, $theme);
		while ($stmt->fetch())
			$outputprofiles[] = array(
										'profileid' => $profileid,
										'profilename' => $profilename,
										'theme' => $theme,
										'usergroups' => explode(',', $usergroups)
									 );
		if ($outputprofiles) 
			return $outputprofiles;
		else
			return false;
	}
	
	/**
	 * Liefert ein Ausgabeprofile zurück
	 * @param integer $profileid
	 * @return array $outputprofile
	 */
	public function getOutputprofile($profileid) 
	{
		$sql = "SELECT profilename, usergroups, columns, theme FROM outputprofiles
				WHERE profileid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $profileid);
		$stmt->execute();
		$stmt->bind_result($profilename, $usergroups, $columns, $theme);
		while ($stmt->fetch())
			$outputprofile = array(
										'profileid'=>$profileid,
										'profilename'=>$profilename,
										'usergroups'=>$usergroups,
										'columns'=>$columns,
										'theme'=>$theme
									 );
		if ($outputprofile)
			return $outputprofile;
		else
			return false;
	}

	// Liefert alle Ausgabeprofile einer Gruppe zurÃ¼ck
	public function getOutputprofilesByGroup($usergroup) 
	{
		$sql = "SELECT profileid, profilename, columns, theme FROM outputprofiles
				WHERE FIND_IN_SET(?, usergroups) > 0";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $usergroup);
		$stmt->execute();
		$stmt->bind_result($profileid, $profilename, $columns, $theme);
		while($stmt->fetch())
			$profiles[] = array(
									'profileid' => $profileid,
									'profilename' => $profilename,
									'columns' => $columns,
									'theme' => $theme
								);
		if(!empty ($profiles))
			return $profiles;
		else
			return false;
	}

    // Editieren eines Ausgabeprofiles
	public function editOutputprofile($profileid, $profilename, $usergroups, $columns, $theme) 
	{
		$sql = "UPDATE outputprofiles SET profilename=?, usergroups=?, columns=?, theme=? WHERE profileid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssii", $profilename, $usergroups, $columns, $theme, $profileid);
		$stmt->execute();
		if (!$stmt->error) {
			return true;
		}
		else {
			return false;
		}
	}

	// LÃ¶schen eines Ausgabeprofils
	public function deleteOutputprofile($profileid) 
	{
		$sql = "DELETE FROM outputprofiles WHERE profileid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $profileid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	/************************\
    |		  rights	     |
    \************************/

	// HinzufÃ¼gen eines Rechtes
	public function addRight($rightname, $defaultValue, $description) 
	{
		$sql = "INSERT INTO rights(rightname, defaultValue, description) VALUES(?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sss", $rightname, $defaultValue, $description);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return $stmt->insert_id;
		else
			return false;
	}

	// Liefert die Informationen zu einem Recht zurrÃ¼ck
	public function getRight($rightid) 
	{
		$sql = "SELECT rightname, defaultValue, description FROM rights WHERE rightid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $rightid);
		$stmt->execute();
		$stmt->bind_result($rightname, $defaultValue, $description);
		if($stmt->fetch())
			return array(
							'rightname' => $rightname,
							'defaultValue' => $defaultValue,
							'description' => $description
						);
		else
			return false;
	}

	// Liefert alle Rechte zurrÃ¼ck in einem zweidimensionalen Array
	public function getRights() 
	{
		$stmt = $this->mysqli->prepare("SELECT rightid, rightname, defaultValue, description FROM rights");
		$stmt->execute();
		$stmt->bind_result($rightid, $rightname, $defaultValue, $description);
		while($stmt->fetch()) {
			$rights[] = array(
								'rightid' => $rightid,
								'rightname' => $rightname,
								'defaultValue' => $defaultValue,
								'descriptiom' => $description
							);
		}
		if(!empty ($rights))
			return $rights;
		else
			return false;
	}

	// Editieren eines Rechtes
	public function editRight($rightid, $rightname, $defaultValue, $description) 
	{
		$sql = "UPDATE rights SET rightname=?, defaultValue=?, description=? WHERE rightid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssi", $rightname, $defaultValue, $description, $rightid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// LÃ¶schen eines Rechtes
	public function deleteRight($rightid) 
	{
		$stmt = $this->mysqli->prepare("DELETE FROM rights WHERE rightid=?");
		$stmt->bind_param("i", $rightid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}


	/************************\
    |	   righttogroup	     |
    \************************/

	// Setzt den Wert eines Rechtes welches fÃ¼r die angegebene Usergruppe gelten soll
	public function setRightForGroup($rightid, $groupid, $value) 
	{
		$sql = "UPDATE righttogroup SET value=? WHERE rightid=? AND groupid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sii", $value, $rightid, $groupid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else {
			$stmt->close();
			$sql = "INSERT INTO righttogroup(rightid, groupid, value) VALUES(?, ?, ?)";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("iis", $rightid, $groupid, $value);
			$stmt->execute();
			if($stmt->affected_rows == 1)
				return true;
			else
				return false;
		}
	}

	// Liefert den aktuellen Wert eines Rechtes einer Usergruppe, ist dieses nicht
	// explizit in righttogroup definiert, wird der default-Value aus rights zurrÃ¼ckgeliefert
	public function getRightForGroup($rightid, $groupid) 
	{
		$right = $this->getRight($rightid);
		if($defaultright === false)
			return false;
		$sql = "SELECT value FROM righttogroup WHERE rightid=? AND groupid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("ii", $rightid, $groupid);
		$stmt->execute();
		$stmt->bind_result($value);
		if($stmt->fetch()) {
			$right['value'] = $value;
			return $right;
		}
		else
			return false;
	}

	// Liefert alle Rechte fÃ¼r eine Usergruppe zurrÃ¼ck
	public function getRightsForGroup($groupid, $rightIDsInsteadOfRightNames = false) 
	{
		$stmt = $this->mysqli->prepare("SELECT rightid, rightname, defaultValue FROM rights");
		$stmt->execute();
		$stmt->bind_result($rightid, $rightname, $value);
		while($stmt->fetch()) {
			$rightArr[$rightid] = array('rightname'=>$rightname, 'value'=>$value);
		}
		$stmt->close();
		$stmt = $this->mysqli->prepare("SELECT rightid, `value` FROM righttogroup WHERE groupid=?");
		$stmt->bind_param("i", $groupid);
		$stmt->execute();
		$stmt->bind_result($rightid, $value);
		while ($stmt->fetch()) {
			if (isset($rightArr[$rightid])) {
				$rightArr[$rightid]['value'] = $value;
			}
		}
		
		foreach ($rightArr as $rightId => $rightNameArr) {
			if ($rightIDsInsteadOfRightNames) {
				$rights[$rightId] = $rightNameArr['value'];
			}
			else {
				$rights[$rightNameArr['rightname']] = $rightNameArr['value'];
			}
		}
		
		return $rights;
	}

	// Liefert den Wert eines Rechtes einer Benutzergruppe
	public function getRightByRightnameAndGroupid($rightname, $groupid) 
	{
		$sql = "SELECT rightid, defaultValue FROM rights WHERE rightname=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $rightname);
		$stmt->execute();
		$stmt->bind_result($rightid, $defaultValue);
		if($stmt->fetch()) {
			$stmt->close();
			$sql = "SELECT value FROM righttogroup WHERE rightid=? AND groupid=?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("ii", $rightid, $groupid);
			$stmt->execute();
			$stmt->bind_result($value);
			if($stmt->fetch())
				return $value;
			else
				return $defaultValue;
		}
		else
			return false;
	}
	
	
	/**
	 * Löscht alle Rechte einer Gruppe
	 * @param int $groupid
	 * @return bool $status
	 */
	public function deleteRightsForGroup($groupid)
	{
		$sql = "DELETE FROM righttogroup WHERE groupid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $groupid);
		$stmt->execute();
		if (!$stmt->error) {
			return true;
		}
		else {
			return false;
		}
	}

    /************************\
    |       sessions	     |
    \************************/

	// Einfügen/Überschreiben einer Session und setzen der Spalte lastAccess
	// auf die aktuelle Zeit
	public function writeSession($sessionid, $data) 
	{
		$sql = "INSERT INTO sessions(id, data, lastAccess) VALUES(?, ?, ?)
				ON DUPLICATE KEY UPDATE data=?, lastAccess=?";
		$stmt = $this->mysqli->prepare($sql);
		$currentTime = time();
		$stmt->bind_param("ssisi", $sessionid, $data, $currentTime, $data, $currentTime);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// ZurrÃ¼ck der Daten einer Session
	public function readSession($sessionid) 
	{
		$sql = "SELECT data FROM sessions WHERE id=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $sessionid);
		$stmt->execute();
		$stmt->bind_result($data);
		if($stmt->fetch())
			return $data;
		else
			return false;
	}

	// Setzt lastAccess auf die aktuelle Zeit
	public function updateLastAccess($sessionid) 
	{
		$sql = "UPDATE sessions SET lastAccess=? WHERE id=?";
		$stmt = $this->mysqli->prepare($sql);
		$currentTime = time();
		$stmt->bind_param("is", $currentTime, $sessionid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// LÃ¶scht die Ã¼bergebene SessionID
	public function deleteSession($sessionid) 
	{
		$sql = "DELETE FROM sessions WHERE id=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $sessionid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	public function deleteOldSessions($dataOfExpire) 
	{
		$sql = "DELETE FROM sessions WHERE lastAccess<?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $dataOfExpire);
		$stmt->execute();
		return $stmt->affected_rows;
	}

	
    /************************\
    |     substitutions	     |
    \************************/

    // Eintragen einer Vertretung
    public function addSubstitution($date, $grade, $classes, $hour, $subject, $teacher, $status, $room, $supply, $postponement, $notice, $programm) 
	{
		$sql = "INSERT INTO substitutions
			(date, grade, classes, hour, subject, teacher, status, room, supply, postponement, notice, programm)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("iisssssssssi",
			$date,
			$grade,
			$classes,
			$hour,
			$subject,
			$teacher,
			$status,
			$room,
			$supply,
			$postponement,
			$notice,
			$programm
		);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
    }
	
	public function getSubstitutions()
	{
		$sql = "SELECT date, hour, grade, classes, subject, teacher, status, room, supply, postponement, notice, programm FROM substitutions
				ORDER BY date ASC, hour ASC, grade ASC";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($date, $hour, $grade, $classes, $subject, $teacher, $status, $room, $supply, $postponement, $notice, $programm);
		$substitutions = array();
		while ($stmt->fetch()) {
			$substitutions[] = array(
										'data' => $date, 'hour' => $hour, 'grade' => $grade, 'classes' => $classes, 
										'subject' => $subject, 'teacher' => $teacher, 'status' => $status, 'room' => $room, 
										'supply' => $supply, 'postponement' => $postponement, 'notice' => $notice, 'programm' => $programm
									);
		}
		if (sizeof($substitutions)) {
			return $substitutions;
		}
		else {
			return false;
		}
	}
	
	// Berechnet den timestamp der $lesson'sten Stunde auf Basis des $timestamp
	private function _getTimestampLesson($lessonEndings, $lesson, $timestamp) 
	{
		if($lesson > sizeof($lessonEndings) || $lesson < 1)
			return false;
		$tmpTime = explode(":", $lessonEndings[$lesson]);
		$hours = $tmpTime[0];
		$minutes = $tmpTime[1];
		return $timestamp + ($hours * 3600 + $minutes * 60);
	}
	
	// Zurückliefern aller Vertretungsdaten einer Klasse
	public function getCurrentSubstitutionsForClass($class) 
	{
		$grade = substr($class, 0, 2);
		$lessonEnds = $this->getVariablesByPrefix('GLB_LESSONTIMES_ENDS_');
		foreach ($lessonEnds as $lessonEnd) {
			$lessonEndings[substr($lessonEnd['name'], -1, 1)] = $lessonEnd['value'];
		}
		$sql = "SELECT date, hour, subject, teacher, status, room, supply, postponement, notice FROM substitutions
				WHERE (
					grade=?
					AND (
						LOCATE(?, classes)
						OR (
							classes REGEXP '^[0-9]{2}-[0-9]{2}$' 
							AND CAST(SUBSTRING(classes,1,2) AS DECIMAL) <= ? 
							AND ? <= CAST(SUBSTRING(classes,4,2) AS DECIMAL)
						)
					)
				)
				
				ORDER BY date ASC, hour ASC, grade ASC";
				
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('isii', $grade, $class, $grade, $grade);
		$stmt->execute();
		$stmt->bind_result($date, $hour, $subject, $teacher, $status, $room, $supply, $postponement, $notice);
		
		$substitutions = array();
		while ($stmt->fetch()) {
			// Überprüfen ob die Stunde vielleicht schon vorbei ist
			if (strpos($hour, ' - ')) {
				$lastHour = substr($hour, -1, 1);
			}
			else {
				$lastHour = $hour[0];
			}
			$timestampOfLesson = $this->_getTimestampLesson($lessonEndings, $lastHour, $date);
			if ($timestampOfLesson >= time()) {
				$substitutions[$date][] = array('date' => $date, 'hour' => $hour, 'subject' => $subject, 'teacher' => $teacher, 
										 'status' => $status, 'room' => $room, 'supply' => $supply,
										 'postponement' => $postponement, 'notice' => $notice);
			}
		}
		if ($substitutions) {
			return $substitutions;
		}
		else {
			return false;
		}
	}
	
	// ZÃ¼rrckliefern aller aktuellen Daten vom Start- bis zum End-Jahrgang, welche noch nicht vorbei sind
	public function getCurrentSubstitutions($min, $max, $targetgroup) 
	{
		$lessonEnds = $this->getVariablesByPrefix('GLB_LESSONTIMES_ENDS_');
		foreach ($lessonEnds as $lessonEnd) {
			$lessonEndings[substr($lessonEnd['name'], -1, 1)] = $lessonEnd['value'];
		}
		if ($targetgroup == 'students') {
			$sql = "SELECT date, grade, classes, hour, subject, teacher, status, room, supply, postponement, notice FROM substitutions
					WHERE ? <= grade AND grade <= ? AND date >= ?
					ORDER BY date ASC, grade ASC, classes ASC, hour ASC";
		}
		else if ($targetgroup == 'teachers') {
			$sql = "SELECT date, grade, classes, hour, subject, teacher, status, room, supply, postponement, notice FROM substitutions
					WHERE ((? <= grade AND grade <= ?) OR grade=0) AND date >= ? AND status != 'Entfall'
					ORDER BY date ASC, supply ASC, teacher ASC, hour ASC";
		}
		$stmt = $this->mysqli->prepare($sql);
		$today = strtotime('today');
		$stmt->bind_param('iii', $min, $max, $today);
		$stmt->execute();
		$stmt->bind_result($date, $grade, $classes, $hour, $subject, $teacher, $status, $room, $supply, $postponement, $notice);
		
		$substitutions = array();
		
		while ($stmt->fetch()) {
			// ÃœberprÃ¼fen ob die Stunde vielleicht schon vorbei ist
			if (strpos($hour, ' - '))
				$lastHour = substr($hour, -1, 1);
			else
				$lastHour = $hour[0];
			$timestampOfLesson = $this->_getTimestampLesson($lessonEndings, $lastHour, $date);
			if ($timestampOfLesson >= time()) {
				$substitutions[] = array('date' => $date, 'grade' => $grade,'classes' => $classes, 'hour' => $hour, 
										 'subject' => $subject, 'teacher' => $teacher, 'status' => $status, 'room' => $room, 
										 'supply' => $supply, 'postponement' => $postponement, 'notice' => $notice);
			}
		}
		if ( count($substitutions) )
			return $substitutions;
		else
			return false;
	}
	
	public function deleteSubstitution($hash) {
		$sql = "DELETE FROM substitutions WHERE SHA1(CONCAT(date, hour, grade, classes, subject, teacher, status, room, supply, postponement, notice, programm)) LIKE ?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('s', $hash);
		$stmt->execute();
		if ($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
	}

    // LÃ¶scht alle Vertretungen
    public function clearSubstitutions() 
	{
		$sql = "TRUNCATE TABLE substitutions";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		if($stmt->affected_rows > 0)
			return true;
		else
			return false;
    }
    
    /**
     * Importiert alle dem $filenamePattern entsprechenden Untis-Vertretungsplan-Dateien im $importDir in die Datenbank
     */
    function importUntisSubstitutionFiles($importDir, $filenamePattern) {
    
    	$substfiles = \FileBrowser::searchFilesInDirectory($importDir, $filenamePattern);
    
    	// Alle vorhandenen Vertertungsdaten löschen
    	$this->clearSubstitutions();
    
    	// Die aus den Datein auszulesenden Kopfzeilen in das Array $columns einfügen
    	$registry = \Registry::getInstance();
    	
    	$neededVariables = array(
    							'GLB_PROGRAMMID_UNTISSUBSTFILE',
    							'USF_HEADLINE_CLASSES',
    							'USF_HEADLINE_HOUR',
						    	'USF_HEADLINE_SUBJECT',
						    	'USF_HEADLINE_TEACHER',
						    	'USF_HEADLINE_ROOM',
						    	'USF_HEADLINE_POSTPONEMENT',
						    	'USF_HEADLINE_SUPPLY',
						    	'USF_HEADLINE_STATUS',
						    	'USF_HEADLINE_NOTICE'
    						);
    	$undefinedVariables = $registry->defineVariables($neededVariables);
    	
    	if ( count($undefinedVariables) > 0 ) {
    		throw new \SystemException(__CLASS__.'-'.__METHOD__.": Es konnten nicht alle nötigen Variablen aus der Datenbank geladen werden. Es fehlen: ".implode(', ',$undefinedVariables));
    	}
    	
    	$untisProgrammID = GLB_PROGRAMMID_UNTISSUBSTFILE;
    	$columns = 	array(
				    	USF_HEADLINE_CLASSES,
				    	USF_HEADLINE_HOUR,
				    	USF_HEADLINE_SUBJECT,
				    	USF_HEADLINE_TEACHER,
				    	USF_HEADLINE_ROOM,
				    	USF_HEADLINE_POSTPONEMENT,
				    	USF_HEADLINE_SUPPLY,
				    	USF_HEADLINE_STATUS,
				    	USF_HEADLINE_NOTICE
    				);
    
    	$this->clearImportsByProgramm($untisProgrammID);
    
    	foreach ($substfiles as $substfile) {
			// Jede Reihe in der Vertretungsdatei in die Datenbank eintragen
    		$substitutions = new \UntisSubstitutionFile($substfile['path'], $columns);
    		
			foreach($substitutions as $row) {
			
				// Bei Freisetzungen der Form (08A1, 08B1, 08A2, 08B2, 08C2, 08D2) die Klammern entfernen
				//$classes = str_replace(array('(', ')'), '', $row[USF_HEADLINE_CLASSES]);
			
				// Für Klassenangaben wie 7 - 10 ein Datensatz pro betroffenen Jahrgang einfügen
				$grades = explode('-', $row[USF_HEADLINE_CLASSES]);
				sort($grades);
				
				$start = substr($grades[0], 0, 2);
				$end = substr(end($grades), 0, 2);
				foreach (range($start, $end) as $grade) {
					$this->addSubstitution(
						$substitutions->getTimestamp(),
						$grade, //substr($row[USF_HEADLINE_CLASSES], 0, 2),
						$row[USF_HEADLINE_CLASSES],
						$row[USF_HEADLINE_HOUR],
						$row[USF_HEADLINE_SUBJECT],
						$row[USF_HEADLINE_TEACHER],
						$row[USF_HEADLINE_STATUS],
						$row[USF_HEADLINE_ROOM],
						$row[USF_HEADLINE_SUPPLY],
						$row[USF_HEADLINE_POSTPONEMENT],
						$row[USF_HEADLINE_NOTICE],
						$untisProgrammID
					);
				}
			}
    		
    		$this->setImport($substfile['filename'], $substitutions->getFiletime(), $untisProgrammID);
    	}
    	$registry->GLB_LASTUPDATE_SUBSTITUTIONS = time();
    }

    
    /************************\
    |		  themes	     |
    \************************/

	// Hinzufügen eines Themes
	public function addTheme($themename, $themepath) 
	{
		$sql = "INSERT INTO themes(themename, path) VALUES(?,?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("ss", $themename, $themepath);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return $stmt->insert_id;
		}
		else {
			return false;
		}
	}

	// Editieren eines Themes
	public function editTheme($themeid, $themename, $themepath) 
	{
		$sql = "UPDATE themes SET themename=?, path=? WHERE themeid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("ssi", $themename, $themepath, $themeid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// LÃ¶schen eines Themes
	public function deleteTheme($themeid) 
	{
		$sql = "DELETE FROM themes WHERE themeid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $themeid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	// Liefert alle Themes zurrÃ¼ck
	public function getThemes() 
	{
		$stmt = $this->mysqli->prepare("SELECT themeid, themename, path FROM themes");
		$stmt->execute();
		$stmt->bind_result($themeid, $themename, $path);
		while ($stmt->fetch())
			$themes[] = array('themeid'=>$themeid, 'themename'=>$themename, 'path'=>$path);
		if ($themes)
			return $themes;
		else
			return false;
	}
	
	// Liefert alle Daten eines Themes zurrÃ¼ck
	public function getTheme($themeid) 
	{
		$stmt = $this->mysqli->prepare("SELECT themename, path FROM themes WHERE themeid=?");
		$stmt->bind_param("i", $themeid);
		$stmt->execute();
		$stmt->bind_result($themename, $path);
		if($stmt->fetch())
			return array('themeid'=>$themeid, 'themename'=>$themename, 'path'=>$path);
		else
			return false;
	}


    /************************\
    |      tickertext	     |
    \************************/
	
	

	/**
	 * Hinzufügen einer Tickernachricht
	 * @param array $usergroups
	 * @param string $message
	 * @param integer $poster
	 * @param integer $start_date
	 * @param integer $end_date
	 * @return bool $status
	 */
	public function addTickermessage($usergroups, $message, $poster, $start_date, $end_date) 
	{
		$usergroups = implode(',', $usergroups);
		$sql = "INSERT INTO tickermessages(usergroups, message, poster, start_date, end_date)
				VALUES (?, ?, ?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssii", $usergroups, $message, $poster, $start_date, $end_date);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return $stmt->insert_id;
		}
		else {
			return false;
		}
	}
	
	

	/**
	 * Löschen einer Tickernachricht
	 * @param integer $tickerid
	 * @return bool $status
	 */
	public function deleteTickermessage($tickerid) 
	{
		$sql = "DELETE FROM tickermessages WHERE tickerid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $tickerid);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
	}
	
	
	
	/**
	 * Gibt ein Array mit sämtlicher Daten aller Tickernachrichten zurück.
	 * @return array $tickermessages
	 */
	public function getTickermessages() 
	{
		$tickermessages = array();
		$sql = "SELECT tickerid, usergroups, message, poster, start_date, end_date FROM tickermessages";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($tickerid, $usergroups, $message, $poster, $start_date, $end_date);
		while ($stmt->fetch()) {
			$tickermessages[] = array(
										'tickerid' => $tickerid,
										'usergroups' => explode(',', $usergroups), 
										'message' => $message, 
										'poster' => $poster, 
										'start_date' => $start_date, 
										'end_date' => $end_date
									 );
		}
		if (!empty($tickermessages)) {
			return $tickermessages;
		}
		else {
			return false;
		}
	}

	
	
	/**
	 * Gibt ein Array mit sämtlichen Daten einer spezifischen Tickernachricht zurück
	 * @param integer $tickerid
	 * @return array $tickermessage
	 */
	public function getTickermessage($tickerid) 
	{
		$sql = "SELECT usergroups, message, poster, start_date, end_date
				FROM tickermessages WHERE tickerid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $tickerid);
		$stmt->execute();
		$stmt->bind_result($usergroups, $message, $poster, $start_date, $end_date);
		if($stmt->fetch())
			return array(
							'usergroups'=> explode(',', $usergroups),
							'message'=>$message,
							'poster'=>$poster,
							'start_date'=>$start_date,
							'end_date'=>$end_date
						);
		else {
			return false;
		}
	}
	
	
	
	/**
	 * Gibt alle Tickermessages die aktuell angezeigt werden fÃ¼r eine Gruppe zurück. Hierbei aber nur die Daten, welche für die Präsentation von Belang sind.
	 * @param integer $groupid
	 */
	public function getCurrentTickermessagesByGroupForPresentation($groupid) 
	{
		$sql = "SELECT message, end_date FROM tickermessages
				WHERE FIND_IN_SET(?, usergroups) > 0 AND start_date <= ? AND ? <= end_date
				ORDER BY start_date DESC";
		$stmt = $this->mysqli->prepare($sql);
		$currentTime = time();
		$stmt->bind_param('iii', $groupid, $currentTime, $currentTime);
		$stmt->execute();
		$stmt->bind_result($message, $end_date);
		$tickermessages = array();
		while ($stmt->fetch()) {
			$tickermessages[] = array(
										'message' => $message,
										'end_date' => $end_date
									);
		}
		if ( count($tickermessages) ) {
			return $tickermessages;
		}
		else {
			return false;
		}
	}

	
	
	/**
	 * Editiert eine Tickernachricht
	 * @param integer $tickerid
	 * @param array $usergroups
	 * @param string $message
	 * @param integer $poster
	 * @param integer $start_date
	 * @param integer $end_date
	 */
	public function editTickermessage($tickerid, $usergroups, $message, $poster, $start_date, $end_date) 
	{
		$usergroups = implode(',', $usergroups);
		$sql = "UPDATE tickermessages
				SET usergroups=?, message=?, poster=?, start_date=?, end_date=?
				WHERE tickerid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sssiii", $usergroups, $message, $poster, $start_date, $end_date, $tickerid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
	}

	
	
	/**
	 * Gibt alle Tickernachrichten einer Benutzergruppe zurück.
	 * @param integer $usergroupid
	 * @return array $tickermessages
	 */
	public function getTickermessagesByUsergroupid($usergroupid) 
	{
		$sql = "SELECT tickerid, message, poster, start_date, end_date
				FROM tickermessages WHERE FIND_IN_SET(?, usergroups) > 0";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $usergroupid);
		$stmt->execute();
		$stmt->bind_result($tickerid, $message, $poster, $start_date, $end_date);
		while($stmt->fetch())
			$tickermessages[] = array(
									'tickerid' => $tickerid,
									'message' => $message,
									'poster' => $poster,
									'start_date' => $start_date,
									'end_date' => $end_date
								);
		if(!empty ($tickermessages)) {
			return $tickermessages;
		}
		else {
			return false;
		}
	}
	
	/************************\
    |       userdata	     |
    \************************/
	
	/**
	 * Fügt einen Datensatz der Tabelle userdata hinzu
	 * @param int $userid
	 * @param string $dataname
	 * @param string $value
	 * @return boolean
	 */
	public function addUserdata($userid, $dataname, $value) 
	{
		$sql = "INSERT INTO userdata (userid, dataname, val) VALUES(?,?,?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('iss', $userid, $dataname, $value);
		$stmt->execute();
		if ($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Bearbeitet einen Datensatz der userdata Tabelle
	 * @param int $userid
	 * @param string $dataname
	 * @param string $newValue
	 */
	public function editUserdata($userid, $dataname, $newValue) 
	{
		$sql = "UPDATE userdata set val=? WHERE userid=? AND dataname=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('sis', $newValue, $userid, $dataname);
		$stmt->execute();
		if ($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Liefert den Wert einer bestimmten Benutzereigenschaft zurück oder bei Misserfolg <i>false</i>
	 * @param int $userid
	 * @param string $dataname
	 * @return string dataValue
	 */
	public function getUserdataValue($userid, $dataname) 
	{
		$sql = "SELECT val FROM userdata WHERE userid=? AND dataname=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('is', $userid, $dataname);
		$stmt->execute();
		$stmt->bind_result($value);
		if ($stmt->fetch()) {
			return $value;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Überprüft ob eine Benutzereigenschaft existiert.
	 * @param int $userid
	 * @param string $dataname
	 */
	public function issetUserdata($userid, $dataname) {
		$sql = "SELECT COUNT(*) FROM userdata WHERE userid=? AND dataname=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('is', $userid, $dataname);
		$stmt->execute();
		$stmt->bind_result($num);
		if ($num > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Liefert alle Eigenschaften eines Benutzers zurück
	 * @param int $userid
	 * @return array keyValuePairs
	 */
	public function getDataByUser($userid) 
	{
		$sql = "SELECT dataname, val FROM userdata WHERE userid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $userid);
		$stmt->execute();
		$stmt->bind_result($dataname, $value);
		while ($stmt->fetch()) {
			$userdata[$dataname] = $value;
		}
		if (!empty($userdata)) {
			return $userdata;
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * Löscht eine bestimmte Benutzereigenschaft. Bei Erfolg liefert sie true, und bei Misserfolg false zurück (auch wenn die Eigenschaft nicht existiert)
	 * @param int $userid
	 * @param string $dataname
	 * @return bool status
	 */
	public function deleteUserdata($userid, $dataname) 
	{
		$sql = "DELETE FROM userdata WHERE userid=? AND dataname=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('is', $userid, $dataname);
		$stmt->execute();
		if ($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false; 
		}
	}
	
	/**
	 * Löscht alle Eigenschaften eines Benutzers in der Tabelle userdata
	 * @param int $userid
	 * @return bool status
	 */
	public function deleteDataByUser($userid) 
	{
		$sql = "DELETE FROM userdata WHERE userid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $userid);
		$stmt->execute();
		return true;
	}

    /************************\
    |      usergroups	     |
    \************************/
	
	/**
	 * Hinzufügen einer Benutzergruppe
	 * @param string $groupname
	 * @param string $description
	 * @return bool status
	 */
    public function addUsergroup($groupname, $description) 
	{
		$sql = "INSERT INTO usergroups (groupname, description) VALUES(?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("ss", $groupname, $description);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
		    return $stmt->insert_id;
		}
		else {
		    return false;
		}
    }

    /**
     * Zurrückgeben des Gruppennamens und der Gruppenbeschreibung einer Gruppe
     * @param int $groupid
     * @return multitype: array |boolean
     */
    public function getUsergroup($groupid) 
	{
		$sql = "SELECT groupname, description FROM usergroups WHERE groupid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $groupid);
		$stmt->execute();
		$stmt->bind_result($groupname, $description);
		if($stmt->fetch()) {
		    return array('groupname'=>$groupname, 'description'=>$description);
		}
		else {
		    return false;
		}
    }
    
    /**
     * Edtieren einer Benutzergrupper
     * @param int $groupid
     * @param string $groupname
     * @param string $description
     * @return bool $status
     */
    public function editUsergroup($groupid, $groupname, $description)
    {
    	$sql = "UPDATE usergroups SET groupname=?, description=? WHERE groupid=?";
    	$stmt = $this->mysqli->prepare($sql);
    	$stmt->bind_param('ssi', $groupname, $description, $groupid);
    	$stmt->execute();
    	if (!$stmt->error) {
    		return true;
    	}
    	else {
    		return false;
    	}
    }

    
    /**
     * Die Gruppe der $groupd löschen
     * @param int $groupid
     * @return boolean status
     */
    public function deleteUsergroup($groupid) 
	{
		$sql = "DELETE FROM usergroups WHERE groupid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $groupid);
		$stmt->execute();
		if($stmt->affected_rows == 1)
			return true;
		else
			return false;
    }

    
	/**
	 * Liefert alle Benutzergruppen zurück
	 * @param bool $description Gibt an, ob man ebenfalls die Beschreibung alle Gruppen haben möchte.
	 * @return array $usergroups
	 */
	public function getUsergroups($description = false) 
	{
		if($description == false) {
			$sql = "SELECT groupid, groupname FROM usergroups";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->execute();
			$stmt->bind_result($groupid, $groupname);
			while($stmt->fetch()) {
				$usergroups[$groupid] = $groupname;
			}
			return $usergroups;
		}
		else {
			$sql = "SELECT groupid, groupname, description FROM usergroups";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->execute();
			$stmt->bind_result($groupid, $groupname, $description);
			while($stmt->fetch()) {
				$usergroups[$groupid] = array('groupname'=>$groupname, 'description'=>$description);
			}
			return $usergroups;
		}
	}
	
	
	/**
	 * Löschen alle Benutzer eine Benutzergruppe
	 * @param int $groupid
	 * @return bool $status
	 */
	public function deleteUsersByUsergroup($groupid)
	{
		$sql = "DELETE FROM userdata WHERE userid IN (SELECT userid FROM users WHERE usergroup=?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $groupid);
		$stmt->execute();
		$status = !$stmt->error;
		$stmt->close();
		
		$sql = "DELETE FROM users WHERE usergroup=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('i', $groupid);
		$stmt->execute();
		$status &= !$stmt->error;
		$stmt->close();
		
		return $status;
	}

    /************************\
    |	       users	     |
    \************************/

    /**
     * Hinzufügen eines Users in der users-Tabelle
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $usergroup
     * @return int $userid | false
     */
    public function registerUser($username, $password, $email, $usergroup) 
	{
		$stmt = $this->mysqli->prepare(
			'INSERT INTO users
			(username, password, email, usergroup, regdate, lastlogin)
			VALUES(?, ?, ?, ?, ?, ?)'
		);
		$currentTime = time();
		$stmt->bind_param('sssiii', $username, $password, $email, $usergroup, $currentTime, $currentTime);
		@$stmt->execute();
		if ($stmt->affected_rows == 1) {
			return $stmt->insert_id;
		}
		else {
			return false;
		}
    }

    /**
     * Überprüfen übergebener Logindaten
     * @param string $username
     * @param string $password
     * @return multitype: array (userid, usergroup) |boolean
     */
    public function checkLogin($username, $password) 
	{
		$sql = 'SELECT userid FROM users WHERE username=? AND password=?';
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param('ss', $username, $password);
		$stmt->execute();
		$stmt->bind_result($userid);
		if($stmt->fetch()) {
			return $userid;
		}
		else {
			return false;
		}
    }

    
    /**
     * Editiert die Benutzerdaten einer gewählten UserID
     * @param int $userid
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $usergroup
     * @return boolean $status
     */
    public function editUser($userid, $username, $password, $email, $usergroup) 
	{
		if ($password == '') {
			$sql = "UPDATE users
					SET username=?, email=?, usergroup=?
					WHERE userid=?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("ssss", $username, $email, $usergroup, $userid);
		}
		else {
			$sql = "UPDATE users
					SET username=?, password=?, email=?, usergroup=?
					WHERE userid=?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("sssii", $username, $password, $email, $usergroup, $userid);
		}
		
		$stmt->execute();
		if($stmt->affected_rows == 1 || $stmt->sqlstate == '00000') {
			return true;
		}
		else {
			return false;
		}
    }

    /**
     * Löscht den User mit der übergebenen UserID
     * @param int $userid
     * @return boolean $status
     */
    public function deleteUser($userid) 
	{
		$sql = "DELETE FROM users WHERE userid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $userid);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
    }

    /**
     * Gibt die Daten eines Benutzers zurück
     * @param int $userid
     * @return multitype: array $user | boolean false
     */
	public function getUser($userid) 
	{
		$sql = "SELECT username, password, email, usergroup, regdate, lastlogin FROM users WHERE userid=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $userid);
		$stmt->execute();
		$stmt->bind_result($username, $password, $email, $usergroup, $regdate, $lastlogin);
		if ($stmt->fetch()) {
			return array(
							'username' => $username,
							'password' => $password,
							'email' => $email,
							'usergroup' => $usergroup,
							'regdate' => $regdate,
							'lastlogin' => $lastlogin
						);
		}
		else {
			return false;
		}
	}
	
	/**
	 * Gibt Daten zu allen Benutzern zurück, dem außer Passwort
	 * @return multitype: array $users | boolean
	 */
	public function getUsers() 
	{
		$sql = "SELECT userid, username, email, usergroup, regdate, lastlogin FROM users";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($userid, $username, $email, $usergroup, $regdate, $lastlogin);
		while ($stmt->fetch()) {
			$users[] = array(
							'userid' => $userid,
							'username' => $username,
							'email' => $email,
							'usergroup' => $usergroup,
							'regdate' => $regdate,
							'lastlogin' => $lastlogin
							);
		}
		if (!empty($users)) {
			return $users;
		}
		else {
			return false;
		}
	}

	/**
	 * Setzt den letzten Login Zeitstempel
	 * @param int $userid
	 * @return bool $status
	 */
	public function setLastLogin($userid) 
	{
		$stmt = $this->mysqli->prepare("UPDATE users SET lastlogin=? WHERE userid=?");
		$currentTime = time();
		$stmt->bind_param("ii", $currentTime, $userid);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
	}


	/************************\
    |	     variables	     |
    \************************/

    /**
     * Eine Variable hinzufügen
     * @param string $name
     * @param string $value
     * @param string $description
     * @return boolean $status
     */
    public function addVariable($name, $value, $description) 
	{
		$sql = "INSERT INTO variables
			(name, value, description)
			VALUES(?, ?, ?)";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("sss", $name, $value, $description);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
    }
	
	/**
	 * Gibt den Wert und die Beschreibung der Variable zurrück
	 * @param string $name
	 * @return multitype: array $variable | false
	 */
	public function getVariable($name) {
		$sql = "SELECT value, description FROM variables WHERE name=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->bind_result($value, $description);
		if($stmt->fetch()) {
			return array( 'name' => $name, 'value' => $value, 'description' => $description );
		}
		else {
			return false;
		}
	}

    /**
     * Gibt den Wert der Variable zurrück
     * @param string $name
     * @return string $value | false
     */
    public function getValue($name) 
	{
		$sql = "SELECT value FROM variables WHERE name=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->bind_result($value);
		if($stmt->fetch()) {
			return $value;
		}
		else {
			return false;
		}
    }
    
    /**
     * Gib den Wert der Variablen zurrück
     * @param array $variableNames
     * @return array $variables
     */
    public function getValues($variableNames)
    {
    	$sql = "SELECT name, value FROM variables WHERE name=?";
    	$types = "s";
    	for ($varIdx = 1; $varIdx < count($variableNames); $varIdx++) {
    		$sql .= " OR name=?";
    		$types .= "s";
    	}
    	$stmt = $this->mysqli->prepare($sql);
    	$parameters = array_merge(array($types), $variableNames);
    	call_user_func_array(array($stmt, "bind_param"), $this->makeValuesReferenced($parameters));
    	$stmt->execute();
    	$stmt->bind_result($name, $value);
    	$foundVariables = array();
    	while ($stmt->fetch()) {
    		$foundVariables[$name] = $value;
    	}
    	return $foundVariables;
    }
    
    private function makeValuesReferenced($arr)
    {
    	$arrWithReferencedValues = array();
    	foreach($arr as $key => $value) {
    		$arrWithReferencedValues[$key] = &$arr[$key];
    	}
    	return $arrWithReferencedValues;
    }
    

    /**
     * Den Wert und/oder Beschreibung einer gegebenen Variable verändern
     * @param string $name
     * @param string $value
     * @param string $description=false
     * @return bool $status
     */
    public function editVariable($name, $value, $description=false) 
	{
		if ($description !== false) {
			$sql = "UPDATE variables
				SET value=?, description=?
				WHERE name=?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("sss", $value, $description, $name);
			$stmt->execute();
		}
		else {
			$sql = "UPDATE variables SET value=? WHERE name=?";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param('ss', $value, $name);
			$stmt->execute();
		}
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
    }

    /**
     * Löscht eine Variable
     * @param string $name
     * @return bool $status
     */
    public function deleteVariable($name) 
	{
		$sql = "DELETE FROM variables WHERE name=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("s", $name);
		$stmt->execute();
		if($stmt->affected_rows == 1) {
			return true;
		}
		else {
			return false;
		}
    }
    
    /**
     * Überprüft ob eine Variable existiert
     * @param sting $name
     * @return bool $status
     */
    public function issetVariable($name)
    {
    	$sql = "SELECT COUNT(*) FROM variables WHERE name=?";
    	$stmt = $this->mysqli->prepare($sql);
    	$stmt->bind_param("s", $name);
    	$stmt->execute();
    	$stmt->bind_result($count);
    	$stmt->fetch();
    	$stmt->close();
    	if ($count > 0) {
    		return true;
    	}
    	else {
    		return false;
    	}
    }

    /**
     * Zurrückgeben aller Variablen und Werte. Fallse true übergeben wird so werden auch die Beschreibungen zuückgegeben
     * @param bool $getDescription
     * @return array $variables
     */
    public function getVariables($getDescription=false) 
	{
		$variables = array();
		if ($getDescription == false) {
			$sql = "SELECT name, value FROM variables ORDER BY name ASC";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->execute();
			$stmt->bind_result($name, $value);
			while($stmt->fetch())
			$variables[] = array('name' => $name, 'value' => $value);
			return $variables;
		}
		else {
			$sql = "SELECT name, value, description FROM variables ORDER BY name ASC";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->execute();
			$stmt->bind_result($name, $value, $description);
			while($stmt->fetch())
			$variables[] = array('name' => $name, 'value' => $value, 'description' => $description);
			return $variables;	
		}
    }

    /**
     * Zurückgeben eines name=>value Arrays mit Variablen die den übergebenen Präfix im Namen haben
     * @param string $namePrefix
     * @return array $variables
     */
    public function getVariablesByPrefix($namePrefix) 
	{
		$sql = "SELECT name, value FROM variables WHERE name LIKE ?";
		$stmt = $this->mysqli->prepare($sql);
		$namePrefix .= '%';
		$stmt->bind_param("s", $namePrefix);
		$stmt->execute();
		$stmt->bind_result($name, $value);
		$variables = array();
		while($stmt->fetch()) {
			$variables[] = array('name' => $name, 'value' => $value);
		}
		return $variables;
    }
}
?>