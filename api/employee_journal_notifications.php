<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (
    !isset($_SESSION['employee_logged_in']) ||
    $_SESSION['employee_logged_in'] !== true ||
    empty($_SESSION['employee_id'])
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Employee sign-in required.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $statement = $conn->prepare("
        SELECT
            submission.id,
            submission.title,
            submission.submitted_at,
            TRIM(CONCAT_WS(
                ' ',
                NULLIF(student.first_name, ''),
                NULLIF(student.middle_name, ''),
                NULLIF(student.last_name, '')
            )) AS student_name
        FROM journal_submissions AS submission
        INNER JOIN students AS student ON student.id = submission.student_pk
        WHERE submission.submission_type = 'weekly'
            AND submission.status = 'pending'
            AND submission.pdf_file IS NOT NULL
            AND submission.pdf_file <> ''
        ORDER BY submission.submitted_at DESC, submission.id DESC
        LIMIT 30
    ");
    $statement->execute();
    $result = $statement->get_result();
    $submissions = [];

    while ($submission = $result->fetch_assoc()) {
        $submissions[] = $submission;
    }

    $statement->close();
    echo json_encode(['success' => true, 'notifications' => $submissions]);
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load adviser journal notifications: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load journal notifications.']);
}
