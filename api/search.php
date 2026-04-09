<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/omdb.php';

auth_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

header('Content-Type: application/json');

$q    = trim($_GET['q'] ?? '');
$year = (int)($_GET['year'] ?? 0) ?: null;

if (strlen($q) < 2) { echo json_encode([]); exit; }

$results = omdb_search($q, $year);
$out = [];
foreach (array_slice($results, 0, 8) as $r) {
    $out[] = [
        'imdb_id' => $r['imdbID'],
        'title'   => $r['Title'],
        'year'    => $r['Year'],
        'poster'  => ($r['Poster'] ?? 'N/A') !== 'N/A' ? $r['Poster'] : null,
    ];
}
echo json_encode($out);
