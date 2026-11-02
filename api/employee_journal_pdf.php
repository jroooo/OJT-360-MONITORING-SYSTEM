<?php
declare(strict_types=1);

session_start();
if (
    !isset($_SESSION['employee_logged_in']) ||
    $_SESSION['employee_logged_in'] !== true ||
    empty($_SESSION['employee_id'])
) {
    http_response_code(401);
    exit('Employee sign-in required.');
}

$submissionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($submissionId === false || $submissionId === null) {
    http_response_code(400);
    exit('A valid submission ID is required.');
}

require_once __DIR__ . '/../config/database.php';
try {
    $statement = $conn->prepare('SELECT pdf_file FROM journal_submissions WHERE id = ? LIMIT 1');
    $statement->bind_param('i', $submissionId);
    $statement->execute();
    $result = $statement->get_result();
    $submission = $result->fetch_assoc();
    $statement->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load journal PDF: ' . $exception->getMessage());
    http_response_code(500);
    exit('The journal PDF could not be loaded.');
}

$filename = (string)($submission['pdf_file'] ?? '');
if (!preg_match('/\A[a-f0-9]{48}\.pdf\z/', $filename)) {
    http_response_code(404);
    exit('Journal PDF not found.');
}

$storageDirectory = realpath(__DIR__ . '/../ojt360-student-dashboard/uploads/journal_submissions');
$pdfPath = $storageDirectory ? realpath($storageDirectory . DIRECTORY_SEPARATOR . $filename) : false;
if (
    !$storageDirectory ||
    !$pdfPath ||
    strpos($pdfPath, $storageDirectory . DIRECTORY_SEPARATOR) !== 0 ||
    !is_file($pdfPath)
) {
    http_response_code(404);
    exit('Journal PDF not found.');
}

$handle = fopen($pdfPath, 'rb');
$signature = $handle ? fread($handle, 5) : false;
$footer = false;
if ($handle) {
    fseek($handle, max(0, filesize($pdfPath) - 2048));
    $footer = fread($handle, 2048);
}
if ($handle) fclose($handle);
if ($signature !== '%PDF-' || !is_string($footer) || strpos($footer, '%%EOF') === false) {
    http_response_code(404);
    exit('Journal PDF not found.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfPath));
header('Content-Disposition: inline; filename="journal-submission-' . (int)$submissionId . '.pdf"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($pdfPath);
