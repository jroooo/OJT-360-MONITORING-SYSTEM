<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required.']); exit; }
verify_csrf();
$uid=(int)$_SESSION['user_id'];
$action=$_POST['action'] ?? 'read_all';

if ($action === 'read_all') {
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);
    echo json_encode(['ok'=>true,'message'=>'All notifications marked as read.']);
    exit;
}

if ($action === 'read_one') {
    $id=(int)($_POST['id']??0);
    db()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$id,$uid]);
    echo json_encode(['ok'=>true]);
    exit;
}

http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Unknown action.']);
