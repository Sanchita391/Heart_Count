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
 * There is no API key. The response is JSON with "question" (image URL)
 * and "solution" (the heart count).
 * Parameter style is documented for the related Banana API:
 * https://marcconrad.com/uob/banana/doc.php
 */
define('HEART_API_URL', 'http://marcconrad.com/uob/heart/api.php?out=json');
