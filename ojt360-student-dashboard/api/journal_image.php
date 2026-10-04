<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'POST required.']); exit; }
verify_csrf();
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Please choose an image.']); exit; }
$file=$_FILES['image'];
if ($file['size'] > 5*1024*1024) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Image must be 5 MB or smaller.']); exit; }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (@getimagesize($file['tmp_name']) === false) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'The uploaded file is not a valid image.']); exit; }
$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
if (!isset($allowed[$mime])) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Only JPG, PNG, WEBP, and GIF images are allowed.']); exit; }
$uid=(int)$_SESSION['user_id'];
$dir=__DIR__.'/../uploads/journal/'.$uid;
if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) { http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Could not create the journal image folder.']); exit; }
$name='journal_'.date('Ymd_His').'_'.bin2hex(random_bytes(5)).'.'.$allowed[$mime];
$target=$dir.'/'.$name;
if (!move_uploaded_file($file['tmp_name'],$target)) { http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Could not save the image.']); exit; }
$url='uploads/journal/'.$uid.'/'.$name;
echo json_encode(['ok'=>true,'url'=>$url,'name'=>$name]);
