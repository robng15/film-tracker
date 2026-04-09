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

$watcher_names = [];
if ($film['watchers']) {
    $wids = implode(',', array_map('intval', $film['watchers']));
    $rows = db()->query("SELECT name FROM watchers WHERE id IN ($wids)")->fetchAll(PDO::FETCH_COLUMN);
    $watcher_names = $rows;
}

$page_title = $film['title'] . ' (' . $film['year'] . ') — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container px-3 pb-5" style="max-width:900px">

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>Changes saved. <button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if (isset($_GET['added'])): ?><div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>Film added. <button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <div class="ms-auto d-flex gap-2">
        <?php if ($film['imdb_id']): ?>
            <a href="https://www.imdb.com/title/<?= e($film['imdb_id']) ?>/" target="_blank" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-box-arrow-up-right me-1"></i>IMDB
            </a>
        <?php endif; ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-warning fw-semibold">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Poster -->
    <div class="col-12 col-md-3 text-center text-md-start">
        <?php if ($film['cover_art_url']): ?>
            <img src="<?= e($film['cover_art_url']) ?>" class="rounded shadow" style="width:100%;max-width:180px" alt="">
        <?php else: ?>
            <div class="rounded shadow bg-secondary d-flex align-items-center justify-content-center mx-auto mx-md-0"
                 style="width:150px;height:225px"><i class="bi bi-film text-white" style="font-size:3rem"></i></div>
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="col-12 col-md-9">
        <h2 class="fw-bold mb-1"><?= e($film['title']) ?></h2>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <?php if ($film['year']): ?><span class="badge bg-secondary"><?= $film['year'] ?></span><?php endif; ?>
            <?php if ($film['certificate']): ?><span class="badge bg-dark"><?= e($film['certificate']) ?></span><?php endif; ?>
            <?php if ($film['length_mins']): ?><span class="text-muted small"><i class="bi bi-clock me-1"></i><?= format_runtime($film['length_mins']) ?></span><?php endif; ?>
            <?php if ($film['imdb_rating']): ?><span class="text-muted small"><i class="bi bi-star-fill text-warning me-1"></i><?= number_format($film['imdb_rating'], 1) ?> IMDB</span><?php endif; ?>
        </div>

        <?php if ($film['genres']): ?>
        <div class="d-flex flex-wrap gap-1 mb-3">
            <?php foreach ($film['genres'] as $g): ?>
                <a href="index.php?genre=<?= urlencode($g) ?>" class="badge bg-dark text-white text-decoration-none"><?= e($g) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($film['synopsis']): ?>
            <p class="text-muted mb-3"><?= e($film['synopsis']) ?></p>
        <?php endif; ?>

        <?php if ($film['director']): ?>
            <p class="mb-2"><span class="fw-semibold">Director:</span> <?= e($film['director']) ?></p>
        <?php endif; ?>

        <?php if ($film['writers']): ?>
            <p class="mb-2"><span class="fw-semibold">Writers:</span> <?= e(implode(', ', $film['writers'])) ?></p>
        <?php endif; ?>

        <hr>

        <div class="row g-3">
            <div class="col-6 col-md-4">
                <div class="small text-muted">Date Watched</div>
                <div class="fw-semibold"><?= $film['date_watched'] ? date('d M Y', strtotime($film['date_watched'])) : '—' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="small text-muted">Source</div>
                <div><?= $film['source'] ? source_badge($film['source']) : '—' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="small text-muted">Watchers</div>
                <div class="fw-semibold"><?= $watcher_names ? e(implode(', ', $watcher_names)) : '—' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="small text-muted">Our Rating</div>
                <div>
                    <?= render_stars($film['rating'] !== null ? (float)$film['rating'] : null) ?>
                    <?= $film['rating'] !== null ? ' <span class="fw-bold">' . number_format((float)$film['rating'], 1) . ' / 10</span>' : '' ?>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="small text-muted">First Watch</div>
                <div><?= $film['first_watch'] ? '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>' : '<span class="text-muted">No</span>' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="small text-muted">Finished</div>
                <div><?= $film['finished'] ? '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Yes</span>' : '<span class="text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i>No</span>' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Cast -->
<?php if ($film['cast']): ?>
<div class="mt-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-people me-2"></i>Cast</h5>
    <div class="row row-cols-2 row-cols-md-4 g-2">
        <?php foreach ($film['cast'] as $c): ?>
        <div class="col">
            <div class="card h-100 border-0 bg-white shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="fw-semibold small"><?= e($c['name']) ?></div>
                    <?php if ($c['character_name']): ?><div class="text-muted" style="font-size:.75rem"><?= e($c['character_name']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
