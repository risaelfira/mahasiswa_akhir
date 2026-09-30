<?php
// views/progress.php
require_once __DIR__ . '/../bootstrap.php';
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$student_id = $_SESSION['user_id'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Monitoring Progres Laporan Magang</title>
  <link rel="stylesheet" href="/assets/style.css">
  <style>
    /* minimal styling for tabs */
    #progress-tabs { max-width:900px; margin:20px auto; }
    #progress-tabs ul { list-style:none; padding:0; display:flex; gap:8px; }
    #progress-tabs ul li { cursor:pointer; padding:8px 12px; background:#eee; border-radius:4px; }
    #progress-tabs ul li.active { background:#2b6cb0; color:#fff; }
    #tab-content { margin-top:16px; padding:12px; background:#fff; border:1px solid #ddd; border-radius:4px; }
    textarea{ width:100%; min-height:80px; }
  </style>
</head>
<body>
  <div id="progress-tabs">
    <ul id="tabs">
      <li data-chapter="1" class="active">Bab 1</li>
      <li data-chapter="2">Bab 2</li>
      <li data-chapter="3">Bab 3</li>
      <li data-chapter="4">Bab 4</li>
      <li data-chapter="5">Bab 5</li>
      <li data-chapter="supporting">Dokumen Pendamping</li>
    </ul>

    <div id="tab-content">Memuat...</div>
  </div>

  <script>
    const STUDENT_ID = <?= (int)$student_id ?>;
  </script>
  <script src="/assets/progress.js"></script>
</body>
</html>