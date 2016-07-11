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
		var turnBased = data.turn_based || false;

		// Define the order in which shapes appear for sets of eight players.
		var ringClasses = [
			"ring-circle",
			"ring-square",
			"ring-hexagon",
			"ring-triangle",
			"ring-cross",
			"ring-rhombus",
			"ring-star",
			"ring-oval"
		];

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

					// Remove existing shape class from the star
					for (var srCls = 0; srCls < ringClasses.length; ++srCls)
					{
						starElement.removeClass(ringClasses[srCls]);
					}

					// Set star color
					if (typeof star.player != "undefined" && star.player != -1 &&
						typeof players[star.player] != "undefined")
					{
						// Determine player's ring shape and apply it and the color
						var ringShape = ringClasses[Math.floor(star.player / 8)];
						starElement.addClass(ringShape);
						starElement.css("color", players[star.player].color);
					}
					else
					{
						// Use an invisible ring
						starElement.addClass(ringClasses[0]);
						starElement.css("color", "transparent");
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
						// If player has become an AI, add a prefix.
						name.html((player.ai ? "[AI] " : "") + player.name);

						// If player has been destroyed, mark them dead.
						name.removeClass("ready icon-ok");
						name.removeClass("dead");
						if (player.conceded && player.total_stars == 0 && player.total_strength == 0)
						{
							name.addClass("dead");
						}
						// Otherwise, mark them ready if applicable
						else if (turnBased)
						{
							if (player.ready)
							{
								name.addClass("ready icon-ok");
							}
						}
					}

					// Set player's ring color and shape
					var ring = playerElement.find(".player-ring");
					if (ring.length)
					{
						// Determine player's ring shape
						var playerRingShape = ringClasses[Math.floor(player.uid / 8)];

						// Remove any existing ring shape from the player
						for (var prCls = 0; prCls < ringClasses.length; ++prCls)
						{
							ring.removeClass(ringClasses[prCls]);
						}

						// Add the shape and color to the player's ring
						ring.addClass(playerRingShape);
						ring.css("color", player.color);
						ring.css("text-shadow", "0 0 4px " + player.color);
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
				if ($(".player").length > 24)
				{
					playerContainer.addClass("player-minimal-extreme");
				}
				else if ($(".player").length > 8)
				{
					playerContainer.addClass("player-minimal");
				}
			}
		}

		// Update the time
		timeNow = new Date(data.now) || 0;
		localTimeCorrection = timeNow.valueOf() - (new Date).valueOf();

		$("#game_title").html("<a target='_blank' href='http://triton.ironhelmet.com/game/" + data.game_id + "'>" + data.name + "</a>");
		$("#game_timer").html(formatTime(!turnBased ? timeToProduction() : timeToTurnTimeout()));

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
			$("#game_status").html(turnBased ? formatTime(timeToProduction(), true, true) : formatTime(timeToTick(1)));
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
					var toTimeout = timeToTurnTimeout();

					// If either timer reaches zero, update the data.
					if (!refreshing && (!turnBased && (toProduction <= 0 || toTick <= 0) || (turnBased && toTimeout <= 0)))
					{
						updateData(true);
					}

					$("#game_timer").html(formatTime(!turnBased ? toProduction : toTimeout));
					$("#game_status").html(!turnBased ? formatTime(toTick) : formatTime(toProduction, true));
				},
				500
			);
		}
	}

	/**
	 * Returns time to the end of the galactic cycle.
	 * @return {string} Time to production in milliseconds.
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
	 * @return {string} Time until a tick, in milliseconds.
	 */
	function timeToTick(ticks)
	{
		var tickRate = data.tick_rate || 0;
		var tickFragment = data.tick_fragment || 0;

		// In a turn based game, the timer is locked to the beginning of the current tick.
		if (data.turn_based)
		{
			tickFragment = 0;
		}

		// Use wacky formula from the game to determine the time
		// Time in ms of the desired number of ticks - time in ms of current tick progress - difference in server and local time - correction.
		// For turn-based games, leave off the actual time bit.
		var tickDiff = (60000 * tickRate * ticks) - (60000 * tickFragment * tickRate);
		if (!data.turn_based)
		{
			tickDiff -= ((new Date).valueOf() - timeNow.valueOf()) - localTimeCorrection;
		}

		return Math.floor(tickDiff);
	}

	/**
	 * Returns time until the turn times out.
	 * @return {string} Time until turn timeout in milliseconds.
	 */
	function timeToTurnTimeout()
	{
		var timeout = data.turn_based_time_out || 0;
		return (timeout - (new Date()).getTime());
	}

	/**
	 * Converts milliseconds to "[DD:]HH:MM[:SS]" or "[Dd ][Hh ][Mm ][Ss]" format
	 * @param  {int}  time        Time to convert in milliseconds.
	 * @param  {bool} altFormat   Whether to display in "DD:HH:MM:SS" or "d h m s" format.
	 * @param  {bool} hideSeconds Whether to hide seconds.
	 * @return {string} Converted time.
	 */
	function formatTime(time, altFormat, hideSeconds)
	{
		var timeInSeconds = Math.floor(time / 1000);
		var timeString = '';

		// Time cannot be negative
		if (timeInSeconds < 0)
		{
			timeInSeconds = 0;
		}

		// If seconds are hidden, we need to round up to the nearest minute if it's not already exact.
		if (hideSeconds)
		{
			if (timeInSeconds % 60 != 0)
			{
				timeInSeconds += (60 - timeInSeconds % 60);
			}
		}

		// Calculate days - do not display them if they do not apply.
		var timeDays = parseInt(timeInSeconds / 86400, 10);
		if (timeDays > 0)
		{
			if (altFormat)
			{
				// Alt format: Add the days and add a "d"
				timeString += timeDays + "d ";
			}
			else
			{
				// Normal format: Add the days and a colon
				timeString += (timeDays < 10 ? "0" : "") + timeDays + ":";
			}
		}

		// If using the alternate format and no narrower time is available, return now.
		timeInSeconds %= 86400;
		if (timeInSeconds == 0 && altFormat)
		{
			return timeString;
		}

		// Calculate hours
		var timeHours = parseInt(timeInSeconds / 3600, 10);
		if (altFormat)
		{
			// Alt format: only display hours if more than an hour remains.
			timeString += ((timeDays <= 0 && timeHours <= 0) ? "" : timeHours + "h ");
		}
		else
		{
			// Normal format: add the hours and a colon
			timeString += (timeHours < 10 ? "0" : "") + timeHours + ":";
		}

		// If using the alternate format and no narrower time is available, return now.
		timeInSeconds %= 3600;
		if (timeInSeconds == 0 && altFormat)
		{
			return timeString;
		}

		// Calculate minutes
		var timeMinutes = parseInt(timeInSeconds / 60, 10);
		if (altFormat)
		{
			// Alt format: only display minutes if more than a minute remains.
			timeString += ((timeDays <= 0 && timeHours <= 0 && timeMinutes <= 0) ? "" : timeMinutes + "m ");
		}
		else
		{
			// Normal format: add the minutes, but do not display the colon if seconds are hidden
			timeString += (timeMinutes < 10 ? "0" : "") + timeMinutes + (hideSeconds ? "" : ":");
		}

		// If using the alternate format and no narrower time is available, return now.
		timeInSeconds %= 60;
		if (timeInSeconds == 0 && altFormat)
		{
			return timeString;
		}

		// Calculate seconds if not hidden.
		if (!hideSeconds)
		{
			if (altFormat)
			{
				timeString += timeInSeconds + "s";
			}
			else
			{
				timeString += (timeInSeconds < 10 ? "0" : "") + timeInSeconds;
			}
		}

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
