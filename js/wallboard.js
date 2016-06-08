var data, productionRate, productionCounter, tickRate, tickFragment, timeNow, localTimeCorrection, stars, players;

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
		players = data.players || {};

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
						starElement.css('border-color', players[star.player].color);
					}
					else
					{
						starElement.css('border-color', "#000");
					}
				}
			}
		}

		$("#game_title").html(data.name);
		$("#game_timer").html(timeToProduction());

		window.setInterval(
			function ()
			{
				$("#game_timer").html(timeToProduction());
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
 * @param  {int} galacticCycle Current status of the galactic cycle.
 * @return {string} Formatted time to tick.
 */
function timeToTick(galacticCycle)
{
	return formatTime((60000 * tickRate * galacticCycle) - (60000 * tickFragment * tickRate) - ((new Date).valueOf() - timeNow.valueOf()) - localTimeCorrection);
}

/**
 * Outputs time in seconds in [DD:]HH:MM:SS format
 * @param  {int} time Time to convert.
 * @return {string} Converted time.
 */
function formatTime(time)
{
	var timeInSeconds = time / 1000, timeString = '';

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
