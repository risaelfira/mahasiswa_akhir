<?php
declare(strict_types=1);

// Detect AJAX requests (fetch with X-Requested-With header or explicit ajax=1)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

$action = $_POST['action'] ?? null;

// Ensure $uploadDir exists (bootstrap.php normally sets this). Provide safe fallback.
if (!isset($uploadDir) || !is_string($uploadDir) || $uploadDir === '') {
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
}

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $nim = trim($_POST['nim'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $prodi = trim($_POST['prodi'] ?? '');
    $perusahaan = trim($_POST['perusahaan'] ?? '');
    $dosen = trim($_POST['dosen_pembimbing'] ?? '');
    $tanggalMulai = trim($_POST['tanggal_mulai'] ?? '');

    if ($username === '' || $email === '' || $password === '' || $nim === '' || $nama === '' || $prodi === '' || $perusahaan === '' || $dosen === '' || $tanggalMulai === '') {
        redirectTo('?page=register&error=Lengkapi semua data registrasi');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redirectTo('?page=register&error=Format email tidak valid');
    if ($password !== $confirm) redirectTo('?page=register&error=Konfirmasi password tidak cocok');
    if (strlen($password) < 6) redirectTo('?page=register&error=Password minimal 6 karakter');

    $exists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u OR email = :e");
    $exists->execute([':u' => $username, ':e' => $email]);
    if ((int)$exists->fetchColumn() > 0) redirectTo('?page=register&error=Username atau email sudah dipakai');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO internships (nim, nama, prodi, perusahaan, dosen_pembimbing, tanggal_mulai)
            VALUES (:nim, :nama, :prodi, :perusahaan, :dosen, :tanggal_mulai)
        ");
        $stmt->execute([
            ':nim' => $nim,
            ':nama' => $nama,
            ':prodi' => $prodi,
            ':perusahaan' => $perusahaan,
            ':dosen' => $dosen,
            ':tanggal_mulai' => $tanggalMulai
        ]);

        $internshipId = (int)$pdo->lastInsertId();
        initializeWeeks($pdo, $internshipId);
        initializeDailyReports($pdo, $internshipId, $tanggalMulai);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userStmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, internship_id)
            VALUES (:username, :email, :password_hash, :internship_id)
        ");
        $userStmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $hash,
            ':internship_id' => $internshipId
        ]);

        $userId = (int)$pdo->lastInsertId();
        $pdo->commit();

        $_SESSION['user_id'] = $userId;
        $_SESSION['internship_id'] = $internshipId;
        $_SESSION['username'] = $username;

        redirectTo('?success=Registrasi berhasil, selamat datang');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirectTo('?page=register&error=Registrasi gagal, coba lagi');
    }
}

if ($action === 'login') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        redirectTo('?page=login&error=Isi username/email dan password');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :login OR email = :login LIMIT 1");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        redirectTo('?page=login&error=Login gagal, cek username/email dan password');
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['internship_id'] = (int)$user['internship_id'];
    $_SESSION['username'] = (string)$user['username'];

    redirectTo('?success=Login berhasil');
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirectTo('?page=login&success=Logout berhasil');
}

if (in_array($action, ['update_judul', 'save_week', 'save_daily_report'], true) && !isLoggedIn()) {
    redirectTo('?page=login&error=Silakan login dulu');
}

if ($action === 'update_judul') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) redirectTo('?error=Akses ditolak');

    $judul = trim($_POST['judul_laporan'] ?? '');
    $status = $_POST['status_judul'] ?? 'belum_diajukan';
    $tanggal = trim($_POST['tanggal_pengajuan_judul'] ?? '') ?: null;

    $allowed = ['belum_diajukan', 'diajukan', 'revisi', 'disetujui', 'ditolak'];
    if (!in_array($status, $allowed, true)) $status = 'belum_diajukan';

    $stmt = $pdo->prepare("UPDATE internships SET judul_laporan=:judul, status_judul=:status, tanggal_pengajuan_judul=:tanggal WHERE id=:id");
    $stmt->execute([
        ':judul' => $judul,
        ':status' => $status,
        ':tanggal' => $tanggal,
        ':id' => $id
    ]);
    redirectTo(progressRedirectTarget() . '&success=Progres laporan diperbarui');
}

if ($action === 'save_week') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
        redirectTo('?error=Akses ditolak');
    }

    $minggu = (int)($_POST['minggu'] ?? 1);
    $rencana = trim($_POST['rencana'] ?? '');
    $realisasi = trim($_POST['realisasi'] ?? '');
    $umpan = trim($_POST['umpan_balik'] ?? '');
    $status = $_POST['status'] ?? 'belum_mulai';
    if ($minggu < 1 || $minggu > 12) $minggu = 1;

    $startStmt = $pdo->prepare("SELECT tanggal_mulai FROM internships WHERE id = :id");
    $startStmt->execute([':id' => $id]);
    $start = (string)$startStmt->fetchColumn();
    // use effective unlocked week (date + manual unlocks)
    $unlockedWeek = getEffectiveUnlockedWeek($pdo, $id, $start);
    if ($minggu > $unlockedWeek) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Minggu belum terbuka']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Minggu belum terbuka');
    }

    $allowed = ['belum_mulai', 'proses', 'selesai', 'terlambat'];
    if (!in_array($status, $allowed, true)) $status = 'belum_mulai';

    $stmt = $pdo->prepare("
        UPDATE weekly_progress
        SET rencana=:rencana, realisasi=:realisasi, umpan_balik=:umpan, status=:status, updated_at=CURRENT_TIMESTAMP
        WHERE internship_id=:id AND minggu=:minggu
    ");
    $stmt->execute([
        ':rencana' => $rencana,
        ':realisasi' => $realisasi,
        ':umpan' => $umpan,
        ':status' => $status,
        ':id' => $id,
        ':minggu' => $minggu
    ]);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'message'=>'Progres mingguan diperbarui','minggu'=>$minggu]);
        exit;
    }

    redirectTo(progressRedirectTarget() . '&success=Progres mingguan diperbarui');
}

if ($action === 'save_daily_report') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
        redirectTo('?error=Akses ditolak');
    }

    $reportDate = trim($_POST['report_date'] ?? '');
    if ($id <= 0 || $reportDate === '') {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Data report harian tidak valid']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Data report harian tidak valid');
    }

    $dateStmt = $pdo->prepare("
        SELECT minggu, hari_ke, kegiatan, checklist, photo_path
        FROM daily_reports
        WHERE internship_id = :id AND tanggal = :tanggal
        LIMIT 1
    ");
    $dateStmt->execute([':id' => $id, ':tanggal' => $reportDate]);
    $target = $dateStmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Data report untuk tanggal ini tidak ditemukan']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Data report untuk tanggal ini tidak ditemukan');
    }

    $targetWeek = (int)($target['minggu'] ?? 0);
    $targetDay = (int)($target['hari_ke'] ?? 0);

    if ($targetWeek <= 0 || $targetDay <= 0) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Data hari report tidak valid']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Data hari report tidak valid');
    }

    $startStmt = $pdo->prepare("SELECT tanggal_mulai FROM internships WHERE id = :id");
    $startStmt->execute([':id' => $id]);
    $start = (string)$startStmt->fetchColumn();
    $unlockedWeek = getEffectiveUnlockedWeek($pdo, $id, $start);

    if ($targetWeek > $unlockedWeek) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Report tanggal ini belum terbuka']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Report tanggal ini belum terbuka');
    }

    $kegiatan = array_key_exists('kegiatan', $_POST)
        ? trim((string)$_POST['kegiatan'])
        : (string)($target['kegiatan'] ?? '');

    $checklist = isset($_POST['checklist_present'])
        ? (isset($_POST['checklist']) ? 1 : 0)
        : (int)($target['checklist'] ?? 0);

    $existingPhoto = trim((string)($_POST['existing_photo'] ?? (string)($target['photo_path'] ?? '')));

    $uploadedPhotos = uploadImages($_FILES['foto_kegiatan'] ?? [], $uploadDir, $id, $targetWeek, $targetDay);

    $hasSelectedPhoto = false;
    if (isset($_FILES['foto_kegiatan']['name']) && is_array($_FILES['foto_kegiatan']['name'])) {
        foreach ($_FILES['foto_kegiatan']['name'] as $photoName) {
            if (trim((string)$photoName) !== '') {
                $hasSelectedPhoto = true;
                break;
            }
        }
    }

    if ($hasSelectedPhoto && count($uploadedPhotos) === 0) {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Upload foto gagal. Gunakan JPG/PNG/WEBP/GIF dan maksimal 3 foto']); exit; }
        redirectTo(progressRedirectTarget() . '&error=Upload foto gagal. Gunakan JPG/PNG/WEBP/GIF dan maksimal 3 foto');
    }

    $existingPaths = array_filter(array_map('trim', explode(',', (string)$existingPhoto)));
    $mergePaths = array_values(array_unique(array_merge($existingPaths, $uploadedPhotos)));
    $photoPath = !empty($mergePaths) ? implode(',', $mergePaths) : null;

    $stmt = $pdo->prepare("
        UPDATE daily_reports
        SET kegiatan = :kegiatan,
            checklist = :checklist,
            photo_path = :photo_path,
            updated_at = CURRENT_TIMESTAMP
        WHERE internship_id = :internship_id AND tanggal = :tanggal
    ");
    $stmt->execute([
        ':kegiatan' => $kegiatan,
        ':checklist' => $checklist,
        ':photo_path' => $photoPath,
        ':internship_id' => $id,
        ':tanggal' => $reportDate
    ]);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'message'=>'Progres harian diperbarui','tanggal'=>$reportDate,'photo_path'=>$photoPath,'kegiatan'=>$kegiatan,'checklist'=>$checklist]);
        exit;
    }

    redirectTo(progressRedirectTarget() . '&success=Progres harian diperbarui');
}

if ($action === 'toggle_note') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) redirectTo('?error=Akses ditolak');

    $noteId = (int)($_POST['note_id'] ?? 0);
    $done = (int)($_POST['note_done'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE notes
        SET note_done = :done, updated_at = CURRENT_TIMESTAMP
        WHERE id = :id AND internship_id = :internship_id
    ");
    $stmt->execute([
        ':done' => $done ? 0 : 1,
        ':id' => $noteId,
        ':internship_id' => $id,
    ]);

    redirectTo('?page=dashboard&success=Catatan diperbarui');
}

if ($action === 'save_note') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) redirectTo('?error=Akses ditolak');

    $noteDate = trim($_POST['note_date'] ?? '');
    $noteText = trim($_POST['note_text'] ?? '');
    $priority = $_POST['note_priority'] ?? 'normal';

    $allowedPriority = ['low', 'normal', 'high'];
    if (!in_array($priority, $allowedPriority, true)) $priority = 'normal';

    if ($noteDate === '' || $noteText === '') {
        redirectTo('?page=catatan&error=Tanggal dan tugas wajib diisi');
    }

    $stmt = $pdo->prepare("
        INSERT INTO notes (internship_id, note_date, note_text, note_done, note_priority, updated_at)
        VALUES (:internship_id, :note_date, :note_text, 0, :note_priority, CURRENT_TIMESTAMP)
        ON CONFLICT(internship_id, note_date)
        DO UPDATE SET
            note_text = excluded.note_text,
            note_priority = excluded.note_priority,
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':internship_id' => $id,
        ':note_date' => $noteDate,
        ':note_text' => $noteText,
        ':note_priority' => $priority,
    ]);

    redirectTo('?page=catatan&success=Catatan tersimpan');
}

if ($action === 'delete_note') {
    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) redirectTo('?error=Akses ditolak');

    $noteId = (int)($_POST['note_id'] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id AND internship_id = :internship_id");
    $stmt->execute([
        ':id' => $noteId,
        ':internship_id' => $id,
    ]);

    redirectTo('?page=catatan&success=Catatan dihapus');
}

if ($action === 'unlock_next_week') {
    if (!isLoggedIn()) redirectTo('?page=login&error=Silakan login dulu');

    $id = (int)($_POST['internship_id'] ?? 0);
    if ($id !== currentInternshipId()) redirectTo('?error=Akses ditolak');

    $nextWeek = (int)$_POST['minggu'];
    if ($nextWeek < 1 || $nextWeek > 12) redirectTo('?error=Minggu tidak valid');

    $startStmt = $pdo->prepare("SELECT tanggal_mulai, manual_unlocked_weeks FROM internships WHERE id = :id");
    $startStmt->execute([':id' => $id]);
    $row = $startStmt->fetch(PDO::FETCH_ASSOC);
    $start = (string)($row['tanggal_mulai'] ?? date('Y-m-d'));
    $manual = (int)($row['manual_unlocked_weeks'] ?? 0);

    $currentUnlocked = getUnlockedWeekByStart($start) + $manual;

    if ($nextWeek <= $currentUnlocked) {
        redirectTo(progressRedirectTarget() . '&success=Minggu sudah terbuka');
    }

    // only allow opening the immediate next week
    if ($nextWeek !== $currentUnlocked + 1) {
        redirectTo('?error=Tidak bisa membuka minggu ini');
    }

    $upd = $pdo->prepare("UPDATE internships SET manual_unlocked_weeks = manual_unlocked_weeks + 1 WHERE id = :id");
    $upd->execute([':id' => $id]);

    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'message'=>'Minggu X dibuka']); exit; }
    redirectTo(progressRedirectTarget() . '&success=Minggu ' . $nextWeek . ' dibuka');
}

function progressRedirectTarget(): string {
    return (($_POST['return_page'] ?? '') === 'progress') ? '?page=progress' : '?';
}
?>
<script>
async function doAjaxSubmit(formEl) {
    const fd = new FormData(formEl);
    fd.set('ajax','1');
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
</script>
