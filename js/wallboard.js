(function ()
{
	var data = {};               // Data from the API

	var timerInterval = null;    // Interval timer for updating the clock
	var refreshInterval = null;  // The interval timer for refreshing game data
	var refreshing = false;      // Whether data is currently refreshing

	var reloadTimer = 0;         // Timer used to reload data in case of error
	var errorInterval = null;    // Interval timer for error reload.

	var timeNow = 0;             // The current time according to server data
	var localTimeCorrection = 0; // Correction between server time and local time.

	$(document).ready(
		function ()
		{
			updateData();
			startRefreshTimer();
		}
	);

	/**
	 * Begins the timer to refresh data
	 * @return {undefined}
	 */
	function startRefreshTimer()
	{
		// Clear any existing timer
		clearRefreshTimer();

		// Reload data every minute
		refreshInterval = window.setInterval(
			function()
			{
				updateData();
			},
			60000
		);
	}

	/**
	 * Terminates the timer to refresh data
	 * @return {undefined}
	 */
	function clearRefreshTimer()
	{
		// Clear the interval, if it's set
		if (refreshInterval != null)
		{
			window.clearInterval(refreshInterval);
			refreshInterval = null;
		}
	}

	/**
	 * Updates the data used for the display
	 * @param {bool} noCache Do not use cache during this refresh.
	 * @return {undefined}
	 */
	 function updateData(noCache)
	 {
		 // Do nothing if we're already refreshing
		 if (refreshing)
		 {
			 return;
		 }

		 // Show the loading indicator
		 $("#pane_stars .star-loader").fadeIn();

		// Get the game ID from the
		var gameId = getUrlParameter('game');
		if (!gameId)
		{
			doError("No game specified", true);
			return;
		}

		 refreshing = true;

		 // Prepare the request data
		 var reqData = {'game': gameId};
		 if (noCache)
		 {
			 reqData.nocache = true;
		 }

		$.ajax({
			url: "data.php",
			type: "get",
			data: reqData,
			dataType: "json",
			timeout: 30000,
			success: function (json)
			{
				// Remove the loading indicator
				$("#pane_stars .star-loader").fadeOut();

				if (json.error)
				{
					doError("An error occurred<br /><span>" + json.error + "</span>");
				}
				else
				{
					data = json;
					refreshDisplay();
				}

				refreshing = false;
			},
			error: function ()
			{
				// Remove the loading indicator
				$("#pane_stars .star-loader").fadeOut();

				doError("Unable to load data from the server.");
				refreshing = false;
			}
		});
	}

	/**
	 * Updates the display to reflect current state of data
	 * @return {undefined}
	 */
	function refreshDisplay()
	{
		var stars = data.stars || [];
		var players = data.players || [];
		var paused = data.paused || false;
		var ended = data.game_over || false;
		var waiting = !data.started;

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
						starElement.css("border-color", "transparent");
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

					// Exclude player if name is not set - player is not ready.
					if (player.name == "")
					{
						continue;
					}

					var playerElement = $(".player[data-player-id='" + player.uid + "']");
					if (!playerElement.length)
					{
						playerElement = $("#player_template").clone();
						playerElement.removeAttr("id");
						playerElement.attr("data-player-id", player.uid);
						playerContainer.append(playerElement);
					}

					// Set player order, by rank
					playerElement.attr('data-rank', player.rank);
					playerElement.css('order', player.rank);

					// Set player's name
					var name = playerElement.find(".player-name");
					if (name.length)
					{
						name.html(player.name);

						// If player has conceded, mark them dead.
						name.removeClass("dead");
						if (player.conceded)
						{
							name.addClass("dead");
						}
						else
						{
							// If player is ready in a turn-based game, mark it.
							name.removeClass("ready icon-ok");
							if (player.ready)
							{
								name.addClass("ready icon-ok");
							}
						}
					}

					// Set player's ring color
					var ring = playerElement.find(".player-ring");
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


				// Determine the size of the player elements based on how many players are displayed.
				playerContainer.removeClass("player-minimal player-minimal-extreme");
				if ($(".player-container").length > 24)
				{
					playerContainer.addClass("player-minimal-extreme");
				}
				else if ($(".player-container").length > 8)
				{
					playerContainer.addClass("player-minimal");
				}
			}
		}

		// Update the time
		timeNow = new Date(data.now) || 0;
		localTimeCorrection = timeNow.valueOf() - (new Date).valueOf();

		$("#game_title").html(data.name);
		$("#game_timer").html(formatTime(timeToProduction()));

		// Update game status indicator and timer
		$("#game_status").removeClass("pause");
		if (paused && !waiting)
		{
			$("#game_status").addClass("pause");
			$("#game_status").html("PAUSED");
			$("#game_timer").html(formatTime(timeToProduction()));
		}
		else if (ended)
		{
			$("#game_status").addClass("pause");
			$("#game_status").html("GAME OVER");
			var winnerPlayer = $(".player[data-rank=1]");
			var winnerId = (winnerPlayer.length ? winnerPlayer.data("player-id") : -1);
			$("#game_timer").html("<div class='winner'><div class='win-heading'>Winner</div>" +
				(winnerId != -1 ? data.players[winnerId].name : "[Player not found]") + "</div>");

			// If the game is over, don't bother reloading data.
			clearRefreshTimer();
		}
		else if (waiting)
		{
			$("#game_status").addClass("pause");
			$("#game_status").html("WAITING...");
			$("#game_timer").html("");
		}
		else
		{
			$("#game_status").html(formatTime(timeToTick(1)));
		}

		// Set a new timer to update the clocks
		window.clearInterval(timerInterval);

		if (!paused && !ended)
		{
			timerInterval = window.setInterval(
				function ()
				{
					var toTick = timeToTick(1);
					var toProduction = timeToProduction();

					// If either timer reaches zero, update the data.
					if (!refreshing && (toProduction <= 0 || toTick <= 0))
					{
						updateData(true);
					}

					$("#game_timer").html(formatTime(toProduction));
					$("#game_status").html(formatTime(toTick));
				},
				500
			);
		}
	}

	/**
	 * Returns time to the end of the galactic cycle.
	 * @return {string} Formatted time to production.
	 */
	function timeToProduction()
	{
		var productionRate = data.production_rate || 0;
		var productionCounter = data.production_counter || 0;

		return timeToTick(productionRate - productionCounter);
	}

	/**
	 * Returns time to a specified tick, relative to the current tick.
	 * @param  {int} ticks Number of ticks to which time should be calculated.
	 * @return {string} Time until a tick,
	 */
	function timeToTick(ticks)
	{
		var tickRate = data.tick_rate || 0;
		var tickFragment = data.tick_fragment || 0;

		// Use wacky formula from the game to determine the time
		return Math.floor((60000 * tickRate * ticks) - (60000 * tickFragment * tickRate) - ((new Date).valueOf() - timeNow.valueOf()) - localTimeCorrection);
	}

	/**
	 * Outputs time in seconds in [DD:]HH:MM:SS format
	 * @param  {int} time Time to convert in milliseconds.
	 * @return {string} Converted time.
	 */
	function formatTime(time)
	{
		var timeInSeconds = Math.floor(time / 1000);
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

	/**
	 * Handles error messages
	 * @param {string} message Error message to display; blank will omit display entirely.
	 * @param {bool} noReload Do not try to reload the wallboard data.
	 * @return {undefined}
	 */
	 function doError(message, noReload)
	 {
		// Clear the reload timer in case it is already running
		if (errorInterval != null)
		{
			window.clearInterval(errorInterval);
			errorInterval = null;
		}

		// Disable the usual refresh timer and run our own
		if (!noReload)
		{
			// Disable refreshing data
			clearRefreshTimer();

			reloadTimer = 20;
			var retryCounter = $("#error .reload-time");
			retryCounter.html(retryCounter.data("reload-msg").replace("%seconds%", reloadTimer));

			// Set a timer
			errorInterval = window.setInterval(
				function ()
				{
					--reloadTimer;

					// Update the error dialog
					retryCounter.html(retryCounter.data("reload-msg").replace("%seconds%", reloadTimer));

					// When the timer runs out, reload data
					if (reloadTimer == 0)
					{
						dismissError();
						updateData(true);
					}
				},
				1000
			);
		}

		if (message)
		{
			$("#error_text").html(message);
			$("#error").fadeIn(300);
		}
	}

	/**
	 * Dismisses any existing error
	 * @return {undefined}
	 */
	function dismissError()
	{
		reloadTimer = 0;

		// Clear the reload timer
		if (errorInterval != null)
		{
			window.clearInterval(errorInterval);
			errorInterval = null;

			// If a reload timer is set, we have also disabled background reloading data, so restart it
			startRefreshTimer();
		}

		// Reset dialog state
		$("#error").fadeOut(
			300,
			function ()
			{
				$("#error_text").html("");
				$("#error .reload-time").html("");
			}
		);
	}

	/**
	 * Gets a parameter from the URL
	 * @param {string} sParam Parameter name.
	 * @return {string|bool} Parameter if present and set, true if present and not set,
	 *                       false if not present.
	 */
	function getUrlParameter(sParam)
	{
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++)
		{
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam)
			{
				return typeof sParameterName[1] === 'undefined' ? true : sParameterName[1];
			}
		}

		return false;
	}
})();
