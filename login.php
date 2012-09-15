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


if ( isset($_GET['action']) ) 
{
	$action = $_GET['action'];
	
	// Der Benutzer versucht sich einzuloggen
	if ( $action == 'login' && isset($_POST['user']) && isset($_POST['pass']) ) 
	{
		require_once 'autoloader.inc.php';
		require_once(_INCLUDE_FILES_PATH_.'config.inc.php');
		require_once(_INCLUDE_FILES_PATH_.'sessionmanagement.inc.php');
		
		$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$username = htmlspecialchars($_POST['user']);
		$password = sha1(htmlspecialchars($_POST['pass']));
		
		$user = User::login($dataBase, $username, $password);
		
		if ($user === false) 
		{
			echo json_encode( array('loginstatus' => 'fail') );
			die();
		}
		
		$outputprofiles = $dataBase->getOutputprofilesByGroup($user->usergroup);
		
		if ($user !== false && $outputprofiles !== false)
		{
			
			$_SESSION['USER'] = $user;
			$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
			
			$minGrade = $dataBase->getValue('GLB_OUTPUT_MIN-GRADE');
			$maxGrade = $dataBase->getValue('GLB_OUTPUT_MAX-GRADE');
			
			foreach ($outputprofiles as $profile) 
			{
				$profiles[] = array('profileid'=>$profile['profileid'], 'profilename'=>$profile['profilename']);
			}
			echo json_encode( array('loginstatus' => 'ok','minGrade' => $minGrade, 'maxGrade' => $maxGrade, 'outputprofiles' => $profiles) );
		}
		else 
		{
			echo json_encode( array('loginstatus' => 'fail') );
		}
		die();
	}
	
	// Einstellungen wurden gewählt und Session soll beginnen
	if ($action == 'setSettings') 
	{
		if (!isset($_GET['minGrade']) || !is_numeric($_GET['minGrade'])
			|| !isset($_GET['maxGrade']) || !is_numeric($_GET['maxGrade'])
			|| !isset($_GET['outputprofileid']) || !is_numeric($_GET['outputprofileid'])
			|| !isset($_GET['save'])
		) 
		{
			echo json_encode( array('error' => 'MISSING_DATA') );
			die();
		}
		require_once 'autoloader.inc.php';
		require_once(_INCLUDE_FILES_PATH_.'config.inc.php');
		require_once(_INCLUDE_FILES_PATH_.'sessionmanagement.inc.php');
		
		$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
		
		$minGrade = $_GET['minGrade'];
		$maxGrade = $_GET['maxGrade'];
		$outputprofileid = $_GET['outputprofileid'];
		$shouldSave = $_GET['save'];
		
		// Überprüfung ob das gewählte Ausgabeprofil vom User benutzt werden darf
		$outputprofiles = $dataBase->getOutputprofilesByGroup($_SESSION['USER']->usergroup);
		$allowedToUseProfile = false;
		foreach ($outputprofiles as $profile) 
		{
			if ($profile['profileid'] == $outputprofileid)
			{
				$allowedToUseProfile = true;
			}
		}
		
		if (!$allowedToUseProfile)
		{
			echo json_encode(array('status'=>'fail', 'error'=>'NOT_ALLOWED_USE_PROFILE'));
			die();
		}
		
		$_SESSION['MINGRADE'] = $minGrade;
		$_SESSION['MAXGRADE'] = $maxGrade;
		$_SESSION['OUTPUTPROFILE'] = $outputprofileid;
		
		if ($shouldSave) 
		{
			setcookie('MINGRADE', $minGrade, time()+(60*60*24*365*10));
			setcookie('MAXGRADE', $maxGrade, time()+(60*60*24*365*10));
			setcookie('OUTPUTPROFILE', $outputprofileid, time()+(60*60*24*365*10));
		}
		echo json_encode( array('status' => 'success') );
		die();
	}
}
?>
<html>
<head>
	<title>Bitte loggen Sie sich ein</title>
	<link type="text/css" href="css/smoothness/jquery-ui.css" rel="stylesheet" />
	<link type="text/css" href="css/index.css" rel="stylesheet" />
	<script type="text/javascript" src="js/libs/jquery.js"></script>
	<script type="text/javascript" src="js/libs/jquery-ui.js"></script>
	<script type="text/javascript" src="js/sha1.js"></script>
	<script type="text/javascript" src="js/login.js"></script>
</head>
<body>
	<div id="Login">
		<table width="100%">
			<tr>
				<td>Benutzername:</td><td><input name="user" id="user" type="text" maxlength="50" /></td>
			</tr>
			<tr>
				<td>Passwort:</td><td><input name="pass" id="pass" type="password" /></td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<button id="sendLogin">Login</button>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="Settings" style="visibility: hidden;">
		<table width="100%" cellspacing="10">
			<tr>
				<td>Von Jahrgang </td>
				<td> <select id="selVon"></select> </td>
			</tr>
			<tr>
				<td>Bis Jahrgang </td>
				<td> <select id="selBis"></select> </td>
			</tr>
			<tr>
				<td>Ausgabeprofil </td>
				<td><select id="selOutputprofile"></selected></td>
			</tr>
			<tr>
				<td align="left" colspan="2"> <font size="2px">Einstellungen speichern</font> <input id="save" type="checkbox" /> 
				<button id="sendSettings">OK</button> </td>
			</tr>
		</table>
	</div>
</body>
</html>
	