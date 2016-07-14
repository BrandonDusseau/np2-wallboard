<?php
/*
 * Neptune's Pride 2 Wallboard
 * Copyright (c) 2016 Brandon Dusseau
 * Licensed with the MIT license; see LICENSE file for details
 *
 * https://github.com/BrandonDusseau/np2-wallboard
 */
ini_set('display_errors', '0');

require_once __DIR__ . "/game.php";

if (empty($_GET['game']))
{
	die("{'error': 'No game specified'}");
}

echo Np2_Game::getGameInfo($_GET['game'], !empty($_GET['nocache']));
