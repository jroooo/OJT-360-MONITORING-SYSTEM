
<?php
require_once __DIR__ . '/includes/bootstrap.php';

date_default_timezone_set('Asia/Manila');

function checkinPage(string $title, string $message, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>OJT360 Attendance</title>';
    echo '<style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;
    padding:20px;background:#f8fafc;font-family:Inter,Arial,sans-serif;color:#0f172a}
    .card{width:100%;max-width:430px;background:#fff;border:1px solid #e2e8f0;border-radius:20px;
    padding:32px 26px;box-shadow:0 12px 40px #0f172a0c;text-align:center}
    .mark{width:58px;height:58px;margin:0 auto 18px;display:grid;place-items:center;
    border-radius:18px;background:#eef2ff;color:#4338ca;font-size:24px;font-weight:900}
    h1{font-size:24px;margin:0 0 12px;letter-spacing:-.03em}
    p{font-size:14px;line-height:1.7;color:#64748b;margin:0}
    a{display:inline-block;margin-top:23px;background:#312e81;color:white;text-decoration:none;
    padding:12px 18px;border-radius:10px;font-size:13px;font-weight:800}
    </style></head><body><main class="card"><div class="mark">360</div>';
    echo '<h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>';
    echo '<a href="login.php">Go to login</a></main></body></html>';
    exit;
}

$token = trim((string) ($_GET['token'] ?? ''));

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    checkinPage('Invalid QR code', 'This QR code is not valid. Scan the current code shown by your company.', 400);
}

/*
 * INTEGRATION POINT:
 * This assumes the Student Portal stores the authenticated student's database ID
 * in $_SESSION['student_id']. Change this key if your actual login uses another one.
 */
$studentId = filter_var($_SESSION['student_id'] ?? null, FILTER_VALIDATE_INT);

if (!$studentId || $studentId < 1) {
    // Save the token so the student can continue after logging in.
    $_SESSION['pending_qr_token'] = $token;
    checkinPage(
        'Student login required',
        'Please sign in to your Student Portal, then scan the company QR code again.',
        401
    );
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $studentStmt = $pdo->prepare(
        'SELECT id, company_id
         FROM students
         WHERE id = :student_id
         LIMIT 1
         FOR UPDATE'
    );
    $studentStmt->execute([':student_id' => $studentId]);
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $pdo->rollBack();
        checkinPage('Student not found', 'Your student account could not be verified.', 403);
    }

    $tokenStmt = $pdo->prepare(
        'SELECT id, company_id
         FROM qr_attendance_tokens
         WHERE token_hash = :token_hash
           AND expires_at > NOW()
           AND revoked_at IS NULL
           AND used_at IS NULL
         LIMIT 1
         FOR UPDATE'
    );
    $tokenStmt->execute([':token_hash' => hash('sha256', $token)]);
    $validToken = $tokenStmt->fetch(PDO::FETCH_ASSOC);

    if (!$validToken) {
        $pdo->rollBack();
        checkinPage('QR expired', 'This code has expired, been stopped, or was already used. Scan the latest QR code.', 410);
    }

    if ((int) $student['company_id'] !== (int) $validToken['company_id']) {
        $pdo->rollBack();
        checkinPage('Company mismatch', 'This QR code is not from your assigned OJT company.', 403);
    }

    $duplicateStmt = $pdo->prepare(
        'SELECT id
         FROM attendance
         WHERE company_id = :company_id
           AND student_id = :student_id
           AND date = CURDATE()
         LIMIT 1'
    );
    $duplicateStmt->execute([
        ':company_id' => (int) $student['company_id'],
        ':student_id' => (int) $student['id']
    ]);

    if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        checkinPage('Already checked in', 'Your attendance has already been recorded for today.', 409);
    }

    $insert = $pdo->prepare(
        "INSERT INTO attendance
            (student_id, company_id, date, time_in, status, verified)
         VALUES
            (:student_id, :company_id, CURDATE(), CURTIME(), 'Present', 1)"
    );
    $insert->execute([
        ':student_id' => (int) $student['id'],
        ':company_id' => (int) $student['company_id']
    ]);

    // One successful scan consumes the token, preventing token replay.
    $consume = $pdo->prepare(
        'UPDATE qr_attendance_tokens
         SET used_at = NOW(), revoked_at = NOW()
         WHERE id = :id AND used_at IS NULL AND revoked_at IS NULL'
    );
    $consume->execute([':id' => (int) $validToken['id']]);

    if ($consume->rowCount() !== 1) {
        $pdo->rollBack();
        checkinPage('QR already used', 'This QR code has already been used. Scan the current code.', 409);
    }

    $pdo->commit();
    checkinPage('Attendance recorded!', 'Your check-in has been saved successfully. You may return to your Student Portal.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('OJT360 QR check-in error: ' . $exception->getMessage());
    checkinPage('Something went wrong', 'Attendance could not be recorded. Please scan again or contact your company representative.', 500);
}
