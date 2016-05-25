<?php
define("API_URL", "https://neptunes-pride-api.herokuapp.com/");
define("API_CONFIG_FILE", __DIR__ . "/config.ini");
define("API_DATA_DIR", __DIR__ . "/data");
define("API_TOKEN_FILE", API_DATA_DIR . "/token.dat");
define("API_TOKEN_EXPIRE", 86400); // Token lasts 24 hours

class Np2_API
{
	private $username;
	private $password;
	private $auth_retry;
	private $api_token;

	/**
	 * Constructor
	 * @return void
	 */
	public function __construct()
	{
		// Try to load configuration file
		$config = parse_ini_file(API_CONFIG_FILE);
		if ($config === false)
		{
			throw new ErrorException("Configuration file is not readable.");
		}

		// Get the credentials from the config file
		if (empty($config['password']) || empty($config['username']))
		{
			throw new ErrorException("Credentials are invalid or incomplete.");
		}
		$this->username = $config['username'];
		$this->password = $config['password'];

		// Create the data directory if it does not exist
		if (!file_exists(API_DATA_DIR) || !is_dir(API_DATA_DIR))
		{
			mkdir(API_DATA_DIR);
		}

		// Get cached API token if possible
		if (file_exists(API_TOKEN_FILE) && time() - filemtime(API_TOKEN_FILE) <= API_TOKEN_EXPIRE)
		{
			$api_token = @file_get_contents(API_TOKEN_FILE);
		}
	}

	/**
	 * Make an API call
	 * @param string $action   API call to make.
	 * @param string $data     Data to POST, if applicable.
	 * @param string $method   HTTP method (GET/POST supported).
	 * @param string $type     MIME type of data (json default).
	 * @param bool   $is_login True if this is a call to login.
	 * @return array Tuple containing boolean success and a string with API response.
	 */
	public function apiCall($action, $data = null, $method = "GET", $type = "application/json", $is_login = false)
	{
		// Ensure the arguments are valid
		if (empty($action) || empty($method) || ($method !== "GET" && $method !== "POST"))
		{
			return array(false, "Invalid arguments");
		}

		$ch = curl_init(API_URL . $action);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_POSTREDIR, 1 & 2); // Follow 301 and 302
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Set up variables
		$headers = array();

		// Set special options based on the method
		if ($method == "GET")
		{
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}
		elseif ($method == "POST")
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$headers[] = "Content-Type: " . $type;
		}

		$attempts = 2;
		$auth_success = false;
		while (!$auth_success && $attempts > 0)
		{
			// If we don't have an auth token, try to log in.
			if (!$is_login)
			{
				if (empty($this->api_token))
				{
					list($login_response, $message) = $this->login();

					if ($login_response)
					{
						$api_token = $message;
					}
					else
					{
						$api_token = "";
					}

					$this->api_token = $api_token;
					@file_put_contents(API_TOKEN_FILE, $api_token);
				}

				// If the token is still empty, we failed.
				if (empty($this->api_token))
				{
					return array(false, "Unable to authenticate with the API (" . $message . ")");
				}

				// Set token for request
				$headers[] = "X-Auth-Token: " . $this->api_token;
			}

			// Inject headers
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			// Execute the request
			$response = curl_exec($ch);

			// Decode the JSON response
			$decoded_response = json_decode($response, true);

			// If we can't read a response, return an error.
			if ($response === false)
			{
				$error = curl_error($ch);
				curl_close($ch);
				return array(false, "Connection failed (" . $error . ")");
			}
			elseif ($decoded_response === false)
			{
				curl_close($ch);
				return array(false, "Invalid response received from API (" . $response . ")");
			}

			// Close the connection
			curl_close($ch);

			// Check for error condition
			if (!empty($decoded_response['error']))
			{
				if (empty($decoded_response['error']['message']))
				{
					$error_message = "Unknown error";
				}
				else
				{
					$error_message = $decoded_response['error']['message'];
				}

				// If we got an error about the auth token, clear our token and retry.
				if ($error_message == "The provided auth token is invalid." ||
					$error_message == "You must provide a valid auth token in the X-Auth-Token header.")
				{
					$this->api_token = "";
					@file_put_contents(NP2_TOKEN_FILE, "");
					$attempts--;
					continue;
				}

				// For any other error, output a failure with the provided message.
				return array(false, $error_message);
			}

			$auth_success = true;
		}

		// If we still haven't authenticated properly, something is wrong with our credentials.
		if (!$auth_success)
		{
			return array(false, "Login failure");
		}

		return array(true, $decoded_response);
	}

	/**
	 * Makes a simple GET call to the API.
	 * @param string $action API action to call.
	 * @return array First element success (bool), second element error message (string) or JSON decoded response (array).
	 */
	private function simpleGet($action)
	{
		list($success, $message) = $this->apiCall($action);

		if (!$success)
		{
			return array(false, $message);
		}

		return array(true, $message['result']);
	}

	/**
	 * Authenticates with the game
	 * @return array First element success (bool), second element error message or auth token (string).
	 */
	private function login()
	{
		$data = http_build_query(array(
			"username" => $this->username,
			"password" => $this->password
		));

		list($success, $message) = $this->apiCall("login", $data, "POST", "application/x-www-form-urlencoded", true);

		if (!$success)
		{
			return array(false, $message);
		}

		if (!empty($message['auth-token']))
		{
			return array(true, $message['auth-token']);
		}

		return array(false, "Received unknown response from API");
	}

	/**
	 * Gets a list of games for the user
	 * @return array First element success (bool), second element error message (string) or game list (array).
	 */
	public function getGames()
	{
		return $this->simpleGet("games");
	}

	/**
	 * Gets information about a particular game
	 * @param string $game_id The ID of the game to fetch..
	 * @return array First element success (bool), second element error message (string) or game properties (array).
	 */
	public function getGame($game_id)
	{
		if (empty($game_id))
		{
			return array(false, "Game ID not specified");
		}

		return $this->simpleGet("games/{$game_id}");
	}

	/**
	 * Gets the list of players for a game
	 * @param string $game_id The ID of the game to fetch.
	 * @return array First element success (bool), second element error message (string) or list of players (array).
	 */
	public function getPlayers($game_id)
	{
		if (empty($game_id))
		{
			return array(false, "Game ID not specified");
		}

		return $this->simpleGet("games/{$game_id}/players");
	}

	/**
	 * Gets the list of stars for a game
	 * @param string $game_id The ID of the game to fetch.
	 * @return array First element success (bool), second element error message (string) or list of stars (array).
	 */
	public function getStars($game_id)
	{
		if (empty($game_id))
		{
			return array(false, "Game ID not specified");
		}

		return $this->simpleGet("games/{$game_id}/stars");
	}
}
