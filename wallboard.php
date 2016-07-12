<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/fontello-embedded.css" />
	<link rel="stylesheet" type="text/css" href="css/ringfont.css" />
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/wallboard.js"></script>
	<title>NP2 Display</title>
	<meta charset="UTF-8" />
</head>
<body>
	<div id="error">
		<div class="icon-attention-alt"></div>
		<div id="error_text"></div>
		<div class="reload-time" data-reload-msg="Retrying in %seconds% seconds..."></div>
	</div>
	<div id="pane_left">
		<div id="player_template" class="player">
			<div class="player-head">
				<div class="player-head-inner">
					<div class="player-ring"></div>
					<div class="player-name"></div>
				</div>
			</div>
			<div class="player-stats">
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
			<div class="star-loader"></div>
			<div id="star_container"></div>
		</div>
		<div id="pane_bottom">
			<div class="title-area">
				<div id="game_status"></div>
				<div id="game_title">&nbsp;</div>
			</div>
			<div class="timer-container">
				<div id="game_timer"></div>
			</div>
		</div>
	</div>
</body>
</html>
