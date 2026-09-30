<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

$student_id = (int)($_GET['student_id'] ?? ($_SESSION['user_id'] ?? 0));
if(!$student_id){ echo json_encode(['ok'=>false,'error'=>'invalid student']); exit; }

try {
  $sql = "
  SELECT sd.id, sd.doc_key, sd.name, sd.required,
    (SELECT COUNT(*) FROM uploads u WHERE u.doc_key = sd.doc_key AND u.user_id = ?) AS file_count,
    COALESCE(sds.is_checked,0) AS is_checked
  FROM supporting_docs sd
  LEFT JOIN supporting_doc_status sds ON sds.doc_id = sd.id AND sds.student_id = ?
  ORDER BY sd.required DESC, sd.name
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$student_id,$student_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'docs'=>$rows]);
} catch(Exception $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}