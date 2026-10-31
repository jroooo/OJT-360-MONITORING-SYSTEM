<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Cache-Control: no-store');
$uid = (int)$_SESSION['student_pk'];

/* ---------- GET endpoints ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'state';

    if ($action === 'state') {
        json_out(['ok' => true, 'state' => tracker_state($uid)]);
    }

    if ($action === 'export') {
        $stmt = db()->prepare(
            "SELECT a.time_in,a.time_out,a.break_seconds,a.duration_minutes,a.company_name,
                    COALESCE(
                        (SELECT MIN(ev.event_time)
                         FROM attendance_events ev
                         WHERE ev.attendance_id = a.id
                           AND ev.student_pk = a.student_pk
                           AND ev.event_type = 'time_in'),
                        a.time_in
                    ) AS actual_time_in
             FROM attendance a
             WHERE a.student_pk=?
             ORDER BY a.time_in DESC"
        );
        $stmt->execute([$uid]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ojt360-attendance.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Company', 'Time In', 'Time Out', 'Break (min)', 'Rendered (min)', 'Rendered (hours)']);
        while ($r = $stmt->fetch()) {
            $mins = $r['duration_minutes'] !== null ? (int)$r['duration_minutes'] : '';
            fputcsv($out, [date('Y-m-d', strtotime($r['time_in'])), $r['company_name'], date('h:i A', strtotime($r['actual_time_in'])), $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '', (int)round(((int)$r['break_seconds']) / 60), $mins, $mins === '' ? '' : number_format($mins / 60, 2)]);
        }
        fclose($out);
        exit;
    }

    if ($action === 'month') {
        $month = (string)($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $s = db()->prepare(
            "SELECT a.*,
                    COALESCE(
                        (SELECT MIN(ev.event_time)
                         FROM attendance_events ev
                         WHERE ev.attendance_id = a.id
                           AND ev.student_pk = a.student_pk
                           AND ev.event_type = 'time_in'),
                        a.time_in
                    ) AS actual_time_in
             FROM attendance a
             WHERE a.student_pk = ? AND DATE_FORMAT(a.time_in,'%Y-%m') = ?
             ORDER BY a.time_in ASC"
        );
        $s->execute([$uid, $month]);
        $sessions = []; $days = []; $now = time(); $today = date('Y-m-d');
        foreach ($s->fetchAll() as $r) {
            $date = date('Y-m-d', strtotime($r['time_in']));
            $sec = session_seconds($r, $now);
            $sessions[] = [
                'id' => (int)$r['id'], 'date' => $date, 'company' => $r['company_name'] ?? '',
                'time_in' => date('h:i A', strtotime($r['actual_time_in'])),
                'time_out' => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'time_out_date' => $r['time_out'] ? date('Y-m-d', strtotime($r['time_out'])) : null,
                'break_min' => (int)round($sec['break'] / 60),
                'net_min' => (int)round($sec['net'] / 60),
                'open' => empty($r['time_out']),
                'on_break' => !empty($r['break_started_at']),
            ];
            if (!isset($days[$date])) $days[$date] = ['sessions' => 0, 'minutes' => 0, 'open' => false];
            $days[$date]['sessions']++;
            $days[$date]['minutes'] += (int)round($sec['net'] / 60);
            if (empty($r['time_out'])) $days[$date]['open'] = true;
        }
        $statusData = student_attendance_statuses($uid, $month . '-01', date('Y-m-t', strtotime($month . '-01')));
        foreach ($statusData['days'] as $date => $dayStatus) {
            if (!isset($days[$date])) $days[$date] = ['sessions' => 0, 'minutes' => 0, 'open' => false];
            $days[$date]['status'] = $dayStatus['status'];
            $days[$date]['hours'] = $dayStatus['hours'];
            $days[$date]['absence_status'] = $dayStatus['absence_status'];
            $days[$date]['reason'] = $dayStatus['reason'];
        }
        foreach ($days as $date => &$d) {
            if (!isset($d['status'])) {
                if ($d['open'] && $date === $today) $d['status'] = 'progress';
                elseif ($d['sessions'] > 0) $d['status'] = 'attendance';
                else $d['status'] = '';
            }
        }
        unset($d);
        $ev = db()->prepare("SELECT event_type, event_time FROM attendance_events WHERE student_pk=? AND DATE_FORMAT(event_time,'%Y-%m')=? ORDER BY event_time DESC, id DESC LIMIT 400");
        $ev->execute([$uid, $month]);
        $events = [];
        foreach ($ev->fetchAll() as $r) $events[] = ['type' => $r['event_type'], 'date' => date('Y-m-d', strtotime($r['event_time'])), 'time' => date('h:i A', strtotime($r['event_time']))];
        json_out(['ok' => true, 'month' => $month, 'days' => (object)$days, 'sessions' => array_reverse($sessions), 'events' => $events, 'today' => $today, 'status_warnings' => $statusData['warnings']]);
    }

    if ($action === 'day') {
        $date = (string)($_GET['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_out(['ok' => false, 'message' => 'Invalid date.'], 422);
        $a = db()->prepare(
            "SELECT a.*,
                    COALESCE(
                        (SELECT MIN(ev.event_time)
                         FROM attendance_events ev
                         WHERE ev.attendance_id = a.id
                           AND ev.student_pk = a.student_pk
                           AND ev.event_type = 'time_in'),
                        a.time_in
                    ) AS actual_time_in
             FROM attendance a
             WHERE a.student_pk = ? AND DATE(a.time_in) = ?
             ORDER BY a.time_in"
        );
        $a->execute([$uid, $date]);
        $sessions = []; $now = time();
        foreach ($a->fetchAll() as $r) {
            $sec = session_seconds($r, $now);
            $ev = db()->prepare('SELECT event_type, event_time FROM attendance_events WHERE attendance_id=? ORDER BY event_time, id');
            $ev->execute([$r['id']]);
            $events = [];
            foreach ($ev->fetchAll() as $e) $events[] = ['type' => $e['event_type'], 'time' => date('h:i A', strtotime($e['event_time']))];
            $sessions[] = [
                'id' => (int)$r['id'], 'company' => $r['company_name'] ?? '',
                'time_in' => date('h:i A', strtotime($r['actual_time_in'])),
                'time_in_date' => date('Y-m-d', strtotime($r['actual_time_in'])),
                'time_out' => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'time_out_date' => $r['time_out'] ? date('Y-m-d', strtotime($r['time_out'])) : null,
                'break_min' => (int)round($sec['break'] / 60), 'net_min' => (int)round($sec['net'] / 60),
                'open' => empty($r['time_out']), 'events' => $events,
            ];
        }
        $statusData = student_attendance_statuses($uid, $date, $date);
        json_out([
            'ok' => true,
            'date' => $date,
            'label' => date('D, F j, Y', strtotime($date)),
            'sessions' => $sessions,
            'day_status' => $statusData['days'][$date] ?? null,
            'status_warnings' => $statusData['warnings'],
        ]);
    }

    json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
}

/* ---------- POST: scan ---------- */
verify_csrf();
$action = (string)($_POST['action'] ?? (isset($_POST['qr_value']) ? 'scan' : ''));
$session = active_session($uid);
$now = time();
$nowStr = date('Y-m-d H:i:s', $now);

if ($action !== 'scan') json_out(['ok' => false, 'message' => 'Unknown action.'], 400);

$qr = trim((string)($_POST['qr_value'] ?? ''));
if ($qr === '') json_out(['ok' => false, 'message' => 'QR value is required.']);

$companyToken = '';
if (preg_match('/^[a-f0-9]{64}$/i', $qr)) {
    $companyToken = strtolower($qr);
} else {
    $parts = parse_url($qr);
    if (is_array($parts) && basename((string)($parts['path'] ?? '')) === 'qr_checkin.php') {
        parse_str((string)($parts['query'] ?? ''), $query);
        $candidate = (string)($query['token'] ?? '');
        if (preg_match('/^[a-f0-9]{64}$/i', $candidate)) $companyToken = strtolower($candidate);
    }
}

$qrRow = null;
$isCompanyGeneratorQr = $companyToken !== '';
if ($companyToken !== '') {
    $stmt = db()->prepare(
        'SELECT c.company_name, NULL AS company_address
         FROM qr_attendance_tokens t
         INNER JOIN companies c ON c.id = t.company_id
         WHERE t.token_hash = ?
           AND t.expires_at > NOW()
           AND t.revoked_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([hash('sha256', $companyToken)]);
    $company = $stmt->fetch();
    if ($company) {
        // The company QR is a short-lived shared token; do not consume it after one student's scan.
        $qrRow = ['id' => null, 'company_name' => $company['company_name'], 'company_address' => null];
    }
} else {
    $stmt = db()->prepare('SELECT * FROM qr_codes WHERE qr_value=? AND is_active=1 LIMIT 1');
    $stmt->execute([$qr]);
    $qrRow = $stmt->fetch();
}

if (!$qrRow) json_out(['ok' => false, 'message' => 'Invalid, expired, or inactive company QR code. Scan the current code.']);

$settings = student_settings($uid);
$date = date('Y-m-d', $now);
$shiftStarts = attendance_shift_start_times($date, $settings);
$isAfternoon = $now >= $shiftStarts['afternoon'];
$slot = $isAfternoon ? 'afternoon' : 'morning';
$slotStart = $shiftStarts[$slot];
$slotBoundary = date('Y-m-d H:i:s', $shiftStarts['afternoon']);

if (!$session) {
    $existingShift = db()->prepare(
        "SELECT COUNT(*)
         FROM attendance_events
         WHERE student_pk = ?
           AND event_type = 'time_in'
           AND DATE(event_time) = ?
           AND event_time " . ($isAfternoon ? '>=' : '<') . ' ?'
    );
    $existingShift->execute([$uid, $date, $slotBoundary]);
    if ((int)$existingShift->fetchColumn() > 0) {
        $shiftName = $isAfternoon ? 'Afternoon' : 'Morning';
        json_out([
            'ok' => false,
            'message' => $shiftName . ' attendance is already recorded for today.',
            'state' => tracker_state($uid),
        ], 409);
    }
}

$internship = internship_for($uid);
if (!$internship) {
    db()->prepare("INSERT INTO internships(student_pk,company_name,company_address,required_hours,completed_hours,start_date,end_date,status) VALUES(?,?,?,480,0,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY),'active')")->execute([$uid, $qrRow['company_name'], $qrRow['company_address']]);
    $internship = internship_for($uid);
} elseif (empty($internship['company_name'])) {
    db()->prepare('UPDATE internships SET company_name=?, company_address=? WHERE id=?')->execute([$qrRow['company_name'], $qrRow['company_address'], $internship['id']]);
} elseif (!$isCompanyGeneratorQr && !empty($internship['company_name']) && strcasecmp(trim((string)$internship['company_name']), trim((string)$qrRow['company_name'])) !== 0) {
    json_out(['ok' => false, 'message' => 'This QR belongs to a different company than your current OJT assignment.']);
}

if ($session) {
    /* ---- TIME OUT ---- */
    if (strcasecmp(trim((string)$session['company_name']), trim((string)$qrRow['company_name'])) !== 0) json_out(['ok' => false, 'message' => 'Please use the same company QR used for Time In.']);
    $requiredSeconds = ATTENDANCE_SHIFT_SECONDS;
    $worked = session_seconds($session, $now);
    $breakSec = (int)$session['break_seconds'];
    $net = min($requiredSeconds, $worked['net']);
    $minutes = min((int)($requiredSeconds / 60), (int)floor($net / 60));
    $outStr = $nowStr;
    db()->prepare('UPDATE attendance SET time_out=?, break_started_at=NULL, break_seconds=?, duration_minutes=? WHERE id=? AND student_pk=?')->execute([$outStr, $breakSec, $minutes, $session['id'], $uid]);
    log_attendance_event((int)$session['id'], $uid, 'time_out', $outStr);
    adjust_completed_hours($uid, $minutes / 60);
    sync_auto_log($uid, (int)$session['id']);
    add_activity($uid, 'Timed out · ' . seconds_to_hm($net) . ' rendered');
    add_notification($uid, 'Time Out recorded', 'This shift is credited as ' . seconds_to_hm($net) . '.', '✓');
    $msg = 'Time Out recorded. This shift is credited as ' . seconds_to_hm($net) . '.';
    json_out(['ok' => true, 'action' => 'timeout', 'message' => $msg, 'state' => tracker_state($uid)]);
}

/* ---- TIME IN ---- */
$qrId = isset($qrRow['id']) ? (int)$qrRow['id'] : null;
db()->prepare('INSERT INTO attendance(student_pk,qr_id,company_name,time_in) VALUES(?,?,?,?)')->execute([$uid, $qrId, $qrRow['company_name'], date('Y-m-d H:i:s', $slotStart)]);
$attId = (int)db()->lastInsertId();
log_attendance_event($attId, $uid, 'time_in', $nowStr);
sync_auto_log($uid, $attId);
add_activity($uid, 'Timed in for the ' . $slot . ' shift using company QR');
add_notification($uid, 'Time In recorded', 'Your ' . $slot . ' attendance has started.', '◷');
json_out([
    'ok' => true,
    'action' => 'timein',
    'message' => ucfirst($slot) . ' shift started at ' . date('g:i A', $now)
        . '. Up to four hours will be credited for this shift.',
    'state' => tracker_state($uid),
]);
