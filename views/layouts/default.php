<?php Session::start() ?>
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
		<?php if ($flash = flash()): ?>
			<div id="flash">
				<?php echo $flash ?>
			</div>			
		<?php endif ?>
		<div id="content">
			<?php View::yield() ?>					
		</div>
		<div id="footer">
			Powered By Microbus
		</div>
	</div>	
</body>
</html>