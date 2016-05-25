<?php
require_once __DIR__ . "/game.php";
?>
<!DOCTYPE html>
<html>
<head>
	<title>NP2 Wallboard Index</title>
	<meta charset="UTF-8" />
</head>
<body>
	<h2>Select a game</h2>
	<?php
		// Load the list of games
		$games = json_decode(Np2_Game::getGameInfo(), true);
		if (!empty($games['error']))
		{
			die("There was an error loading games.</body></html>");
		}

		if (empty($games))
		{
			echo "No games available at this time.";
		}
		else
		{
			foreach ($games as $game)
			{
				echo "<a href='wallboard.php?game={$game['gameId']}'>{$game['name']}</a><br />" . PHP_EOL;
			}
		}
	?>
</body>
</html>
