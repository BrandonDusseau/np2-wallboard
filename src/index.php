<?php
require_once __DIR__ . "/game.php";
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
</body>
</html>
