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


if (!isset($_SESSION['USER']) || !isset($_SESSION['IP']) || $_SESSION['USER']->right('CAN_SEE_EXTRAPAGES') != 'true' || $_SESSION['IP'] != $_SERVER['REMOTE_ADDR'] ) {
	session_destroy();
	header('Location: login.php');
	exit('Bitte <a href="login.php">HIER</a> einloggen!');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Extraseiten Editor</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="stylesheet" href="../js/libs/jwysiwyg/css/jquery.wysiwyg.css" type="text/css"/>
<link rel="stylesheet" href="../js/libs/jwysiwyg/css/jquery.wysiwyg.modal.css" type="text/css"/>
<link rel="stylesheet" href="../js/libs/jquery.simplemodal.css" type="text/css"/>
<link type="text/css" href="../css/smoothness/jquery-ui.css" rel="stylesheet" />
<link type="text/css" href="css/jqueryui-settings.css" rel="stylesheet" />
<script type="text/javascript" src="../js/libs/jquery.js"></script>
<script type="text/javascript" src="../js/libs/jquery-ui.js"></script>
<script type="text/javascript" src="../js/libs/jwysiwyg/jquery.wysiwyg.js"></script>
<script type="text/javascript" src="../js/libs/jquery.simplemodal.js"></script>
<?php if (isset($_GET['action']) && $_GET['action'] == 'edit') echo '<script type="text/javascript" src="js/preview.js"></script>' ?>
<style type="text/css" rel="stylesheet">
	body{
		background-image: url(images/screenshot.jpg);
	}
	.wysiwyg {
		position: absolute;
		top: 35px;
		left: 60px;
	}
	#main{
		position: absolute;
		top: 39px;
		left: 60px;
		width: 1160px;
		height: 600px;
	}
	
	#pictureBox {
		width:326px;
		height:300px;
		top: 0px;
		left: 0px;
		background-color:#fff;
		position:absolute;
		z-index:10000000;
		visibility: hidden;
		overflow:auto;
		border: 2px solid #000;
	}

	#pictureBox img.uploadedImage{
		max-height: 281px;
		max-width: 281px;
		margin:10px;
		border: 1px solid #ccc;
	}

	#pictureBox img.uploadedImageMouseover {
		border: 3px solid #f00;
	}
</style>
</head>
<body>
	<?php
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
		if($action == 'edit') {
			echo '<textarea id="wysiwyg" rows="37" cols="144">';
		}
		else {
			echo '<div id="main">';
		}
		if (isset($_GET['pageid'])) {
			$dataBase = DataBase::getInstance(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DATABASE);
			if($extrapage = $dataBase->getExtrapage($_GET['pageid'])){
				echo $extrapage['code'];
			}
		}
		if ($action == 'edit'){
			echo '</textarea>';
		}
		else {
			echo '</div>';
		}
	}
	?>
	
</body>
</html>