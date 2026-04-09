<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$db = db();
$films = $db->query("
    SELECT f.*,
           GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS genre_list,
           GROUP_CONCAT(DISTINCT w.name ORDER BY w.name) AS watcher_list,
           GROUP_CONCAT(DISTINCT fc.name ORDER BY fc.cast_order) AS cast_list,
           GROUP_CONCAT(DISTINCT fw2.name) AS writer_list
    FROM films f
    LEFT JOIN film_genres fg ON fg.film_id=f.id
    LEFT JOIN genres g ON g.id=fg.genre_id
    LEFT JOIN film_watchers fww ON fww.film_id=f.id
    LEFT JOIN watchers w ON w.id=fww.watcher_id
    LEFT JOIN film_cast fc ON fc.film_id=f.id
    LEFT JOIN film_writers fw2 ON fw2.film_id=f.id
    GROUP BY f.id
    ORDER BY f.date_watched ASC
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="film-tracker-export-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, [
    'Cover Art', 'Date Watched', 'Title', 'Year', 'First Watch', 'Source',
    'Rating', 'Finished', 'Watchers', 'Director', 'Genre', 'Length',
    'Synopsis', 'Certificate Rating', 'IMDB Rating', 'Cast (top20)', 'Writers', 'IMDB ID'
]);

foreach ($films as $f) {
    fputcsv($out, [
        $f['cover_art_url'] ?? '',
        $f['date_watched'] ? date('d/m/Y', strtotime($f['date_watched'])) : '',
        $f['title'],
        $f['year'] ?? '',
        $f['first_watch'] ? 'Yes' : 'No',
        $f['source'] ?? '',
        $f['rating'] !== null ? number_format((float)$f['rating'], 1) : '',
        $f['finished'] ? 'Yes' : 'No',
        $f['watcher_list'] ?? '',
        $f['director'] ?? '',
        $f['genre_list'] ?? '',
        $f['length_mins'] ?? '',
        $f['synopsis'] ?? '',
        $f['certificate'] ?? '',
        $f['imdb_rating'] ?? '',
        $f['cast_list'] ?? '',
        $f['writer_list'] ?? '',
        $f['imdb_id'] ?? '',
    ]);
}
fclose($out);
exit;
