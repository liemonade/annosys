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

if (isset($_POST['user']) && isset($_POST['pass']))
{
	require_once 'autoloader.inc.php';

	require_once _INCLUDE_FILES_PATH_.'config.inc.php';
	require_once _INCLUDE_FILES_PATH_.'sessionmanagement.inc.php';
	
	$dataBase = \DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
	
	$username = htmlspecialchars($_POST['user']);
	$password = sha1(htmlspecialchars($_POST['pass']));

	$user = User::login($dataBase, $username, $password);
	
	if ($user !== false && $user->right('CAN_ENTER_ACP')) {
		$_SESSION['USER'] = $user;
		$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
		echo json_encode(array( 'loginstatus' => 'ok' ));
	}
	else {
		echo json_encode(array( 'loginstatus' => 'fail' ));
	}
	
	die();
}
?>
<html>
<head>
	<title>Bitte loggen Sie sich ein</title>
	<link type="text/css" href="../css/base/jquery.ui.all.css" rel="stylesheet" />
	<link type="text/css" href="css/index.css" rel="stylesheet" />
	<script type="text/javascript" src="../js/libs/jquery.js"></script>
	<script type="text/javascript" src="../js/libs/jquery-ui.js"></script>
	<script type="text/javascript" src="js/login.js"></script>
</head>
<body>
	<div id="Login" >
		<form autocomplete="on" onSubmit="return false;">  
			<table width="100%">
				<tr>
					<td>Benutzername:</td><td><input name="user" type="text" maxlength="50" /></td>
				</tr>
				<tr>
					<td>Passwort:</td><td><input name="pass" type="password" /></td>
				</tr>
				<tr>
					<td colspan="2" align="right">
						<button id="sendLogin">Login</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<div id="answer"></div>
</body>
</html>