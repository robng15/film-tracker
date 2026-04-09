<?php
function get_film(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM films WHERE id = ?');
    $stmt->execute([$id]);
    $film = $stmt->fetch();
    if (!$film) return null;
    $film['genres']   = get_film_genres($id);
    $film['cast']     = get_film_cast($id);
    $film['writers']  = get_film_writers($id);
    $film['watchers'] = get_film_watcher_ids($id);
    return $film;
}

function get_film_genres(int $id): array {
    $s = db()->prepare('SELECT g.name FROM genres g JOIN film_genres fg ON g.id=fg.genre_id WHERE fg.film_id=? ORDER BY g.name');
    $s->execute([$id]);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}

function get_film_cast(int $id): array {
    $s = db()->prepare('SELECT name, character_name FROM film_cast WHERE film_id=? ORDER BY cast_order');
    $s->execute([$id]);
    return $s->fetchAll();
}

function get_film_writers(int $id): array {
    $s = db()->prepare('SELECT name FROM film_writers WHERE film_id=?');
    $s->execute([$id]);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}

function get_film_watcher_ids(int $id): array {
    $s = db()->prepare('SELECT watcher_id FROM film_watchers WHERE film_id=?');
    $s->execute([$id]);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}

function get_watchers(): array {
    return db()->query('SELECT * FROM watchers WHERE active=1 ORDER BY name')->fetchAll();
}

function save_film(array $d, ?int $id = null): int {
    $db = db();
    $fields = ['imdb_id','tmdb_id','title','year','date_watched','first_watch','source',
               'rating','finished','director','length_mins','synopsis','certificate',
               'imdb_rating','cover_art_url'];
    $vals = array_map(fn($f) => isset($d[$f]) && $d[$f] !== '' ? $d[$f] : null, $fields);

    if ($id) {
        $sets = implode(', ', array_map(fn($f) => "$f=?", $fields)) . ", updated_at=datetime('now')";
        $db->prepare("UPDATE films SET $sets WHERE id=?")->execute([...$vals, $id]);
        return $id;
    }
    $cols  = implode(', ', $fields);
    $phs   = implode(', ', array_fill(0, count($fields), '?'));
    $db->prepare("INSERT INTO films ($cols) VALUES ($phs)")->execute($vals);
    return (int)$db->lastInsertId();
}

function save_film_relations(int $film_id, array $genres, array $cast, array $writers, array $watcher_ids): void {
    $db = db();
    $db->prepare('DELETE FROM film_genres WHERE film_id=?')->execute([$film_id]);
    $db->prepare('DELETE FROM film_cast WHERE film_id=?')->execute([$film_id]);
    $db->prepare('DELETE FROM film_writers WHERE film_id=?')->execute([$film_id]);
    $db->prepare('DELETE FROM film_watchers WHERE film_id=?')->execute([$film_id]);

    foreach ($genres as $name) {
        $name = trim($name);
        if (!$name) continue;
        $db->prepare('INSERT OR IGNORE INTO genres (name) VALUES (?)')->execute([$name]);
        $gid = $db->prepare('SELECT id FROM genres WHERE name=?');
        $gid->execute([$name]);
        $db->prepare('INSERT OR IGNORE INTO film_genres VALUES (?,?)')->execute([$film_id, $gid->fetchColumn()]);
    }

    foreach ($cast as $i => $m) {
        $name = is_array($m) ? ($m['name'] ?? '') : $m;
        $char = is_array($m) ? ($m['character'] ?? $m['character_name'] ?? null) : null;
        if (trim($name)) {
            $db->prepare('INSERT INTO film_cast (film_id, name, character_name, cast_order) VALUES (?,?,?,?)')->execute([$film_id, trim($name), $char, $i]);
        }
    }

    foreach ($writers as $name) {
        if (trim($name)) $db->prepare('INSERT INTO film_writers (film_id, name) VALUES (?,?)')->execute([$film_id, trim($name)]);
    }

    foreach ($watcher_ids as $wid) {
        if ($wid) $db->prepare('INSERT OR IGNORE INTO film_watchers VALUES (?,?)')->execute([$film_id, (int)$wid]);
    }
}

function format_runtime(?int $mins): string {
    if (!$mins) return '';
    $h = intdiv($mins, 60);
    $m = $mins % 60;
    return $h ? "{$h}h {$m}m" : "{$m}m";
}

function render_stars(?float $rating): string {
    if ($rating === null) return '<span class="text-muted small fst-italic">Unrated</span>';
    $stars = $rating / 2;
    $full  = (int)floor($stars);
    $half  = ($stars - $full) >= 0.5 ? 1 : 0;
    $empty = max(0, 5 - $full - $half);
    return '<span class="stars text-warning">'
        . str_repeat('<i class="bi bi-star-fill"></i>', $full)
        . ($half ? '<i class="bi bi-star-half"></i>' : '')
        . str_repeat('<i class="bi bi-star"></i>', $empty)
        . '</span>';
}

function source_badge(string $source): string {
    $map = [
        'Netflix'     => 'danger',
        'Firestick'   => 'warning text-dark',
        'Amazon Prime'=> 'info text-dark',
        'Cinema'      => 'success',
        'Paramount+'  => 'primary',
        'DVD/Blu-ray' => 'secondary',
    ];
    $cls = $map[$source] ?? 'light text-dark border';
    return '<span class="badge bg-' . $cls . '">' . e($source) . '</span>';
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function normalize_source(string $s): string {
    $s = trim($s);
    if ($s === 'DVD') return 'DVD/Blu-ray';
    return $s;
}
