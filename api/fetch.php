<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/omdb.php';
require_once __DIR__ . '/../includes/tmdb.php';

auth_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

header('Content-Type: application/json');

$imdb_id = trim($_GET['imdb_id'] ?? '');
if (!$imdb_id) { echo json_encode(['error' => 'No IMDB ID']); exit; }

$omdb = omdb_by_id($imdb_id);
if (!$omdb) { echo json_encode(['error' => 'Not found']); exit; }

$fields = omdb_to_fields($omdb);

// Get cast from TMDB
$cast = [];
$tmdb_id = null;
$tmdb_id = tmdb_find_id($fields['title'], $fields['year']);
if ($tmdb_id) {
    $fields['tmdb_id'] = $tmdb_id;
    $raw_cast = tmdb_get_cast($tmdb_id, 20);
    foreach ($raw_cast as $c) {
        $cast[] = ['name' => $c['name'], 'character' => $c['character'] ?? null];
    }
}
$fields['cast'] = $cast;

echo json_encode($fields);
