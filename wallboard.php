<?php
require_once __DIR__ . "/game.php";
$gameInfoString = Np2_Game::getGameInfo($_GET['game'], !empty($_GET['nocache']));
$gameInfo = json_decode($gameInfoString, true);
//die(str_replace("\n", "<br>", preg_replace("/\ /", "&nbsp;", var_export(array_keys(array_diff_key($gameInfo['stars'][4], $gameInfo['stars'][3])), true))));
//die(str_replace("\n", "<br>", preg_replace("/\ /", "&nbsp;", var_export($gameInfo['stars'], true))));
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
		<div id="player_template" class="player">
			<div class="player_head">
				<div class="player_head_inner">
					<div class="player_ring"></div>
					<div class="player_name"></div>
				</div>
			</div>
			<div class="player_stats"></div>
			<div class="player_tech"></div>
		</div>
	</div>
	<div id="pane_right">
		<div id="pane_stars">
			<div id="star_container"></div>
		</div>
		<div id="pane_bottom">
			<div class="title-area">
				<div id="game_status"></div>
				<div id="game_title"></div>
			</div>
			<div class="timer-container">
				<div id="game_timer"></div>
			</div>
		</div>
	</div>
</body>
</html>
