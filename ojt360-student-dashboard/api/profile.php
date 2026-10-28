<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'POST required.'], 405);
verify_csrf();
$uid = (int)$_SESSION['student_pk'];

$first = trim((string)($_POST['first_name'] ?? ''));
$middle = trim((string)($_POST['middle_name'] ?? ''));
$last = trim((string)($_POST['last_name'] ?? ''));
$mobile = trim((string)($_POST['mobile_number'] ?? ''));
$address = trim((string)($_POST['complete_address'] ?? ''));

if ($first === '' || $last === '') json_out(['ok' => false, 'message' => 'First name and last name are required.'], 422);
if ($mobile === '') json_out(['ok' => false, 'message' => 'Mobile number is required.'], 422);
if ($address === '') json_out(['ok' => false, 'message' => 'Complete address is required.'], 422);
if (mb_strlen($first) > 50 || mb_strlen($middle) > 50 || mb_strlen($last) > 50 || mb_strlen($mobile) > 20 || mb_strlen($address) > 1000) {
    json_out(['ok' => false, 'message' => 'One or more fields are too long.'], 422);
}

db()->prepare('UPDATE students SET first_name=?, middle_name=?, last_name=?, mobile_number=?, complete_address=? WHERE id=?')
    ->execute([$first, $middle !== '' ? $middle : null, $last, $mobile, $address, $uid]);
add_activity($uid, 'Updated profile information');
$full = trim($first . ' ' . ($middle !== '' ? $middle . ' ' : '') . $last);
json_out(['ok' => true, 'message' => 'Profile saved successfully.', 'full_name' => $full]);
