<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Keep the dashboard session compatible with the student login/registration
 * system. Older sessions may contain student_id without user_id, so recover
 * the dashboard user automatically when possible.
 */
function sync_student_session(): ?int {
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    $studentId = trim((string)($_SESSION['student_id'] ?? ''));
    if ($studentId === '') {
        return null;
    }

    $stmt = db()->prepare('SELECT id, first_name, middle_name, last_name, course, mobile_number, complete_address, student_id, institutional_email, password_hash FROM students WHERE student_id = ? LIMIT 1');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) {
        return null;
    }

    $find = db()->prepare('SELECT id FROM users WHERE student_no = ? OR email = ? LIMIT 1');
    $find->execute([$student['student_id'], $student['institutional_email']]);
    $userId = $find->fetchColumn();

    if (!$userId) {
        $fullName = trim($student['first_name'] . ' ' . ($student['middle_name'] !== '' ? $student['middle_name'] . ' ' : '') . $student['last_name']);
        $create = db()->prepare('INSERT INTO users (full_name,email,password_hash,student_no,course,section,phone,address,role) VALUES (?,?,?,?,?,?,?,?,\'student\')');
        $create->execute([
            $fullName,
            $student['institutional_email'],
            $student['password_hash'],
            $student['student_id'],
            $student['course'],
            'AI23',
            $student['mobile_number'],
            $student['complete_address']
        ]);
        $userId = db()->lastInsertId();
    }

    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['role'] = 'student';
    return (int)$userId;
}

function require_login(): void {
    $userId = sync_student_session();

    if (!$userId || ($_SESSION['role'] ?? '') !== 'student') {
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array {
    static $user = null;
    if ($user !== null) return $user;

    $userId = sync_student_session();
    if (!$userId) return null;

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND role = \'student\' LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch() ?: null;
    return $user;
}
