<?php
$todayObj = new DateTimeImmutable('today');
$startObj = new DateTimeImmutable($tanggalMulai ?: date('Y-m-d'));
$diffDays = (int)$startObj->diff($todayObj)->format('%a');
$unlockedWeek = ($todayObj < $startObj) ? 1 : max(1, min(12, intdiv($diffDays, 7) + 1));
?>

<?php
$weeklyStats = [];
$totalOpenDays = 0;
$totalChecked = 0;
$allReportRows = [];

for ($weekNo = 1; $weekNo <= $unlockedWeek; $weekNo++) {
    $weekRows = $dailyByWeek[$weekNo] ?? [];
    $dayCount = count($weekRows);
    $checkedCount = 0;

    foreach ($weekRows as $row) {
        if ((int)($row['checklist'] ?? 0) === 1) $checkedCount++;
        $row['week_no'] = $weekNo;
        $allReportRows[] = $row;
    }

    $weeklyStats[] = [
        'week' => $weekNo,
        'days' => $dayCount,
        'checked' => $checkedCount,
        'percentage' => $dayCount > 0 ? (int)round(($checkedCount / $dayCount) * 100) : 0,
    ];

    $totalOpenDays += $dayCount;
    $totalChecked += $checkedCount;
}

$currentWeekRows = $dailyByWeek[$unlockedWeek] ?? [];
$photoHighlights = array_values(array_filter($allReportRows, function (array $row): bool {
    return !empty($row['photo_path']);
}));

usort($photoHighlights, function (array $a, array $b): int {
    return strcmp((string)$b['tanggal'], (string)$a['tanggal']);
});
?>

<?php if (!$m): ?>
    <div class="card"><p>Data tidak ditemukan.</p></div>
<?php else: ?>
    <div class="card">
        <h2><?= e($m['nama']) ?> (<?= e($m['nim']) ?>)</h2>
        <p class="muted"><?= e($m['prodi']) ?> | <?= e($m['perusahaan']) ?> | Dosen: <?= e($m['dosen_pembimbing']) ?></p>
        <p class="muted">Mulai Magang: <?= e($tanggalMulai) ?></p>
        <p>Progress Mingguan: <strong><?= $done ?>/12 (<?= $percent ?>%)</strong></p>
        <div class="reminder">
            <strong>Pengingat Harian:</strong>
            <?= $todayDone ? 'Report hari ini sudah di-checklist.' : 'Report hari ini belum diisi / belum di-checklist. Mohon isi report kegiatan hari ini.' ?>
            <?php if ($overdue > 0): ?><div class="small">Ada <?= (int)$overdue ?> report harian terlewat.</div><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Minggu 1 - Pengajuan Judul Laporan</h3>
        <form method="post">
            <input type="hidden" name="action" value="update_judul">
            <input type="hidden" name="internship_id" value="<?= (int)$m['id'] ?>">
            <div class="grid">
                <div><label>Judul Laporan</label><input name="judul_laporan" value="<?= e((string)$m['judul_laporan']) ?>"></div>
                <div><label>Tanggal Pengajuan</label><input type="date" name="tanggal_pengajuan_judul" value="<?= e((string)$m['tanggal_pengajuan_judul']) ?>"></div>
                <div><label>Status Judul</label>
                    <select name="status_judul"><?php foreach (['belum_diajukan','diajukan','revisi','disetujui','ditolak'] as $s): ?><option value="<?= $s ?>" <?= $m['status_judul'] === $s ? 'selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option><?php endforeach; ?></select>
                </div>
            </div>
            <p style="margin-top:12px;"><?= statusBadge((string)$m['status_judul']) ?></p>
            <p><button type="submit">Simpan Judul</button></p>
        </form>
    </div>

    <div class="card">
        <h3>Report Kegiatan Harian (Checklist + Upload Foto)</h3>
        <?php for ($weekNo = 1; $weekNo <= $unlockedWeek; $weekNo++): $weekReports = $dailyByWeek[$weekNo] ?? []; ?>
            <h4 class="week-title">Minggu <?= $weekNo ?></h4>
            <table>
                <thead><tr><th>Tanggal</th><th>Kegiatan Harian</th><th>Foto</th><th>Report Selesai</th></tr></thead>
                <tbody>
                <?php if (!$weekReports): ?>
                    <tr><td colspan="4">Belum ada data minggu ini.</td></tr>
                <?php else: foreach ($weekReports as $dr): ?>
                    <tr>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_daily_report">
                            <input type="hidden" name="internship_id" value="<?= (int)$m['id'] ?>">
                            <input type="hidden" name="report_date" value="<?= e((string)$dr['tanggal']) ?>">
                            <input type="hidden" name="existing_photo" value="<?= e((string)($dr['photo_path'] ?? '')) ?>">
                            <td><strong><?= e((string)$dr['tanggal']) ?></strong><div class="small">Hari ke-<?= (int)$dr['hari_ke'] ?></div></td>
                            <td><textarea name="kegiatan" required onblur="this.form.requestSubmit()"><?= e((string)($dr['kegiatan'] ?? '')) ?></textarea></td>
                            <td>
                                <?php if (!empty($dr['photo_path'])): ?><div><img class="photo-preview" src="<?= e((string)$dr['photo_path']) ?>" alt="foto"></div><?php endif; ?>
                                <input type="file" name="foto_kegiatan" accept="image/*" onchange="this.form.requestSubmit()">
                            </td>
                            <td>
                                <label class="check-box">
                                    <input type="checkbox" name="checklist" value="1" <?= ((int)$dr['checklist']===1)?'checked':'' ?> onchange="this.form.requestSubmit()">
                                    <span>Checklist</span>
                                </label>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        <?php endfor; ?>
    </div>

    <div class="card">
        <h3>Progress Mingguan (Bisa Diketik)</h3>
        <table>
            <thead>
            <tr>
                <th>Minggu</th>
                <th>Rencana</th>
                <th>Realisasi</th>
                <th>Umpan Balik</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($weeks as $wk): ?>
                <?php if ((int)$wk['minggu'] > $unlockedWeek) continue; ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="action" value="save_week">
                        <input type="hidden" name="internship_id" value="<?= (int)$m['id'] ?>">
                        <input type="hidden" name="minggu" value="<?= (int)$wk['minggu'] ?>">
                        <td><strong><?= (int)$wk['minggu'] ?></strong></td>
                        <td><textarea name="rencana" onblur="this.form.requestSubmit()"><?= e((string)($wk['rencana'] ?? '')) ?></textarea></td>
                        <td><textarea name="realisasi" onblur="this.form.requestSubmit()"><?= e((string)($wk['realisasi'] ?? '')) ?></textarea></td>
                        <td><textarea name="umpan_balik" onblur="this.form.requestSubmit()"><?= e((string)($wk['umpan_balik'] ?? '')) ?></textarea></td>
                        <td>
                            <select name="status" onchange="this.form.requestSubmit()">
                                <?php foreach (['belum_mulai','proses','selesai','terlambat'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $wk['status'] === $s ? 'selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="margin-top:6px;"><?= statusBadge((string)$wk['status']) ?></div>
                        </td>
                        <td><button type="submit">Simpan</button></td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small">Minggu terbuka saat ini: <?= (int)$unlockedWeek ?> dari 12.</p>

    <div class="card">
        <h3>Diagram Report Harian dan Mingguan</h3>
        <p class="muted">Ringkasan progres checklist report kegiatan yang sudah dilakukan.</p>

        <div class="small" style="margin-bottom:10px;">
            Total report selesai: <?= (int)$totalChecked ?> dari <?= (int)$totalOpenDays ?> hari terbuka
            (<?= $totalOpenDays > 0 ? (int)round(($totalChecked / $totalOpenDays) * 100) : 0 ?>%)
        </div>

        <div class="weekly-chart">
            <?php foreach ($weeklyStats as $stat): ?>
                <div class="bar-row">
                    <div class="bar-label">Minggu <?= (int)$stat['week'] ?></div>
                    <div class="bar-track"><div class="bar-fill" style="width: <?= (int)$stat['percentage'] ?>%"></div></div>
                    <div class="bar-meta"><?= (int)$stat['checked'] ?>/<?= (int)$stat['days'] ?> (<?= (int)$stat['percentage'] ?>%)</div>
                </div>
            <?php endforeach; ?>
        </div>

        <h4 class="week-title">Diagram Harian Minggu <?= (int)$unlockedWeek ?></h4>
        <div class="daily-chart">
            <?php foreach ($currentWeekRows as $dr): ?>
                <?php $doneClass = ((int)$dr['checklist'] === 1) ? 'daily-done' : 'daily-todo'; ?>
                <div class="daily-chip <?= $doneClass ?>">
                    <div class="small"><?= e((string)$dr['tanggal']) ?></div>
                    <strong><?= ((int)$dr['checklist'] === 1) ? 'Selesai' : 'Belum' ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Highlight Kegiatan Menarik (Cinematic)</h3>
        <?php if (count($photoHighlights) === 0): ?>
            <p class="muted">Belum ada foto kegiatan yang diunggah.</p>
        <?php else: ?>
            <?php $hero = $photoHighlights[0]; ?>
            <div class="cinematic-hero" style="background-image:url('<?= e((string)$hero['photo_path']) ?>');">
                <div class="cinematic-overlay">
                    <div class="small">Highlight Utama · <?= e((string)$hero['tanggal']) ?></div>
                    <h4><?= e((string)($hero['kegiatan'] ?: 'Kegiatan lapangan')) ?></h4>
                </div>
            </div>

            <div class="cinematic-grid">
                <?php foreach (array_slice($photoHighlights, 1, 4) as $item): ?>
                    <div class="cinematic-item">
                        <img src="<?= e((string)$item['photo_path']) ?>" alt="highlight kegiatan">
                        <div class="cinematic-caption">
                            <div class="small"><?= e((string)$item['tanggal']) ?></div>
                            <div><?= e((string)($item['kegiatan'] ?: 'Aktivitas harian')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>