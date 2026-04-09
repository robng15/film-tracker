/* ── IMDB Search (add.php / edit.php) ── */

let searchDebounce = null;

function initImdbSearch() {
    const searchInput = document.getElementById('imdbSearch');
    const yearInput   = document.getElementById('imdbYear');
    const searchBtn   = document.getElementById('imdbSearchBtn');
    const resultsDiv  = document.getElementById('searchResults');
    if (!searchInput) return;

    searchBtn.addEventListener('click', doSearch);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(doSearch, 500);
    });

    async function doSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) return;
        resultsDiv.innerHTML = '<div class="text-muted small p-2"><i class="bi bi-hourglass-split me-1"></i>Searching…</div>';

        const params = new URLSearchParams({ q, year: yearInput.value });
        const res    = await fetch(BASE_URL + 'api/search.php?' + params);
        const data   = await res.json();

        if (!data.length) {
            resultsDiv.innerHTML = '<div class="text-muted small p-2">No results found.</div>';
            return;
        }

        resultsDiv.innerHTML = '';
        data.forEach(film => {
            const el = document.createElement('div');
            el.className = 'search-result-item';
            el.innerHTML = `
                ${film.poster
                    ? `<img src="${film.poster}" alt="">`
                    : `<div class="no-poster"><i class="bi bi-film"></i></div>`}
                <div>
                    <div class="fw-semibold">${escHtml(film.title)}</div>
                    <div class="text-muted small">${escHtml(film.year || '')} &bull; ${escHtml(film.imdb_id)}</div>
                </div>`;
            el.addEventListener('click', () => fetchAndPopulate(film.imdb_id, resultsDiv));
            resultsDiv.appendChild(el);
        });
    }
}

async function fetchAndPopulate(imdb_id, resultsDiv) {
    resultsDiv.innerHTML = '<div class="text-muted small p-2"><i class="bi bi-hourglass-split me-1"></i>Fetching data…</div>';
    const res  = await fetch(BASE_URL + 'api/fetch.php?imdb_id=' + encodeURIComponent(imdb_id));
    const data = await res.json();
    if (data.error) {
        resultsDiv.innerHTML = `<div class="alert alert-danger">${escHtml(data.error)}</div>`;
        return;
    }
    populateForm(data);
    resultsDiv.innerHTML = `<div class="alert alert-success py-1 mt-1"><i class="bi bi-check-circle me-1"></i>Populated: <strong>${escHtml(data.title)}</strong> (${data.year || ''})</div>`;
}

function populateForm(d) {
    setVal('f_imdb_id',     d.imdb_id     || '');
    setVal('f_tmdb_id',     d.tmdb_id     || '');
    setVal('f_title',       d.title       || '');
    setVal('f_year',        d.year        || '');
    setVal('f_director',    d.director    || '');
    setVal('f_certificate', d.certificate || '');
    setVal('f_length_mins', d.length_mins || '');
    setVal('f_synopsis',    d.synopsis    || '');
    setVal('f_imdb_rating', d.imdb_rating || '');
    setVal('f_genres',      (d.genres || []).join(', '));
    setVal('f_writers',     (d.writers || []).join(', '));

    // Cast textarea
    const castLines = (d.cast || []).map(c => c.name).join('\n');
    setVal('f_cast', castLines);

    // Cover art
    if (d.cover_art_url) {
        setVal('f_cover_art_url', d.cover_art_url);
        updateCoverPreview(d.cover_art_url);
    }
}

/* ── Rating slider ── */
function initRatingSlider() {
    const slider  = document.getElementById('f_rating');
    const valEl   = document.getElementById('ratingVal');
    const starsEl = document.getElementById('ratingStars');
    const setFlag = document.getElementById('rating_set');
    if (!slider) return;

    slider.addEventListener('input', () => {
        const v = parseFloat(slider.value);
        valEl.textContent = v.toFixed(1);
        if (setFlag) setFlag.value = '1';
        if (starsEl) starsEl.innerHTML = renderStars(v);
    });
}

function clearRating() {
    const slider  = document.getElementById('f_rating');
    const valEl   = document.getElementById('ratingVal');
    const starsEl = document.getElementById('ratingStars');
    const setFlag = document.getElementById('rating_set');
    if (valEl)   valEl.textContent = '—';
    if (starsEl) starsEl.innerHTML = '';
    if (setFlag) setFlag.value = '0';
}

function renderStars(rating) {
    const stars = rating / 2;
    const full  = Math.floor(stars);
    const half  = (stars - full) >= 0.5 ? 1 : 0;
    const empty = Math.max(0, 5 - full - half);
    return '<span class="text-warning">'
        + '<i class="bi bi-star-fill"></i>'.repeat(full)
        + (half ? '<i class="bi bi-star-half"></i>' : '')
        + '<i class="bi bi-star"></i>'.repeat(empty)
        + '</span>';
}

/* ── Other Source toggle ── */
function toggleOtherSource(sel) {
    const other = document.getElementById('sourceOther');
    if (!other) return;
    other.style.display = sel.value === 'Other' ? '' : 'none';
    if (sel.value !== 'Other') other.value = '';
}

/* ── CSV Import ── */
function initImport() {
    const btn = document.getElementById('startImport');
    if (!btn || typeof IMPORT_API === 'undefined') return;

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Importing…';
        document.getElementById('importProgress').classList.remove('d-none');
        document.getElementById('importProgress').classList.add('d-flex');

        const rows = document.querySelectorAll('#importTable tbody tr');
        let imported = 0, unmatched = 0;

        for (let i = 0; i < rows.length; i++) {
            const row     = rows[i];
            const rowData = JSON.parse(row.dataset.row);
            const statusEl = document.getElementById('status_' + i);
            statusEl.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Fetching…</span>';

            try {
                const res  = await fetch(IMPORT_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(rowData),
                });
                const data = await res.json();

                if (data.status === 'imported') {
                    imported++;
                    statusEl.innerHTML = data.poster
                        ? `<span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>${escHtml(data.title)}</span>`
                        : `<span class="badge bg-success"><i class="bi bi-check-lg"></i> Imported</span>`;
                } else if (data.status === 'unmatched') {
                    unmatched++;
                    statusEl.innerHTML = `<span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>No match</span>
                        <a href="${BASE_URL}add.php" class="btn btn-xs btn-outline-secondary ms-1">Add manually</a>`;
                } else {
                    unmatched++;
                    statusEl.innerHTML = `<span class="badge bg-danger" title="${escHtml(data.error || '')}">Error</span>`;
                }
            } catch (e) {
                unmatched++;
                statusEl.innerHTML = '<span class="badge bg-danger">Error</span>';
            }

            // Update progress
            const pct = Math.round(((i + 1) / IMPORT_TOTAL) * 100);
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressText').textContent = (i + 1) + ' / ' + IMPORT_TOTAL;
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        document.getElementById('countImported').textContent = imported;
        document.getElementById('countUnmatched').textContent = unmatched;
        document.getElementById('importSummary').classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Done';
    });
}

/* ── Helpers ── */
function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str || '');
    return d.innerHTML;
}

/* ── Cover art preview (manual URL entry) ── */
function updateCoverPreview(url) {
    const img  = document.getElementById('coverPreview');
    const wrap = document.getElementById('coverPreviewWrap');
    if (!img || !wrap) return;
    if (url) {
        img.src = url;
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
    }
}

function initCoverArtInput() {
    const input = document.getElementById('f_cover_art_url');
    if (!input) return;
    input.addEventListener('input', () => updateCoverPreview(input.value.trim()));
    // Show preview on load if value present
    if (input.value.trim()) updateCoverPreview(input.value.trim());
}

/* ── View toggle (list / browse grid) ── */
function initViewToggle() {
    const btnList    = document.getElementById('viewList');
    const btnGrid    = document.getElementById('viewGrid');
    const listView   = document.getElementById('listView');
    const browseGrid = document.getElementById('browseGrid');
    const viewInput  = document.getElementById('viewInput');
    if (!btnList || !btnGrid || !browseGrid) return;

    // Server-rendered view is the source of truth
    const current = (viewInput ? viewInput.value : null) || 'list';

    function applyView(view) {
        const isBrowse = view === 'grid';
        browseGrid.style.display = isBrowse ? '' : 'none';
        if (listView) listView.style.display = isBrowse ? 'none' : '';
        btnList.classList.toggle('active', !isBrowse);
        btnGrid.classList.toggle('active',  isBrowse);
    }

    function navigateTo(view) {
        const url = new URL(window.location);
        url.searchParams.set('view', view);
        url.searchParams.set('page', '1');
        window.location = url.toString();
    }

    btnList.addEventListener('click', () => navigateTo('list'));
    btnGrid.addEventListener('click', () => navigateTo('grid'));
    applyView(current);
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    initImdbSearch();
    initRatingSlider();
    initCoverArtInput();
    initImport();
    initViewToggle();
});
