<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$error = '';
$rows  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    $file = $_FILES['csv']['tmp_name'];
    if ($file && ($fh = fopen($file, 'r')) !== false) {
        $headers = array_map('trim', fgetcsv($fh));
        // Normalise header names
        $map = [];
        foreach ($headers as $i => $h) {
            $map[strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $h))] = $i;
        }
        while (($row = fgetcsv($fh)) !== false) {
            $get = fn(string $k, string $default = '') => trim($row[$map[$k] ?? -1] ?? $default);
            $date_raw = $get('date_watched');
            // Convert DD/MM/YYYY to YYYY-MM-DD
            $date_watched = null;
            if ($date_raw && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date_raw, $m)) {
                $date_watched = "{$m[3]}-{$m[2]}-{$m[1]}";
            } elseif ($date_raw) {
                $date_watched = date('Y-m-d', strtotime($date_raw)) ?: null;
            }
            $rows[] = [
                'title'        => $get('title'),
                'year'         => (int)$get('year') ?: null,
                'date_watched' => $date_watched,
                'first_watch'  => strtolower($get('first_watch')) === 'yes' ? 1 : 0,
                'source'       => normalize_source($get('source')),
                'finished'     => strtolower($get('finished')) === 'yes' ? 1 : 0,
                'watchers_raw' => $get('watchers'),
            ];
        }
        fclose($fh);
        if (empty($rows)) $error = 'No data rows found in CSV.';
    } else {
        $error = 'Could not read uploaded file.';
    }
}

$watchers     = get_watchers();
$watcher_map  = array_column($watchers, 'id', 'name');
$page_title   = 'Import CSV — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container px-3" style="max-width:900px">
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0 fw-bold">Import CSV</h4>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (empty($rows)): ?>
<!-- Upload step -->
<div class="card shadow-sm">
    <div class="card-body">
        <p class="text-muted">Upload a CSV with the columns: <code>Title, Year, Date Watched, First Watch, Source, Finished, Watchers</code></p>
        <p class="text-muted small">Date format: <code>DD/MM/YYYY</code>. Each row will be matched against IMDB to fill in director, genres, cast, etc.</p>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-semibold">CSV File</label>
                <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
            </div>
            <button class="btn btn-warning fw-bold"><i class="bi bi-upload me-1"></i>Upload &amp; Preview</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Preview & import step -->
<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Found <strong><?= count($rows) ?></strong> rows. Click <strong>Start Import</strong> to match each film against IMDB and import. This may take a few minutes.
</div>

<div class="d-flex gap-2 mb-3 flex-wrap">
    <button id="startImport" class="btn btn-warning fw-bold"><i class="bi bi-cloud-download me-1"></i>Start Import</button>
    <a href="import.php" class="btn btn-outline-secondary">Cancel</a>
    <div id="importProgress" class="d-none align-items-center gap-2 ms-2">
        <div class="progress flex-grow-1" style="min-width:150px;height:20px">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:0%"></div>
        </div>
        <span id="progressText" class="small text-muted">0 / <?= count($rows) ?></span>
    </div>
</div>

<div class="table-responsive rounded shadow-sm">
<table class="table table-sm table-hover align-middle bg-white mb-0" id="importTable">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Title (CSV)</th>
            <th>Year</th>
            <th>Date Watched</th>
            <th>Source</th>
            <th>Watchers</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $i => $row): ?>
        <?php
        // Resolve watcher IDs
        $wids = [];
        foreach (array_map('trim', explode(',', $row['watchers_raw'])) as $wname) {
            if (isset($watcher_map[$wname])) $wids[] = $watcher_map[$wname];
        }
        $row_json = json_encode([
            'title'        => $row['title'],
            'year'         => $row['year'],
            'date_watched' => $row['date_watched'],
            'first_watch'  => $row['first_watch'],
            'source'       => $row['source'],
            'finished'     => $row['finished'],
            'watcher_ids'  => $wids,
        ]);
        ?>
        <tr id="row_<?= $i ?>" data-row='<?= htmlspecialchars($row_json, ENT_QUOTES) ?>'>
            <td class="text-muted small"><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= e($row['title']) ?></td>
            <td><?= $row['year'] ?: '—' ?></td>
            <td class="small"><?= $row['date_watched'] ? date('d M Y', strtotime($row['date_watched'])) : '—' ?></td>
            <td><?= $row['source'] ? source_badge($row['source']) : '' ?></td>
            <td class="small"><?= e($row['watchers_raw']) ?></td>
            <td id="status_<?= $i ?>"><span class="badge bg-secondary">Pending</span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div id="importSummary" class="mt-3 d-none">
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i>
        Import complete: <strong id="countImported">0</strong> imported,
        <strong id="countUnmatched">0</strong> unmatched.
        <a href="index.php" class="alert-link ms-2">View Films &rarr;</a>
    </div>
</div>

<?php
$api_url = BASE_URL . 'api/import-row.php';
$total   = count($rows);
$extra_js = "const IMPORT_API = '$api_url'; const IMPORT_TOTAL = $total;";
?>

<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
