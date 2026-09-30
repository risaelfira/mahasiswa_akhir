<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

$chapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 1;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : ($_SESSION['user_id'] ?? 0);

try {
  $stmt = $pdo->prepare("SELECT content FROM chapter_guides WHERE chapter = ?");
  $stmt->execute([$chapter]);
  $guide = $stmt->fetchColumn() ?: '';

  $revStmt = $pdo->prepare("
    SELECT r.id, r.note,
      IFNULL(rs.is_done,0) AS is_done
    FROM revisions r
    LEFT JOIN revision_status rs ON rs.revision_id = r.id AND rs.student_id = ?
    WHERE r.chapter = ?
    ORDER BY r.created_at DESC
  ");
  $revStmt->execute([$student_id, $chapter]);
  $revisions = $revStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'guide'=>$guide,'revisions'=>$revisions]);
} catch(Exception $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}