<?php
/*
 * Neptune's Pride 2 Wallboard
 * Copyright (c) 2019 Brandon Dusseau
 * Licensed with the MIT license; see LICENSE file for details
 *
 * https://github.com/BrandonDusseau/np2-wallboard
 */
define("NP2_CACHE_DIR", __DIR__ . "/data");
define("NP2_CACHE_FILE", NP2_CACHE_DIR . "/game_%suffix%.dat");
define("NP2_AUTH_FILE", NP2_CACHE_DIR . "/auth_%suffix%.dat");
define("NP2_CACHE_EXPIRE",  300); // 5 minutes (0 for always use cache, -1 to never use cache)
define("NP2_LIST_EXPIRE",   900); // 15 minutes; same exceptions as above
define("NP2_AUTH_EXPIRE", 86400); // 24 hours
define("API_CONFIG_FILE", __DIR__ . "/config.ini");

require_once __DIR__ . "/vendor/BrandonDusseau/phpTriton/client.php";

class Np2_Game
{
	/**
	 * Fetches a value from the cache, if available.
	 * @param  string     $key    Cache filename suffix.
	 * @param  int        $expire How long the cache should last, in seconds.
	 * @return bool|array False on failure or array of data on success.
	 */
	private static function readCachedValue($key, $expire = NP2_CACHE_EXPIRE)
	{
		// Fail if no key was specified.
		if (empty($key))
		{
			return false;
		}

		// Generate a cache file name based on the key
		$cache_file = str_replace("%suffix%", $key, NP2_CACHE_FILE);

		// Get cached result if possible
		if ($expire != -1 && file_exists($cache_file) && ($expire == 0 || time() - filemtime($cache_file) <= $expire))
		{
			$cache_content = @file_get_contents($cache_file);
		}

		// If cache is empty, fail
		if (!empty($cache_content))
		{
			// Validate the cache contents
			$cache_content = @unserialize($cache_content);
			if ($cache_content === false || empty($cache_content['hash'] || empty($cache_content['data'] ||
				$cache_content['hash'] != md5($cache_content['data']))))
			{
				return false;
			}

			return unserialize($cache_content['data']);
		}

		return false;
	}

	/**
	 * Saves a value to a cache file
	 * @param  string $key  Filename suffix for the cache file.
	 * @param  array  $data An array of data to store to cache.
	 * @return bool   True if successfully saved, false otherwise.
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
		$cache_file = str_replace("%suffix%", $key, NP2_CACHE_FILE);

		$cache_data = array();
		$data = serialize($data);
		$cache_data['hash'] = md5($data);
		$cache_data['data'] = $data;

		$result = @file_put_contents($cache_file, serialize($cache_data));

		return ($result !== false);
	}

	/**
	 * Gets information about one or more games.
	 * @param  string $game_id      The ID of a game for which to fetch info, or null to fetch
	 *                              info for all games.
	 * @param  bool   $ignore_cache Do not load data from the cache.
	 * @return string JSON encoded array of game data.
	 */
	public static function getGameInfo($game_id = null, $ignore_cache = false)
	{
		// If a game ID is specified, use that. Otherwise, fetch all games.
		// NOTE: This validation must be present or a file delete operation below may
		//       become dangerous!
		if (!empty($game_id) && !is_numeric($game_id))
		{
			return json_encode(array("error" => "Invalid or no game ID specified."));
		}

		// Attempt to read data from cache first
		// If no game ID is specified, get the cache for the list of games instead.
		if (!$ignore_cache)
		{
			$cache_key = (!empty($game_id) ? $game_id : "all");
			$cache_expire = (!empty($game_id) ? NP2_CACHE_EXPIRE : NP2_LIST_EXPIRE);
			$cache = self::readCachedValue($cache_key, $cache_expire);

			// Game info
			if (!empty($cache) && !empty($game_id))
			{
				// Get the current time and use it to recalculate some time-dependent properties
				$current_time = round(microtime(true) * 1000);
				$time_diff = $current_time - $cache['now'];
				$minutes = $time_diff / 60000;

				// Recalculate time-based information if the cached state isn't paused. If it is, we
				// can't reliably calculate anything based on the difference in time.
				// In a turn-based game, these values should remain the same as the cache.
				if (!$cache['paused'] && $cache['started'] && !$cache['turn_based'])
				{
					// Recalculate tick information
					$tick_diff = ($cache['tick'] + $cache['tick_fragment']) + ($minutes / $cache['tick_rate']);
					$tick = floor($tick_diff);
					$tick_fragment = $tick_diff - $tick;

					// Recalculate production information
					$tick_diff_in_hours = ($tick_diff * $cache['tick_rate']) / 60;
					$productions = floor($tick_diff_in_hours / $cache['production_rate']);
					$production_counter = floor($tick_diff_in_hours % $cache['production_rate']);
				}
				else
				{
					$tick = $cache['tick'];
					$tick_fragment = $cache['tick_fragment'];
					$productions = $cache['productions'];
					$production_counter = $cache['production_counter'];
				}

				// Inject the new values into the data we're returning
				$cache['now'] = $current_time;
				$cache['tick'] = $tick;
				$cache['tick_fragment'] = $tick_fragment;
				$cache['productions'] = $productions;
				$cache['production_counter'] = $production_counter;

				return json_encode($cache, JSON_NUMERIC_CHECK);
			}
			// List of games
			else if (!empty($cache))
			{
				return json_encode($cache, JSON_NUMERIC_CHECK);
			}
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

		// Attempt to load auth token from cache and inject it into the client.
		$username_sanitized = preg_replace("/[^a-zA-Z0-9]/", "_", $config['username']);
		$auth_file = str_replace("%suffix%", $username_sanitized, NP2_AUTH_FILE);
		if (file_exists($auth_file) && time() - filemtime($auth_file) <= NP2_AUTH_EXPIRE)
		{
			$auth_token = @file_get_contents($auth_file);
			if (!empty($auth_token))
			{
				$client->auth_cookie = $auth_token;
				$client->logged_in = true;
			}
		}

		// If not logged in already, attempt to authenticate.
		if (!$client->logged_in)
		{
			if (!$client->authenticate())
			{
				ob_end_clean();
				return json_encode(array("error" => "Login failed; the credentials may be incorrect."));
			}

			// Try to save the cookie data to cache
			@file_put_contents($auth_file, $client->auth_cookie);
		}

		// If a game ID is specified, get the data for the requested game.
		// Otherwise, get a list of games.
		if (!empty($game_id))
		{
			$game = $client->GetGame($game_id);
			if (!$game)
			{
				// If we used an auth token and failed on the first request, assume it is bad and log in again on the next attempt.
				if (!empty($auth_token))
				{
					unlink($auth_file);
				}

				ob_end_clean();
				return json_encode(array("error" => "The specified game could not be loaded. Perhaps the game ID is incorrect?"));
			}

			$universe = $game->getFullUniverse();
			if (!$universe)
			{
				// Remove any cache file associated with this game ID, if it exists.
				// NOTE: The game ID entered has already been validated as numeric at this point. This operation should be safe.
				$game_cache_file = str_replace("%suffix%", $game_id, NP2_CACHE_FILE);
				unlink($game_cache_file);

				ob_end_clean();
				return json_encode(array("error" => "Failed to load universe."));
			}

			ob_end_clean();

			// Modify player information to remove private data and add attributes
			if (!empty($universe['players']))
			{
				// Define colors for the players. These eight colors are repeated with
				// each set of eight players.
				$player_colors = [
					"#0000FF",
					"#009FDF",
					"#40C000",
					"#FFC000",
					"#DF5F00",
					"#C00000",
					"#C000C0",
					"#6000C0",
				];

				$players_rekeyed = [];

				// This array is used to determine ranking
				$rank = [];

				foreach ($universe['players'] as &$player)
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

					// Add player color and shape
					$player['color'] = $player_colors[$player['uid'] % 8];
					$player['shape'] = $player['uid'] % 8;

					$players_rekeyed[$player['uid']] = $player;
					$rank[] = ['player' => $player['uid'], 'stars' => $player['total_stars'], 'ships' => $player['total_strength']];
				}

				// Rank the players by stars, ships, then UID.
				usort(
					$rank,
					function ($a, $b)
					{
						// B ranks higher if A has fewer stars, or if A has fewer ships and stars are equal
						if ($a['stars'] < $b['stars'] || ($a['stars'] == $b['stars'] && $a['ships'] < $b['ships']))
						{
							return 1;
						}
						// A ranks higher if B has fewer stars, or if B has fewer ships and stars are equal
						elseif ($a['stars'] > $b['stars'] || ($a['stars'] == $b['stars'] && $a['ships'] > $b['ships']))
						{
							return -1;
						}
						// Otherwise, everything is equal and we should just sort by UID
						else
						{
							return ($a['player'] - $b['player']);
						}
					}
				);

				// Add the ranks back into the player data
				// Add 1 to the index to make rankings start at 1.
				foreach ($rank as $index => $player_rank)
				{
					$players_rekeyed[$player_rank['player']]['rank'] = $index + 1;
				}

				$universe['players'] = $players_rekeyed;
			}

			// The game is dark if the number of stars given does not match the total number of stars.
			// This could be unreliable if the account used to fetch data can see all the stars,
			// but it's all we've got.
			if ($universe['total_stars'] != count($universe['stars']))
			{
				$universe['stars'] = [];
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

			// Add in the game ID
			$universe['game_id'] = $game_id;

			// Cache the result
			self::saveCachedValue($game_id, $universe);

			// Since there is a bit of delay in getting the current time, replace it with the real current time.
			$universe['now'] = round(microtime(true) * 1000);
			return json_encode($universe, JSON_NUMERIC_CHECK);
		}
		// Get list of all games
		else
		{
			// Get server information in order to fetch player info.
			$server = $client->GetServer();

			// Handle failure to get server
			if (!$server)
			{
				// If we used an auth token and failed on the first request, assume it is bad and log in again on the next attempt.
				if (!empty($auth_token))
				{
					unlink($auth_file);
				}

				ob_end_clean();
				return json_encode(array("error" => "Unable to load server information. Please try again later."));
			}

			// Load the player info
			$player = $server->GetPlayer();
			if (!$player)
			{
				ob_end_clean();
				return json_encode(array("error" => "Failed to load player information."));
			}

			ob_end_clean();

			// Assemble the open and completed games into individual lists
			$completed = (isset($player['complete_games']) ? $player['complete_games'] : []);
			$open = (isset($player['open_games']) ? $player['open_games'] : []);

			// Add a "completed" attribute to the game so we can combine them into a single list
			// Also clean up the data while we're at it.
			foreach ($completed as &$game)
			{
				$game['completed'] = true;
				self::arrayKeyRename($game, "number", "id");
				unset($game['config']);
			}

			foreach ($open as &$game)
			{
				$game['completed'] = false;
				self::arrayKeyRename($game, "number", "id");
				unset($game['config']);
			}

			// Combine the lists into one
			$game_list = [
				"games" => array_merge($open, $completed)
			];

			// Sort the array by name
			usort(
				$game_list['games'],
				function ($a, $b)
				{
					return strnatcmp($a['name'], $b['name']);
				}
			);

			// Cache the result
			self::saveCachedValue("all", $game_list);

			return json_encode($game_list, JSON_NUMERIC_CHECK);
		}
	}

	/**
	 * Renames a key in an array
	 * @param  array  $array   The array on which to operate (passed by reference).
	 * @param  string $old_key The key to be removed.
	 * @param  string $new_key The key to be added.
	 * @return void
	 */
	private static function arrayKeyRename(array &$array, $old_key, $new_key)
	{
		if (array_key_exists($old_key, $array))
		{
			$array[$new_key] = $array[$old_key];
			unset($array[$old_key]);
		}
	}
}
