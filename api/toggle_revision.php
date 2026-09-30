<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

$revision_id = (int)($_POST['revision_id'] ?? 0);
$student_id = (int)($_POST['student_id'] ?? ($_SESSION['user_id'] ?? 0));
$is_done = (int)($_POST['is_done'] ?? 0);

if(!$revision_id || !$student_id){
  echo json_encode(['ok'=>false,'error'=>'invalid input']); exit;
}

try {
  $up = $pdo->prepare("
    INSERT INTO revision_status (revision_id, student_id, is_done)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE is_done = VALUES(is_done), updated_at = CURRENT_TIMESTAMP
  ");
  $up->execute([$revision_id, $student_id, $is_done]);
  echo json_encode(['ok'=>true]);
} catch(Exception $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
