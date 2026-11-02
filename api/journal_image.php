<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'POST required.'], 405);
verify_csrf();
if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    json_out(['ok' => false, 'message' => in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'That image is larger than the server upload limit.' : 'Please choose an image.'], 422);
}
$file = $_FILES['image'];
if ($file['size'] > 8 * 1024 * 1024) json_out(['ok' => false, 'message' => 'Image must be 8 MB or smaller.'], 422);
if (@getimagesize($file['tmp_name']) === false) json_out(['ok' => false, 'message' => 'The uploaded file is not a valid image.'], 422);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!isset($allowed[$mime])) json_out(['ok' => false, 'message' => 'Only JPG, PNG, WEBP, and GIF images are allowed.'], 422);
$uid = (int)$_SESSION['student_pk'];
$dir = __DIR__ . '/../uploads/journal/' . $uid;
if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) json_out(['ok' => false, 'message' => 'Could not create the journal image folder.'], 500);
$name = 'journal_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) json_out(['ok' => false, 'message' => 'Could not save the image.'], 500);
json_out(['ok' => true, 'url' => 'uploads/journal/' . $uid . '/' . $name, 'name' => $name]);
