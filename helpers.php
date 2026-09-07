<?php
declare(strict_types=1);

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function redirectTo(string $url): void { header('Location: ' . $url); exit; }

function isLoggedIn(): bool { return isset($_SESSION['user_id'], $_SESSION['internship_id']); }
function currentInternshipId(): int { return (int)($_SESSION['internship_id'] ?? 0); }

function uploadImage(string $fileField, string $uploadDir, int $internshipId, int $weekNo, int $dayNo): ?string {
    if (!isset($_FILES[$fileField])) return null;

    $errorCode = $_FILES[$fileField]['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode === UPLOAD_ERR_NO_FILE) return null;
    if ($errorCode !== UPLOAD_ERR_OK) return null;

    $tmpName = $_FILES[$fileField]['tmp_name'];
    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) return null;

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    $mime = $imageInfo['mime'] ?? '';
    if (!isset($extMap[$mime])) return null;

    $studentDirName = 'internship_' . $internshipId;
    $targetDir = $uploadDir . DIRECTORY_SEPARATOR . $studentDirName;
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $baseName = 'minggu-' . $weekNo . '_hari-' . $dayNo;
    foreach (['jpg', 'png', 'webp', 'gif'] as $ext) {
        $oldPath = $targetDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext;
        if (is_file($oldPath)) @unlink($oldPath);
    }

    $fileName = $baseName . '.' . $extMap[$mime];
    $dest = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $dest)) return null;

    return 'uploads/' . $studentDirName . '/' . $fileName;
}

function uploadImages(array $files, string $uploadDir, int $internshipId, int $weekNo, int $dayNo): array {
    if (!isset($files['name']) || !is_array($files['name'])) return [];

    $uploaded = [];
    $count = min(count($files['name']), 3);

    for ($i = 0; $i < $count; $i++) {
        $name = $files['name'][$i] ?? '';
        $tmpName = $files['tmp_name'][$i] ?? '';
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;

        if ($name === '' || $tmpName === '' || $error !== UPLOAD_ERR_OK) continue;

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) continue;

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif'
        ];
        $mime = $imageInfo['mime'] ?? '';
        if (!isset($extMap[$mime])) continue;

        $studentDirName = 'internship_' . $internshipId;
        $targetDir = $uploadDir . DIRECTORY_SEPARATOR . $studentDirName;
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = 'minggu-' . $weekNo . '_hari-' . $dayNo . '_foto-' . ($i + 1) . '.' . $extMap[$mime];
        $dest = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        if (move_uploaded_file($tmpName, $dest)) {
            $uploaded[] = 'uploads/' . $studentDirName . '/' . $fileName;
        }
    }

    return $uploaded;
}

function initializeWeeks(PDO $pdo, int $internshipId): void {
    $plans = [
        1 => 'Orientasi lingkungan kerja, pengenalan tim operasional Iconnet, pemahaman alur kerja sistem pemantauan yang ada, serta penetapan ruang lingkup analisis dashboard pemantauan.',
        2 => 'Observasi proses pemantauan jaringan/layanan saat ini, identifikasi kebutuhan visualisasi data real-time, serta pengumpulan kendala operasional pada dashboard yang berjalan.',
        3 => 'Pengumpulan data teknis dan arsitektur dashboard pemantauan mandiri (in-house), mencakup tech stack, alur integrasi data, serta estimasi sumber daya pengembangannya.',
        4 => 'Pengumpulan data spesifikasi dan fitur alternatif dashboard berbayar (SaaS/Vendor), mencakup batas metrics, alur integrasi, dan skema biaya lisensi.',
        5 => 'Penyusunan indikator komparasi bersama pembimbing lapangan, meliputi visualisasi, latency/alerting, fleksibilitas kustomisasi, keamanan akses, dan efisiensi biaya.',
        6 => 'Analisis perbandingan biaya (TCO) antara investasi pengembangan/perawatan dashboard mandiri versus biaya langganan dashboard berbayar.',
        7 => 'Analisis teknis dan performa, mencakup kemudahan integrasi dengan data source Iconnet, fleksibilitas panel visualisasi, dan manajemen hak akses pengguna.',
        8 => 'Penyusunan matriks perbandingan serta perumusan rekomendasi keputusan (Build vs Buy) dashboard pemantauan paling optimal.',
        9 => 'Presentasi dan diskusi hasil analisis komparatif dashboard awal bersama pembimbing lapangan/tim operasional.',
        10 => 'Penyusunan draf laporan magang bab demi bab yang berfokus pada hasil analisis komparatif dashboard pemantauan.',
        11 => 'Finalisasi laporan magang, revisi berdasarkan masukan pembimbing lapangan, serta perumusan kesimpulan dan saran operasional.',
        12 => 'Evaluasi akhir kegiatan magang, penyerahan laporan resmi, dan pengurusan lembar pengesahan serta nilai magang.'
    ];

    $insertStmt = $pdo->prepare("INSERT OR IGNORE INTO weekly_progress (internship_id, minggu, rencana) VALUES (:id, :minggu, :rencana)");
    $fillStmt = $pdo->prepare("UPDATE weekly_progress SET rencana = :rencana WHERE internship_id = :id AND minggu = :minggu AND (rencana IS NULL OR TRIM(rencana) = '')");

    for ($i = 1; $i <= 12; $i++) {
        $plan = $plans[$i] ?? '';
        $insertStmt->execute([':id'=>$internshipId, ':minggu'=>$i, ':rencana'=>$plan]);
        $fillStmt->execute([':id'=>$internshipId, ':minggu'=>$i, ':rencana'=>$plan]);
    }
}

function statusBadge(string $status): string {
    $map = [
        'belum_mulai'=>'#be185d','proses'=>'#ec4899','selesai'=>'#f472b6','terlambat'=>'#9d174d',
        'belum_diajukan'=>'#be185d','diajukan'=>'#ec4899','revisi'=>'#db2777','disetujui'=>'#f472b6','ditolak'=>'#9d174d'
    ];
    $color = $map[$status] ?? '#be185d';
    return '<span style="background:'.$color.';color:#fff;padding:4px 10px;border-radius:999px;font-size:12px;">'.e(str_replace('_',' ',$status)).'</span>';
}

function getUnlockedWeekByStart(string $start): int {
    try { $startDate = new DateTimeImmutable($start); }
    catch (Exception $e) { $startDate = new DateTimeImmutable('today'); }

    $today = new DateTimeImmutable('today');
    if ($today < $startDate) return 1;
    $diffDays = (int)$startDate->diff($today)->format('%a');
    return max(1, min(12, intdiv($diffDays, 7) + 1));
}

function getEffectiveUnlockedWeek(PDO $pdo, int $internshipId, string $start): int {
    $base = getUnlockedWeekByStart($start);
    $stmt = $pdo->prepare("SELECT manual_unlocked_weeks FROM internships WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $internshipId]);
    $extra = (int)$stmt->fetchColumn();
    return max(1, min(12, $base + $extra));
}
