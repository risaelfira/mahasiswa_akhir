<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) session_start();

$dbDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dbDir)) mkdir($dbDir, 0777, true);

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$pdo = null;
try {
    $pdo = new PDO('sqlite:' . $dbDir . DIRECTORY_SEPARATOR . 'magang.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    if (!extension_loaded('pdo_sqlite')) {
        $hint = "PDO SQLite driver not enabled. Enable the 'pdo_sqlite' and 'sqlite3' extensions in your php.ini (uncomment extension=sqlite3 and extension=pdo_sqlite or extension=php_pdo_sqlite.dll on Windows), then restart Laragon/Apache/PHP-FPM.";
    } else {
        $hint = 'Unable to open SQLite database: ' . $e->getMessage();
    }
    echo "<h1>Configuration error</h1><p>" . htmlspecialchars($hint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
    exit;
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS internships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nim TEXT NOT NULL,
    nama TEXT NOT NULL,
    prodi TEXT NOT NULL,
    perusahaan TEXT NOT NULL,
    dosen_pembimbing TEXT NOT NULL,
    tanggal_mulai TEXT NOT NULL,
    judul_laporan TEXT,
    status_judul TEXT NOT NULL DEFAULT 'belum_diajukan',
    tanggal_pengajuan_judul TEXT,
    status_sidang TEXT NOT NULL DEFAULT 'belum_dijadwalkan',
    tanggal_sidang TEXT,
    catatan_akhir TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    internship_id INTEGER NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS weekly_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    internship_id INTEGER NOT NULL,
    minggu INTEGER NOT NULL,
    rencana TEXT,
    realisasi TEXT,
    umpan_balik TEXT,
    status TEXT NOT NULL DEFAULT 'belum_mulai',
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(internship_id, minggu),
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS daily_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    internship_id INTEGER NOT NULL,
    minggu INTEGER NOT NULL,
    hari_ke INTEGER NOT NULL,
    tanggal TEXT NOT NULL,
    kegiatan TEXT,
    checklist INTEGER NOT NULL DEFAULT 0,
    photo_path TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(internship_id, tanggal),
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    internship_id INTEGER NOT NULL,
    note_date TEXT NOT NULL,
    note_text TEXT NOT NULL,
    note_done INTEGER NOT NULL DEFAULT 0,
    note_priority TEXT NOT NULL DEFAULT 'normal',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(internship_id, note_date),
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);
");

$noteCols = $pdo->query("PRAGMA table_info(notes)")->fetchAll(PDO::FETCH_COLUMN, 1);

if (!in_array('note_done', $noteCols, true)) {
    $pdo->exec("ALTER TABLE notes ADD COLUMN note_done INTEGER NOT NULL DEFAULT 0");
}

if (!in_array('note_priority', $noteCols, true)) {
    $pdo->exec("ALTER TABLE notes ADD COLUMN note_priority TEXT NOT NULL DEFAULT 'normal'");
}

function initializeDailyReports(PDO $pdo, int $internshipId, string $tanggalMulai): void {
    try {
        $startDate = new DateTimeImmutable($tanggalMulai);
    } catch (Exception $e) {
        $startDate = new DateTimeImmutable('today');
    }

    $deleteWeekendStmt = $pdo->prepare("
        DELETE FROM daily_reports
        WHERE internship_id = :internship_id
          AND strftime('%w', tanggal) IN ('0', '6')
    ");
    $deleteWeekendStmt->execute([':internship_id' => $internshipId]);

    $stmt = $pdo->prepare("
        INSERT INTO daily_reports (internship_id, minggu, hari_ke, tanggal)
        VALUES (:internship_id, :minggu, :hari_ke, :tanggal)
        ON CONFLICT(internship_id, tanggal)
        DO UPDATE SET
            minggu = excluded.minggu,
            hari_ke = excluded.hari_ke
    ");

    $startMonday = $startDate->modify('monday this week');
    $endDate = $startMonday->modify('+12 weeks');
    $currentDate = $startDate;

    while ($currentDate < $endDate) {
        $dayNum = (int)$currentDate->format('N');

        if ($dayNum >= 1 && $dayNum <= 5) {
            $diffDays = (int)$startMonday->diff($currentDate)->format('%a');
            $weekNo = intdiv($diffDays, 7) + 1;

            if ($weekNo >= 1 && $weekNo <= 12) {
                $stmt->execute([
                    ':internship_id' => $internshipId,
                    ':minggu' => $weekNo,
                    ':hari_ke' => $dayNum,
                    ':tanggal' => $currentDate->format('Y-m-d'),
                ]);
            }
        }

        $currentDate = $currentDate->modify('+1 day');
    }
}

// Add manual_unlocked_weeks column to internships for manual week unlocking (backwards compatible)
$internCols = $pdo->query("PRAGMA table_info(internships)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('manual_unlocked_weeks', $internCols, true)) {
    $pdo->exec("ALTER TABLE internships ADD COLUMN manual_unlocked_weeks INTEGER NOT NULL DEFAULT 0");
}