<?php
function tmdb_find_id(string $title, ?int $year = null): ?int {
    $p = ['query' => $title, 'language' => 'en-GB'];
    if ($year) $p['year'] = $year;
    $data = _tmdb_get('search/movie', $p);
    return $data['results'][0]['id'] ?? null;
}

function tmdb_get_cast(int $tmdb_id, int $limit = 20): array {
    $data = _tmdb_get("movie/{$tmdb_id}/credits");
    if (empty($data['cast'])) return [];
    return array_slice($data['cast'], 0, $limit);
}

function _tmdb_get(string $endpoint, array $params = []): ?array {
    $url = TMDB_BASE_URL . $endpoint . ($params ? '?' . http_build_query($params) : '');
    $ctx = stream_context_create(['http' => [
        'header' => "Authorization: Bearer " . TMDB_READ_TOKEN . "\r\n" .
                    "Accept: application/json\r\n",
        'timeout' => 10,
    ]]);
    $json = @file_get_contents($url, false, $ctx);
    return $json ? json_decode($json, true) : null;
}
