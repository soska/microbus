<!DOCTYPE HTML>
<html lang="ru-RU">
<head>
	<meta charset="UTF-8">
	<title><?php echo $pageTitle ?></title>
	<style type="text/css">
		@import url(css/styles.css);
	</style>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1><?php echo $pageTitle ?></h1>
		</div>
		<div id="content">
			<?php View::output() ?>					
		</div>
	</div>	
</body>
</html>