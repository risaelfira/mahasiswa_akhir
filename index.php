<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/actions.php';

$page = $_GET['page'] ?? 'dashboard';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if (!isLoggedIn() && !in_array($page, ['login','register'], true)) $page = 'login';
if (isLoggedIn() && in_array($page, ['login','register'], true)) redirectTo('?');

$data = [];

if (in_array($page, ['dashboard','progress','cetak','catatan','report'], true) && isLoggedIn()) {
    $id = currentInternshipId();
    $stmt = $pdo->prepare("SELECT * FROM internships WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$m) {
        $_SESSION = [];
        redirectTo('?page=login&error=Data akun tidak ditemukan');
    }

    initializeWeeks($pdo, $id);
    $tanggalMulai = (string)($m['tanggal_mulai'] ?: date('Y-m-d'));
    initializeDailyReports($pdo, $id, $tanggalMulai);

    $wStmt = $pdo->prepare("SELECT * FROM weekly_progress WHERE internship_id = :id ORDER BY minggu");
    $wStmt->execute([':id'=>$id]);
    $weeks = $wStmt->fetchAll(PDO::FETCH_ASSOC);

    $dStmt = $pdo->prepare("SELECT * FROM daily_reports WHERE internship_id = :id ORDER BY tanggal ASC");
    $dStmt->execute([':id'=>$id]);
    $dailyRows = $dStmt->fetchAll(PDO::FETCH_ASSOC);

    $dailyByWeek = [];
    $today = date('Y-m-d');
    foreach ($dailyRows as $dr) {
        $weekIndex = (int)$dr['minggu'];
        $dailyByWeek[$weekIndex][] = $dr;
    }

    $doneWeeks = 0;
    foreach ($weeks as $wk) if ($wk['status'] === 'selesai') $doneWeeks++;
    $weekPercent = (int)round(($doneWeeks / 12) * 100);

    $checkedDays = 0;
    foreach ($dailyRows as $dr) if ((int)$dr['checklist'] === 1) $checkedDays++;
    $dayPercent = count($dailyRows) > 0 ? (int)round(($checkedDays / count($dailyRows)) * 100) : 0;

    $unlockedWeek = getEffectiveUnlockedWeek($pdo, $id, $tanggalMulai);

    $nStmt = $pdo->prepare("SELECT * FROM notes WHERE internship_id = :id ORDER BY note_date DESC, id DESC");
    $nStmt->execute([':id' => $id]);
    $notes = $nStmt->fetchAll(PDO::FETCH_ASSOC);

    $data = compact('id','m','weeks','dailyByWeek','today','weekPercent','dayPercent','doneWeeks','checkedDays','unlockedWeek','tanggalMulai','notes');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sistem Pemantauan Magang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topnav card">
        <?php if (isLoggedIn()): ?>
            <a href="?">Dashboard</a>
            <a href="?page=progress">Isi Progres</a>
            <a href="?page=report">Laporan Mingguan</a>
            <a href="?page=cetak" target="_blank">Cetak</a>
            <a href="?page=catatan">Catatan</a>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-plain">Logout</button>
            </form>
        <?php else: ?>
            <a href="?page=login">Login</a>
            <a href="?page=register">Registrasi</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="msg-ok"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg-err"><?= e($error) ?></div><?php endif; ?>

    <?php
    extract($data);
    if ($page === 'register') require __DIR__ . '/views/register.php';
    elseif ($page === 'login') require __DIR__ . '/views/login.php';
    elseif ($page === 'progress') require __DIR__ . '/views/progress.php';
    elseif ($page === 'cetak') require __DIR__ . '/views/cetak.php';
    elseif ($page === 'catatan') require __DIR__ . '/views/catatan.php';
    elseif ($page === 'report') require __DIR__ . '/views/report.php';
    else require __DIR__ . '/views/dashboard.php';
    ?>
</div>
</body>
</html>