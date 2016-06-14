<?php
require_once __DIR__ . "/game.php";

if (empty($_GET['game']))
{
	die("{'error': 'No game specified'}");
}

echo Np2_Game::getGameInfo($_GET['game'], !empty($_GET['nocache']));
