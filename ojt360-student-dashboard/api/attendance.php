<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Cache-Control: no-store');
$uid = (int)$_SESSION['student_pk'];
const MAX_SESSION_SECONDS = 12 * 3600;

/* ---------- GET endpoints ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'state';

    if ($action === 'state') {
        json_out(['ok' => true, 'state' => tracker_state($uid)]);
    }

    if ($action === 'export') {
        $stmt = db()->prepare('SELECT a.time_in,a.time_out,a.break_seconds,a.duration_minutes,a.company_name FROM attendance a WHERE a.student_pk=? ORDER BY a.time_in DESC');
        $stmt->execute([$uid]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ojt360-attendance.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Company', 'Time In', 'Time Out', 'Break (min)', 'Rendered (min)', 'Rendered (hours)']);
        while ($r = $stmt->fetch()) {
            $mins = $r['duration_minutes'] !== null ? (int)$r['duration_minutes'] : '';
            fputcsv($out, [date('Y-m-d', strtotime($r['time_in'])), $r['company_name'], date('h:i A', strtotime($r['time_in'])), $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '', (int)round(((int)$r['break_seconds']) / 60), $mins, $mins === '' ? '' : number_format($mins / 60, 2)]);
        }
        fclose($out);
        exit;
    }

    if ($action === 'month') {
        $month = (string)($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $s = db()->prepare("SELECT a.* FROM attendance a WHERE a.student_pk=? AND DATE_FORMAT(a.time_in,'%Y-%m')=? ORDER BY a.time_in ASC");
        $s->execute([$uid, $month]);
        $sessions = []; $days = []; $now = time(); $today = date('Y-m-d');
        foreach ($s->fetchAll() as $r) {
            $date = date('Y-m-d', strtotime($r['time_in']));
            $sec = session_seconds($r, $now);
            $sessions[] = [
                'id' => (int)$r['id'], 'date' => $date, 'company' => $r['company_name'] ?? '',
                'time_in' => date('h:i A', strtotime($r['time_in'])),
                'time_out' => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'break_min' => (int)round($sec['break'] / 60),
                'net_min' => (int)round($sec['net'] / 60),
                'open' => empty($r['time_out']),
                'on_break' => !empty($r['break_started_at']),
            ];
            if (!isset($days[$date])) $days[$date] = ['sessions' => 0, 'minutes' => 0, 'open' => false, 'logs' => 0, 'incomplete' => 0, 'hours' => 0.0];
            $days[$date]['sessions']++;
            $days[$date]['minutes'] += (int)round($sec['net'] / 60);
            if (empty($r['time_out'])) $days[$date]['open'] = true;
        }
        $l = db()->prepare("SELECT log_date, title, description, hours FROM daily_logs WHERE student_pk=? AND DATE_FORMAT(log_date,'%Y-%m')=?");
        $l->execute([$uid, $month]);
        foreach ($l->fetchAll() as $r) {
            $date = $r['log_date'];
            if (!isset($days[$date])) $days[$date] = ['sessions' => 0, 'minutes' => 0, 'open' => false, 'logs' => 0, 'incomplete' => 0, 'hours' => 0.0];
            $days[$date]['logs']++;
            $days[$date]['hours'] += (float)$r['hours'];
            if (trim((string)$r['title']) === '' || trim((string)$r['description']) === '') $days[$date]['incomplete']++;
        }
        foreach ($days as $date => &$d) {
            if ($d['open'] && $date === $today) $d['status'] = 'progress';
            elseif ($d['sessions'] > 0 && $d['incomplete'] === 0 && $d['logs'] > 0) $d['status'] = 'both';
            elseif ($d['sessions'] > 0 && $d['logs'] === 0) $d['status'] = 'att';
            elseif ($d['sessions'] > 0 || $d['incomplete'] > 0) $d['status'] = 'incomplete';
            elseif ($d['logs'] > 0) $d['status'] = 'log';
            else $d['status'] = '';
            $d['hours'] = round($d['hours'], 2);
        }
        unset($d);
        $ev = db()->prepare("SELECT event_type, event_time FROM attendance_events WHERE student_pk=? AND DATE_FORMAT(event_time,'%Y-%m')=? ORDER BY event_time DESC, id DESC LIMIT 400");
        $ev->execute([$uid, $month]);
        $events = [];
        foreach ($ev->fetchAll() as $r) $events[] = ['type' => $r['event_type'], 'date' => date('Y-m-d', strtotime($r['event_time'])), 'time' => date('h:i A', strtotime($r['event_time']))];
        json_out(['ok' => true, 'month' => $month, 'days' => (object)$days, 'sessions' => array_reverse($sessions), 'events' => $events, 'today' => $today]);
    }

    if ($action === 'day') {
        $date = (string)($_GET['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_out(['ok' => false, 'message' => 'Invalid date.'], 422);
        $a = db()->prepare('SELECT a.* FROM attendance a WHERE a.student_pk=? AND DATE(a.time_in)=? ORDER BY a.time_in');
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
                'time_in' => date('h:i A', strtotime($r['time_in'])),
                'time_out' => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'break_min' => (int)round($sec['break'] / 60), 'net_min' => (int)round($sec['net'] / 60),
                'open' => empty($r['time_out']), 'events' => $events,
            ];
        }
        $l = db()->prepare('SELECT * FROM daily_logs WHERE student_pk=? AND log_date=? ORDER BY time_in, id');
        $l->execute([$uid, $date]);
        $logs = [];
        foreach ($l->fetchAll() as $r) {
            $logs[] = [
                'id' => (int)$r['id'], 'title' => $r['title'], 'description' => $r['description'], 'hours' => (float)$r['hours'],
                'time_in' => $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : null,
                'time_out' => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'break_min' => (int)$r['break_minutes'], 'is_auto' => (int)$r['is_auto'] === 1,
                'evidence' => evidence_url($r['evidence_image']),
            ];
        }
        json_out(['ok' => true, 'date' => $date, 'label' => date('D, F j, Y', strtotime($date)), 'sessions' => $sessions, 'logs' => $logs]);
    }

    json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
}

/* ---------- POST: scan / break ---------- */
verify_csrf();
$action = (string)($_POST['action'] ?? (isset($_POST['qr_value']) ? 'scan' : ''));
$session = active_session($uid);
$now = time();
$nowStr = date('Y-m-d H:i:s', $now);

if ($action === 'break_start') {
    if (!$session) json_out(['ok' => false, 'message' => 'Time in first before taking a break.', 'state' => tracker_state($uid)]);
    if (!empty($session['break_started_at'])) json_out(['ok' => false, 'message' => 'You are already on a break.', 'state' => tracker_state($uid)]);
    db()->prepare('UPDATE attendance SET break_started_at=? WHERE id=? AND student_pk=?')->execute([$nowStr, $session['id'], $uid]);
    log_attendance_event((int)$session['id'], $uid, 'break_start', $nowStr);
    add_activity($uid, 'Started a break');
    json_out(['ok' => true, 'action' => 'break_start', 'message' => 'Break started. Your work timer is paused.', 'state' => tracker_state($uid)]);
}

if ($action === 'break_end') {
    if (!$session || empty($session['break_started_at'])) json_out(['ok' => false, 'message' => 'You are not on a break.', 'state' => tracker_state($uid)]);
    $spent = max(0, $now - strtotime((string)$session['break_started_at']));
    db()->prepare('UPDATE attendance SET break_started_at=NULL, break_seconds=break_seconds+? WHERE id=? AND student_pk=?')->execute([$spent, $session['id'], $uid]);
    log_attendance_event((int)$session['id'], $uid, 'break_end', $nowStr);
    sync_auto_log($uid, (int)$session['id']);
    add_activity($uid, 'Ended a break');
    json_out(['ok' => true, 'action' => 'break_end', 'message' => 'Welcome back! Your work timer is running again.', 'state' => tracker_state($uid)]);
}

if ($action !== 'scan') json_out(['ok' => false, 'message' => 'Unknown action.'], 400);

$qr = trim((string)($_POST['qr_value'] ?? ''));
if ($qr === '') json_out(['ok' => false, 'message' => 'QR value is required.']);

$stmt = db()->prepare('SELECT * FROM qr_codes WHERE qr_value=? AND is_active=1 LIMIT 1');
$stmt->execute([$qr]);
$qrRow = $stmt->fetch();
if (!$qrRow) json_out(['ok' => false, 'message' => 'Invalid or inactive company QR code.']);

$internship = internship_for($uid);
if (!$internship) {
    db()->prepare("INSERT INTO internships(student_pk,company_name,company_address,required_hours,completed_hours,start_date,end_date,status) VALUES(?,?,?,480,0,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY),'active')")->execute([$uid, $qrRow['company_name'], $qrRow['company_address']]);
    $internship = internship_for($uid);
} elseif (empty($internship['company_name'])) {
    db()->prepare('UPDATE internships SET company_name=?, company_address=? WHERE id=?')->execute([$qrRow['company_name'], $qrRow['company_address'], $internship['id']]);
} elseif (!empty($internship['company_name']) && strcasecmp(trim((string)$internship['company_name']), trim((string)$qrRow['company_name'])) !== 0) {
    json_out(['ok' => false, 'message' => 'This QR belongs to a different company than your current OJT assignment.']);
}

if ($session) {
    /* ---- TIME OUT ---- */
    if (strcasecmp(trim((string)$session['company_name']), trim((string)$qrRow['company_name'])) !== 0) json_out(['ok' => false, 'message' => 'Please use the same company QR used for Time In.']);
    $inTs = strtotime((string)$session['time_in']);
    $breakSec = (int)$session['break_seconds'];
    if (!empty($session['break_started_at'])) {
        $breakSec += max(0, $now - strtotime((string)$session['break_started_at']));
        log_attendance_event((int)$session['id'], $uid, 'break_end', $nowStr);
    }
    $total = $now - $inTs; $outTs = $now; $capped = false;
    if ($total > MAX_SESSION_SECONDS) { $capped = true; $total = MAX_SESSION_SECONDS; $outTs = $inTs + MAX_SESSION_SECONDS; }
    $breakSec = min($breakSec, $total);
    $net = max(0, $total - $breakSec);
    $minutes = (int)round($net / 60);
    $outStr = date('Y-m-d H:i:s', $outTs);
    db()->prepare('UPDATE attendance SET time_out=?, break_started_at=NULL, break_seconds=?, duration_minutes=? WHERE id=? AND student_pk=?')->execute([$outStr, $breakSec, $minutes, $session['id'], $uid]);
    log_attendance_event((int)$session['id'], $uid, 'time_out', $outStr);
    adjust_completed_hours($uid, $minutes / 60);
    sync_auto_log($uid, (int)$session['id']);
    add_activity($uid, 'Timed out · ' . seconds_to_hm($net) . ' rendered');
    add_notification($uid, 'Time Out recorded', 'You rendered ' . seconds_to_hm($net) . ' today. Add your task details in Daily Logs.', '✓');
    $lg = db()->prepare('SELECT id FROM daily_logs WHERE attendance_id=? AND student_pk=? LIMIT 1');
    $lg->execute([$session['id'], $uid]);
    $msg = 'Time Out recorded. You rendered ' . seconds_to_hm($net) . '.';
    if ($capped) $msg .= ' The session had been open for more than 12 hours, so it was capped at 12 hours.';
    json_out(['ok' => true, 'action' => 'timeout', 'message' => $msg, 'log_id' => (int)$lg->fetchColumn(), 'state' => tracker_state($uid)]);
}

/* ---- TIME IN ---- */
db()->prepare('INSERT INTO attendance(student_pk,qr_id,company_name,time_in) VALUES(?,?,?,?)')->execute([$uid, (int)$qrRow['id'], $qrRow['company_name'], $nowStr]);
$attId = (int)db()->lastInsertId();
log_attendance_event($attId, $uid, 'time_in', $nowStr);
sync_auto_log($uid, $attId);
add_activity($uid, 'Timed in using company QR');
add_notification($uid, 'Time In recorded', 'Your attendance has started. A daily log was created for today.', '◷');
json_out(['ok' => true, 'action' => 'timein', 'message' => 'Time In recorded. Your timer is running!', 'state' => tracker_state($uid)]);
