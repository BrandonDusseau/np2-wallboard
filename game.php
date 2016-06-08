<?php
define("NP2_CACHE_DIR", __DIR__ . "/data");
define("NP2_CACHE_FILE", NP2_CACHE_DIR . "/game_%suffix%.dat");
define("NP2_CACHE_EXPIRE", 300); // 5 minutes
define("API_CONFIG_FILE", __DIR__ . "/config.ini");

require_once __DIR__ . "/vendor/BrandonDusseau/phpTriton/client.php";
require_once __DIR__ . "/color.php";

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
		if (file_exists($cacheFile) && (NP2_CACHE_EXPIRE == 0 || time() - filemtime($cacheFile) <= NP2_CACHE_EXPIRE))
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
		// Validate input
		if (empty($key) || !isset($data))
		{
			return false;
		}

		// Create the cache directory if it does not exist
		if (!file_exists(NP2_CACHE_DIR) || !is_dir(NP2_CACHE_DIR))
		{
			mkdir(NP2_CACHE_DIR);
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
	 * @param string $gameId      The ID of a game for which to fetch info, or null to fetch
	 *                            info for all games.
	 * @return string JSON encoded array of game data.
	 */
	public static function getGameInfo($gameId = null, $ignoreCache = false)
	{
		// If a game ID is specified, use that. Otherwise, fetch all games.
		if (!empty($gameId) && !is_numeric($gameId))
		{
			return json_encode(array("error" => "Invalid or no game ID specified."));
		}

		// Attempt to read data from cache first
		$cacheContent = self::readCachedValue($gameId);
		if (!$ignoreCache && !empty($cacheContent))
		{
			// Replace cached "now" with current time.
			$cacheContent['now'] = round(microtime(true) * 1000);

			return json_encode($cacheContent, JSON_NUMERIC_CHECK);
		}

		// Try to load configuration file
		$config = parse_ini_file(API_CONFIG_FILE);
		if ($config === false || empty($config['password']) || empty($config['username']))
		{
			return json_encode(array("error" => "Incomplete or missing configuration. Check the config file and try again."));
		}

		// Fetch data from the API
		ob_start();
		$client = new TritonClient($config['username'], $config['password']);
		if (!$client->authenticate())
		{
			return json_encode(array("error" => "Login failed; check your credentials."));
		}

		$game = $client->GetGame($gameId);
		if (!$game)
		{
			return json_encode(array("error" => "Could not load the game! Perhaps the Game ID is incorrect."));
		}

		$universe = $game->getFullUniverse();
		if (!$universe)
		{
			return json_encode(array("error" => "Internal error: Could not load universe"));
		}
		ob_end_clean();

		// Modify player information to remove private data and add attributes
		if (!empty($universe['players']))
		{
			$playerCount = count($universe['players']);
			$players_rekeyed = [];

			foreach ($universe['players'] as $pIndex => &$player)
			{
				// Strip private information
				$player_strip = ['researching', 'researching_next', 'war', 'countdown_to_war', 'cash', 'stars_abandoned'];
				$player = array_diff_key($player, array_flip($player_strip));

				// Rename 'alias' to 'name' for consistency.
				self::arrayKeyRename($player, 'alias', 'name');

				foreach ($player['tech'] as &$tech)
				{
					$tech_strip = ['sv', 'research', 'bv', 'brr'];
					$tech = array_diff_key($tech, array_flip($tech_strip));
				}

				// Calculate the player's color
				// If there are few enough players, shift the colors a little
				// to better match the game.
				$colorIndex = floor(64 / $playerCount) * $pIndex;
				if ($playerCount < 59)
				{
					$colorIndex += 4;
				}

				// Inject player colors
				$player['color'] = Color::getColor($colorIndex);

				$players_rekeyed[$player['uid']] = $player;
			}

			$universe['players'] = $players_rekeyed;
		}

		// Modify star information to remove private data, and normalize the key names
		if (!empty($universe['stars']))
		{
			$stars_rekeyed = [];

			foreach ($universe['stars'] as $index => &$star)
			{
				// Strip private information
				$star_strip = ['c', 'e', 'i', 's', 'r', 'ga', 'nr', 'st', 'v'];
				$star = array_diff_key($star, array_flip($star_strip));

				// Rename the keys to something useful
				self::arrayKeyRename($star, 'n', 'name');
				self::arrayKeyRename($star, 'puid', 'player');

				// Group the coordinates
				$star['position'] = [
					'x' => (isset($star['x']) ? $star['x'] : 0),
					'y' => (isset($star['y']) ? $star['y'] : 0)
				];
				unset($star['x']);
				unset($star['y']);

				// 0-index stars
				$stars_rekeyed[] = $star;
			}

			$universe['stars'] = $stars_rekeyed;
		}

		// Remove visible fleet information
		unset($universe['fleets']);

		// Cache the result
		self::saveCachedValue($gameId, $universe);

		// Since there is a bit of delay in getting the current time, replace it with the real current time.
		$universe['now'] = round(microtime(true) * 1000);
		return json_encode($universe, JSON_NUMERIC_CHECK);
	}

	/**
	 * Renames a key in an array
	 * @param array  $array  The arran on which to operate (passed by reference).
	 * @param string $oldKey The key to be removed.
	 * @param string $newKey The key to be added.
	 * @return void
	 */
	private static function arrayKeyRename(array &$array, $oldKey, $newKey)
	{
		if (array_key_exists($oldKey, $array))
		{
			$array[$newKey] = $array[$oldKey];
			unset($array[$oldKey]);
		}
	}
}
