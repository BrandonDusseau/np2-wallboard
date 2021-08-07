<?php
require_once __DIR__ . "/game.php";
$app_version = "#APPVERSION#";
?>
<!DOCTYPE html>
<html>
<head>
	<title>NP2 Display :: Game List</title>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="css/list.css">
</head>
<body>
	<div class="game-list">
		<div class="games">
			<h1>Select a game</h1>
			<?php
				$game_list = json_decode(Np2_Game::getGameInfo(null), true);

				if ($game_list == false)
				{
					echo "<div class='error'>Unable to load game list at this time. Please try again later.</div>";
				}
				elseif (isset($game_list['error']))
				{
					echo "<div class='error'>An error has occurred<br>" . $game_list['error'] . "</div>";
				}
				elseif (empty($game_list["games"]))
				{
					echo "No games are currently available to view.";
				}
				else
				{
					foreach ($game_list["games"] as $game)
					{
						$game_id = $game["id"];
						$game_name = $game["name"];
						echo "<a href='wallboard.php?game={$game_id}'><span>{$game_name}</span></a>";
					}
				}
			?>
		</div>
		<div class="version">
			NP2 Wallboard v<?php echo (strpos($app_version, "APPVERSION") !== false) ? "Dev" : $app_version; ?>
			&middot;
			&copy;2016-2021 Brandon Dusseau
			&middot;
			<a href="https://github.com/BrandonDusseau/np2-wallboard">GitHub</a>
		</div>
	</div>
</body>
</html>
