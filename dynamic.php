<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();
$companyId = (int) $company['id'];

$pageKey = 'qr';
$pageTitle = 'Dynamic QR Code';
$eyebrow = 'SMART ATTENDANCE';

date_default_timezone_set('Asia/Manila');

function qrSendJson(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo json_encode($data);
    exit;
}

if (isset($_GET['api'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        qrSendJson(['ok' => false, 'message' => 'POST required.'], 405);
    }

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

        if ($action === 'revoke') {
            $stmt = $pdo->prepare(
                'UPDATE qr_attendance_tokens
                 SET revoked_at = NOW()
                 WHERE company_id = :cid
                   AND revoked_at IS NULL'
            );
            $stmt->execute([':cid' => $companyId]);

            qrSendJson([
                'ok' => true,
                'message' => 'QR session stopped.'
            ]);
        }

        if ($action !== 'generate') {
            qrSendJson(['ok' => false, 'message' => 'Unknown action.'], 404);
        }

        $pdo->beginTransaction();

        $revoke = $pdo->prepare(
            'UPDATE qr_attendance_tokens
             SET revoked_at = NOW()
             WHERE company_id = :cid
               AND revoked_at IS NULL'
        );
        $revoke->execute([':cid' => $companyId]);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $insert = $pdo->prepare(
            'INSERT INTO qr_attendance_tokens
                (company_id, token_hash, created_at, expires_at)
             VALUES
                (:cid, :hash, NOW(), DATE_ADD(NOW(), INTERVAL 30 SECOND))'
        );
        $insert->execute([
            ':cid' => $companyId,
            ':hash' => $hash
        ]);

        $pdo->commit();

        $https = (
            !empty($_SERVER['HTTPS']) &&
            strtolower((string) $_SERVER['HTTPS']) !== 'off'
        );

        if (
            !$https &&
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https'
        ) {
            $https = true;
        }

        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (!preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]{1,5})?$/', $host)) {
            qrSendJson([
                'ok' => false,
                'message' => 'Invalid server host configuration.'
            ], 500);
        }

        $basePath = str_replace(
            '\\',
            '/',
            dirname($_SERVER['SCRIPT_NAME'] ?? '/')
        );
        $basePath = rtrim($basePath, '/');

        $scanUrl = $scheme . '://' . $host . $basePath
            . '/qr_checkin.php?token=' . rawurlencode($token);

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

        error_log('OJT360 QR generation error: ' . $ex->getMessage());

        qrSendJson([
            'ok' => false,
            'message' => 'Could not generate a QR code. Check the server error log.'
        ], 500);
    }
}

$todayStmt = $pdo->prepare(
    'SELECT a.id, a.time_in, a.status,
            s.first_name, s.last_name, s.student_id_no, s.program
     FROM attendance a
     INNER JOIN students s ON s.id = a.student_id
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
$csrf = csrf_token();

include __DIR__ . '/includes/layout_top.php';
?>

<style>
.qr-shell{color:#0f172a}
.qr-hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}
.qr-hero h1{font-size:29px;letter-spacing:-.04em;font-weight:800;margin:0 0 7px;color:#0f172a}
.qr-hero p{font-size:13px;color:#64748b;margin:0;line-height:1.6}
.qr-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(290px,.9fr);gap:20px;align-items:start}
.qr-card{background:white;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 4px 14px #0f172a08;transition:background .2s, border-color .2s}
.qr-head{padding:20px 22px;border-bottom:1px solid #edf2f7}
.qr-head h2{font-size:15px;font-weight:800;margin:0 0 6px;color:#0f172a}
.qr-head p{font-size:12px;color:#64748b;margin:0;line-height:1.6}
.qr-content{padding:22px}
.qr-stage{display:flex;align-items:center;flex-direction:column;padding:22px 16px;background:linear-gradient(145deg,#f8fafc,#eef2ff);border:1px solid #e0e7ff;border-radius:16px}
.qr-live{display:flex;align-items:center;gap:8px;background:#dcfce7;color:#166534;border-radius:99px;padding:7px 11px;font-size:10px;font-weight:800;margin-bottom:16px}
.qr-dot{width:7px;height:7px;background:#16a34a;border-radius:50%;animation:qrblink 1.5s infinite}
@keyframes qrblink{50%{opacity:.35}}
.qr-imgbox{width:250px;height:250px;max-width:100%;padding:12px;background:white;border:1px solid #e2e8f0;border-radius:16px;display:grid;place-items:center;box-shadow:0 8px 25px #0f172a0d}
.qr-imgbox img{width:100%;height:100%;object-fit:contain}
.qr-count-label{margin-top:17px;font-size:10px;color:#64748b;font-weight:700}
.qr-count{font-size:30px;font-weight:800;color:#312e81;font-variant-numeric:tabular-nums}
.qr-track{height:5px;width:100%;max-width:250px;background:#dbeafe;border-radius:99px;overflow:hidden}
.qr-track span{display:block;height:100%;width:100%;background:#4f46e5;transition:width 1s linear}
.qr-actions{display:flex;gap:9px;width:100%;margin-top:17px}
.qr-btn{border:1px solid #dbe3ee;border-radius:10px;padding:11px 14px;min-height:42px;background:white;color:#334155;font-size:12px;font-weight:800;cursor:pointer}
.qr-btn.primary{background:#172554;border-color:#172554;color:white}
.qr-btn.danger{color:#b91c1c;border-color:#fecaca}
.qr-btn:disabled{opacity:.5;cursor:not-allowed}
.qr-status{font-size:11px;line-height:1.6;color:#64748b;margin-top:14px;min-height:18px;text-align:center}
.qr-note{font-size:11px;color:#64748b;line-height:1.7;margin:16px 0 0}
.qr-row{display:flex;align-items:center;gap:11px;padding:14px 18px;border-bottom:1px solid #f1f5f9}
.qr-row:last-child{border-bottom:0}
.qr-avatar{width:38px;height:38px;flex-shrink:0;border-radius:12px;background:#eef2ff;color:#4338ca;display:grid;place-items:center;font-size:11px;font-weight:800}
.qr-name{font-size:12px;font-weight:800;color:#0f172a}
.qr-sub{font-size:10px;color:#94a3b8;margin-top:4px}
.qr-pill{margin-left:auto;white-space:nowrap;border-radius:8px;padding:6px 8px;font-size:10px;font-weight:800;background:#d1fae5;color:#047857}
.qr-empty{padding:28px 18px;text-align:center;color:#94a3b8;font-size:12px;line-height:1.7}

/* DARK MODE ADAPTATION */
html[data-theme="dark"] .qr-hero h1, .dark .qr-hero h1,
html[data-theme="dark"] .qr-head h2, .dark .qr-head h2,
html[data-theme="dark"] .qr-name, .dark .qr-name { color: #f8fafc !important; }
html[data-theme="dark"] .qr-hero p, .dark .qr-hero p,
html[data-theme="dark"] .qr-head p, .dark .qr-head p,
html[data-theme="dark"] .qr-count-label, .dark .qr-count-label,
html[data-theme="dark"] .qr-status, .dark .qr-status,
html[data-theme="dark"] .qr-note, .dark .qr-note,
html[data-theme="dark"] .qr-sub, .dark .qr-sub { color: #94a3b8 !important; }
html[data-theme="dark"] .qr-card, .dark .qr-card {
    background: #111c38 !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}
html[data-theme="dark"] .qr-head, .dark .qr-head { border-bottom-color: #1e293b !important; }
html[data-theme="dark"] .qr-stage, .dark .qr-stage {
    background: linear-gradient(145deg, #16223f, #111c38) !important;
    border-color: #233354 !important;
}
html[data-theme="dark"] .qr-imgbox, .dark .qr-imgbox {
    background: #16223f !important;
    border-color: #233354 !important;
}
html[data-theme="dark"] .qr-count, .dark .qr-count { color: #38bdf8 !important; }
html[data-theme="dark"] .qr-track, .dark .qr-track { background: #1e293b !important; }
html[data-theme="dark"] .qr-btn, .dark .qr-btn {
    background: #16223f !important;
    border-color: #233354 !important;
    color: #f8fafc !important;
}
html[data-theme="dark"] .qr-btn.primary, .dark .qr-btn.primary {
    background: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #fff !important;
}
html[data-theme="dark"] .qr-btn:hover, .dark .qr-btn:hover { background: #1e293b !important; }
html[data-theme="dark"] .qr-row, .dark .qr-row { border-bottom-color: #1e293b !important; }
html[data-theme="dark"] .qr-row:hover td, .dark .qr-row:hover td { background: #16223f !important; }
html[data-theme="dark"] .qr-avatar, .dark .qr-avatar {
    background: #1e293b !important;
    color: #38bdf8 !important;
}

@media(max-width:850px){.qr-grid{grid-template-columns:1fr}}
@media(max-width:540px){.qr-hero h1{font-size:24px}.qr-content{padding:14px}.qr-head{padding:17px}.qr-actions{flex-direction:column}.qr-btn{width:100%}.qr-imgbox{width:220px;height:220px}}
</style>

<div class="qr-shell">
    <div class="qr-hero">
        <div>
            <div style="font-size:10px;font-weight:800;letter-spacing:.14em;color:#0284c7;margin-bottom:8px">
                <?= e($eyebrow) ?>
            </div>
            <h1>Dynamic QR Attendance</h1>
            <p>Rotate secure check-in codes and monitor your interns' attendance.</p>
        </div>
    </div>

    <div class="qr-grid">
        <section class="qr-card">
            <div class="qr-head">
                <h2>Live Attendance QR</h2>
                <p>Each QR opens your OJT360 check-in page and expires after 30 seconds.</p>
            </div>

            <div class="qr-content">
                <div class="qr-stage">
                    <div class="qr-live"><span class="qr-dot"></span> DYNAMIC QR SESSION</div>

                    <div class="qr-imgbox">
                        <img id="qrImage"
                             alt="Dynamic OJT360 attendance QR code"
                             src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&amp;data=OJT360%20Loading"
                             referrerpolicy="no-referrer">
                    </div>

                    <div class="qr-count-label">QR EXPIRES IN</div>
                    <div class="qr-count" id="qrCountdown">00:30</div>
                    <div class="qr-track"><span id="qrProgress"></span></div>

                    <div class="qr-actions">
                        <button class="qr-btn primary" type="button" id="qrRefresh">↻ Generate New QR</button>
                        <button class="qr-btn" type="button" id="qrDownload" disabled>↓ Download QR</button>
                        <button class="qr-btn danger" type="button" id="qrStop">Stop QR</button>
                    </div>

                    <div class="qr-status" id="qrStatus" role="status" aria-live="polite">
                        Preparing a secure QR code…
                    </div>
                </div>

                <p class="qr-note">
                    The QR image is generated by the goQR.me QR Server API. Attendance is validated by your own PHP endpoint.
                    A student must be signed in to the Student Portal to record attendance.
                </p>
            </div>
        </section>

        <section class="qr-card">
            <div class="qr-head">
                <h2>Today's Attendance</h2>
                <p>Live records currently saved for your company.</p>
            </div>

            <div id="qrAttendanceList">
                <?php if (!$attendanceToday): ?>
                    <div class="qr-empty">No attendance recorded for today yet.</div>
                <?php else: ?>
                    <?php foreach ($attendanceToday as $row): ?>
                        <?php
                        $name = trim($row['first_name'] . ' ' . $row['last_name']);
                        $parts = preg_split('/\s+/', $name);
                        $initials = strtoupper(
                            substr($parts[0] ?? 'S', 0, 1) .
                            substr($parts[count($parts) - 1] ?? '', 0, 1)
                        );
                        ?>
                        <div class="qr-row">
                            <div class="qr-avatar"><?= e($initials) ?></div>

                            <div style="min-width:0;flex:1">
                                <div class="qr-name"><?= e($name) ?></div>
                                <div class="qr-sub">
                                    <?= e($row['student_id_no'] ?: 'No student ID') ?>
                                    ·
                                    <?= e($row['program'] ?: 'OJT Intern') ?>
                                </div>
                                <div class="qr-sub">
                                    Time in: <?= e($row['time_in'] ?: 'Not recorded') ?>
                                </div>
                            </div>

                            <span class="qr-pill"><?= e($row['status'] ?: 'Present') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="qr-head" style="border-top:1px solid #edf2f7;border-bottom:0">
                <h2>Attendance Summary</h2>
                <p><?= count($attendanceToday) ?> check-in record(s) saved today.</p>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    'use strict';

    const csrf = <?= json_encode($csrf) ?>;
    const img = document.getElementById('qrImage');
    const countdown = document.getElementById('qrCountdown');
    const progress = document.getElementById('qrProgress');
    const status = document.getElementById('qrStatus');
    const refresh = document.getElementById('qrRefresh');
    const stop = document.getElementById('qrStop');
    const download = document.getElementById('qrDownload');

    let seconds = 30;
    let timer = null;
    let scanUrl = '';
    let imageUrl = '';
    let busy = false;
    let stopped = false;
    let stopRequested = false;
    let requestId = 0;

    function say(message, error = false) {
        status.textContent = message;
        status.style.color = error ? '#b91c1c' : '#64748b';
    }

    function paintTimer() {
        const remaining = Math.max(0, seconds);
        countdown.textContent = '00:' + String(remaining).padStart(2, '0');
        progress.style.width = (remaining / 30 * 100) + '%';
    }

    function clearTimer() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    async function postAction(action) {
        const response = await fetch('dynamic.php?api=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'X-CSRF-Token': csrf,
                'Accept': 'application/json'
            }
        });

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw new Error('The server returned an invalid response. Refresh the page and try again.');
        }

        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'The request failed.');
        }

        return data;
    }

    async function revokeToken() {
        try {
            await postAction('revoke');
            return true;
        } catch (error) {
            console.error('QR revocation failed:', error);
            return false;
        }
    }

    function clearDisplayedQr() {
        scanUrl = '';
        imageUrl = '';
        img.removeAttribute('src');
        download.disabled = true;
    }

    async function generate() {
        if (busy || stopped) return;

        busy = true;
        stopRequested = false;

        const thisRequest = ++requestId;

        refresh.disabled = true;
        download.disabled = true;
        say('Generating a new secure token…');

        try {
            const data = await postAction('generate');

            if (stopped || thisRequest !== requestId || stopRequested) {
                await revokeToken();
                clearDisplayedQr();
                return;
            }

            scanUrl = data.scan_url;

            imageUrl =
                'https://api.qrserver.com/v1/create-qr-code/' +
                '?size=500x500&format=png&ecc=H&margin=12&data=' +
                encodeURIComponent(scanUrl);

            const currentImageUrl = imageUrl;

            img.onload = function () {
                if (
                    !stopped &&
                    thisRequest === requestId &&
                    imageUrl === currentImageUrl
                ) {
                    download.disabled = false;
                }
            };

            img.onerror = function () {
                if (!stopped && thisRequest === requestId) {
                    download.disabled = true;
                    say('The QR image service is unavailable. Generate a new QR or try again.', true);
                }
            };

            seconds = Math.max(1, Number(data.expires_in) || 30);
            paintTimer();

            img.src = imageUrl;
            say('QR is active. Students can scan it while it is valid.');

            clearTimer();

            timer = setInterval(function () {
                if (stopped) {
                    clearTimer();
                    return;
                }

                seconds = Math.max(0, seconds - 1);
                paintTimer();

                if (seconds <= 0) {
                    clearTimer();
                    say('QR expired. Generating a fresh code…');
                    generate();
                }
            }, 1000);

        } catch (error) {
            if (!stopped && thisRequest === requestId) {
                clearTimer();
                seconds = 0;
                paintTimer();
                say(error.message || 'Network error while generating QR.', true);
            }
        } finally {
            busy = false;
            refresh.disabled = false;

            if (stopRequested || stopped) {
                await revokeToken();
                clearTimer();
                clearDisplayedQr();
                seconds = 0;
                paintTimer();
                say('QR session stopped. Generate a new QR to resume.');
                stopRequested = false;
            }
        }
    }

    refresh.addEventListener('click', function () {
        stopped = false;
        stopRequested = false;
        clearTimer();
        generate();
    });

    stop.addEventListener('click', async function () {
        stopped = true;
        stopRequested = true;
        requestId++;

        clearTimer();
        seconds = 0;
        paintTimer();

        refresh.disabled = true;
        download.disabled = true;
        clearDisplayedQr();

        say('Stopping QR session…');

        if (busy) {
            return;
        }

        const revoked = await revokeToken();

        clearDisplayedQr();
        refresh.disabled = false;

        if (revoked) {
            say('QR session stopped. Generate a new QR to resume.');
        } else {
            say('QR display stopped, but server revocation could not be confirmed. Refresh and try again.', true);
        }

        stopRequested = false;
    });

    download.addEventListener('click', async function () {
        if (!imageUrl || stopped) return;

        const urlToDownload = imageUrl;
        const originalLabel = download.textContent;

        download.disabled = true;
        download.textContent = 'Preparing download…';

        try {
            const response = await fetch(urlToDownload, { cache: 'no-store' });

            if (!response.ok) {
                throw new Error('Could not download the QR image.');
            }

            const blob = await response.blob();
            const blobUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = blobUrl;
            link.download = 'ojt360-attendance-qr.png';
            document.body.appendChild(link);
            link.click();
            link.remove();

            setTimeout(function () {
                URL.revokeObjectURL(blobUrl);
            }, 1000);

        } catch (error) {
            window.open(urlToDownload, '_blank', 'noopener');
            say('If the download did not start, save the QR image from the opened tab.', true);
        } finally {
            download.textContent = originalLabel;

            if (!stopped && imageUrl) {
                download.disabled = false;
            }
        }
    });

    paintTimer();
    generate();
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>