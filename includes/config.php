<?php
/**
 * Local XAMPP settings for the HeartCount web app.
 * MySQL 8 is already using port 3306 on this PC, so this app uses
 * XAMPP's MariaDB on port 3307 with the default empty root password.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'heart_game');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Puzzle pictures come from Dr Marc Conrad's Heart API.
 * There is no API key. A GET request returns JSON:
 * "question" is an image URL, "solution" is how many hearts are in the picture (0-9).
 * PHP calls the API so the browser never sees the solution first.
 */
define('HEART_API_URL', 'https://marcconrad.com/uob/heart/api.php?out=json');
