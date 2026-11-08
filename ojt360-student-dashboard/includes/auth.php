<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/* Resolve the shared app login session to the students.id used by dashboard data. */
function require_login(): void {
    if (
        empty($_SESSION['student_pk'])
        && ($_SESSION['role'] ?? null) === 'student'
        && !empty($_SESSION['student_id'])
    ) {
        $stmt = db()->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
        $stmt->execute([(string)$_SESSION['student_id']]);
        $studentPk = $stmt->fetchColumn();

        if ($studentPk !== false) {
            $_SESSION['student_pk'] = (int)$studentPk;
        }
    }

    if (empty($_SESSION['student_pk'])) {
        if (function_exists('is_api_request') && is_api_request()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode([
                'ok' => false,
                'message' => 'Your session has expired. Please sign in again.',
                'error' => 'Your session has expired. Please sign in again.',
            ]);
            exit;
        }

        header('Location: ../login.php');
        exit;
    }
}

/**
 * The signed-in student's row from the `students` table.
 * A few convenience keys are added so pages can use short names:
 *   full_name  = first + middle + last name
 *   student_no = students.student_id (7-digit school ID)
 *   email      = institutional_email,  phone = mobile_number,  address = complete_address
 */
function current_user(): ?array {
    static $user = null;
    if ($user !== null) return $user;
    if (empty($_SESSION['student_pk'])) return null;

    $stmt = db()->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['student_pk']]);
    $row = $stmt->fetch();
    if (!$row) { unset($_SESSION['student_pk']); return null; }

    $middle = trim((string)($row['middle_name'] ?? ''));
    $row['full_name'] = trim($row['first_name'] . ' ' . ($middle !== '' ? $middle . ' ' : '') . $row['last_name']);
    $row['student_no'] = $row['student_id'];
    $row['email'] = $row['institutional_email'];
    $row['phone'] = $row['mobile_number'];
    $row['address'] = $row['complete_address'];
    $user = $row;
    return $user;
}
