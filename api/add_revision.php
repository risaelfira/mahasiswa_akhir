<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'mentor'){
  echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit;
}

$chapter = (int)($_POST['chapter'] ?? 0);
$note = trim($_POST['note'] ?? '');
$created_by = $_SESSION['user_id'];

if(!$chapter || $note === ''){
  echo json_encode(['ok'=>false,'error'=>'invalid input']); exit;
}

try {
  $stmt = $pdo->prepare("INSERT INTO revisions (chapter,note,created_by) VALUES (?,?,?)");
  $stmt->execute([$chapter,$note,$created_by]);
  echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
} catch(Exception $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}