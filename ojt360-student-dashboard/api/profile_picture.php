<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'POST required.']); exit; }
verify_csrf();
$uid=(int)$_SESSION['user_id'];
$stmt=db()->prepare('SELECT profile_image FROM users WHERE id=? AND role="student" LIMIT 1');
$stmt->execute([$uid]);
$current=$stmt->fetchColumn();
$dir=__DIR__.'/../uploads/profile';
if (($_POST['action'] ?? '') === 'delete') {
    if ($current) { $old=$dir.'/'.basename((string)$current); if (is_file($old)) @unlink($old); }
    db()->prepare('UPDATE users SET profile_image=NULL WHERE id=? AND role="student"')->execute([$uid]);
    db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Removed profile picture']);
    echo json_encode(['ok'=>true,'message'=>'Profile picture removed.']); exit;
}
if (empty($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Please choose a profile picture.']); exit; }
$f=$_FILES['profile_picture'];
if ($f['size']>3*1024*1024) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Profile picture must be 3 MB or smaller.']); exit; }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name'])===false) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Only valid JPG, PNG, or WEBP images are allowed.']); exit; }
if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) { http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Could not create the profile image folder.']); exit; }
$name='student_'.$uid.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) { http_response_code(500); echo json_encode(['ok'=>false,'message'=>'The profile picture could not be saved.']); exit; }
if ($current) { $old=$dir.'/'.basename((string)$current); if (is_file($old)) @unlink($old); }
db()->prepare('UPDATE users SET profile_image=? WHERE id=? AND role="student"')->execute([$name,$uid]);
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Updated profile picture']);
echo json_encode(['ok'=>true,'message'=>'Profile picture updated.','url'=>'uploads/profile/'.$name.'?v='.time()]);
