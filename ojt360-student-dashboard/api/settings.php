<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'POST required.'], 405);
verify_csrf();
$uid = (int)$_SESSION['student_pk'];
$action = (string)($_POST['action'] ?? '');

try {
    if ($action === 'schedule') {
        $start = (string)($_POST['shift_start'] ?? '08:00');
        $grace = (int)($_POST['grace_minutes'] ?? 15);
        $target = (float)($_POST['daily_target'] ?? 8);
        if (!preg_match('/^\d{2}:\d{2}$/', $start)) json_out(['ok' => false, 'message' => 'Enter a valid shift start time.'], 422);
        if ($grace < 0 || $grace > 120) json_out(['ok' => false, 'message' => 'Grace period must be between 0 and 120 minutes.'], 422);
        if ($target < 1 || $target > 16) json_out(['ok' => false, 'message' => 'Daily target must be between 1 and 16 hours.'], 422);
        db()->prepare('INSERT INTO student_settings(student_pk,shift_start,grace_minutes,daily_target) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE shift_start=VALUES(shift_start),grace_minutes=VALUES(grace_minutes),daily_target=VALUES(daily_target)')->execute([$uid, $start . ':00', $grace, $target]);
        json_out(['ok' => true, 'message' => 'Work schedule saved.']);
    }
    if ($action === 'goal') {
        $hours = (float)($_POST['required_hours'] ?? 0);
        if ($hours < 1 || $hours > 2000) json_out(['ok' => false, 'message' => 'Required hours must be between 1 and 2000.'], 422);
        $i = internship_for($uid);
        if (!$i) json_out(['ok' => false, 'message' => 'You have no internship record yet. Time in once with your company QR first.'], 422);
        db()->prepare('UPDATE internships SET required_hours=?, completed_hours=LEAST(completed_hours, ?) WHERE student_pk=?')->execute([$hours, $hours, $uid]);
        add_activity($uid, 'Updated required OJT hours to ' . rtrim(rtrim(number_format($hours, 2), '0'), '.'));
        json_out(['ok' => true, 'message' => 'Hours goal updated.']);
    }
    if ($action === 'password') {
        $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $conf = (string)($_POST['confirm'] ?? '');
        if (strlen($new) < 8) json_out(['ok' => false, 'message' => 'The new password must be at least 8 characters.'], 422);
        if ($new !== $conf) json_out(['ok' => false, 'message' => 'The new passwords do not match.'], 422);
        $q = db()->prepare('SELECT password_hash FROM students WHERE id=?'); $q->execute([$uid]);
        $hash = (string)$q->fetchColumn();
        if (!password_verify($cur, $hash)) json_out(['ok' => false, 'message' => 'Your current password is incorrect.'], 422);
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        db()->prepare('UPDATE students SET password_hash=? WHERE id=?')->execute([$newHash, $uid]);
        add_activity($uid, 'Changed account password');
        json_out(['ok' => true, 'message' => 'Password changed successfully.']);
    }
    json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    json_out(['ok' => false, 'message' => 'Could not save: ' . $e->getMessage()], 500);
}
