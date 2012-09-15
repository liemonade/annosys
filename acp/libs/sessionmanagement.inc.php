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

require_once('config.inc.php');

// Öffnet die Session Datenbank
function _open($pfad, $name) 
{
	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
}

// Schließt die Verbindung mit der Session Datenbank
function _close() 
{
	// Das Schließen der Datenbankverbindung übernimmt der Destruktor von DataBase
}

// Liest die Daten einer Session ein
function _read($sessionid) 
{
	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	
	$data = $dataBase->readSession($sessionid);
	if($data === false)
	{
		$data = '';
	}
	$dataBase->updateLastAccess($sessionid);
	return $data;
}

// Erzeugt oder Überschreibt eine Session
function _write($sessionid, $data) 
{
	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	$success = $dataBase->writeSession($sessionid, $data);
	return $success;
}

// Löscht eine Session
function _destroy($sessionid) 
{
	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	$success = $dataBase->deleteSession($sessionid);
	return $success;
}

// Garbage Collection - Löscht Sessions die vor lÃ¤ngerer Zeit als der $lifespan
// zuletzt aktualisiert wurden.
function _gc($lifespan)
{
	$dateOfExpire = time() - $lifespan;
	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	$dataBase->deleteOldSessions($dateOfExpire);
	return true;
}

// Die Lebensdauer eine Session ist 900s = 15min und der GarbageCollector
// soll in ein drittel = (33/100) aller Seitenaufrufe gestartet werden
ini_set('session.gc_maxlifetime', 900);
ini_set('session.gc_probability', 33);
ini_set('session.gc_divisor', 100);
session_set_save_handler("_open", "_close", "_read", "_write", "_destroy", "_gc");
session_start();
?>