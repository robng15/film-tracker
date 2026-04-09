<?php
// Copy this file to config.php and fill in your values.

define('APP_NAME',        'Film Tracker');
define('OMDB_API_KEY',    'YOUR_OMDB_API_KEY');
define('OMDB_BASE_URL',   'https://www.omdbapi.com/');
define('TMDB_API_KEY',    'YOUR_TMDB_API_KEY');
define('TMDB_READ_TOKEN', 'YOUR_TMDB_READ_ACCESS_TOKEN');
define('TMDB_BASE_URL',   'https://api.themoviedb.org/3/');
define('DB_PATH',         dirname(__DIR__) . '/db/film-tracker.db');

// Auto-derive web base URL from document root
$_app  = str_replace('\\', '/', dirname(__DIR__));
$_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
define('BASE_URL', ($_root && str_starts_with($_app, $_root) ? str_replace($_root, '', $_app) : '') . '/');
unset($_app, $_root);

define('SOURCES', ['Netflix', 'Firestick', 'Amazon Prime', 'Cinema', 'Paramount+', 'DVD/Blu-ray', 'Other']);
