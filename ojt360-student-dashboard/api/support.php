<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required.']); exit; }
verify_csrf();
$uid=(int)$_SESSION['user_id'];
$subject=trim((string)($_POST['subject']??''));
$message=trim((string)($_POST['message']??''));
if ($subject==='' || $message==='') { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Please complete the subject and message.']); exit; }
if (mb_strlen($subject)>180 || mb_strlen($message)>5000) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Your request is too long.']); exit; }
db()->prepare('INSERT INTO support_requests(user_id,subject,message) VALUES(?,?,?)')->execute([$uid,$subject,$message]);
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Submitted a support request']);
db()->prepare('INSERT INTO notifications(user_id,title,message,icon) VALUES(?,?,?,?)')->execute([$uid,'Support Request Received','Your support request was received by OJT360.','?']);
echo json_encode(['ok'=>true,'message'=>'Your support request has been submitted.']);
