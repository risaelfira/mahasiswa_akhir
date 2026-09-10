<?php
// Safe defaults to avoid undefined variable warnings
$m = $m ?? [];
$weeks = $weeks ?? [];
$dailyByWeek = $dailyByWeek ?? [];
$unlockedWeek = $unlockedWeek ?? 1;
$notes = $notes ?? [];
$id = $id ?? 0;
$weekPercent = $weekPercent ?? 0;
$dayPercent = $dayPercent ?? 0;

$judulScoreMap = [
    'belum_diajukan' => 0,
    'diajukan' => 35,
    'revisi' => 60,
    'disetujui' => 100,
    'ditolak' => 20,
];

$laporanPercent = $judulScoreMap[$m['status_judul'] ?? 'belum_diajukan'] ?? 0;
$overallMagangPercent = (int) round((($weekPercent ?? 0) + ($dayPercent ?? 0)) / 2);

$weekMap = [];
foreach ($weeks as $wk) {
    $weekMap[(int)($wk['minggu'] ?? 0)] = $wk;
}

$currentWeekNo = max(1, (int)($unlockedWeek ?? 1));
$nextWeekNo = $currentWeekNo + 1;

$currentWeekData = $weekMap[$currentWeekNo] ?? null;
$nextWeekData = $weekMap[$nextWeekNo] ?? null;

$weeklyLabels = [];
$weeklyValues = [];
for ($w = 1; $w <= $unlockedWeek; $w++) {
    $weeklyLabels[] = 'Minggu ' . $w;
    $rows = $dailyByWeek[$w] ?? [];
    $done = 0;

    foreach ($rows as $row) {
        if ((int)($row['checklist'] ?? 0) === 1) {
            $done++;
        }
    }

    // avoid division by zero
    $weeklyValues[] = (int) round((($done / max(1, 5)) * 100));
}

$allDaily = [];
foreach ($dailyByWeek as $rows) {
    foreach ($rows as $r) {
        $date = (string)($r['tanggal'] ?? '');
        if ($date === '') {
            continue;
        }

        $dayNum = (int) date('N', strtotime($date));
        if ($dayNum >= 6) {
            continue;
        }

        $allDaily[] = $r;
    }
}

usort($allDaily, function($a, $b) {
    return strcmp((string)($a['tanggal'] ?? ''), (string)($b['tanggal'] ?? ''));
});
$first14 = array_slice($allDaily, 0, 14);

$dailyLabels = [];
$dailyValues = [];
foreach ($first14 as $r) {
    $date = (string)($r['tanggal'] ?? '');
    $dailyLabels[] = date('d M', strtotime($date));
    $dailyValues[] = ((int)($r['checklist'] ?? 0) === 1) ? 100 : 0;
}

$todayTs = strtotime(date('Y-m-d'));
$limitTs = strtotime('+7 days', $todayTs);
$plannedNotes = [];

foreach ($notes as $note) {
    $noteDate = trim((string)($note['note_date'] ?? ''));
    $noteDone = (int)($note['note_done'] ?? 0);

    if ($noteDate === '' || $noteDone === 1) {
        continue;
    }

    $noteTs = strtotime($noteDate);
    if ($noteTs === false) {
        continue;
    }

    if ($noteTs >= $todayTs && $noteTs <= $limitTs) {
        $plannedNotes[] = $note;
    }
}

usort($plannedNotes, function($a, $b) {
    return strcmp((string)($a['note_date'] ?? ''), (string)($b['note_date'] ?? ''));
});
?>

<div class="dashboard-card card">
    <h2>Dashboard Visualisasi Progres Magang</h2>
    <p class="muted lead">Diagram progres laporan magang, progres magang, progres harian magang, dan progres mingguan magang.</p>
    <p>
        <a href="?page=progress" class="btn-plain link-pink" style="display:inline-block;padding:10px 14px;border-radius:8px;">Isi Progres Sekarang</a>
    </p>
</div>

<div class="metrics-row">
    <div class="metric">
        <div class="label">Laporan</div>
        <div class="value"><?= (int)$laporanPercent ?>%</div>
    </div>
    <div class="metric">
        <div class="label">Keseluruhan</div>
        <div class="value"><?= (int)$overallMagangPercent ?>%</div>
    </div>
    <div class="metric">
        <div class="label">Minggu Aktif</div>
        <div class="value"><?= (int)$currentWeekNo ?></div>
    </div>
</div>

<div class="dashboard-plan-grid">
    <div class="card plan-card">
        <h3>Rencana Mingguan Berjalan</h3>
        <p class="muted">Minggu <?= (int)$currentWeekNo ?></p>
        <?php if ($currentWeekData): ?>
            <div class="plan-status"><?= statusBadge((string)($currentWeekData['status'] ?? '-')) ?></div>
            <p class="plan-text"><?= nl2br(e((string)($currentWeekData['rencana'] ?? 'Belum ada rencana.'))) ?></p>
        <?php else: ?>
            <p class="muted">Belum ada rencana untuk minggu ini.</p>
        <?php endif; ?>
    </div>

    <div class="card plan-card">
        <h3>Rencana Minggu Berikutnya</h3>
        <p class="muted">Minggu <?= (int)$nextWeekNo ?></p>
        <?php if ($nextWeekData): ?>
            <div class="plan-status"><?= statusBadge((string)($nextWeekData['status'] ?? '-')) ?></div>
            <p class="plan-text"><?= nl2br(e((string)($nextWeekData['rencana'] ?? 'Belum ada rencana.'))) ?></p>
        <?php else: ?>
            <p class="muted">Belum ada rencana untuk minggu berikutnya.</p>
        <?php endif; ?>
    </div>
</div>

<div class="chart-grid">
    <div class="card chart-card">
        <h3>Progres Laporan Magang</h3>
        <canvas id="chartLaporan" height="180"></canvas>
    </div>
    <div class="card chart-card">
        <h3>Progres Magang</h3>
        <canvas id="chartMagang" height="180"></canvas>
    </div>
    <div class="card chart-card">
        <h3>Progres Harian Magang (14 Hari)</h3>
        <canvas id="chartHarian" height="180"></canvas>
    </div>
    <div class="card chart-card">
        <h3>Progres Mingguan Magang</h3>
        <canvas id="chartMingguan" height="180"></canvas>
    </div>
</div>

<div class="task-board card">
    <div class="task-board-top">
        <div class="task-board-title">
            <span class="task-check">✓</span>
            <div>
                <h3>Planned</h3>
                <div class="task-email"><?= e((string)($_SESSION['username'] ?? '')) ?></div>
            </div>
        </div>

        <button type="button" class="task-add-btn" id="openNoteModal">+</button>
    </div>

    <div class="task-divider"></div>

    <?php if (!empty($plannedNotes)): ?>
        <div class="planned-list">
            <?php foreach ($plannedNotes as $note): ?>
                <div class="task-item">
                    <form method="post" class="task-check-form">
                        <input type="hidden" name="action" value="toggle_note">
                        <input type="hidden" name="internship_id" value="<?= (int)$id ?>">
                        <input type="hidden" name="note_id" value="<?= (int)$note['id'] ?>">
                        <input type="hidden" name="note_done" value="<?= (int)($note['note_done'] ?? 0) ?>">

                        <label class="task-circle-label">
                            <input type="checkbox" onchange="this.form.submit()">
                            <span class="task-circle"></span>
                        </label>
                    </form>

                    <div class="task-content">
                        <div class="task-text"><?= e((string)($note['note_text'] ?? '')) ?></div>
                        <div class="task-meta">
                            Tenggat • <?= e(date('d M Y', strtotime((string)($note['note_date'] ?? '')))) ?>
                            <?php if (!empty($note['note_priority'])): ?>
                                • Prioritas: <?= e((string)$note['note_priority']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="task-item no-notes">
            <div class="task-circle"></div>
            <div class="task-content">
                <div class="task-text">Tidak ada catatan tenggat 7 hari ke depan</div>
                <div class="task-meta">Planned reminder</div>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top:14px;">
        <a href="?page=catatan" class="link-pink">Buka menu catatan</a>
    </div>
</div>

<div class="modal-backdrop" id="noteModal">
    <div class="modal-card">
        <div class="modal-head">
            <h3>Tambah Catatan</h3>
            <button type="button" class="modal-close" id="closeNoteModal">×</button>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="save_note">
            <input type="hidden" name="internship_id" value="<?= (int)$id ?>">

            <div class="grid">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="note_date" required>
                </div>
                <div>
                    <label>Prioritas</label>
                    <select name="note_priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div style="grid-column:1 / -1;">
                    <label>Catatan</label>
                    <textarea name="note_text" required placeholder="Contoh: Membuat report selama 1 minggu"></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="cancelNoteModal">Batal</button>
                <button type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const laporanPercent = <?= (int)$laporanPercent ?>;
const overallMagangPercent = <?= (int)$overallMagangPercent ?>;
const weeklyLabels = <?= json_encode($weeklyLabels) ?>;
const weeklyValues = <?= json_encode($weeklyValues) ?>;
const dailyLabels = <?= json_encode($dailyLabels) ?>;
const dailyValues = <?= json_encode($dailyValues) ?>;

const centerTextPlugin = {
  id: 'centerTextPlugin',
  afterDraw(chart, args, pluginOptions) {
    if (chart.config.type !== 'doughnut') return;

    const { ctx, chartArea: { left, right, top, bottom } } = chart;
    const x = (left + right) / 2;
    const y = (top + bottom) / 2;
    const value = Number(pluginOptions.value ?? 0);
    const label = pluginOptions.label ?? '';
    const displayValue = value > 0 && value < 1 ? 1 : Math.round(value);

    ctx.save();
    ctx.textAlign = 'center';
    ctx.fillStyle = '#9d174d';
    ctx.font = '700 24px Arial';
    ctx.fillText(displayValue + '%', x, y - 6);
    ctx.font = '12px Arial';
    ctx.fillStyle = '#a21caf';
    ctx.fillText(label, x, y + 14);
    ctx.restore();
  }
};

new Chart(document.getElementById('chartLaporan'), {
  type: 'doughnut',
  data: {
    labels: ['Selesai', 'Sisa'],
    datasets: [{
      data: [laporanPercent, 100 - laporanPercent],
      backgroundColor: ['#ec4899', '#fde7f3'],
      borderWidth: 0
    }]
  },
  options: {
    cutout: '72%',
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: (ctx) => `${ctx.label}: ${ctx.raw}%`
        }
      },
      centerTextPlugin: { value: laporanPercent, label: 'Laporan' }
    }
  },
  plugins: [centerTextPlugin],
  centerTextPlugin: { value: laporanPercent, label: 'Laporan' }
});

new Chart(document.getElementById('chartMagang'), {
  type: 'doughnut',
  data: {
    labels: ['Selesai', 'Sisa'],
    datasets: [{
      data: [overallMagangPercent, 100 - overallMagangPercent],
      backgroundColor: ['#db2777', '#fce7f3'],
      borderWidth: 0
    }]
  },
  options: {
    cutout: '72%',
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: (ctx) => `${ctx.label}: ${ctx.raw}%`
        }
      },
      centerTextPlugin: { value: overallMagangPercent, label: 'Keseluruhan' }
    }
  },
  plugins: [centerTextPlugin],
  centerTextPlugin: { value: overallMagangPercent, label: 'Keseluruhan' }
});

new Chart(document.getElementById('chartHarian'), {
  type: 'line',
  data: {
    labels: dailyLabels,
    datasets: [{
      data: dailyValues,
      borderColor: '#ec4899',
      backgroundColor: 'rgba(236,72,153,.15)',
      fill: true,
      tension: .35,
      pointRadius: 4,
      pointHoverRadius: 6
    }]
  },
  options: {
    scales: {
      y: {
        beginAtZero: true,
        min: 0,
        max: 100,
        ticks: {
          stepSize: 10,
          callback: (value) => value + '%'
        },
        title: {
          display: true,
          text: 'Persentase Progres'
        }
      },
      x: {
        title: {
          display: true,
          text: 'Tanggal'
        }
      }
    },
    plugins: {
      legend: { display: false }
    },
    maintainAspectRatio: false,
    responsive: true,
  }
});

new Chart(document.getElementById('chartMingguan'), {
  type: 'bar',
  data: {
    labels: weeklyLabels,
    datasets: [{
      data: weeklyValues,
      backgroundColor: 'rgba(219,39,119,.75)'
    }]
  },
  options: {
    scales: {
      y: {
        beginAtZero: true,
        max: 100,
        ticks: {
          stepSize: 10,
          callback: (value) => value + '%'
        }
      }
    },
    plugins: {
      legend: { display: false }
    },
    maintainAspectRatio: false,
    responsive: true,
  }
});

const noteModal = document.getElementById('noteModal');
const openNoteModal = document.getElementById('openNoteModal');
const closeNoteModal = document.getElementById('closeNoteModal');
const cancelNoteModal = document.getElementById('cancelNoteModal');

function showNoteModal() {
  if (noteModal) noteModal.classList.add('show');
}

function hideNoteModal() {
  if (noteModal) noteModal.classList.remove('show');
}

if (openNoteModal) openNoteModal.addEventListener('click', showNoteModal);
if (closeNoteModal) closeNoteModal.addEventListener('click', hideNoteModal);
if (cancelNoteModal) cancelNoteModal.addEventListener('click', hideNoteModal);
if (noteModal) noteModal.addEventListener('click', function (e) {
  if (e.target === noteModal) hideNoteModal();
});
</script>