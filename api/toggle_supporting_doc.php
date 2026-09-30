<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

$doc_id = (int)($_POST['doc_id'] ?? 0);
$student_id = (int)($_POST['student_id'] ?? ($_SESSION['user_id'] ?? 0));
$is_checked = (int)($_POST['is_checked'] ?? 0);

if(!$doc_id || !$student_id){ echo json_encode(['ok'=>false,'error'=>'invalid input']); exit; }

try {
  $q = $pdo->prepare("SELECT doc_key FROM supporting_docs WHERE id = ?");
  $q->execute([$doc_id]);
  $doc_key = $q->fetchColumn();
  if(!$doc_key){ echo json_encode(['ok'=>false,'error'=>'doc not found']); exit; }

  $c = $pdo->prepare("SELECT COUNT(*) FROM uploads WHERE doc_key = ? AND user_id = ?");
  $c->execute([$doc_key,$student_id]);
  $count = (int)$c->fetchColumn();
  if($count === 0){
    echo json_encode(['ok'=>false,'error'=>'file_missing']); exit;
  }

  $up = $pdo->prepare("
    INSERT INTO supporting_doc_status (doc_id, student_id, is_checked, checked_at)
    VALUES (?, ?, ?, CASE WHEN ?=1 THEN NOW() ELSE NULL END)
    ON DUPLICATE KEY UPDATE is_checked = VALUES(is_checked), checked_at = VALUES(checked_at)
  ");
  $up->execute([$doc_id, $student_id, $is_checked, $is_checked]);
  echo json_encode(['ok'=>true]);
} catch(Exception $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}