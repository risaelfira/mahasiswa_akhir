<div class="report-view">
    <div class="card report-head">
        <h2>Laporan Mingguan</h2>
        <p class="muted">Ringkasan kegiatan & output selama satu minggu (tampilan satu layar untuk presentasi).</p>
    </div>

    <div class="card report-head">
        <form method="get">
            <input type="hidden" name="page" value="report">
            <label>Pilih Minggu</label>
            <select name="minggu" onchange="this.form.submit()">
                <?php foreach (($weeks ?? []) as $wk): ?>
                    <?php $no = (int)($wk['minggu'] ?? 0); if ($no === 0) continue; ?>
                    <option value="<?= $no ?>" <?= (isset($_GET['minggu']) && (int)$_GET['minggu'] === $no) ? 'selected' : '' ?>>
                        Minggu <?= $no ?> <?= ($wk['status'] ?? '') === 'selesai' ? '(selesai)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

<?php
$selected = isset($_GET['minggu']) ? (int)$_GET['minggu'] : null;
if ($selected):
    $rows = $dailyByWeek[$selected] ?? [];

    $totalDaysWithActivity = 0;
    $totalPhotos = 0;
    $checklistDone = 0;
    $dates = [];
    $outputs = []; // aggregated outputs from daily kegiatan
    $photoList = [];

    foreach ($rows as $r) {
        $kegiatan = trim((string)($r['kegiatan'] ?? ''));
        if ($kegiatan !== '') {
            $totalDaysWithActivity++;
            foreach (preg_split('/\r\n|\r|\n/', $kegiatan) as $line) {
                $line = trim($line);
                if ($line !== '' && !in_array($line, $outputs, true)) $outputs[] = $line;
            }
        }

        $photos = array_values(array_filter(array_map('trim', explode(',', (string)($r['photo_path'] ?? ''))), function($p) { return $p !== ''; }));
        foreach ($photos as $p) {
            if ($p !== '' && !in_array($p, $photoList, true)) $photoList[] = $p;
        }
        $totalPhotos += count($photos);
        if ((int)($r['checklist'] ?? 0) === 1) $checklistDone++;
        if (!empty($r['tanggal'])) $dates[] = $r['tanggal'];
    }

    sort($dates);
    $range = $dates ? (reset($dates) . ' — ' . end($dates)) : '-';

    $weekMeta = null;
    foreach (($weeks ?? []) as $w) if ((int)($w['minggu'] ?? 0) === $selected) { $weekMeta = $w; break; }
?>
    <div class="card report-grid">
        <div class="report-main">
            <div class="report-period">
                <h3>Minggu <?= $selected ?></h3>
                <div class="muted">Periode: <?= e($range) ?></div>
            </div>

            <div class="card report-activities">
                <?php if (empty($rows)): ?>
                    <div>Tidak ada kegiatan tercatat pada minggu ini.</div>
                <?php else: ?>
                    <?php foreach ($rows as $dr): ?>
                        <div class="daily-entry">
                            <div class="entry-date"><?= e((string)($dr['tanggal'] ?? '-')) ?></div>
                            <div class="entry-text"><?= nl2br(e((string)($dr['kegiatan'] ?? '-'))) ?></div>

                            <?php
                            $uploadedPhotos = array_values(array_filter(array_map('trim', explode(',', (string)($dr['photo_path'] ?? ''))), function($photo) { return $photo !== ''; }));
                            if (!empty($uploadedPhotos)): ?>
                                <div class="entry-photos">
                                    <?php foreach ($uploadedPhotos as $ph): ?>
                                        <a href="<?= e($ph) ?>" target="_blank" class="thumb">
                                            <img src="<?= e($ph) ?>" alt="" loading="lazy">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <aside class="report-sidebar">
            <div class="card report-summary">
                <h4>Ringkasan</h4>
                <div>- Total hari dengan kegiatan: <strong><?= $totalDaysWithActivity ?></strong></div>
                <div>- Total foto terupload: <strong><?= $totalPhotos ?></strong></div>
                <div>- Checklist selesai: <strong><?= $checklistDone ?></strong></div>
                <div>- Status minggu: <strong><?= e((string)($weekMeta['status'] ?? '-')) ?></strong></div>
            </div>

            <div class="card report-output">
                <h4>Output / Hasil (diambil dari kegiatan harian)</h4>
                <?php
                if (!empty($weekMeta) && trim((string)($weekMeta['realisasi'] ?? '')) !== '') {
                    echo '<div class="output-text">' . nl2br(e((string)$weekMeta['realisasi'])) . '</div>';
                } elseif (empty($outputs)) {
                    echo '<div class="muted">Belum ada output tercatat.</div>';
                } else {
                    echo '<ol class="output-list">';
                    foreach (array_slice($outputs, 0, 10) as $o) {
                        echo '<li>' . e($o) . '</li>';
                    }
                    echo '</ol>';
                }
                ?>
            </div>

            <?php if (!empty($photoList)): ?>
                <div class="card report-photos">
                    <h4>Preview Foto Mingguan</h4>
                    <div class="photo-grid">
                        <?php foreach (array_slice($photoList,0,8) as $ph): ?>
                            <a href="<?= e($ph) ?>" target="_blank" class="thumb">
                                <img src="<?= e($ph) ?>" alt="" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
<?php endif; ?>

</div>

<style>
/* Report: make view fit one screen and keep scrolling inside content panels */
.report-view {
  height: calc(100vh - 140px); /* adjust 140px jika header/header+footer lebih tinggi/pendek */
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding-bottom: 12px;
}

/* Report: larger, responsive typography */
.report-view { font-size: clamp(16px, 1.05vw, 18px); line-height: 1.6; }

/* Headings */
.report-head h2 { font-size: clamp(26px, 2.2vw, 36px) !important; margin-bottom: 6px; }
.report-period h3 { font-size: clamp(20px, 1.6vw, 26px) !important; }

/* layout: main + sidebar */
.report-grid {
  display: flex;
  gap: 18px;
  align-items: stretch;
  flex: 1 1 auto;
  min-height: 0; /* allow children to shrink properly inside flex container */
}

.report-main {
  flex: 1 1 65%;
  display: flex;
  flex-direction: column;
  min-width: 300px;
  min-height: 0;
}

.report-period {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

/* Entries */
.entry-date { font-size: clamp(14px, 0.9vw, 16px) !important; font-weight:700; }
.entry-text { font-size: clamp(15px, 1.02vw, 18px) !important; line-height: 1.65; }

/* make activity list scroll inside its card to avoid page scroll */
.report-activities {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 12px;
  box-shadow: var(--shadow);
  overflow: auto;
  flex: 1 1 auto;
  min-height: 0;
}

/* Sidebar rules: stack cards and allow internal scroll for long content */
.report-sidebar {
  flex: 0 0 32%;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 0;
}

.report-sidebar .card {
  padding: 12px;
  overflow: auto;
  max-height: calc(100vh - 220px); /* keep each sidebar card within viewport */
}

/* compact photo thumbnails so they don't expand layout */
.thumb img {
  width: 72px;
  height: 48px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #ddd;
  display: block;
}

/* sidebar & lists */
.report-sidebar, .report-sidebar .card, .report-output, .output-list li {
  font-size: clamp(14px, 1.0vw, 16px) !important;
  line-height: 1.55 !important;
}

/* Thumbnails caption (if any) */
.thumb { font-size: clamp(12px, 0.9vw, 14px) !important; }

/* Small screens: keep readable */
@media (max-width: 640px) {
  .report-view { font-size: 15px; }
  .report-head h2 { font-size: 22px !important; }
  .report-period h3 { font-size: 18px !important; }
  .entry-text { font-size: 15px !important; }
}

/* mobile tweak: make thumbs a bit smaller */
@media (max-width: 640px) {
  .entry-photos .thumb,
  .photo-grid .thumb { width: clamp(48px, 18vw, 90px); }
}

/* responsive tweaks */
@media (max-width: 900px) {
  .report-grid { flex-direction: column; }
  .report-sidebar { flex: 0 0 auto; width: 100%; }
  .report-view { height: auto; } /* allow normal flow on small screens */
}

/* Report images: responsive & adaptive */
.report-activities img,
.report-output img,
.report-photos img,
.daily-entry img,
.entry-photos img {
  max-width: 100%;
  height: auto;
  display: block;
  border-radius: 6px;
  object-fit: cover;
}

/* thumbnail grid (kecepatan muat + konsistensi ukuran) */
.entry-photos,
.photo-grid {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: flex-start;
}

.entry-photos .thumb,
.photo-grid .thumb {
  flex: 0 0 auto;
  width: clamp(56px, 12vw, 120px); /* responsive thumb width */
  height: auto;
}

.entry-photos .thumb img,
.photo-grid .thumb img {
  width: 100%;
  height: auto;
  aspect-ratio: 16/9;
  object-fit: cover;
}

/* make embedded images inside activity text scale but not overflow */
.entry-text img {
  max-width: 100%;
  height: auto;
  margin: 8px 0;
}

/* optional: reduce large chart/image overflow inside activity card */
.report-activities,
.report-output,
.report-photos {
  overflow: auto;
}
</style>