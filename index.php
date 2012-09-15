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


	require_once 'autoloader.inc.php';

	require_once _INCLUDE_FILES_PATH_.'config.inc.php';
	require_once _INCLUDE_FILES_PATH_.'sessionmanagement.inc.php';

	$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);

	if ( !isset($_SESSION['USER']) || !isset($_SESSION['IP']) || !isset($_SESSION['MINGRADE']) 
		 || !isset($_SESSION['MAXGRADE']) || !isset($_SESSION['OUTPUTPROFILE']) || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] )
	{
		if (!isset($_SESSION['USER']) && !empty($_COOKIE)) {
			$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
			$status = User::startSubstSession($dataBase, $_COOKIE);
			
			if (!$status) {
				session_destroy();
				header('Location: login.php');
				exit('Bitte <a href="login.php">HIER</a> einloggen!');
			}
		}
		else {
			session_destroy();
			header('Location: login.php');
			exit('Bitte <a href="login.php">HIER</a> einloggen!');
		}
	}

	// Session usw. sind gesetzt. Die Präsentation kann mit dem ausgewählten
	// Ausgabeprofil losgehen
	$outputprofile = $dataBase->getOutputprofile($_SESSION['OUTPUTPROFILE']);
	$theme = $dataBase->getTheme($outputprofile['theme']);

	$themeVars = array();
	$varPrefix = 'OUTPUTPROFILE_'.strtoupper($outputprofile['profilename']).'_';
	$variables = $dataBase->getVariablesByPrefix($varPrefix);
	if ($variables != false)
	{
		foreach ($variables as $variable)
		{
			$templateVarName = substr($variable['name'], strlen($varPrefix));
			$themeVars[$templateVarName] = $variable['value'];
		}
	}
	
	$themeVars['THEME_PATH'] = $theme['path'];
	
	
	require_once('acp/libs/global.functions.php');
	$pathToMainTpl = $theme['path'].'main.tpl';
	echo \Template::fillTemplate($pathToMainTpl, $themeVars);

?>