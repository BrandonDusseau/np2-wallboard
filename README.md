# Neptune's Pride 2 Wallboard #
## Version 1.0.1 ##
A ten-foot style display for tracking stats in Neptune's Pride 2: Triton. A live
demo is available [on my website](http://www.brandonjd.net/np2/).

This project is licensed under the MIT License - see the LICENSE file for
details.

### Features ###
* List of the user's active and completed games for quick access.
* Prominent game timers for turn due time, production, and ticks.
* A full map of the visible galaxy.
* Player leaderboard with strength and research stats, and indicators for
  defeated/conceded players and turn completion.
* Support for normal, turn-based, and dark games.
* Server-side caching of authentication token and game data.

### Screenshots ###
Left: a standard game, right: a dark, turn-based game.

![Screenshot](scr_std.png "Normal game") ![Screenshot](scr_dark.png "Dark game")

### Limitations ###
* The display requires game credentials to be set in order to load game data on
  behalf of the user.
* Only public games, or private games that the user has joined, can be
  displayed.
* If multiple users reload the display simultaneously after the cache expires,
  the game will throttle logins and the data may fail to load. This will also
	prevent login to the game for a few seconds. This is a limitation of
	Neptune's Pride 2, and is somewhat mitigated by login caching.

### Requirements ###
* A web server running PHP 5.4 or higher.
  * **NOTE:** `.htaccess` files are included for security. If not using Apache, be sure to deny permission to `data/` and `config.ini` in your web server configuration.

### Usage ###
1. Download the [latest compiled zip](https://github.com/BrandonDusseau/np2-wallboard/releases/latest/) (the one named `np2-wallboard.zip`).
2. Upload the contents of the zip file to your desired location on your web server.
3. Enable write permissions on the `data` directory to whichever user your web server (or PHP server) is running as.
4. Rename `config.sample.ini` to `config.ini` and put your NP2 credentials into it.
5. Navigate your browser to the directory in which you installed the display.

Sample configuration:
```
username=your_email_or_true_alias
password=your_password
```

### Running in Docker ###
A sample Dockerfile and docker-compose file are provided in the source code if you desire to run the application from Docker. The provided configurations assume a compiled version of the application is located in a `build` directory relative to where Docker is running.

If running from source rather than a pre-compiled release, make sure to follow the instructions in
_Building From Source_ before starting the Docker container.

### Building From Source ###
These steps are only necessary if building the application from source. If you are using a release package as suggested in _Usage_, you should ignore this section.

1. Install nodejs (tested on version 13.x)
2. Install gulp-cli: `npm install --global gulp-cli`
3. Navigate your terminal to this directory and run `gulp`

### Attributions ###
This project utilizes icon fonts found on [Fontello](http://fontello.com).
License info available in LICENSE file. Thanks!
