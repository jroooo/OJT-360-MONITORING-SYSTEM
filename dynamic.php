<?php

require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

$companyId = (int) $company['id'];

$pageKey = 'qr';
$pageTitle = 'Dynamic QR Code';
$eyebrow = 'SMART ATTENDANCE';

date_default_timezone_set('Asia/Manila');

/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/
function qrSendJson(array $data, int $status = 200)
{
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| QR API
|--------------------------------------------------------------------------
*/
if (isset($_GET['api'])) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        qrSendJson([
            'ok' => false,
            'message' => 'POST required.'
        ], 405);
    }

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */
    $submittedCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (
        !is_string($submittedCsrf) ||
        $submittedCsrf === '' ||
        !hash_equals(csrf_token(), $submittedCsrf)
    ) {
        qrSendJson([
            'ok' => false,
            'message' => 'Security check failed. Refresh the page.'
        ], 403);
    }

    try {

        $action = (string) $_GET['api'];

        /*
        |--------------------------------------------------------------------------
        | STOP / REVOKE CURRENT QR
        |--------------------------------------------------------------------------
        */
        if ($action === 'revoke') {

            $stmt = $pdo->prepare(
                'UPDATE qr_attendance_tokens
                 SET revoked_at = NOW()
                 WHERE company_id = :cid
                   AND revoked_at IS NULL'
            );

            $stmt->execute([
                ':cid' => $companyId
            ]);

            qrSendJson([
                'ok' => true,
                'message' => 'QR session stopped.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ONLY GENERATE IS ALLOWED FROM HERE
        |--------------------------------------------------------------------------
        */
        if ($action !== 'generate') {

            qrSendJson([
                'ok' => false,
                'message' => 'Unknown action.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | START TRANSACTION
        |--------------------------------------------------------------------------
        */
        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | REVOKE OLD QR
        |--------------------------------------------------------------------------
        */
        $revoke = $pdo->prepare(
            'UPDATE qr_attendance_tokens
             SET revoked_at = NOW()
             WHERE company_id = :cid
               AND revoked_at IS NULL'
        );

        $revoke->execute([
            ':cid' => $companyId
        ]);

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW RANDOM TOKEN
        |--------------------------------------------------------------------------
        */
        $token = bin2hex(random_bytes(32));

        /*
        |--------------------------------------------------------------------------
        | HASH TOKEN FOR DATABASE
        |--------------------------------------------------------------------------
        */
        $tokenHash = hash('sha256', $token);

        /*
        |--------------------------------------------------------------------------
        | SAVE TOKEN
        | EXPIRES AFTER 30 SECONDS
        |--------------------------------------------------------------------------
        */
        $insert = $pdo->prepare(
            'INSERT INTO qr_attendance_tokens
                (
                    company_id,
                    token_hash,
                    created_at,
                    expires_at
                )
             VALUES
                (
                    :cid,
                    :hash,
                    NOW(),
                    DATE_ADD(NOW(), INTERVAL 30 SECOND)
                )'
        );

        $insert->execute([
            ':cid' => $companyId,
            ':hash' => $tokenHash
        ]);

        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */
        $pdo->commit();

        /*
        |--------------------------------------------------------------------------
        | DETERMINE HTTP / HTTPS
        |--------------------------------------------------------------------------
        */
        $https = false;

        if (
            !empty($_SERVER['HTTPS']) &&
            strtolower((string) $_SERVER['HTTPS']) !== 'off'
        ) {
            $https = true;
        }

        if (
            !$https &&
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ) {
            $forwardedProto = strtolower(
                trim(
                    explode(
                        ',',
                        (string) $_SERVER['HTTP_X_FORWARDED_PROTO']
                    )[0]
                )
            );

            if ($forwardedProto === 'https') {
                $https = true;
            }
        }

        $scheme = $https ? 'https' : 'http';

        /*
        |--------------------------------------------------------------------------
        | SERVER HOST
        |--------------------------------------------------------------------------
        */
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (
            !preg_match(
                '/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/',
                $host
            )
        ) {
            qrSendJson([
                'ok' => false,
                'message' => 'Invalid server host configuration.'
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT FOLDER
        |--------------------------------------------------------------------------
        */
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/dynamic.php';

        $basePath = str_replace(
            '\\',
            '/',
            dirname($scriptName)
        );

        $basePath = rtrim($basePath, '/');

        /*
        |--------------------------------------------------------------------------
        | QR CHECK-IN URL
        |--------------------------------------------------------------------------
        */
        $scanUrl =
            $scheme .
            '://' .
            $host .
            $basePath .
            '/qr_checkin.php?token=' .
            rawurlencode($token);

        /*
        |--------------------------------------------------------------------------
        | SEND RESPONSE
        |--------------------------------------------------------------------------
        */
        qrSendJson([
            'ok' => true,
            'scan_url' => $scanUrl,
            'token' => $token,
            'expires_in' => 30
        ]);

    } catch (Throwable $ex) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'OJT360 QR generation error: ' .
            $ex->getMessage()
        );

        qrSendJson([
            'ok' => false,
            'message' => 'Could not generate a QR code. Check the server error log.'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| TODAY'S ATTENDANCE
|--------------------------------------------------------------------------
|
| IMPORTANT:
| There is NO "a.status" here because your attendance table
| does not have a status column.
|
|--------------------------------------------------------------------------
*/

$attendanceToday = [];

try {

    $todayStmt = $pdo->prepare(
        'SELECT
            a.id,
            a.time_in,
            s.first_name,
            s.last_name,
            s.student_id_no,
            s.program
         FROM attendance a
         INNER JOIN students s
             ON s.id = a.student_id
         WHERE a.company_id = :cid
           AND a.date = CURDATE()
           AND s.company_id = :student_cid
         ORDER BY a.id DESC'
    );

    $todayStmt->execute([
        ':cid' => $companyId,
        ':student_cid' => $companyId
    ]);

    $attendanceToday = $todayStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $ex) {

    error_log(
        'OJT360 attendance query error: ' .
        $ex->getMessage()
    );

    $attendanceToday = [];
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
$csrf = csrf_token();

include __DIR__ . '/includes/layout_top.php';

?>

<style>
/* ============================================================
   DYNAMIC QR PAGE
   ============================================================ */

.qr-page {
    width: 100%;
    min-height: calc(100vh - 80px);
    padding: 24px;
}

.qr-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

/* ============================================================
   HEADER
   ============================================================ */

.qr-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}

.qr-header-left {
    min-width: 0;
}

.qr-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;

    color: #2563eb;

    margin-bottom: 8px;
}

.qr-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2563eb;
    box-shadow: 0 0 12px rgba(37, 99, 235, 0.6);
}

.qr-title {
    margin: 0;

    font-size: clamp(28px, 4vw, 42px);
    line-height: 1.1;
    font-weight: 900;

    color: #0f172a;
}

.qr-description {
    margin: 8px 0 0;

    max-width: 700px;

    color: #64748b;

    font-size: 14px;
    line-height: 1.6;
}

/* ============================================================
   STATUS
   ============================================================ */

.qr-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 14px;

    border-radius: 999px;

    background: #eff6ff;
    color: #2563eb;

    font-size: 12px;
    font-weight: 800;

    white-space: nowrap;
}

.qr-status-dot {
    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: #22c55e;

    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
}

.qr-status.stopped {
    background: #f8fafc;
    color: #64748b;
}

.qr-status.stopped .qr-status-dot {
    background: #94a3b8;
    box-shadow: none;
}

/* ============================================================
   MAIN GRID
   ============================================================ */

.qr-grid {
    display: grid;

    grid-template-columns: minmax(320px, 0.9fr) minmax(420px, 1.1fr);

    gap: 24px;

    align-items: stretch;
}

/* ============================================================
   CARDS
   ============================================================ */

.qr-card {
    background: #ffffff;

    border: 1px solid #e2e8f0;

    border-radius: 24px;

    box-shadow:
        0 10px 30px rgba(15, 23, 42, 0.05);

    overflow: hidden;
}

.qr-card-header {
    padding: 22px 24px 16px;

    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 15px;

    border-bottom: 1px solid #f1f5f9;
}

.qr-card-title {
    margin: 0;

    font-size: 16px;
    font-weight: 900;

    color: #0f172a;
}

.qr-card-subtitle {
    margin: 4px 0 0;

    font-size: 12px;

    color: #94a3b8;
}

.qr-card-body {
    padding: 24px;
}

/* ============================================================
   QR DISPLAY
   ============================================================ */

.qr-display {
    position: relative;

    width: 100%;

    min-height: 500px;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: center;

    padding: 30px;

    background:
        radial-gradient(
            circle at center,
            rgba(37, 99, 235, 0.06),
            transparent 45%
        ),
        #f8fafc;

    border-radius: 20px;

    border: 1px solid #e2e8f0;
}

.qr-box {
    width: 320px;
    max-width: 100%;
    aspect-ratio: 1 / 1;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #ffffff;

    border-radius: 22px;

    padding: 20px;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 20px 50px rgba(15, 23, 42, 0.10);
}

.qr-box img {
    display: block;

    width: 100%;
    height: 100%;

    object-fit: contain;

    image-rendering: pixelated;
}

.qr-placeholder {
    width: 100%;
    height: 100%;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    text-align: center;

    color: #94a3b8;
}

.qr-placeholder-icon {
    width: 70px;
    height: 70px;

    border-radius: 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eff6ff;

    color: #2563eb;

    font-size: 30px;

    margin-bottom: 14px;
}

.qr-placeholder strong {
    color: #475569;

    font-size: 14px;

    margin-bottom: 4px;
}

.qr-placeholder span {
    font-size: 12px;
}

/* ============================================================
   COUNTDOWN
   ============================================================ */

.qr-countdown {
    margin-top: 22px;

    display: flex;
    flex-direction: column;

    align-items: center;
    gap: 7px;
}

.qr-countdown-label {
    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.12em;

    color: #94a3b8;
}

.qr-timer {
    font-size: 34px;

    line-height: 1;

    font-weight: 900;

    color: #2563eb;
}

.qr-timer.warning {
    color: #f59e0b;
}

.qr-timer.danger {
    color: #ef4444;
}

/* ============================================================
   BUTTONS
   ============================================================ */

.qr-actions {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    margin-top: 22px;

    width: 100%;
}

.qr-btn {
    border: none;

    border-radius: 13px;

    min-height: 46px;

    padding: 0 16px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 8px;

    font-family: inherit;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;

    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease,
        opacity 0.15s ease;
}

.qr-btn:hover {
    transform: translateY(-1px);
}

.qr-btn:active {
    transform: translateY(0);
}

.qr-btn:disabled {
    cursor: not-allowed;

    opacity: 0.5;

    transform: none;
}

.qr-btn-primary {
    background: #2563eb;

    color: #ffffff;

    box-shadow:
        0 8px 20px rgba(37, 99, 235, 0.22);
}

.qr-btn-danger {
    background: #fee2e2;

    color: #dc2626;
}

.qr-btn-secondary {
    background: #f1f5f9;

    color: #475569;
}

/* ============================================================
   INFO
   ============================================================ */

.qr-info {
    margin-top: 18px;

    padding: 14px 16px;

    border-radius: 14px;

    background: #eff6ff;

    border: 1px solid #dbeafe;
}

.qr-info-title {
    display: flex;
    align-items: center;
    gap: 8px;

    color: #1d4ed8;

    font-size: 12px;
    font-weight: 900;

    margin-bottom: 5px;
}

.qr-info-text {
    color: #64748b;

    font-size: 12px;

    line-height: 1.5;
}

/* ============================================================
   ATTENDANCE
   ============================================================ */

.attendance-table-wrap {
    overflow-x: auto;
}

.attendance-table {
    width: 100%;

    border-collapse: collapse;

    min-width: 520px;
}

.attendance-table th {
    padding: 13px 14px;

    text-align: left;

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: 0.08em;

    color: #94a3b8;

    background: #f8fafc;

    border-bottom: 1px solid #e2e8f0;
}

.attendance-table td {
    padding: 15px 14px;

    font-size: 13px;

    color: #475569;

    border-bottom: 1px solid #f1f5f9;
}

.attendance-table tbody tr:hover {
    background: #f8fafc;
}

.student-name {
    font-weight: 800;

    color: #0f172a;
}

.student-id {
    margin-top: 3px;

    font-size: 11px;

    color: #94a3b8;
}

.qr-pill {
    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 999px;

    background: #dcfce7;

    color: #15803d;

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;
}

/* ============================================================
   EMPTY STATE
   ============================================================ */

.empty-state {
    padding: 60px 25px;

    text-align: center;
}

.empty-icon {
    width: 58px;
    height: 58px;

    margin: 0 auto 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 16px;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 25px;
}

.empty-state strong {
    display: block;

    color: #475569;

    font-size: 14px;

    margin-bottom: 5px;
}

.empty-state span {
    font-size: 12px;

    color: #94a3b8;
}

/* ============================================================
   LOADING
   ============================================================ */

.qr-loading {
    position: absolute;

    inset: 0;

    display: none;

    align-items: center;

    justify-content: center;

    background: rgba(248, 250, 252, 0.88);

    backdrop-filter: blur(3px);

    border-radius: 20px;

    z-index: 5;
}

.qr-loading.show {
    display: flex;
}

.spinner {
    width: 34px;
    height: 34px;

    border: 3px solid #dbeafe;

    border-top-color: #2563eb;

    border-radius: 50%;

    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ============================================================
   TOAST
   ============================================================ */

.qr-toast {
    position: fixed;

    right: 22px;
    bottom: 22px;

    z-index: 9999;

    max-width: 360px;

    padding: 14px 17px;

    border-radius: 14px;

    background: #0f172a;

    color: #ffffff;

    box-shadow:
        0 15px 40px rgba(15, 23, 42, 0.25);

    font-size: 13px;

    font-weight: 700;

    opacity: 0;

    transform: translateY(15px);

    pointer-events: none;

    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.qr-toast.show {
    opacity: 1;

    transform: translateY(0);
}

.qr-toast.error {
    background: #dc2626;
}

.qr-toast.success {
    background: #16a34a;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 1000px) {

    .qr-grid {
        grid-template-columns: 1fr;
    }

    .qr-display {
        min-height: 450px;
    }
}

@media (max-width: 640px) {

    .qr-page {
        padding: 15px;
    }

    .qr-header {
        flex-direction: column;
    }

    .qr-card-body {
        padding: 16px;
    }

    .qr-display {
        padding: 20px;

        min-height: 420px;
    }

    .qr-box {
        width: 280px;
    }

    .qr-actions {
        grid-template-columns: 1fr;
    }
}
</style>


<div class="qr-page">

    <div class="qr-container">

        <!-- =====================================================
             PAGE HEADER
             ====================================================== -->

        <div class="qr-header">

            <div class="qr-header-left">

                <div class="qr-eyebrow">
                    <?= e($eyebrow) ?>
                </div>

                <h1 class="qr-title">
                    Dynamic QR Code
                </h1>

                <p class="qr-description">
                    Generate a secure QR code for student attendance.
                    The QR code automatically expires every 30 seconds.
                </p>

            </div>

            <div
                class="qr-status stopped"
                id="qrStatus"
            >
                <span class="qr-status-dot"></span>

                <span id="qrStatusText">
                    QR Inactive
                </span>
            </div>

        </div>


        <!-- =====================================================
             MAIN GRID
             ====================================================== -->

        <div class="qr-grid">

            <!-- =================================================
                 QR CARD
                 ================================================== -->

            <div class="qr-card">

                <div class="qr-card-header">

                    <div>

                        <h2 class="qr-card-title">
                            Attendance QR
                        </h2>

                        <p class="qr-card-subtitle">
                            Students can scan this code to check in
                        </p>

                    </div>

                </div>

                <div class="qr-card-body">

                    <div class="qr-display">

                        <!-- Loading -->
                        <div
                            class="qr-loading"
                            id="qrLoading"
                        >
                            <div class="spinner"></div>
                        </div>


                        <!-- QR -->
                        <div
                            class="qr-box"
                            id="qrBox"
                        >

                            <div
                                class="qr-placeholder"
                                id="qrPlaceholder"
                            >

                                <div class="qr-placeholder-icon">
                                    ▦
                                </div>

                                <strong>
                                    QR code not active
                                </strong>

                                <span>
                                    Click Generate QR to start
                                </span>

                            </div>

                            <img
                                id="qrImage"
                                src=""
                                alt="Dynamic Attendance QR Code"
                                style="display:none;"
                            >

                        </div>


                        <!-- Timer -->
                        <div class="qr-countdown">

                            <div class="qr-countdown-label">
                                Expires in
                            </div>

                            <div
                                class="qr-timer"
                                id="qrTimer"
                            >
                                --:--
                            </div>

                        </div>


                        <!-- Buttons -->
                        <div class="qr-actions">

                            <button
                                type="button"
                                class="qr-btn qr-btn-primary"
                                id="generateBtn"
                            >
                                <span>↻</span>
                                <span>Generate QR</span>
                            </button>

                            <button
                                type="button"
                                class="qr-btn qr-btn-danger"
                                id="stopBtn"
                                disabled
                            >
                                <span>■</span>
                                <span>Stop QR</span>
                            </button>

                            <button
                                type="button"
                                class="qr-btn qr-btn-secondary"
                                id="downloadBtn"
                                disabled
                            >
                                <span>↓</span>
                                <span>Download QR</span>
                            </button>

                        </div>


                        <!-- Information -->
                        <div class="qr-info">

                            <div class="qr-info-title">
                                <span>●</span>
                                <span>Automatic Security</span>
                            </div>

                            <div class="qr-info-text">
                                This QR code automatically changes every
                                30 seconds. Previous QR codes become invalid
                                once a new QR code is generated.
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ATTENDANCE CARD
                 ================================================== -->

            <div class="qr-card">

                <div class="qr-card-header">

                    <div>

                        <h2 class="qr-card-title">
                            Today's Attendance
                        </h2>

                        <p class="qr-card-subtitle">
                            Students who checked in today
                        </p>

                    </div>

                </div>

                <div class="qr-card-body">

                    <?php if (!empty($attendanceToday)): ?>

                        <div class="attendance-table-wrap">

                            <table class="attendance-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Student
                                        </th>

                                        <th>
                                            Program
                                        </th>

                                        <th>
                                            Time In
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($attendanceToday as $row): ?>

                                        <tr>

                                            <td>

                                                <div class="student-name">
                                                    <?= e(
                                                        trim(
                                                            ($row['first_name'] ?? '') .
                                                            ' ' .
                                                            ($row['last_name'] ?? '')
                                                        )
                                                    ) ?>
                                                </div>

                                                <div class="student-id">
                                                    <?= e(
                                                        $row['student_id_no'] ?? 'N/A'
                                                    ) ?>
                                                </div>

                                            </td>

                                            <td>
                                                <?= e(
                                                    $row['program'] ?? 'N/A'
                                                ) ?>
                                            </td>

                                            <td>

                                                <?php

                                                $timeIn = $row['time_in'] ?? '';

                                                if ($timeIn !== '') {

                                                    $timestamp = strtotime($timeIn);

                                                    if ($timestamp !== false) {
                                                        echo e(
                                                            date(
                                                                'h:i A',
                                                                $timestamp
                                                            )
                                                        );
                                                    } else {
                                                        echo e($timeIn);
                                                    }

                                                } else {

                                                    echo '—';

                                                }

                                                ?>

                                            </td>

                                            <td>

                                                <span class="qr-pill">
                                                    Present
                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                ✓
                            </div>

                            <strong>
                                No attendance yet
                            </strong>

                            <span>
                                Scanned students will appear here.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ============================================================
     TOAST
     ============================================================ -->

<div
    class="qr-toast"
    id="qrToast"
></div>


<script>
(function () {

    'use strict';


    /* ============================================================
       CONFIG
       ============================================================ */

    const csrfToken = <?= json_encode($csrf) ?>;

    const generateUrl = 'dynamic.php?api=generate';

    const revokeUrl = 'dynamic.php?api=revoke';

    const qrImage = document.getElementById('qrImage');

    const qrPlaceholder =
        document.getElementById('qrPlaceholder');

    const qrTimer =
        document.getElementById('qrTimer');

    const qrStatus =
        document.getElementById('qrStatus');

    const qrStatusText =
        document.getElementById('qrStatusText');

    const qrLoading =
        document.getElementById('qrLoading');

    const generateBtn =
        document.getElementById('generateBtn');

    const stopBtn =
        document.getElementById('stopBtn');

    const downloadBtn =
        document.getElementById('downloadBtn');

    const toast =
        document.getElementById('qrToast');


    /* ============================================================
       STATE
       ============================================================ */

    let countdownInterval = null;

    let secondsRemaining = 0;

    let currentScanUrl = '';

    let busy = false;

    let stopped = true;

    let generationNumber = 0;


    /* ============================================================
       TOAST
       ============================================================ */

    function showToast(message, type = '') {

        toast.textContent = message;

        toast.className = 'qr-toast';

        if (type) {
            toast.classList.add(type);
        }

        toast.classList.add('show');

        window.clearTimeout(
            showToast.timeout
        );

        showToast.timeout = window.setTimeout(
            function () {

                toast.classList.remove('show');

            },
            3000
        );
    }


    /* ============================================================
       LOADING
       ============================================================ */

    function setLoading(value) {

        if (value) {

            qrLoading.classList.add('show');

        } else {

            qrLoading.classList.remove('show');

        }
    }


    /* ============================================================
       STATUS
       ============================================================ */

    function setStatus(active) {

        if (active) {

            qrStatus.classList.remove('stopped');

            qrStatusText.textContent =
                'QR Active';

        } else {

            qrStatus.classList.add('stopped');

            qrStatusText.textContent =
                'QR Inactive';

        }

    }


    /* ============================================================
       TIMER
       ============================================================ */

    function updateTimer() {

        if (secondsRemaining <= 0) {

            qrTimer.textContent = '00:00';

            qrTimer.classList.remove('warning');
            qrTimer.classList.add('danger');

            return;
        }

        const seconds =
            Math.max(
                0,
                Math.floor(secondsRemaining)
            );

        const formatted =
            '00:' +
            String(seconds).padStart(2, '0');

        qrTimer.textContent = formatted;

        qrTimer.classList.remove(
            'warning',
            'danger'
        );

        if (seconds <= 10) {

            qrTimer.classList.add('danger');

        } else if (seconds <= 20) {

            qrTimer.classList.add('warning');

        }

    }


    /* ============================================================
       CLEAR COUNTDOWN
       ============================================================ */

    function clearCountdown() {

        if (countdownInterval !== null) {

            window.clearInterval(
                countdownInterval
            );

            countdownInterval = null;

        }

    }


    /* ============================================================
       START COUNTDOWN
       ============================================================ */

    function startCountdown(seconds) {

        clearCountdown();

        secondsRemaining =
            Number(seconds) || 30;

        updateTimer();

        countdownInterval =
            window.setInterval(
                function () {

                    secondsRemaining--;

                    updateTimer();

                    if (secondsRemaining <= 0) {

                        clearCountdown();

                        if (!stopped) {

                            generateQr(true);

                        }

                    }

                },
                1000
            );

    }


    /* ============================================================
       GENERATE QR
       ============================================================ */

    async function generateQr(autoRefresh = false) {

        if (busy) {
            return;
        }

        if (stopped && autoRefresh) {
            return;
        }

        busy = true;

        const requestId =
            ++generationNumber;

        setLoading(true);

        generateBtn.disabled = true;

        stopBtn.disabled = true;

        try {

            const response =
                await fetch(
                    generateUrl,
                    {
                        method: 'POST',

                        headers: {
                            'X-CSRF-Token': csrfToken,

                            'Content-Type':
                                'application/x-www-form-urlencoded',

                            'Cache-Control':
                                'no-cache'
                        },

                        body: ''
                    }
                );

            let data;

            try {

                data = await response.json();

            } catch (jsonError) {

                throw new Error(
                    'Invalid server response.'
                );

            }

            if (!response.ok || !data.ok) {

                throw new Error(
                    data.message ||
                    'Unable to generate QR code.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | IF USER STOPPED WHILE REQUEST WAS RUNNING
            |--------------------------------------------------------------------------
            */

            if (
                requestId !== generationNumber ||
                stopped
            ) {

                await revokeToken();

                return;

            }

            /*
            |--------------------------------------------------------------------------
            | BUILD QR IMAGE
            |--------------------------------------------------------------------------
            */

            currentScanUrl =
                String(data.scan_url || '');

            if (!currentScanUrl) {

                throw new Error(
                    'The server did not return a QR URL.'
                );

            }

            const qrApiUrl =
                'https://api.qrserver.com/v1/create-qr-code/' +
                '?size=500x500' +
                '&margin=10' +
                '&data=' +
                encodeURIComponent(
                    currentScanUrl
                );

            qrImage.src = qrApiUrl;

            qrImage.style.display = 'block';

            qrPlaceholder.style.display = 'none';

            /*
            |--------------------------------------------------------------------------
            | ACTIVE STATE
            |--------------------------------------------------------------------------
            */

            stopped = false;

            setStatus(true);

            stopBtn.disabled = false;

            downloadBtn.disabled = false;

            /*
            |--------------------------------------------------------------------------
            | START TIMER
            |--------------------------------------------------------------------------
            */

            startCountdown(
                Number(data.expires_in) || 30
            );

            if (!autoRefresh) {

                showToast(
                    'New QR code generated.',
                    'success'
                );

            }

        } catch (error) {

            console.error(
                'QR generation error:',
                error
            );

            clearCountdown();

            currentScanUrl = '';

            qrImage.removeAttribute('src');

            qrImage.style.display = 'none';

            qrPlaceholder.style.display = 'flex';

            qrTimer.textContent = '--:--';

            qrTimer.classList.remove(
                'warning',
                'danger'
            );

            setStatus(false);

            stopBtn.disabled = true;

            downloadBtn.disabled = true;

            showToast(
                error.message ||
                'Could not generate QR code.',
                'error'
            );

        } finally {

            busy = false;

            setLoading(false);

            generateBtn.disabled = false;

            if (!stopped) {

                stopBtn.disabled = false;

            }

        }

    }


    /* ============================================================
       REVOKE TOKEN
       ============================================================ */

    async function revokeToken() {

        try {

            const response =
                await fetch(
                    revokeUrl,
                    {
                        method: 'POST',

                        headers: {
                            'X-CSRF-Token': csrfToken,

                            'Content-Type':
                                'application/x-www-form-urlencoded',

                            'Cache-Control':
                                'no-cache'
                        },

                        body: ''
                    }
                );

            const data =
                await response.json();

            return (
                response.ok &&
                data.ok === true
            );

        } catch (error) {

            console.error(
                'QR revoke error:',
                error
            );

            return false;
        }

    }


    /* ============================================================
       STOP QR
       ============================================================ */

    async function stopQr() {

        generationNumber++;

        stopped = true;

        clearCountdown();

        secondsRemaining = 0;

        qrTimer.textContent = '00:00';

        qrTimer.classList.remove(
            'warning',
            'danger'
        );

        stopBtn.disabled = true;

        downloadBtn.disabled = true;

        setStatus(false);

        currentScanUrl = '';

        qrImage.removeAttribute('src');

        qrImage.style.display = 'none';

        qrPlaceholder.style.display = 'flex';

        setLoading(true);

        try {

            const success =
                await revokeToken();

            if (success) {

                showToast(
                    'QR session stopped.',
                    'success'
                );

            } else {

                showToast(
                    'QR stopped locally, but server revoke failed.',
                    'error'
                );

            }

        } finally {

            setLoading(false);

        }

    }


    /* ============================================================
       DOWNLOAD QR
       ============================================================ */

    async function downloadQr() {

        if (!currentScanUrl) {

            showToast(
                'Generate a QR code first.',
                'error'
            );

            return;
        }

        try {

            const imageUrl =
                'https://api.qrserver.com/v1/create-qr-code/' +
                '?size=1000x1000' +
                '&margin=20' +
                '&data=' +
                encodeURIComponent(
                    currentScanUrl
                );

            const response =
                await fetch(
                    imageUrl,
                    {
                        cache: 'no-store'
                    }
                );

            if (!response.ok) {

                throw new Error(
                    'Could not download QR image.'
                );

            }

            const blob =
                await response.blob();

            const blobUrl =
                URL.createObjectURL(blob);

            const link =
                document.createElement('a');

            link.href = blobUrl;

            link.download =
                'OJT360-Dynamic-QR.png';

            document.body.appendChild(link);

            link.click();

            link.remove();

            URL.revokeObjectURL(blobUrl);

            showToast(
                'QR code downloaded.',
                'success'
            );

        } catch (error) {

            console.error(
                'QR download error:',
                error
            );

            /*
            |--------------------------------------------------------------------------
            | FALLBACK
            |--------------------------------------------------------------------------
            */

            const fallbackUrl =
                'https://api.qrserver.com/v1/create-qr-code/' +
                '?size=1000x1000' +
                '&margin=20' +
                '&data=' +
                encodeURIComponent(
                    currentScanUrl
                );

            window.open(
                fallbackUrl,
                '_blank',
                'noopener'
            );

        }

    }


    /* ============================================================
       BUTTON EVENTS
       ============================================================ */

    generateBtn.addEventListener(
        'click',
        function () {

            stopped = false;

            generateQr(false);

        }
    );


    stopBtn.addEventListener(
        'click',
        function () {

            stopQr();

        }
    );


    downloadBtn.addEventListener(
        'click',
        function () {

            downloadQr();

        }
    );


    /* ============================================================
       PAGE VISIBILITY
       ============================================================ */

    document.addEventListener(
        'visibilitychange',
        function () {

            /*
            |--------------------------------------------------------------------------
            | We intentionally do not stop the QR when the tab
            | becomes hidden.
            |--------------------------------------------------------------------------
            */

            if (
                document.visibilityState === 'visible' &&
                !stopped &&
                secondsRemaining <= 0
            ) {

                generateQr(true);

            }

        }
    );


    /* ============================================================
       INITIAL STATE
       ============================================================ */

    stopped = true;

    setStatus(false);

    clearCountdown();

    qrTimer.textContent = '--:--';

    generateBtn.disabled = false;

    stopBtn.disabled = true;

    downloadBtn.disabled = true;


})();
</script>


<?php

include __DIR__ . '/includes/layout_bottom.php';

?>