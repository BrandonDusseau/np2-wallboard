<?php
define("NP2_CACHE_DIR", __DIR__ . "/data");
define("NP2_CACHE_FILE", NP2_CACHE_DIR . "/game_%suffix%.dat");
define("NP2_CACHE_EXPIRE", 300); // 5 minutes

require_once __DIR__ . "/api.php";

// Create the cache directory if it does not exist
if (!file_exists(NP2_CACHE_DIR) || !is_dir(NP2_CACHE_DIR))
{
	mkdir(NP2_CACHE_DIR);
}

class Np2_Game
{
	private static function readCachedValue($key)
	{
		if (empty($key))
		{
			return false;
		}

		// Generate a cache file name based on the key
		$cacheFile = str_replace("%suffix%", $key, NP2_CACHE_FILE);

		// Get cached result if possible
		if (file_exists($cacheFile) && time() - filemtime($cacheFile) <= NP2_CACHE_EXPIRE)
		{
			$cacheContent = @file_get_contents($cacheFile);
		}

		// If cache is empty, fail
		if (!empty($cacheContent))
		{
			// Validate the cache contents
			$cacheContent = @unserialize($cacheContent);
			if ($cacheContent === false || empty($cacheContent['hash'] || empty($cacheContent['data'] ||
				$cacheContent['hash'] != md5($cacheContent['data']))))
			{
				return false;
			}

			return unserialize($cacheContent['data']);
		}

		return false;
	}

	private static function saveCachedValue($key, array $data)
	{
		if (empty($key) || !isset($data))
		{
			return false;
		}

		// Generate a cache file name based on the key
		$cacheFile = str_replace("%suffix%", $key, NP2_CACHE_FILE);

		$cacheData = array();
		$data = serialize($data);
		$cacheData['hash'] = md5($data);
		$cacheData['data'] = $data;

		$result = @file_put_contents($cacheFile, serialize($cacheData));

		return ($result !== false);
	}

	public static function getGameInfo($getExtended = false, $gameId = null)
	{
		// If a game ID is specified, use that. Otherwise, fetch all games.
		if (!empty($gameId) && !is_numeric($gameId))
		{
			echo json_encode(array("error" => "Invalid game ID specified"));
			return;
		}

		// Attempt to read data from cache first
		$cacheKey = (!empty($gameId) ? $gameId : "all") . ($getExtended ? "_ext" : "");
		$cacheContent = self::readCachedValue($cacheKey);
		if (!empty($cacheContent))
		{
			return json_encode($cacheContent);
		}

		$api = new Np2_API();

		list($success, $message) = $api->getGames();

		if (!$success)
		{
			echo json_encode(array("error" => "Unable to load list of games"));
			return;
		}

		// Put the detailed game info with each requested game
		$games = $message;

		foreach ($games as $gameIdx => &$game)
		{
			$thisGameId = $game['gameId'];

			// Remove any games that were not requested
			if (!empty($gameId) && $thisGameId != $gameId)
			{
				unset($games[$gameIdx]);
				continue;
			}

			// Merge the game data into the game element
			if ($getExtended)
			{
				list($success, $thisGame) = $api->getGame($thisGameId);
				if ($success)
				{
					$game = array_merge($game, $thisGame);
				}
			}

			// Only get additional information if requesting a specific game and extended info
			if (!empty($gameId) && $getExtended)
			{
				// Get players for the game
				list($success, $playerList) = $api->getPlayers($thisGameId);
				$playerArray = [];
				if ($success)
				{
					$playerArray = $playerList;
				}

				// Get stars for the game
				list($success, $starList) = $api->getStars($thisGameId);
				$starArray = [];
				if ($success)
				{
					$starArray = $starList;
				}

				// Embed this information in the game
				$game['players'] = $playerArray;
				$game['stars'] = $starArray;
			}
		}

		// Cache the result
		self::saveCachedValue($cacheKey, $games);

		return json_encode($games);
	}
}
