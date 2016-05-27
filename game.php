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
	/**
	 * Fetches a value from the cache, if available.
	 * @param string $key Cache filename suffix.
	 * @return bool|array False on failure or array of data on success.
	 */
	private static function readCachedValue($key)
	{
		// Fail if no key was specified.
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

	/**
	 * Saves a value to a cache file
	 * @param string $key  Filename suffix for the cache file.
	 * @param array  $data An array of data to store to cache.
	 * @return bool True if successfully saved, false otherwise.
	 */
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

	/**
	 * Gets information about one or more games.
	 * @param bool   $getExtended If true, fetches game details without a gameId speficied
	 *                            or gets player and star data if a gameId is speficied.
	 * @param string $gameId      The ID of a game for which to fetch info, or null to fetch
	 *                            info for all games.
	 * @return string JSON encoded array of game data.
	 */
	public static function getGameInfo($getExtended = false, $gameId = null, $ignoreCache = false)
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
		if (!$ignoreCache && !empty($cacheContent))
		{
			// Inject the current time into the game info
			if (!empty($gameId))
			{
				$cacheContent['realNow'] = round(microtime(true) * 1000);
			}

			return json_encode($cacheContent);
		}

		// Initialize the API adapter
		$api = new Np2_API();

		// Get games list
		list($success, $message) = $api->getGames();
		if (!$success)
		{
			echo json_encode(array("error" => "Unable to load list of games"));
			return;
		}

		// Put the detailed game info with each requested game
		$gameData = $message;
		$returnData = array();

		// If we are loading all games, only get basic information
		if (empty($gameId))
		{
			// If requesting extended data, also load that information.
			if ($getExtended)
			{
				foreach ($gameData as &$game)
				{
					// Merge the extended information into this game's record.
					list($success, $thisGame) = $api->getGame($game['gameId']);
					if ($success)
					{
						$game = array_merge($game, $thisGame);
					}
				}
			}

			// Put the information into a new array to prepare it for sending
			$returnData['games'] = $gameData;
		}
		else
		{
			// Filter out any games not requested
			$gameData = array_filter(
				$gameData,
				function ($a) use ($gameId)
				{
					return ($a['gameId'] == $gameId);
				}
			);

			// Move the data into a new array
			$returnData = $gameData[0];

			// Get basic game information
			list($success, $thisGame) = $api->getGame($gameId);
			if ($success)
			{
				$returnData = array_merge($returnData, $thisGame);
			}

			// If extended info is requested, get that too
			if ($getExtended)
			{
				// Get players for the game
				list($success, $playerList) = $api->getPlayers($gameId);
				$returnData['players'] = $success ? $playerList : [];

				// Get stars for the game
				list($success, $starList) = $api->getStars($gameId);
				$starArray = [];
				if ($success)
				{
					$starArray = $starList;

					// Remove player private data
					foreach ($starList as &$star)
					{
						$star = array_diff_key($star, ["ships" => "", "visible" => ""]);
					}
				}
				$returnData['stars'] = $starArray;
			}
		}

		// Cache the result
		self::saveCachedValue($cacheKey, $returnData);

		// Inject current time
		$returnData['realNow'] = round(microtime(true) * 1000);

		return json_encode($returnData);
	}
}
