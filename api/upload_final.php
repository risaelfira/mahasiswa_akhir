<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';
session_start();

if(!isset($_SESSION['user_id'])){ echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
$student_id = $_SESSION['user_id'];
$chapter = (int)($_POST['chapter'] ?? 0);

if(!isset($_FILES['file']) || $chapter < 1 || $chapter > 5){
  echo json_encode(['ok'=>false,'error'=>'invalid input']); exit;
}

$dir = __DIR__ . '/../uploads/final_chapters';
if(!is_dir($dir)) mkdir($dir,0755,true);

$file = $_FILES['file'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed = ['pdf','doc','docx'];
if(!in_array(strtolower($ext), $allowed)){
  echo json_encode(['ok'=>false,'error'=>'invalid file type']); exit;
}

$basename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$dest = $dir . '/' . $basename;
if(!move_uploaded_file($file['tmp_name'], $dest)){
  echo json_encode(['ok'=>false,'error'=>'upload failed']); exit;
}

try {
  $pdo->beginTransaction();
  $ins = $pdo->prepare("INSERT INTO uploads (user_id, chapter, filename, filepath) VALUES (?,?,?,?)");
  $ins->execute([$student_id, $chapter, $file['name'], 'uploads/final_chapters/'.$basename]);
  $upload_id = $pdo->lastInsertId();

  $ins2 = $pdo->prepare("INSERT INTO final_uploads (chapter, upload_id, uploaded_by) VALUES (?,?,?)");
  $ins2->execute([$chapter, $upload_id, $student_id]);

  $pdo->commit();
  echo json_encode(['ok'=>true,'upload_id'=>$upload_id]);
} catch(Exception $e){
  $pdo->rollBack();
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
