<?php
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_NAME', 'namesyncredux');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('SECURE_TRIP_SALT', 'please-change-me'); // Salt used for encrypting secure trips

// Submit post
define('SUBMIT_MAX_HITS', 5); // This many requests allowed within SUBMIT_TIME (per board)
define('SUBMIT_TIME', 60); // Seconds

// Get posts
define('GET_MAX_HITS', 150);
define('GET_TIME', 60);

// Username DB removal
define('REMOVE_MAX_HITS', 1);
define('REMOVE_TIME', 60);