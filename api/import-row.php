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

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['error' => 'Invalid input']); exit; }

$title       = trim($body['title'] ?? '');
$year        = (int)($body['year'] ?? 0) ?: null;
$date_watched = $body['date_watched'] ?? null;
$first_watch = (int)($body['first_watch'] ?? 1);
$source      = normalize_source($body['source'] ?? '');
$finished    = (int)($body['finished'] ?? 1);
$watcher_ids = $body['watcher_ids'] ?? [];

if (!$title) { echo json_encode(['error' => 'No title']); exit; }

// Fetch from OMDb
$omdb = omdb_find($title, $year);
if (!$omdb) {
    echo json_encode(['status' => 'unmatched', 'title' => $title, 'year' => $year]);
    exit;
}

$fields = omdb_to_fields($omdb);

// Fetch cast from TMDB
$cast    = [];
$tmdb_id = tmdb_find_id($fields['title'], $fields['year']);
if ($tmdb_id) {
    $fields['tmdb_id'] = $tmdb_id;
    foreach (tmdb_get_cast($tmdb_id, 20) as $c) {
        $cast[] = ['name' => $c['name'], 'character' => $c['character'] ?? null];
    }
}

// Merge manual fields
$fields['date_watched'] = $date_watched;
$fields['first_watch']  = $first_watch;
$fields['source']       = $source;
$fields['finished']     = $finished;

$db = db();
$db->beginTransaction();
try {
    $film_id = save_film($fields);
    save_film_relations($film_id, $fields['genres'], $cast, $fields['writers'], $watcher_ids);
    $db->commit();
    echo json_encode([
        'status'  => 'imported',
        'film_id' => $film_id,
        'title'   => $fields['title'],
        'year'    => $fields['year'],
        'poster'  => $fields['cover_art_url'],
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
