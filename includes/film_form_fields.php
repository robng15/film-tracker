<div class="row g-3">

    <!-- Cover art preview -->
    <div class="col-12 text-center" id="coverPreviewWrap" style="<?= empty($film['cover_art_url']) ? 'display:none' : '' ?>">
        <img id="coverPreview" src="<?= e($film['cover_art_url'] ?? '') ?>" style="width:100px;border-radius:6px" alt="Cover">
        <input type="hidden" name="cover_art_url" id="f_cover_art_url" value="<?= e($film['cover_art_url'] ?? '') ?>">
    </div>

    <!-- Title -->
    <div class="col-12 col-md-8">
        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" id="f_title" class="form-control" required value="<?= e($film['title'] ?? '') ?>">
    </div>

    <!-- Year -->
    <div class="col-6 col-md-4">
        <label class="form-label fw-semibold">Year</label>
        <input type="number" name="year" id="f_year" class="form-control" min="1888" max="2030" value="<?= e($film['year'] ?? '') ?>">
    </div>

    <!-- Date Watched -->
    <div class="col-6 col-md-4">
        <label class="form-label fw-semibold">Date Watched</label>
        <input type="date" name="date_watched" id="f_date_watched" class="form-control" value="<?= e($film['date_watched'] ?? date('Y-m-d')) ?>">
    </div>

    <!-- Source -->
    <div class="col-6 col-md-4">
        <label class="form-label fw-semibold">Source</label>
        <select name="source" id="f_source" class="form-select" onchange="toggleOtherSource(this)">
            <option value="">— Select —</option>
            <?php foreach (SOURCES as $s): ?>
                <option value="<?= e($s) ?>"<?= ($film['source'] ?? '') === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="source_other" id="sourceOther" class="form-control mt-1"
               placeholder="Specify source…"
               style="<?= !in_array($film['source'] ?? '', SOURCES) && ($film['source'] ?? '') !== '' ? '' : 'display:none' ?>"
               value="<?= !in_array($film['source'] ?? '', SOURCES) ? e($film['source'] ?? '') : '' ?>">
    </div>

    <!-- Rating -->
    <div class="col-6 col-md-4">
        <label class="form-label fw-semibold">Our Rating <span class="text-muted small">(0–10)</span></label>
        <div class="d-flex align-items-center gap-2">
            <input type="range" name="rating" id="f_rating" class="form-range flex-grow-1"
                   min="0" max="10" step="0.5"
                   value="<?= $film['rating'] ?? 5 ?>"
                   oninput="document.getElementById('ratingVal').textContent = this.value">
            <span id="ratingVal" class="fw-bold" style="min-width:2.5rem;text-align:right">
                <?= $film['rating'] !== null ? number_format((float)$film['rating'], 1) : '—' ?>
            </span>
        </div>
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="clearRating()">Clear rating</button>
            <small class="text-muted" id="ratingStars"><?= $film['rating'] !== null ? render_stars((float)$film['rating']) : '' ?></small>
        </div>
        <input type="hidden" name="rating_set" id="rating_set" value="<?= $film['rating'] !== null ? '1' : '0' ?>">
    </div>

    <!-- Watchers -->
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Watchers</label>
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($watchers as $w): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="watchers[]"
                       id="w_<?= $w['id'] ?>" value="<?= $w['id'] ?>"
                       <?= in_array($w['id'], $film['watchers'] ?? []) ? 'checked' : '' ?>>
                <label class="form-check-label" for="w_<?= $w['id'] ?>"><?= e($w['name']) ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- First Watch / Finished -->
    <div class="col-6 col-md-3">
        <div class="form-check form-switch mt-md-4">
            <input class="form-check-input" type="checkbox" name="first_watch" id="f_first_watch" role="switch"
                   <?= ($film['first_watch'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="f_first_watch">First Watch</label>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="form-check form-switch mt-md-4">
            <input class="form-check-input" type="checkbox" name="finished" id="f_finished" role="switch"
                   <?= ($film['finished'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="f_finished">Finished</label>
        </div>
    </div>

</div>

<hr class="my-3">
<p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Fields below are populated from IMDB but can be edited manually.</p>
<div class="row g-3">

    <!-- Director -->
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Director</label>
        <input type="text" name="director" id="f_director" class="form-control" value="<?= e($film['director'] ?? '') ?>">
    </div>

    <!-- Certificate -->
    <div class="col-6 col-md-3">
        <label class="form-label fw-semibold">Certificate</label>
        <input type="text" name="certificate" id="f_certificate" class="form-control" value="<?= e($film['certificate'] ?? '') ?>">
    </div>

    <!-- Length -->
    <div class="col-6 col-md-3">
        <label class="form-label fw-semibold">Length (mins)</label>
        <input type="number" name="length_mins" id="f_length_mins" class="form-control" min="1" value="<?= e($film['length_mins'] ?? '') ?>">
    </div>

    <!-- IMDB Rating -->
    <div class="col-6 col-md-3">
        <label class="form-label fw-semibold">IMDB Rating</label>
        <input type="number" name="imdb_rating" id="f_imdb_rating" class="form-control" step="0.1" min="0" max="10" value="<?= e($film['imdb_rating'] ?? '') ?>">
    </div>

    <!-- Genre -->
    <div class="col-12 col-md-9">
        <label class="form-label fw-semibold">Genres <span class="text-muted small">(comma-separated)</span></label>
        <input type="text" name="genres" id="f_genres" class="form-control"
               value="<?= e(implode(', ', $film['genres'] ?? [])) ?>"
               placeholder="Action, Thriller, Drama…">
    </div>

    <!-- Synopsis -->
    <div class="col-12">
        <label class="form-label fw-semibold">Synopsis</label>
        <textarea name="synopsis" id="f_synopsis" class="form-control" rows="3"><?= e($film['synopsis'] ?? '') ?></textarea>
    </div>

    <!-- Cast -->
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Cast (top 20) <span class="text-muted small">(one per line)</span></label>
        <textarea name="cast_text" id="f_cast" class="form-control" rows="8"><?php
            foreach ($film['cast'] ?? [] as $c) echo e($c['name']) . "\n";
        ?></textarea>
    </div>

    <!-- Writers -->
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Writers <span class="text-muted small">(comma-separated)</span></label>
        <input type="text" name="writers_text" id="f_writers" class="form-control"
               value="<?= e(implode(', ', $film['writers'] ?? [])) ?>">
    </div>

</div>
