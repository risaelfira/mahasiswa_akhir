<?php
// Add manual_unlocked_weeks column to internships for manual week unlocking (backwards compatible)
$internCols = $pdo->query("PRAGMA table_info(internships)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('manual_unlocked_weeks', $internCols, true)) {
    $pdo->exec("ALTER TABLE internships ADD COLUMN manual_unlocked_weeks INTEGER NOT NULL DEFAULT 0");
}
?>

<div class="card">
    <h2>Menu Pengisian Progres</h2>
    <p class="muted">Halaman khusus input progres, terpisah dari dashboard visualisasi.</p>
</div>

<div class="card">
    <h3>Progres Laporan Magang</h3>
    <form method="post">
        <input type="hidden" name="action" value="update_judul">
        <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? ($m['internship_id'] ?? 0)) ?>">
        <input type="hidden" name="return_page" value="progress">

        <div class="grid">
            <div>
                <label>Judul Laporan</label>
                <input
                    name="judul_laporan"
                    value="<?= e((string)($m['judul_laporan'] ?? '')) ?>"
                    onblur="this.form.requestSubmit()"
                >
            </div>

            <div>
                <label>Tanggal Pengajuan</label>
                <input
                    type="text"
                    name="tanggal_pengajuan_judul"
                    value="<?= e((string)($m['tanggal_pengajuan_judul'] ?? '')) ?>"
                    placeholder="yyyy-mm-dd"
                    onblur="this.form.requestSubmit()"
                >
            </div>

            <div>
                <label>Status Laporan</label>
                <select name="status_judul" onchange="this.form.requestSubmit()">
                    <?php foreach (['belum_diajukan','diajukan','revisi','disetujui','ditolak'] as $s): ?>
                        <option value="<?= $s ?>" <?= (($m['status_judul'] ?? '') === $s) ? 'selected' : '' ?>>
                            <?= e(str_replace('_', ' ', $s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3>Progres Magang Harian</h3>

    <?php $unlockedWeek = (int)($unlockedWeek ?? $internship_unlocked_week ?? ($m['unlocked_week'] ?? 1)); for ($weekNo = 1; $weekNo <= $unlockedWeek; $weekNo++): ?>
        <?php $weekReports = $dailyByWeek[$weekNo] ?? []; ?>

        <h4 class="week-title">Minggu <?= $weekNo ?></h4>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-date">Tanggal</th>
                        <th>Kegiatan Harian</th>
                        <th class="col-photos">Foto</th>
                        <th class="col-check">Report Selesai</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$weekReports): ?>
                    <tr>
                        <td colspan="4">Belum ada data minggu ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weekReports as $index => $dr): ?>
                        <?php $formId = 'daily-form-' . $weekNo . '-' . $index; ?>
                        <tr>
                            <td>
                                <strong><?= e((string)($dr['tanggal'] ?? '-')) ?></strong>

                                <form id="<?= e($formId) ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save_daily_report">
                                    <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? ($m['internship_id'] ?? 0)) ?>">
                                    <input type="hidden" name="report_date" value="<?= e((string)($dr['tanggal'] ?? '')) ?>">
                                    <input type="hidden" name="existing_photo" value="<?= e((string)($dr['photo_path'] ?? '')) ?>">
                                    <input type="hidden" name="return_page" value="progress">
                                    <input type="hidden" name="checklist_present" value="1">
                                </form>
                            </td>

                            <td>
                                <textarea
                                    name="kegiatan"
                                    form="<?= e($formId) ?>"
                                    onblur="document.getElementById('<?= e($formId) ?>').requestSubmit()"
                                ><?= e((string)($dr['kegiatan'] ?? '')) ?></textarea>
                            </td>

                            <td>
                                <?php
                                $uploadedPhotos = array_values(array_filter(
                                    array_map('trim', explode(',', (string)($dr['photo_path'] ?? ''))),
                                    static fn($photo): bool => $photo !== ''
                                ));
                                ?>

                                <?php if (!empty($uploadedPhotos)): ?>
                                    <div class="status-chip status-success">
                                        ✅ <?= count($uploadedPhotos) ?> foto terupload
                                    </div>
                                <?php else: ?>
                                    <div class="status-chip status-warning">
                                        ⚠️ Belum upload foto
                                    </div>
                                <?php endif; ?>

                                <input
                                    type="file"
                                    name="foto_kegiatan[]"
                                    form="<?= e($formId) ?>"
                                    accept="image/*"
                                    multiple
                                    onchange="document.getElementById('<?= e($formId) ?>').requestSubmit()"
                                >
                                <div class="small">Maksimal 3 foto per upload</div>
                            </td>

                            <td>
                                <label class="check-box">
                                    <input
                                        type="checkbox"
                                        name="checklist"
                                        value="1"
                                        form="<?= e($formId) ?>"
                                        <?= ((int)($dr['checklist'] ?? 0) === 1) ? 'checked' : '' ?>
                                        onchange="document.getElementById('<?= e($formId) ?>').requestSubmit()"
                                    >
                                    <span>Checklist</span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endfor; ?>
</div>

<div class="card">
    <h3>Progres Mingguan Magang</h3>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th class="col-week">Minggu</th>
                    <th>Rencana</th>
                    <th>Realisasi</th>
                    <th>Umpan Balik</th>
                    <th class="col-week-status">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $weeks = $weeks ?? []; ?>
            <?php foreach ($weeks as $index => $wk): ?>
                <?php if ((int)($wk['minggu'] ?? 0) > $unlockedWeek) continue; ?>
                <?php $weekFormId = 'week-form-' . $index; ?>

                <tr>
                    <td>
                        <strong><?= (int)($wk['minggu'] ?? 0) ?></strong>
                        <form id="<?= e($weekFormId) ?>" method="post">
                            <input type="hidden" name="action" value="save_week">
                            <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? ($m['internship_id'] ?? 0)) ?>">
                            <input type="hidden" name="minggu" value="<?= (int)($wk['minggu'] ?? 0) ?>">
                            <input type="hidden" name="return_page" value="progress">
                        </form>
                    </td>

                    <td>
                        <textarea
                            name="rencana"
                            form="<?= e($weekFormId) ?>"
                            onblur="document.getElementById('<?= e($weekFormId) ?>').requestSubmit()"
                        ><?= e((string)($wk['rencana'] ?? '')) ?></textarea>
                    </td>

                    <td>
                        <textarea
                            name="realisasi"
                            form="<?= e($weekFormId) ?>"
                            onblur="document.getElementById('<?= e($weekFormId) ?>').requestSubmit()"
                        ><?= e((string)($wk['realisasi'] ?? '')) ?></textarea>
                    </td>

                    <td>
                        <textarea
                            name="umpan_balik"
                            form="<?= e($weekFormId) ?>"
                            onblur="document.getElementById('<?= e($weekFormId) ?>').requestSubmit()"
                        ><?= e((string)($wk['umpan_balik'] ?? '')) ?></textarea>
                    </td>

                    <td>
                        <select
                            name="status"
                            form="<?= e($weekFormId) ?>"
                            onchange="document.getElementById('<?= e($weekFormId) ?>').requestSubmit()"
                        >
                            <?php foreach (['belum_mulai','proses','selesai','terlambat'] as $s): ?>
                                <option value="<?= $s ?>" <?= (($wk['status'] ?? '') === $s) ? 'selected' : '' ?>>
                                    <?= e(str_replace('_', ' ', $s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php for ($weekNo = 1; $weekNo <= $unlockedWeek; $weekNo++): ?>
    <div class="week-controls" data-week="<?= $weekNo ?>">
        <?php $nextWeek = $weekNo + 1; ?>
        <?php if ($nextWeek <= count($weeks)): ?>
            <form method="post" class="next-week-form" id="next-week-form-<?= $weekNo ?>">
                <input type="hidden" name="action" value="unlock_next_week">
                <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? ($m['internship_id'] ?? 0)) ?>">
                <input type="hidden" name="minggu" value="<?= $nextWeek ?>">
                <input type="hidden" name="return_page" value="progress">
                <button type="submit" class="next-week-btn hidden" disabled>
                    Isi Minggu <?= $nextWeek ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php endfor; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const unlockedWeek = <?= (int)$unlockedWeek ?>;
    const totalWeeks = <?= count($weeks) ?>;

    // debounce map
    const debounces = {};
    function debounce(key, fn, wait = 700) {
        clearTimeout(debounces[key]);
        debounces[key] = setTimeout(fn, wait);
    }

    async function doAjaxSubmit(formEl) {
        const fd = new FormData(formEl);
        fd.set('ajax', '1');
        try {
            const res = await fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            return json;
        } catch (err) {
            return { ok:false, error: 'Network error' };
        }
    }

    // Attach autosave for daily forms
    document.querySelectorAll('form[id^="daily-form-"]').forEach(formEl => {
        const id = formEl.id;
        const kegiatan = document.querySelector(`textarea[form="${id}"]`);
        const checklist = document.querySelector(`input[name="checklist"][form="${id}"]`);
        const fileInput = document.querySelector(`input[type="file"][form="${id}"]`);

        const triggerSave = () => debounce(id + '-save', async () => {
            // perform AJAX submit for this form
            const resp = await doAjaxSubmit(formEl);
            if (!resp.ok) {
                console.warn('Save failed', resp.error);
                return;
            }
            // update existing_photo hidden input if server returned photo_path
            if (resp.photo_path !== undefined) {
                const existing = formEl.querySelector('input[name="existing_photo"]');
                if (existing) existing.value = resp.photo_path ?? '';
                // update photo status chip (find sibling in DOM)
                const statusChip = formEl.parentElement.querySelector('.status-chip');
                if (statusChip) {
                    if (resp.photo_path && resp.photo_path !== '') {
                        const count = resp.photo_path.split(',').filter(p => p.trim() !== '').length;
                        statusChip.className = 'status-chip status-success';
                        statusChip.textContent = '✅ ' + count + ' foto terupload';
                    } else {
                        statusChip.className = 'status-chip status-warning';
                        statusChip.textContent = '⚠️ Belum upload foto';
                    }
                }
            }
        }, 600);

        [kegiatan, checklist, fileInput].forEach(el => {
            if (!el) return;
            el.addEventListener('input', triggerSave);
            el.addEventListener('change', triggerSave);
            el.addEventListener('blur', triggerSave);
        });

        // prevent default submit if something triggers it
        formEl.addEventListener('submit', function(e){ e.preventDefault(); });
    });

    // Attach autosave for weekly forms (rencana/realisasi/umpan_balik/status)
    document.querySelectorAll('form[id^="week-form-"]').forEach(formEl => {
        const formId = formEl.id;
        const els = document.querySelectorAll(`[form="${formId}"]`);
        const trigger = () => debounce(formId + '-week-save', async () => {
            const resp = await doAjaxSubmit(formEl);
            if (!resp.ok) console.warn('Week save failed', resp.error);
        }, 600);

        els.forEach(el => {
            el.addEventListener('input', trigger);
            el.addEventListener('change', trigger);
            el.addEventListener('blur', trigger);
        });

        formEl.addEventListener('submit', function(e){ e.preventDefault(); });
    });

    // Next-button visibility: check each unlocked week for completeness
    function checkWeekCompletion(weekNo) {
        const forms = document.querySelectorAll(`form[id^="daily-form-${weekNo}-"]`);
        if (!forms.length) return;
        let allDone = true;
        forms.forEach(f => {
            const id = f.id;
            const kegiatan = document.querySelector(`textarea[form="${id}"]`);
            const checklist = document.querySelector(`input[name="checklist"][form="${id}"]`);
            const existingPhoto = f.querySelector('input[name="existing_photo"]');
            const fileInput = document.querySelector(`input[type="file"][form="${id}"]`);

            const kegiatanVal = kegiatan ? kegiatan.value.trim() : '';
            const hasPhoto = (existingPhoto && existingPhoto.value.trim() !== '') || (fileInput && fileInput.files && fileInput.files.length > 0);
            const checked = checklist ? checklist.checked : false;

            if (!kegiatanVal || !checked || !hasPhoto) allDone = false;
        });

        const btn = document.querySelector(`#next-week-form-${weekNo} .next-week-btn`);
        if (btn) {
            if (allDone && (weekNo + 1) <= totalWeeks) {
                btn.style.display = 'inline-block';
                btn.disabled = false;
            } else {
                btn.style.display = 'none';
                btn.disabled = true;
            }
        }
    }

    // Initial check + listeners to update Next button when fields change
    for (let w = 1; w <= unlockedWeek; w++) {
        checkWeekCompletion(w);
        document.querySelectorAll(`form[id^="daily-form-${w}-"]`).forEach(f => {
            const id = f.id;
            const kegiatan = document.querySelector(`textarea[form="${id}"]`);
            const checklist = document.querySelector(`input[name="checklist"][form="${id}"]`);
            const fileInput = document.querySelector(`input[type="file"][form="${id}"]`);
            [kegiatan, checklist, fileInput].forEach(el => {
                if (!el) return;
                el.addEventListener('change', () => checkWeekCompletion(w));
                el.addEventListener('input', () => checkWeekCompletion(w));
                el.addEventListener('blur', () => checkWeekCompletion(w));
            });
        });
    }

    // Handle Next button as AJAX too (prevent full page reload)
    document.querySelectorAll('.next-week-form').forEach(f => {
        f.addEventListener('submit', async function(e){
            e.preventDefault();
            const resp = await doAjaxSubmit(f);
            if (!resp.ok) {
                alert(resp.error || 'Gagal membuka minggu');
                return;
            }
            // On success, refresh local unlockedWeek UI by reloading the part or simply reload page to re-render weeks
            // but requirement is no reload; better to reload the page fragment – simplest fallback: reload:
            window.location.reload();
        });
    });

});
</script>