var data, productionRate, productionCounter, tickRate, tickFragment, timeNow, localTimeCorrection, stars, players;

$(document).ready(
	function ()
	{
		data = JSON.parse($("#data").html());
		productionRate = data.details.productionRate || 0;
		productionCounter = data.status.productionCounter || 0;
		tickRate = data.details.tickRate || 0;
		tickFragment = data.status.tickFragment || 0;
		timeNow = new Date(data.realNow) || 0;
		localTimeCorrection = timeNow.valueOf() - (new Date).valueOf();
		stars = data.stars || 0;
		players = data.players || 0;

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
				for (var s = 0; s < stars.length; ++s)
				{
					var star = stars[s];
					var starX = star.position.x;
					var starY = star.position.y;
					starMaxX = (starX > starMaxX ? starX : starMaxX);
					starMaxY = (starY > starMaxY ? starY : starMaxY);
					starMinX = (starX < starMinX ? starX : starMinX);
					starMinY = (starY < starMinY ? starY : starMinY);
				}

				var starXSpan = starMaxX - starMinX;
				var starYSpan = starMaxY - starMinY;
				var starMaxDim = Math.max(starXSpan, starYSpan);
				console.log(players);

				for (var st = 0; st < stars.length; ++st)
				{
					var star = stars[st];
					var starId = star.starId;
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
					if (typeof star.playerId != "undefined" && star.playerId != -1 &&
						typeof players[star.playerId] != "undefined")
					{
						starElement.css('border-color', players[star.playerId].color);
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

function timeToProduction()
{
	return timeToTick(productionRate - productionCounter);
}

function timeToTick(galactic_cycle)
{
	return formatTime((60000 * tickRate * galactic_cycle) - (60000 * tickFragment * tickRate) - ((new Date).valueOf() - timeNow.valueOf()) - localTimeCorrection);
}

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
