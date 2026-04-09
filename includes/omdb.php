<?php
function omdb_find(string $title, ?int $year = null): ?array {
    $p = ['apikey' => OMDB_API_KEY, 't' => $title, 'type' => 'movie', 'plot' => 'full'];
    if ($year) $p['y'] = $year;
    return _omdb_request($p);
}

function omdb_by_id(string $imdb_id): ?array {
    return _omdb_request(['apikey' => OMDB_API_KEY, 'i' => $imdb_id, 'plot' => 'full']);
}

function omdb_search(string $query, ?int $year = null): array {
    $p = ['apikey' => OMDB_API_KEY, 's' => $query, 'type' => 'movie'];
    if ($year) $p['y'] = $year;
    $data = _omdb_request($p, 'Search');
    return $data ?? [];
}

function omdb_to_fields(array $o): array {
    return [
        'imdb_id'       => $o['imdbID'] ?? null,
        'title'         => $o['Title'] ?? '',
        'year'          => isset($o['Year']) && is_numeric($o['Year']) ? (int)$o['Year'] : null,
        'director'      => _omdb_val($o['Director'] ?? ''),
        'length_mins'   => _parse_runtime($o['Runtime'] ?? ''),
        'synopsis'      => _omdb_val($o['Plot'] ?? ''),
        'certificate'   => _omdb_val($o['Rated'] ?? ''),
        'imdb_rating'   => isset($o['imdbRating']) && is_numeric($o['imdbRating']) ? (float)$o['imdbRating'] : null,
        'cover_art_url' => (_omdb_val($o['Poster'] ?? '') !== null) ? $o['Poster'] : null,
        'genres'        => _omdb_val($o['Genre'] ?? '') ? array_map('trim', explode(',', $o['Genre'])) : [],
        'writers'       => _omdb_val($o['Writer'] ?? '') ? array_map('trim', explode(',', $o['Writer'])) : [],
    ];
}

function _omdb_val(string $v): ?string {
    return ($v !== '' && $v !== 'N/A') ? $v : null;
}

function _omdb_request(array $params, ?string $key = null): ?array {
    $url  = OMDB_BASE_URL . '?' . http_build_query($params);
    $json = @file_get_contents($url);
    if (!$json) return null;
    $data = json_decode($json, true);
    if (!$data || ($data['Response'] ?? '') !== 'True') return null;
    return $key ? ($data[$key] ?? null) : $data;
}

function _parse_runtime(string $s): ?int {
    return preg_match('/(\d+)\s*min/i', $s, $m) ? (int)$m[1] : null;
}
