<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Vertretungsplan</title>
	<link type="text/css" href="css/smoothness/jquery-ui.css" rel="stylesheet" />
	<link type="text/css" href="{%%THEME_PATH%%}css/main.css" rel="stylesheet" />
	<link type="text/css" href="{%%THEME_PATH%%}css/subst.css" rel="stylesheet" />
	<link type="text/css" href="js/libs/liscroller.css" rel="stylesheet" />
	<script type="text/javascript" src="js/libs/jquery.js"></script>
	<script type="text/javascript" src="js/libs/jquery-ui.js"></script>
	<script type="text/javascript" src="js/libs/liscroller.js"></script>
	<script type="text/javascript">
		var targetgroup = '{%%TARGETGROUP%%}';
	</script>
	<script type="text/javascript" src="{%%THEME_PATH%%}js/main.js"></script>
</head>
<body class="main">
	<div id="mainFrame">
		<div id="mainFrameContent" height="96%" width="96%" frameborder="0" scrolling="no"></div>
	</div>
	<div id="footer">
		<div class="generalInfos">
			<div class="lastEdit">
				<img src="{%%THEME_PATH%%}images/edit.png" class="images" />
				<p class="text" id="lastUpdate">
					&nbsp;
				</p>
			</div>
			<div class="clock">
				<img src="{%%THEME_PATH%%}images/clock.png" class="images" />
				<p class="text" id="timeNow">
					&nbsp;
				</p>
			</div>
			<div class="news">
				<img src="{%%THEME_PATH%%}images/news.png" class="newsImages" />
			</div>
		</div>
		
	</div>
	<div class="tickercontainer">
		<ul id="ticker">
				<li><span>&nbsp;</span></li>
		</ul>
	</div>
</body>