<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'index.php'); exit; }

$film = get_film($id);
if (!$film) { header('Location: ' . BASE_URL . 'index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = $_POST;
    $d['first_watch'] = isset($d['first_watch']) ? 1 : 0;
    $d['finished']    = isset($d['finished'])    ? 1 : 0;
    $d['rating']      = ($d['rating_set'] ?? '0') === '1' ? (float)$d['rating'] : null;
    $d['year']        = $d['year'] ? (int)$d['year'] : null;
    $d['length_mins'] = $d['length_mins'] ? (int)$d['length_mins'] : null;
    $d['tmdb_id']     = $d['tmdb_id'] ? (int)$d['tmdb_id'] : null;
    $d['imdb_rating'] = $d['imdb_rating'] !== '' ? (float)$d['imdb_rating'] : null;

    if (!trim($d['title'] ?? '')) {
        $error = 'Title is required.';
    } else {
        save_film($d, $id);
        $genres  = array_filter(array_map('trim', explode(',', $d['genres'] ?? '')));
        $cast    = array_filter(array_map('trim', explode("\n", $d['cast_text'] ?? '')));
        $writers = array_filter(array_map('trim', explode(',', $d['writers_text'] ?? '')));
        $cast    = array_map(fn($line) => ['name' => $line, 'character_name' => null], $cast);
        save_film_relations($id, $genres, $cast, $writers, (array)($d['watchers'] ?? []));
        header('Location: ' . BASE_URL . 'film.php?id=' . $id . '&saved=1');
        exit;
    }
}

$watchers  = get_watchers();
$page_title = 'Edit — ' . $film['title'] . ' — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container px-3" style="max-width:760px">
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="film.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Edit Film</h4>
    </div>
    <button type="button" class="btn btn-sm btn-outline-danger"
            onclick="if(confirm('Delete this film?')) window.location='delete.php?id=<?= $id ?>&csrf=<?= e($_SESSION['user_id'] ?? '') ?>'">
        <i class="bi bi-trash"></i> Delete
    </button>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- IMDB re-fetch -->
<div class="card mb-4 shadow-sm border-warning">
    <div class="card-header bg-warning text-dark fw-semibold"><i class="bi bi-arrow-repeat me-2"></i>Re-fetch from IMDB</div>
    <div class="card-body pb-2">
        <div class="row g-2 align-items-end">
            <div class="col">
                <input type="text" id="imdbSearch" class="form-control" placeholder="Film title…"
                       value="<?= e($film['title']) ?>" autocomplete="off">
            </div>
            <div class="col-3">
                <input type="number" id="imdbYear" class="form-control" placeholder="Year" value="<?= e($film['year'] ?? '') ?>">
            </div>
            <div class="col-auto">
                <button type="button" id="imdbSearchBtn" class="btn btn-warning fw-semibold"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div id="searchResults" class="mt-2"></div>
    </div>
</div>

<form method="post" id="filmForm">
    <input type="hidden" name="imdb_id" id="f_imdb_id" value="<?= e($film['imdb_id'] ?? '') ?>">
    <input type="hidden" name="tmdb_id" id="f_tmdb_id" value="<?= e($film['tmdb_id'] ?? '') ?>">

    <?php include __DIR__ . '/includes/film_form_fields.php'; ?>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 pb-4">
        <a href="film.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-warning fw-bold px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
    </div>
</form>
</div>

<?php
$extra_js = "const BASE_URL = '" . BASE_URL . "';";
require_once __DIR__ . '/includes/footer.php';
?>
