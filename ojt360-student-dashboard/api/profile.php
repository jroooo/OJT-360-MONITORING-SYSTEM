<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required.']); exit; }
verify_csrf();
$uid=(int)$_SESSION['user_id'];
$full=trim((string)($_POST['full_name']??''));
$phone=trim((string)($_POST['phone']??''));
$address=trim((string)($_POST['address']??''));
if ($full==='') { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Full name is required.']); exit; }
if (mb_strlen($full)>120 || mb_strlen($phone)>40 || mb_strlen($address)>255) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'One or more fields are too long.']); exit; }

db()->prepare('UPDATE users SET full_name=?, phone=?, address=? WHERE id=? AND role="student"')->execute([$full,$phone,$address,$uid]);
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Updated profile information']);
echo json_encode(['ok'=>true,'message'=>'Profile saved successfully.']);
