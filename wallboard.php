<?php
require_once __DIR__ . "/game.php";
$gameInfoString = Np2_Game::getGameInfo(true, $_GET['game'], !empty($_GET['nocache']));
$gameInfo = json_decode($gameInfoString, true);
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/wallboard.js"></script>
	<title>NP2 Wallboard</title>
	<meta charset="UTF-8" />
</head>
<body>
	<div id="data" style="display:none;"><?php echo $gameInfoString; ?></div>
	<div id="pane_left">
		<?php
			foreach ($gameInfo['players'] as $player)
			{
				echo $player['name'] . "<br />";
			}
		?>
	</div>
	<div id="pane_right">
		<div id="pane_stars">
			<div id="star_container"></div>
		</div>
		<div id="pane_bottom">
			<div class="title-area">
				<div id="game_title"></div>
				<div id="game_status">PAUSED</div>
			</div>
			<div class="timer-container">
				<div id="game_timer"></div>
			</div>
		</div>
	</div>
</body>
</html>
