<?php
if (!isset($m) || !is_array($m)) {
    $m = [
        'nim' => '-',
        'nama' => '-',
        'prodi' => '-',
        'perusahaan' => '-',
        'dosen_pembimbing' => '-',
        'status_judul' => '-',
        'judul_laporan' => '-',
    ];
}

if (!isset($tanggalMulai) || !is_string($tanggalMulai) || $tanggalMulai === '') {
    $tanggalMulai = '-';
}

if (!isset($weeks) || !is_array($weeks)) {
    $weeks = [];
}

if (!isset($dailyByWeek) || !is_array($dailyByWeek)) {
    $dailyByWeek = [];
}

$totalWeeks = count($weeks);
$totalDailyGroups = count($dailyByWeek);
?>

<div class="card no-print report-head">
    <div>
        <h2>Laporan Detail Magang</h2>
        <p class="muted">Ringkasan identitas, progres mingguan, dan progres harian.</p>
    </div>
    <button onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="report-summary">
    <div class="card summary-card">
        <span class="summary-label">Status Laporan</span>
        <div class="summary-value"><?= statusBadge((string)($m['status_judul'] ?? '-')) ?></div>
    </div>
    <div class="card summary-card">
        <span class="summary-label">Jumlah Minggu</span>
        <div class="summary-value"><?= (int)$totalWeeks ?></div>
    </div>
    <div class="card summary-card">
        <span class="summary-label">Kelompok Harian</span>
        <div class="summary-value"><?= (int)$totalDailyGroups ?></div>
    </div>
</div>

<div class="report-grid">
    <div class="card">
        <h3>Identitas Mahasiswa</h3>
        <div class="table-wrap">
            <table class="report-table">
                <tr><td>NIM</td><td><?= e((string)($m['nim'] ?? '-')) ?></td></tr>
                <tr><td>Nama</td><td><?= e((string)($m['nama'] ?? '-')) ?></td></tr>
                <tr><td>Program Studi</td><td><?= e((string)($m['prodi'] ?? '-')) ?></td></tr>
                <tr><td>Perusahaan</td><td><?= e((string)($m['perusahaan'] ?? '-')) ?></td></tr>
                <tr><td>Dosen Pembimbing</td><td><?= e((string)($m['dosen_pembimbing'] ?? '-')) ?></td></tr>
                <tr><td>Tanggal Mulai</td><td><?= e((string)$tanggalMulai) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Progres Laporan</h3>
        <div class="report-info">
            <div>
                <span class="summary-label">Status</span>
                <div><?= statusBadge((string)($m['status_judul'] ?? '-')) ?></div>
            </div>
            <div>
                <span class="summary-label">Judul</span>
                <div class="report-value"><?= e((string)($m['judul_laporan'] ?? '-')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="section-title">
        <h3>Progres Mingguan</h3>
        <p class="muted">Rencana, realisasi, umpan balik, dan status per minggu.</p>
    </div>

    <div class="table-wrap">
        <table class="report-table report-table-wide">
            <thead>
                <tr>
                    <th style="width:70px;">Minggu</th>
                    <th>Rencana</th>
                    <th>Realisasi</th>
                    <th>Umpan Balik</th>
                    <th style="width:110px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$weeks): ?>
                <tr>
                    <td colspan="5" class="muted center">Belum ada progres mingguan.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($weeks as $wk): ?>
                <tr>
                    <td class="center"><?= (int)($wk['minggu'] ?? 0) ?></td>
                    <td><?= nl2br(e((string)($wk['rencana'] ?? ''))) ?></td>
                    <td><?= nl2br(e((string)($wk['realisasi'] ?? ''))) ?></td>
                    <td><?= nl2br(e((string)($wk['umpan_balik'] ?? ''))) ?></td>
                    <td class="center"><?= statusBadge((string)($wk['status'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="section-title">
        <h3>Progres Harian</h3>
        <p class="muted">Dikelompokkan berdasarkan minggu.</p>
    </div>

    <?php if (!$dailyByWeek): ?>
        <p class="muted">Belum ada progres harian.</p>
    <?php endif; ?>

    <?php foreach ($dailyByWeek as $weekNo => $rows): ?>
        <div class="week-block">
            <div class="week-title">Minggu <?= (int)$weekNo ?></div>

            <div class="table-wrap">
                <table class="report-table report-table-wide">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th>Kegiatan</th>
                            <th style="width:100px;">Checklist</th>
                            <th style="width:120px;">Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ((array)$rows as $dr): ?>
                        <tr>
                            <td><?= e((string)($dr['tanggal'] ?? '-')) ?></td>
                            <td><?= nl2br(e((string)($dr['kegiatan'] ?? ''))) ?></td>
                            <td class="center"><?= ((int)($dr['checklist'] ?? 0) === 1) ? 'Ya' : 'Tidak' ?></td>
                            <td class="center">
                                <?php
                                $photos = array_values(array_filter(array_map('trim', explode(',', (string)($dr['photo_path'] ?? '')))));
                                echo count($photos) . ' foto';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.report-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.report-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
}

.summary-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.summary-label {
    font-size: 12px;
    color: #a45579;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 700;
}

.summary-value {
    font-size: 18px;
    font-weight: 700;
    color: #b02f68;
}

.report-grid {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 14px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.report-table th,
.report-table td {
    border-bottom: 1px solid #f4c9da;
    padding: 10px 12px;
    vertical-align: top;
}

.report-table th {
    text-align: left;
    background: #fff7fb;
    color: #a83c6b;
    font-size: 13px;
}

.report-table td {
    color: #5f4251;
}

.report-table-wide td {
    line-height: 1.6;
}

.table-wrap {
    overflow-x: auto;
}

.center {
    text-align: center;
}

.section-title {
    margin-bottom: 10px;
}

.section-title h3 {
    margin-bottom: 4px;
}

.report-info {
    display: grid;
    gap: 14px;
}

.report-value {
    margin-top: 4px;
    line-height: 1.6;
    color: #5f4251;
}

.week-block {
    margin-top: 16px;
}

.week-title {
    display: inline-block;
    margin-bottom: 10px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #ffe8f1;
    color: #b02f68;
    font-weight: 700;
    font-size: 13px;
}

.muted {
    color: #8c6a78;
}

@media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    .report-summary,
    .report-grid {
        grid-template-columns: 1fr;
    }
    .table-wrap {
        overflow: visible;
    }
}
</style>