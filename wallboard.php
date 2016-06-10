<?php
require_once __DIR__ . "/game.php";
$gameInfoString = Np2_Game::getGameInfo($_GET['game'], !empty($_GET['nocache']));
$gameInfo = json_decode($gameInfoString, true);
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/fontello-embedded.css" />
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
			<div class="player_stats">
				<div class="stat stars">
					<div class="stat-inner">
						<div class="stat-icon icon-star"></div>
						<span></span>
					</div>
				</div>
					<div class="stat ships">
						<div class="stat-inner">
							<div class="stat-icon icon-rocket"></div>
							<span></span>
						</div>
					</div>
				<div class="stat economy">
					<div class="stat-inner">
						<div class="stat-icon icon-dollar"></div>
						<span></span>
					</div>
				</div>
				<div class="stat industry">
					<div class="stat-inner">
						<div class="stat-icon icon-tools"></div>
						<span></span>
					</div>
				</div>
				<div class="stat science">
					<div class="stat-inner">
						<div class="stat-icon icon-college"></div>
						<span></span>
					</div>
				</div>
			</div>
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
