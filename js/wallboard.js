var data, productionRate, productionCounter, tickRate, tickFragment, timeNow, localTimeCorrection, stars, players, paused;

$(document).ready(
	function ()
	{
		data = JSON.parse($("#data").html());
		productionRate = data.production_rate || 0;
		productionCounter = data.production_counter || 0;
		tickRate = data.tick_rate || 0;
		tickFragment = data.tick_fragment || 0;
		timeNow = new Date(data.now) || 0;
		localTimeCorrection = timeNow.valueOf() - (new Date).valueOf();
		stars = data.stars || [];
		players = data.players || [];
		paused = data.paused || false;

		if (stars.length)
		{
			var starContainer = $("#star_container");
			if (starContainer.length)
			{
				// Get the extreme location values
				var starMaxX = 0;
				var starMaxY = 0;
				var starMinX = 0;
				var starMinY = 0;
				for (var st = 0; st < stars.length; ++st)
				{
					var star = stars[st];
					var starX = star.position.x;
					var starY = star.position.y;
					starMaxX = (starX > starMaxX ? starX : starMaxX);
					starMaxY = (starY > starMaxY ? starY : starMaxY);
					starMinX = (starX < starMinX ? starX : starMinX);
					starMinY = (starY < starMinY ? starY : starMinY);
				}

				var starXSpan = starMaxX - starMinX;
				var starYSpan = starMaxY - starMinY;

				for (var st = 0; st < stars.length; ++st)
				{
					var star = stars[st];
					var starId = star.uid;
					var starElement = $(".star[data-star-id='" + starId + "']");
					if (!starElement.length)
					{
						starElement = $("<div class='star' data-star-id='" + starId + "'></div>");
						starContainer.append(starElement);
					}

					var starPosX = ((star.position.x - starMinX) / starXSpan) * 100;
					var starPosY = ((star.position.y - starMinY) / starYSpan) * 100;
					starElement.css("left", starPosX + "%").css("top", starPosY + "%");

					// Set star color
					if (typeof star.player != "undefined" && star.player != -1 &&
						typeof players[star.player] != "undefined")
					{
						starElement.css("border-color", players[star.player].color);
					}
					else
					{
						starElement.css("border-color", "#000");
					}
				}
			}
		}

		if (players.length)
		{
			var playerContainer = $("#pane_left");
			if (playerContainer.length)
			{
				for (var pl = 0; pl < players.length; ++pl)
				{
					var player = players[pl];
					var playerElement = $(".player[data-player-id='" + player.uid + "']");
					if (!playerElement.length)
					{
						playerElement = $("#player_template").clone();
						playerElement.removeAttr("id");
						playerElement.attr("data-player-id", player.uid);
						playerContainer.append(playerElement);
					}

					// Set player order, by rank
					playerElement.css('order', player.rank);

					// Set player's name
					var name = playerElement.find(".player_name");
					if (name.length)
					{
						name.html(player.name);
						name.removeClass("dead");
						if (player.conceded)
						{
							name.addClass("dead");
						}
					}

					// Set player's ring color
					var ring = playerElement.find(".player_ring");
					if (ring.length)
					{
						ring.css("border-color", player.color);
					}

					// Set player's star stat
					var starEl = playerElement.find('.stat.stars span');
					if (starEl.length)
					{
						starEl.html(player.total_stars);
					}

					// Set player's ships stat
					var shipEl = playerElement.find('.stat.ships span');
					if (shipEl.length)
					{
						shipEl.html(player.total_strength);
					}

					// Set player's econ stat
					var econEl = playerElement.find('.stat.economy span');
					if (econEl.length)
					{
						econEl.html(player.total_economy);
					}

					// Set player's industry stat
					var indEl = playerElement.find('.stat.industry span');
					if (indEl.length)
					{
						indEl.html(player.total_industry);
					}

					// Set player's science stat
					var eciEl = playerElement.find('.stat.science span');
					if (eciEl.length)
					{
						eciEl.html(player.total_science);
					}
				}
			}
		}

		$("#game_title").html(data.name);
		$("#game_timer").html(timeToProduction());

		// Update game status indicator
		if (!paused)
		{
			$("#game_status").removeClass("paused");
			$("#game_status").html(timeToTick(1));
		}
		else
		{
			$("#game_status").addClass("pause");
			$("#game_status").html("PAUSED");
		}

		window.setInterval(
			function ()
			{
				if (!paused)
				{
					$("#game_timer").html(timeToProduction());
					$("#game_status").html(timeToTick(1));
				}
			},
			500
		);
	}
);

/**
 * Returns time to the end of the galactic cycle.
 * @return {string} Formatted time to production.
 */
function timeToProduction()
{
	return timeToTick(productionRate - productionCounter);
}

/**
 * Returns time to the next tick.
 * @param  {int} ticks Number of ticks to which time should be calculated.
 * @return {string} Formatted time to tick.
 */
function timeToTick(ticks)
{
	// Use wacky formula from the game to determine the time
	return formatTime((60000 * tickRate * ticks) - (60000 * tickFragment * tickRate) - ((new Date).valueOf() - timeNow.valueOf()) - localTimeCorrection);
}

/**
 * Outputs time in seconds in [DD:]HH:MM:SS format
 * @param  {int} time Time to convert in milliseconds.
 * @return {string} Converted time.
 */
function formatTime(time)
{
	var timeInSeconds = time / 1000;
	var timeString = '';

	// Time cannot be negative
	if (timeInSeconds < 0)
	{
		timeInSeconds = 0;
	}

	// Loop through each divisor (days, hours, minutes, seconds) and generate the formatting
	[86400, 3600, 60, 1].forEach(
		function(divisor)
		{
			var timeTemp = parseInt(timeInSeconds / divisor, 10);

			if (divisor != 86400 || (divisor == 86400 && timeTemp > 0))
			{
				timeString += (timeTemp < 10 ? "0" : "") + timeTemp + (divisor != 1 ? ":" : "");
			}

			timeInSeconds %= divisor;
		}
	);

	return timeString;
}
