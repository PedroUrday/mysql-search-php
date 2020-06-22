<?php

define('DEV_MODE', true);
define('DB_HOST', '127.0.0.1');
define('DB_CHARSET', 'utf8mb4');
define('DB_NAME', 'test');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

// For testing purposes, choose between PDO handler and MySQLi handler:

// Uncomment the following block of code is you want to test PDO handler
/*
try {
	$dbh = new PDO(
		'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET . ';dbname=' . DB_NAME,
		DB_USERNAME,
		DB_PASSWORD,
		array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		)
	);
} catch (Exception $e) {
	error_log($e->getMessage());
	header('Content-Type: application/json');
	if (DEV_MODE) {
		print json_encode('Connection failed: ' . $e->getMessage());
	} else {
		print json_encode('Something weird happened');
	}
	exit();
}
*/

// Uncomment the following block of code is you want to test MySQLi handler
/*
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
	$dbh = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
	$dbh->set_charset(DB_CHARSET);
} catch (Exception $e) {
	error_log($e->getMessage());
	header('Content-Type: application/json');
	if (DEV_MODE) {
		print json_encode('Connection failed: ' . $e->getMessage());
	} else {
		print json_encode('Something weird happened');
	}
	exit();
}
*/
