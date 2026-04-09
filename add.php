<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$error = $success = '';
$film = [
    'date_watched' => date('Y-m-d'),
    'first_watch'  => 1,
    'finished'     => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = $_POST;
    $d['first_watch'] = isset($d['first_watch']) ? 1 : 0;
    $d['finished']    = isset($d['finished'])    ? 1 : 0;
    $d['rating']      = $d['rating'] !== '' ? (float)$d['rating'] : null;
    $d['year']        = $d['year'] ? (int)$d['year'] : null;
    $d['length_mins'] = $d['length_mins'] ? (int)$d['length_mins'] : null;
    $d['tmdb_id']     = $d['tmdb_id'] ? (int)$d['tmdb_id'] : null;
    $d['imdb_rating'] = $d['imdb_rating'] !== '' ? (float)$d['imdb_rating'] : null;

    if (!trim($d['title'] ?? '')) {
        $error = 'Title is required.';
    } else {
        $film_id = save_film($d);
        $genres  = array_filter(array_map('trim', explode(',', $d['genres'] ?? '')));
        $cast    = array_filter(array_map('trim', explode("\n", $d['cast_text'] ?? '')));
        $writers = array_filter(array_map('trim', explode(',', $d['writers_text'] ?? '')));
        $cast    = array_map(fn($line) => ['name' => $line, 'character_name' => null], $cast);
        save_film_relations($film_id, $genres, $cast, $writers, (array)($d['watchers'] ?? []));
        header('Location: ' . BASE_URL . 'film.php?id=' . $film_id . '&added=1');
        exit;
    }
}

$watchers  = get_watchers();
$page_title = 'Add Film — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container px-3" style="max-width:760px">
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold">Add Film</h4>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- IMDB Search -->
<div class="card mb-4 shadow-sm border-warning">
    <div class="card-header bg-warning text-dark fw-semibold"><i class="bi bi-search me-2"></i>Search IMDB</div>
    <div class="card-body pb-2">
        <div class="row g-2 align-items-end">
            <div class="col">
                <input type="text" id="imdbSearch" class="form-control" placeholder="Film title…" autocomplete="off">
            </div>
            <div class="col-3">
                <input type="number" id="imdbYear" class="form-control" placeholder="Year" min="1900" max="2030">
            </div>
            <div class="col-auto">
                <button type="button" id="imdbSearchBtn" class="btn btn-warning fw-semibold"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div id="searchResults" class="mt-2"></div>
    </div>
</div>

<form method="post" id="filmForm">
    <input type="hidden" name="imdb_id" id="f_imdb_id">
    <input type="hidden" name="tmdb_id" id="f_tmdb_id">

    <?php include __DIR__ . '/includes/film_form_fields.php'; ?>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 pb-4">
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-warning fw-bold px-4"><i class="bi bi-plus-lg me-1"></i>Add Film</button>
    </div>
</form>
</div>

<?php
$extra_js = "const BASE_URL = '" . BASE_URL . "';";
require_once __DIR__ . '/includes/footer.php';
?>
