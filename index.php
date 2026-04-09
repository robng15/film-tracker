<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$sort    = in_array($_GET['sort'] ?? '', ['title','year','date_watched','rating','imdb_rating']) ? $_GET['sort'] : 'date_watched';
$dir     = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$year_w  = (int)($_GET['year_w'] ?? 0);
$year_r  = (int)($_GET['year_r'] ?? 0);
$source  = $_GET['source'] ?? '';
$genre   = $_GET['genre'] ?? '';
$watcher = (int)($_GET['watcher'] ?? 0);
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 25;

$where  = ['1=1'];
$params = [];
if ($year_w)  { $where[] = "strftime('%Y', f.date_watched) = ?"; $params[] = (string)$year_w; }
if ($year_r)  { $where[] = 'f.year = ?';       $params[] = $year_r; }
if ($source)  { $where[] = 'f.source = ?';     $params[] = $source; }
if ($genre)   { $where[] = 'EXISTS (SELECT 1 FROM film_genres fg JOIN genres g ON g.id=fg.genre_id WHERE fg.film_id=f.id AND g.name=?)'; $params[] = $genre; }
if ($watcher) { $where[] = 'EXISTS (SELECT 1 FROM film_watchers fw WHERE fw.film_id=f.id AND fw.watcher_id=?)'; $params[] = $watcher; }
if ($search)  { $where[] = '(f.title LIKE ? OR f.director LIKE ? OR EXISTS (SELECT 1 FROM film_cast fc WHERE fc.film_id=f.id AND fc.name LIKE ?))'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$w_sql = implode(' AND ', $where);
$db    = db();

$total = (int)$db->prepare("SELECT COUNT(*) FROM films f WHERE $w_sql")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM films f WHERE $w_sql")->execute($params) : 0;
$total_s = $db->prepare("SELECT COUNT(*) FROM films f WHERE $w_sql");
$total_s->execute($params);
$total = (int)$total_s->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;

$q = $db->prepare("
    SELECT f.*,
           GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS genre_list,
           GROUP_CONCAT(DISTINCT w.name ORDER BY w.name) AS watcher_list
    FROM films f
    LEFT JOIN film_genres fg ON fg.film_id=f.id
    LEFT JOIN genres g ON g.id=fg.genre_id
    LEFT JOIN film_watchers fw ON fw.film_id=f.id
    LEFT JOIN watchers w ON w.id=fw.watcher_id
    WHERE $w_sql
    GROUP BY f.id
    ORDER BY f.$sort $dir
    LIMIT $per OFFSET $off
");
$q->execute($params);
$films = $q->fetchAll();

$yrs_w = $db->query("SELECT DISTINCT strftime('%Y', date_watched) y FROM films WHERE date_watched IS NOT NULL ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
$yrs_r = $db->query("SELECT DISTINCT year FROM films WHERE year IS NOT NULL ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
$srcs  = $db->query("SELECT DISTINCT source FROM films WHERE source IS NOT NULL ORDER BY source")->fetchAll(PDO::FETCH_COLUMN);
$gnrs  = $db->query("SELECT DISTINCT g.name FROM genres g JOIN film_genres fg ON g.id=fg.genre_id ORDER BY g.name")->fetchAll(PDO::FETCH_COLUMN);
$wchrs = get_watchers();

$page_title = APP_NAME;

function sort_link(string $col, string $label): string {
    global $sort, $dir;
    $new_dir = ($sort === $col && $dir === 'DESC') ? 'asc' : 'desc';
    $p = array_merge($_GET, ['sort' => $col, 'dir' => $new_dir, 'page' => 1]);
    $icon = $sort === $col ? ($dir === 'ASC' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>') : ' <i class="bi bi-arrow-down-up opacity-50"></i>';
    return '<a href="?' . http_build_query($p) . '" class="text-white text-decoration-none">' . $label . $icon . '</a>';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-2 px-md-3">

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Year Watched</label>
                <select name="year_w" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($yrs_w as $y): ?><option value="<?= $y ?>"<?= $year_w == $y ? ' selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Year Released</label>
                <select name="year_r" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($yrs_r as $y): ?><option value="<?= $y ?>"<?= $year_r == $y ? ' selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Source</label>
                <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($srcs as $s): ?><option value="<?= e($s) ?>"<?= $source === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Genre</label>
                <select name="genre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($gnrs as $g): ?><option value="<?= e($g) ?>"<?= $genre === $g ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Watcher</label>
                <select name="watcher" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($wchrs as $w): ?><option value="<?= $w['id'] ?>"<?= $watcher == $w['id'] ? ' selected' : '' ?>><?= e($w['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small mb-1">Search Title / Director / Cast</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search…" value="<?= e($search) ?>">
                    <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($search || $year_w || $year_r || $source || $genre || $watcher): ?>
                        <a href="index.php" class="btn btn-outline-danger" title="Clear"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= strtolower($dir) ?>">
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2 px-1">
    <small class="text-muted"><?= number_format($total) ?> film<?= $total !== 1 ? 's' : '' ?></small>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group" aria-label="View">
            <button type="button" id="viewList" class="btn btn-outline-secondary" title="List view"><i class="bi bi-list-ul"></i></button>
            <button type="button" id="viewGrid" class="btn btn-outline-secondary" title="Browse view"><i class="bi bi-grid-3x3-gap-fill"></i></button>
        </div>
        <a href="<?= BASE_URL ?>add.php" class="btn btn-sm btn-warning fw-semibold d-none d-md-inline-flex">
            <i class="bi bi-plus-lg me-1"></i>Add Film
        </a>
    </div>
</div>

<!-- Mobile cards -->
<div class="d-md-none vstack gap-2">
<?php foreach ($films as $f): ?>
<div class="card shadow-sm film-card">
    <div class="card-body p-2">
        <div class="d-flex gap-2">
            <a href="<?= $f['imdb_id'] ? 'https://www.imdb.com/title/' . e($f['imdb_id']) . '/' : '#' ?>" <?= $f['imdb_id'] ? 'target="_blank"' : '' ?> class="flex-shrink-0">
                <?php if ($f['cover_art_url']): ?>
                    <img src="<?= e($f['cover_art_url']) ?>" class="poster-sm rounded" alt="">
                <?php else: ?>
                    <div class="poster-sm poster-ph rounded d-flex align-items-center justify-content-center"><i class="bi bi-film text-white fs-4"></i></div>
                <?php endif; ?>
            </a>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div class="min-w-0">
                        <?php if ($f['imdb_id']): ?>
                            <a href="https://www.imdb.com/title/<?= e($f['imdb_id']) ?>/" target="_blank" class="fw-bold d-block text-truncate text-dark text-decoration-none film-title"><?= e($f['title']) ?></a>
                        <?php else: ?>
                            <span class="fw-bold d-block text-truncate"><?= e($f['title']) ?></span>
                        <?php endif; ?>
                        <div class="text-muted small"><?= $f['year'] ?><?= $f['length_mins'] ? ' · ' . format_runtime($f['length_mins']) : '' ?></div>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <a href="film.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </div>
                </div>
                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                    <?php if ($f['source']): echo source_badge($f['source']); endif; ?>
                    <?php if ($f['date_watched']): ?><small class="text-muted"><?= date('d M Y', strtotime($f['date_watched'])) ?></small><?php endif; ?>
                    <?php if ($f['first_watch']): ?><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-eye-fill"></i> First</span><?php endif; ?>
                    <?php if (!$f['finished']): ?><span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split"></i></span><?php endif; ?>
                </div>
                <div class="mt-1 d-flex justify-content-between align-items-center">
                    <div><?= render_stars($f['rating'] !== null ? (float)$f['rating'] : null) ?><?= $f['rating'] !== null ? ' <small class="ms-1 text-muted">' . number_format((float)$f['rating'], 1) . '</small>' : '' ?></div>
                    <?php if ($f['imdb_rating']): ?><small class="text-muted"><i class="bi bi-star-fill text-warning"></i> <?= number_format((float)$f['imdb_rating'], 1) ?> IMDB</small><?php endif; ?>
                </div>
                <?php if ($f['genre_list']): ?>
                <div class="mt-1 genre-tags">
                    <?php foreach (explode(',', $f['genre_list']) as $g): ?>
                        <a href="?genre=<?= urlencode(trim($g)) ?>" class="badge bg-dark text-white text-decoration-none"><?= e(trim($g)) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($films)): ?>
    <div class="text-center text-muted py-5"><i class="bi bi-film fs-1 d-block mb-2"></i>No films found.</div>
<?php endif; ?>
</div>

<!-- Desktop table -->
<div class="d-none d-md-block">
<div class="table-responsive rounded shadow-sm">
<table class="table table-hover align-middle mb-0 bg-white">
    <thead class="table-dark">
        <tr>
            <th style="width:56px"></th>
            <th><?= sort_link('title', 'Title') ?></th>
            <th><?= sort_link('year', 'Year') ?></th>
            <th><?= sort_link('date_watched', 'Watched') ?></th>
            <th>Source</th>
            <th>Watchers</th>
            <th><?= sort_link('rating', 'Rating') ?></th>
            <th><?= sort_link('imdb_rating', 'IMDB') ?></th>
            <th>Genre</th>
            <th style="width:80px"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($films as $f): ?>
    <tr>
        <td class="p-1 text-center">
            <?php if ($f['cover_art_url']): ?>
                <img src="<?= e($f['cover_art_url']) ?>" class="poster-xs rounded" alt="">
            <?php else: ?>
                <div class="poster-xs poster-ph rounded d-flex align-items-center justify-content-center mx-auto"><i class="bi bi-film text-white"></i></div>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($f['imdb_id']): ?>
                <a href="https://www.imdb.com/title/<?= e($f['imdb_id']) ?>/" target="_blank" class="fw-semibold text-dark text-decoration-none title-hover"><?= e($f['title']) ?></a>
            <?php else: ?>
                <span class="fw-semibold"><?= e($f['title']) ?></span>
            <?php endif; ?>
            <?php if ($f['first_watch']): ?><i class="bi bi-eye-fill text-success ms-1" title="First watch"></i><?php endif; ?>
            <?php if (!$f['finished']): ?><i class="bi bi-hourglass-split text-warning ms-1" title="Not finished"></i><?php endif; ?>
        </td>
        <td><?= $f['year'] ?: '—' ?></td>
        <td class="text-nowrap small"><?= $f['date_watched'] ? date('d M Y', strtotime($f['date_watched'])) : '' ?></td>
        <td><?= $f['source'] ? source_badge($f['source']) : '' ?></td>
        <td class="small text-nowrap"><?= e($f['watcher_list'] ?? '') ?></td>
        <td class="text-nowrap small">
            <?= render_stars($f['rating'] !== null ? (float)$f['rating'] : null) ?>
            <?= $f['rating'] !== null ? '<span class="ms-1">' . number_format((float)$f['rating'], 1) . '</span>' : '' ?>
        </td>
        <td class="small"><?= $f['imdb_rating'] ? number_format((float)$f['imdb_rating'], 1) : '—' ?></td>
        <td>
            <?php if ($f['genre_list']): ?>
            <div class="genre-tags">
                <?php foreach (explode(',', $f['genre_list']) as $g): ?>
                    <a href="?genre=<?= urlencode(trim($g)) ?>" class="badge bg-dark text-white text-decoration-none"><?= e(trim($g)) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-nowrap">
            <a href="film.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details"><i class="bi bi-eye"></i></a>
            <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($films)): ?>
        <tr><td colspan="10" class="text-center text-muted py-5">No films found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<!-- Browse grid (hidden by default, toggled by JS) -->
<div id="browseGrid" style="display:none">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-2">
    <?php foreach ($films as $f): ?>
    <div class="col">
        <div class="card browse-card h-100 border-0 shadow-sm">
            <a href="film.php?id=<?= $f['id'] ?>" class="position-relative d-block">
                <?php if ($f['cover_art_url']): ?>
                    <img src="<?= e($f['cover_art_url']) ?>" class="card-img-top browse-poster" alt="">
                <?php else: ?>
                    <div class="browse-poster poster-ph d-flex align-items-center justify-content-center">
                        <i class="bi bi-film text-white" style="font-size:2.5rem"></i>
                    </div>
                <?php endif; ?>
                <!-- Overlay badges -->
                <div class="position-absolute top-0 start-0 p-1 d-flex flex-column gap-1">
                    <?php if ($f['first_watch']): ?><span class="badge bg-success bg-opacity-90" title="First watch"><i class="bi bi-eye-fill"></i></span><?php endif; ?>
                    <?php if (!$f['finished']): ?><span class="badge bg-warning bg-opacity-90 text-dark" title="Not finished"><i class="bi bi-hourglass-split"></i></span><?php endif; ?>
                </div>
                <?php if ($f['rating'] !== null): ?>
                <div class="position-absolute bottom-0 end-0 p-1">
                    <span class="badge bg-dark bg-opacity-80 fw-bold"><?= number_format((float)$f['rating'], 1) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($f['source']): ?>
                <div class="position-absolute bottom-0 start-0 p-1">
                    <?= source_badge($f['source']) ?>
                </div>
                <?php endif; ?>
            </a>
            <div class="card-body p-2">
                <?php if ($f['imdb_id']): ?>
                    <a href="https://www.imdb.com/title/<?= e($f['imdb_id']) ?>/" target="_blank"
                       class="fw-semibold small d-block text-truncate text-dark text-decoration-none title-hover"><?= e($f['title']) ?></a>
                <?php else: ?>
                    <span class="fw-semibold small d-block text-truncate"><?= e($f['title']) ?></span>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.72rem"><?= $f['year'] ?: '—' ?><?= $f['imdb_rating'] ? ' · ★ ' . number_format((float)$f['imdb_rating'],1) : '' ?></div>
            </div>
            <div class="card-footer p-1 bg-transparent border-0 d-flex justify-content-end gap-1">
                <a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-xs btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($films)): ?>
        <div class="col-12 text-center text-muted py-5"><i class="bi bi-film fs-1 d-block mb-2"></i>No films found.</div>
    <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a></li>
        <?php endif; ?>
        <?php for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++): ?>
            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
