<?php
require_once __DIR__ . "/game.php";
?>
<!DOCTYPE html>
<html>
<head>
	<title>NP2 Wallboard</title>
	<meta charset="UTF-8" />
</head>
<body>
	<?php
		if (empty($_GET['game']) || !is_numeric($_GET['game']))
		{
			die("No game selected.</body></html>");
		}

		echo "<pre>" . Np2_Game::getGameInfo(true, $_GET['game'], !empty($_GET['nocache'])) . "</pre>" . PHP_EOL;
	?>
</body>
</html>
