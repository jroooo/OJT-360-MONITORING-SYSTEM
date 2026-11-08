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

        $sessionKey = strtolower(trim((string) ($_POST['session'] ?? 'am')));
        $stageKey = strtolower(trim((string) ($_POST['stage'] ?? '')));

        if (!in_array($sessionKey, ['am', 'pm'], true)) {
            $sessionKey = 'am';
        }

        if ($sessionKey === 'am') {
            if (!in_array($stageKey, ['am_in', 'am_out'], true)) {
                $stageKey = 'am_in';
            }
        } else {
            if (!in_array($stageKey, ['pm_in', 'pm_out'], true)) {
                $stageKey = 'pm_in';
            }
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
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/dynamic_qr.php';

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
            rawurlencode($token) .
            '&session=' .
            rawurlencode($sessionKey) .
            '&stage=' .
            rawurlencode($stageKey);

        /*
        |--------------------------------------------------------------------------
        | SEND RESPONSE
        |--------------------------------------------------------------------------
        */
        qrSendJson([
            'ok' => true,
            'scan_url' => $scanUrl,
            'token' => $token,
            'expires_in' => 30,
            'session' => $sessionKey,
            'stage' => $stageKey
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
*/

$attendanceToday = [];

try {

    $todayStmt = $pdo->prepare(
        'SELECT
            a.id,
            a.student_pk,
            a.time_in,
            a.time_out,
            s.first_name,
            s.last_name,
            s.student_id,
            s.course,
            CASE
                WHEN a.time_in IS NOT NULL AND TIME(a.time_in) < "12:00:00" THEN "AM IN"
                WHEN a.time_in IS NOT NULL AND TIME(a.time_in) >= "12:00:00" AND TIME(a.time_in) < "13:00:00" THEN "AM OUT"
                WHEN a.time_in IS NOT NULL AND TIME(a.time_in) >= "13:00:00" THEN "PM IN"
                ELSE "PM OUT"
            END AS session_label
         FROM attendance a
         INNER JOIN students s
            ON s.id = a.student_pk
         WHERE DATE(a.time_in) = CURDATE()
            OR DATE(a.time_out) = CURDATE()
         ORDER BY a.time_in DESC, a.id DESC'
    );

    $todayStmt->execute();

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
   DYNAMIC QR PAGE - DUAL THEME STYLING
   ============================================================ */

.qr-page {
    --qr-bg: #f8fafc;
    --qr-surface: #ffffff;
    --qr-surface-alt: #f8fafc;
    --qr-surface-soft: #f1f5f9;
    --qr-border: #e2e8f0;
    --qr-border-strong: #cbd5e1;
    --qr-text: #0f172a;
    --qr-text-soft: #334155;
    --qr-text-muted: #64748b;
    --qr-text-subtle: #94a3b8;
    --qr-brand: #2563eb;
    --qr-brand-strong: #1d4ed8;
    --qr-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);

    width: 100%;
    min-height: calc(100vh - 80px);
    padding: 24px;
    color: var(--qr-text);
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
    background: var(--qr-bg);
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

    color: var(--qr-brand);

    margin-bottom: 8px;
    line-height: 1.4;
}

.qr-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.qr-title {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text);
}

.qr-description {
    margin: 10px 0 0;
    max-width: 720px;
    color: var(--qr-text-muted);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 400;
    letter-spacing: 0;
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
    background: var(--qr-surface);
    border: 1px solid var(--qr-border);
    color: var(--qr-text-soft);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.01em;
    white-space: nowrap;
    line-height: 1.4;
}

.qr-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

.qr-status.stopped {
    background: var(--qr-surface);
    color: var(--qr-text-subtle);
}

.qr-status.stopped .qr-status-dot {
    background: var(--qr-text-subtle);
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
    background: var(--qr-surface);
    border: 1px solid var(--qr-border);
    border-radius: 20px;
    box-shadow: var(--qr-shadow);
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.qr-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
    border-color: var(--qr-border-strong);
}

.qr-card-header {
    padding: 20px 22px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid var(--qr-border);
    background: var(--qr-surface);
}

.qr-session-switch {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border-radius: 12px;
    background: var(--qr-surface-alt);
    border: 1px solid var(--qr-border);
}

.qr-session-option {
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: var(--qr-text-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 9px 12px;
    cursor: pointer;
    transition: all 0.18s ease;
    line-height: 1.4;
}

.qr-session-option.active {
    background: linear-gradient(135deg, var(--qr-brand) 0%, var(--qr-brand-strong) 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.qr-stage-row {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.qr-stage-option {
    border: 1px solid var(--qr-border);
    background: var(--qr-surface-alt);
    color: var(--qr-text-muted);
    border-radius: 999px;
    min-height: 32px;
    padding: 7px 11px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.18s ease;
    line-height: 1.4;
}

.qr-stage-option[hidden] {
    display: none;
}

.qr-stage-option.active {
    background: linear-gradient(135deg, var(--qr-brand-strong) 0%, var(--qr-brand) 100%);
    border-color: var(--qr-brand);
    color: #ffffff;
}

.qr-stage-option.dimmed {
    opacity: 0.52;
}

.qr-stage-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 8px 12px;
    border-radius: 999px;
    background: var(--qr-surface-alt);
    color: var(--qr-brand);
    border: 1px solid var(--qr-border);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1.4;
}

.qr-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    line-height: 1.4;
    letter-spacing: -0.02em;
    color: var(--qr-text);
}

.qr-card-subtitle {
    margin: 4px 0 0;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
    color: var(--qr-text-muted);
}

.qr-card-body {
    padding: 24px;
    background: var(--qr-surface);
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
    background: var(--qr-surface-alt);
    border-radius: 20px;
    border: 1px solid var(--qr-border);
}

.qr-box {
    width: 320px;
    max-width: 100%;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--qr-surface);
    border-radius: 22px;
    padding: 20px;
    border: 1px solid var(--qr-border);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
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
    color: var(--qr-text-subtle);
}

.qr-placeholder-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--qr-surface-alt);
    color: var(--qr-brand);
    font-size: 30px;
    margin-bottom: 14px;
    border: 1px solid var(--qr-border);
}

.qr-placeholder strong {
    color: var(--qr-text);
    font-size: 14px;
    font-weight: 700;
    line-height: 1.5;
    margin-bottom: 4px;
}

.qr-placeholder span {
    font-size: 12px;
    color: var(--qr-text-muted);
    line-height: 1.5;
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
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--qr-text-subtle);
    line-height: 1.5;
}

.qr-timer {
    font-size: 34px;
    line-height: 1;
    font-weight: 800;
    color: var(--qr-brand);
    letter-spacing: -0.04em;
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
    font-weight: 700;
    line-height: 1.4;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.qr-btn:hover {
    transform: translateY(-1px);
}

.qr-btn:active {
    transform: translateY(0);
}

.qr-btn:disabled {
    cursor: not-allowed;
    opacity: 0.4;
    transform: none;
}

.qr-btn-primary {
    background: #2563eb;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.2);
}

.qr-btn-danger {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.qr-btn-secondary {
    background: var(--qr-surface-alt);
    color: var(--qr-text-soft);
    border: 1px solid var(--qr-border);
}

/* ============================================================
   INFO
   ============================================================ */

.qr-info {
    margin-top: 18px;
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--qr-surface-alt);
    border: 1px solid var(--qr-border);
}

.qr-info-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--qr-brand);
    font-size: 12px;
    font-weight: 700;
    line-height: 1.5;
    margin-bottom: 5px;
    letter-spacing: 0.01em;
}

.qr-info-text {
    color: var(--qr-text-muted);
    font-size: 12px;
    line-height: 1.6;
    font-weight: 400;
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
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--qr-text-subtle);
    background: var(--qr-surface-alt);
    border-bottom: 1px solid var(--qr-border);
    line-height: 1.5;
}

.attendance-table td {
    padding: 15px 14px;
    font-size: 13px;
    color: var(--qr-text-soft);
    border-bottom: 1px solid var(--qr-border);
    line-height: 1.6;
    font-weight: 400;
}

.attendance-table tbody tr:hover {
    background: var(--qr-surface-alt);
}

.student-name {
    font-weight: 800;
    color: var(--qr-text);
    line-height: 1.4;
}

.student-id {
    margin-top: 3px;
    font-size: 11px;
    color: var(--qr-text-muted);
    line-height: 1.5;
}

.qr-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 999px;
    color: #ffffff;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.qr-pill.am-in { background: #16a34a; }
.qr-pill.am-out { background: #f59e0b; }
.qr-pill.pm-in { background: #2563eb; }
.qr-pill.pm-out { background: #7c3aed; }

.qr-live-row {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.qr-live-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--qr-surface-alt);
    color: var(--qr-brand);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.04em;
    flex-shrink: 0;
    border: 1px solid var(--qr-border);
}

.qr-live-info {
    min-width: 0;
}

.qr-live-name {
    color: var(--qr-text);
    font-weight: 800;
    line-height: 1.4;
}

.qr-live-meta {
    margin-top: 3px;
    font-size: 11px;
    color: var(--qr-text-muted);
    line-height: 1.5;
    font-weight: 500;
}

.qr-live-timestamp {
    font-size: 12px;
    color: var(--qr-text-soft);
    white-space: nowrap;
    line-height: 1.5;
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
    background: var(--qr-surface-alt);
    color: var(--qr-text-subtle);
    font-size: 25px;
    border: 1px solid var(--qr-border);
}

.empty-state strong {
    display: block;
    color: var(--qr-text);
    font-size: 14px;
    margin-bottom: 5px;
}

.empty-state span {
    font-size: 12px;
    color: var(--qr-text-muted);
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
    background: rgba(255, 255, 255, 0.8);
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
    border: 3px solid var(--qr-border);
    border-top-color: var(--qr-brand);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
    box-shadow: 0 15px 40px rgba(15, 23, 42, 0.25);
    font-size: 13px;
    font-weight: 700;
    opacity: 0;
    transform: translateY(15px);
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    border: 1px solid var(--qr-border);
}

.qr-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.qr-toast.error { background: #dc2626; }
.qr-toast.success { background: #16a34a; }

/* ============================================================
   DARK MODE OVERRIDES (DEEP NAVY)
   ============================================================ */
html[data-theme="dark"] .qr-page,
.dark .qr-page {
    --qr-bg: #0b0f19;
    --qr-surface: #111c38;
    --qr-surface-alt: #0d152c;
    --qr-surface-soft: #172342;
    --qr-border: #1e293b;
    --qr-border-strong: #2b3d58;
    --qr-text: #f8fafc;
    --qr-text-soft: #e2e8f0;
    --qr-text-muted: #cbd5e1;
    --qr-text-subtle: #94a3b8;
    --qr-brand: #60a5fa;
    --qr-brand-strong: #bfdbfe;
    --qr-shadow: 0 18px 42px rgba(2, 6, 23, 0.45);
}

html[data-theme="dark"] .qr-title,
.dark .qr-title,
html[data-theme="dark"] .qr-card-title,
.dark .qr-card-title,
html[data-theme="dark"] .qr-live-name,
.dark .qr-live-name,
html[data-theme="dark"] .empty-state strong,
.dark .empty-state strong {
    color: #ffffff !important;
}

html[data-theme="dark"] .qr-btn-danger,
.dark .qr-btn-danger {
    background: #2c1b1b !important;
    color: #fca5a5 !important;
    border-color: #7f1d1d !important;
}

html[data-theme="dark"] .qr-loading,
.dark .qr-loading {
    background: rgba(11, 15, 25, 0.75) !important;
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
    .qr-page { padding: 15px; }
    .qr-header { flex-direction: column; }
    .qr-card-body { padding: 16px; }
    .qr-display { padding: 20px; min-height: 420px; }
    .qr-box { width: 280px; }
    .qr-actions { grid-template-columns: 1fr; }
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
                    Here's your scannable QR code
                </h1>

                <p class="qr-description">
                    Generate a secure QR code for each attendance stage.
                    Every session rotates automatically and expires after 30 seconds.
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
                            Here's your scannable QR code
                        </h2>

                        <p class="qr-card-subtitle">
                            Launch the right attendance stage for AM or PM check-ins.
                        </p>

                    </div>

                    <div class="qr-session-switch" id="sessionSwitch" aria-label="Attendance session switcher">
                        <button type="button" class="qr-session-option active" data-session="am">Morning Session (AM)</button>
                        <button type="button" class="qr-session-option" data-session="pm">Afternoon Session (PM)</button>
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


                        <div class="qr-stage-row" id="stageRow">
                            <button type="button" class="qr-stage-option active" data-stage="am_in">AM In</button>
                            <button type="button" class="qr-stage-option" data-stage="am_out">AM Out</button>
                            <button type="button" class="qr-stage-option" data-stage="pm_in">PM In</button>
                            <button type="button" class="qr-stage-option" data-stage="pm_out">PM Out</button>
                        </div>

                        <div class="qr-stage-row">
                            <div class="qr-stage-badge" id="sessionStageBadge">AM IN</div>
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
                            Real-Time Attendance Feed
                        </h2>

                        <p class="qr-card-subtitle">
                            Live check-ins and check-outs from today’s session logs
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
                                            Course
                                        </th>

                                        <th>
                                            Timestamp
                                        </th>

                                        <th>
                                            Session
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($attendanceToday as $row): ?>

                                        <?php
                                            $fullName = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
                                            $initials = '';
                                            foreach (preg_split('/\s+/', $fullName) as $part) {
                                                if ($part !== '') {
                                                    $initials .= strtoupper(substr($part, 0, 1));
                                                }
                                            }
                                            $initials = substr($initials, 0, 2);
                                            $sessionLabel = strtoupper((string)($row['session_label'] ?? 'AM IN'));
                                            $sessionKey = strtolower(str_replace(' ', '-', $sessionLabel));
                                            $sessionClass = str_replace(['am-', 'pm-'], ['am-', 'pm-'], $sessionKey);
                                            if ($sessionLabel === 'AM IN') { $sessionClass = 'am-in'; }
                                            elseif ($sessionLabel === 'AM OUT') { $sessionClass = 'am-out'; }
                                            elseif ($sessionLabel === 'PM IN') { $sessionClass = 'pm-in'; }
                                            else { $sessionClass = 'pm-out'; }
                                            $timestamp = !empty($row['time_in']) ? $row['time_in'] : ($row['time_out'] ?? '');
                                            $formattedTimestamp = $timestamp !== '' ? date('M j, Y h:i:s A', strtotime($timestamp)) : '—';
                                        ?>

                                        <tr>

                                            <td>

                                                <div class="qr-live-row">
                                                    <div class="qr-live-avatar">
                                                        <?= e($initials !== '' ? $initials : 'ST') ?>
                                                    </div>

                                                    <div class="qr-live-info">
                                                        <div class="qr-live-name">
                                                            <?= e($fullName !== '' ? $fullName : 'Student') ?>
                                                        </div>

                                                        <div class="qr-live-meta">
                                                            <?= e((string)($row['student_id'] ?? 'N/A')) ?>
                                                        </div>
                                                    </div>
                                                </div>

                                            </td>

                                            <td>
                                                <?= e((string)($row['course'] ?? 'N/A')) ?>
                                            </td>

                                            <td class="qr-live-timestamp">
                                                <?= e($formattedTimestamp) ?>
                                            </td>

                                            <td>
                                                <span class="qr-pill <?= e($sessionClass) ?>">
                                                    <?= e($sessionLabel) ?>
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

    const generateUrl = 'dynamic_qr.php?api=generate';

    const revokeUrl = 'dynamic_qr.php?api=revoke';

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

    const sessionButtons =
        Array.from(
            document.querySelectorAll('.qr-session-option')
        );

    const sessionStageBadge =
        document.getElementById('sessionStageBadge');

    const stageButtons =
        Array.from(
            document.querySelectorAll('.qr-stage-option')
        );

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

    let currentSession = 'am';

    let currentStage = 'am_in';

    const sessionStages = {
        am: [
            { key: 'am_in', label: 'AM In' },
            { key: 'am_out', label: 'AM Out' }
        ],
        pm: [
            { key: 'pm_in', label: 'PM In' },
            { key: 'pm_out', label: 'PM Out' }
        ]
    };


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
                (currentSession === 'pm' ? 'PM QR Active' : 'AM QR Active');

        } else {

            qrStatus.classList.add('stopped');

            qrStatusText.textContent =
                'QR Inactive';

        }

    }


    /* ============================================================
       SESSION STATE
       ============================================================ */

    function resolveStageForSession(sessionKey) {

        const now = new Date();
        const hour = now.getHours();
        const minute = now.getMinutes();

        if (sessionKey === 'pm') {

            if (hour >= 13) {
                return 'pm_in';
            }

            return 'pm_out';
        }

        if (hour >= 12 || (hour === 11 && minute >= 59)) {
            return 'am_out';
        }

        return 'am_in';
    }

    function updateSessionControlState() {

        const activeSessionMeta =
            sessionStages[currentSession] || sessionStages.am;

        sessionButtons.forEach(function (button) {

            const isActive =
                button.dataset.session === currentSession;

            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

        });

        stageButtons.forEach(function (button) {

            const stageKey = button.dataset.stage;
            const isCurrentStage = stageKey === currentStage;
            const isSessionStage =
                (currentSession === 'am' && stageKey.indexOf('am_') === 0) ||
                (currentSession === 'pm' && stageKey.indexOf('pm_') === 0);

            button.hidden = !isSessionStage;
            button.classList.toggle('active', isCurrentStage);
            button.classList.toggle('dimmed', !isSessionStage && !isCurrentStage);
            button.setAttribute('aria-pressed', isCurrentStage ? 'true' : 'false');

        });

        const activeStageMeta =
            (activeSessionMeta.find(function (stage) {
                return stage.key === currentStage;
            }) || activeSessionMeta[0]);

        sessionStageBadge.textContent =
            activeStageMeta.label + ' • ' +
            (currentSession === 'pm' ? 'PM' : 'AM');

    }

    function setSession(sessionKey) {

        if (!sessionStages[sessionKey]) {
            return;
        }

        currentSession = sessionKey;
        currentStage = resolveStageForSession(sessionKey);

        updateSessionControlState();
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

        if (!sessionStages[currentSession]) {
            currentSession = 'am';
        }

        if (!sessionStages[currentSession].some(function (stage) {
            return stage.key === currentStage;
        })) {
            currentStage = sessionStages[currentSession][0].key;
        }

        updateSessionControlState();

        setLoading(true);

        generateBtn.disabled = true;

        stopBtn.disabled = true;

        try {

            const formBody = new URLSearchParams({
                session: currentSession,
                stage: currentStage
            });

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

                        body: formBody.toString()
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

    sessionButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            setSession(button.dataset.session || 'am');

            stopped = false;

            generateQr(false);

        });

    });

    stageButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const candidateStage = button.dataset.stage || currentStage;

            if (
                currentSession === 'am' &&
                (candidateStage === 'am_in' || candidateStage === 'am_out')
            ) {
                currentStage = candidateStage;
            }

            if (
                currentSession === 'pm' &&
                (candidateStage === 'pm_in' || candidateStage === 'pm_out')
            ) {
                currentStage = candidateStage;
            }

            updateSessionControlState();

            stopped = false;

            generateQr(false);

        });

    });

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

    setSession('am');

    setStatus(false);

    clearCountdown();

    qrTimer.textContent = '--:--';

    generateBtn.disabled = false;

    stopBtn.disabled = true;

    downloadBtn.disabled = true;

    updateSessionControlState();


})();
</script>


<?php

include __DIR__ . '/includes/layout_bottom.php';

?>