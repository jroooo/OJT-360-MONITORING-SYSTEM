<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/icons.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function is_api_request(): bool {
    return str_contains((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/api/');
}

function verify_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', (string)$token)) {
        http_response_code(419);
        if (is_api_request()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Your session expired. Please reload the page.', 'error' => 'Your session expired. Please reload the page.']);
            exit;
        }
        exit('Invalid CSRF token.');
    }
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (isset($data['message']) && !isset($data['error']) && empty($data['ok'])) $data['error'] = $data['message'];
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function hours_to_hm(float $hours): string {
    $minutes = (int)round($hours * 60);
    return intdiv($minutes, 60) . 'h ' . str_pad((string)($minutes % 60), 2, '0', STR_PAD_LEFT) . 'm';
}

function seconds_to_hm(int $seconds): string {
    $seconds = max(0, $seconds);
    return intdiv($seconds, 3600) . 'h ' . str_pad((string)intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT) . 'm';
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach (array_slice($parts ?: [], 0, 2) as $p) {
        if ($p !== '') $letters .= strtoupper(substr($p, 0, 1));
    }
    return $letters ?: 'U';
}

function week_start_of(string $date): string {
    $ts = strtotime($date) ?: time();
    return date('Y-m-d', strtotime('monday this week', $ts));
}

/* ---------- student data helpers ---------- */

function student_settings(int $uid): array {
    $defaults = ['shift_start' => '08:00:00', 'grace_minutes' => 15];
    try {
        $stmt = db()->prepare('SELECT shift_start, grace_minutes FROM student_settings WHERE student_pk=? LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if ($row) {
            $shiftStart = (string)$row['shift_start'];
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $shiftStart)) return $defaults;
            return ['shift_start' => $shiftStart, 'grace_minutes' => (int)$row['grace_minutes']];
        }
    } catch (Throwable $e) { /* use defaults */ }
    return $defaults;
}

function internship_for(int $uid): ?array {
    $stmt = db()->prepare('SELECT * FROM internships WHERE student_pk=? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adjust_completed_hours(int $uid, float $delta): void {
    if ($delta == 0.0) return;
    db()->prepare('UPDATE internships SET completed_hours = LEAST(required_hours, GREATEST(0, completed_hours + ?)) WHERE student_pk=?')->execute([$delta, $uid]);
}

function add_activity(int $uid, string $text): void {
    db()->prepare('INSERT INTO activity_logs(student_pk,action_text) VALUES(?,?)')->execute([$uid, $text]);
}

function add_notification(int $uid, string $title, string $message, string $icon = '•'): void {
    db()->prepare('INSERT INTO notifications(student_pk,title,message,icon) VALUES(?,?,?,?)')->execute([$uid, $title, $message, $icon]);
}

/* ---------- attendance / time tracking ---------- */

const ATTENDANCE_SHIFT_SECONDS = 4 * 3600;
const ATTENDANCE_DAILY_SECONDS = 2 * ATTENDANCE_SHIFT_SECONDS;

function attendance_shift_start_times(string $date, array $settings): array {
    $morning = strtotime($date . ' ' . substr($settings['shift_start'], 0, 5));
    return ['morning' => $morning, 'afternoon' => $morning + 5 * 3600];
}

function active_session(int $uid): ?array {
    $stmt = db()->prepare(
        'SELECT a.*,
                COALESCE(
                    (SELECT MIN(ev.event_time)
                     FROM attendance_events ev
                     WHERE ev.attendance_id = a.id
                       AND ev.student_pk = a.student_pk
                       AND ev.event_type = \'time_in\'),
                    a.time_in
                ) AS actual_time_in
         FROM attendance a
         WHERE a.student_pk = ? AND a.time_out IS NULL
         ORDER BY a.time_in DESC
         LIMIT 1'
    );
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* total / break / net seconds of one attendance row, live when the session is still open */
function session_seconds(array $s, ?int $now = null): array {
    $now = $now ?? time();
    $scheduledIn = strtotime((string)$s['time_in']);
    $actualIn = strtotime((string)($s['actual_time_in'] ?? '')) ?: $scheduledIn;
    $end = !empty($s['time_out']) ? strtotime((string)$s['time_out']) : $now;
    $break = (int)($s['break_seconds'] ?? 0);
    if (!empty($s['break_started_at']) && empty($s['time_out'])) {
        $break += max(0, $now - strtotime((string)$s['break_started_at']));
    }
    $total = max(0, $end - $actualIn);
    $net = !empty($s['time_out']) && array_key_exists('duration_minutes', $s) && $s['duration_minutes'] !== null
        ? min(ATTENDANCE_SHIFT_SECONDS, max(0, (int)$s['duration_minutes'] * 60))
        : min(ATTENDANCE_SHIFT_SECONDS, max(0, $total - $break));
    return ['start' => $actualIn, 'total' => $total, 'break' => $break, 'net' => $net];
}

function log_attendance_event(int $attendanceId, int $uid, string $type, string $time): void {
    db()->prepare('INSERT INTO attendance_events(attendance_id,student_pk,event_type,event_time) VALUES(?,?,?,?)')->execute([$attendanceId, $uid, $type, $time]);
}

function today_events(int $uid): array {
    $stmt = db()->prepare(
        'SELECT ev.event_type, ev.event_time, a.time_in AS scheduled_time_in
         FROM attendance_events ev
         LEFT JOIN attendance a ON a.id = ev.attendance_id AND a.student_pk = ev.student_pk
         WHERE ev.student_pk = ? AND DATE(ev.event_time) = ?
         ORDER BY ev.event_time ASC, ev.id ASC'
    );
    $stmt->execute([$uid, date('Y-m-d')]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $scheduledIn = $r['event_type'] === 'time_in' && !empty($r['scheduled_time_in'])
            ? strtotime($r['scheduled_time_in'])
            : false;
        $out[] = [
            'type' => $r['event_type'],
            'ts' => strtotime($r['event_time']),
            'label' => date('h:i A', $scheduledIn ?: strtotime($r['event_time'])),
            'actual_label' => date('h:i A', strtotime($r['event_time'])),
        ];
    }
    return $out;
}

function tracker_state(int $uid): array {
    $now = time();
    $s = active_session($uid);
    $state = ['status' => 'idle', 'server_now' => $now, 'session' => null];
    if ($s) {
        $scheduledStart = strtotime((string)$s['time_in']);
        $actualStart = strtotime((string)($s['actual_time_in'] ?? '')) ?: $scheduledStart;
        $shiftTargetSeconds = ATTENDANCE_SHIFT_SECONDS;
        $shiftStarts = attendance_shift_start_times(date('Y-m-d', $scheduledStart), student_settings($uid));
        $shift = $scheduledStart >= $shiftStarts['afternoon'] ? 'afternoon' : 'morning';
        $sessionTime = session_seconds($s, $now);
        $state['status'] = $now < $actualStart ? 'scheduled' : 'working';
        $state['session'] = [
            'id' => (int)$s['id'],
            'slot' => $shift,
            'time_in_ts' => $scheduledStart,
            'time_in_label' => date('h:i A', $actualStart),
            'scheduled_time_in_label' => date('h:i A', $scheduledStart),
            'actual_time_in_ts' => $actualStart,
            'break_seconds' => (int)$s['break_seconds'],
            'required_seconds' => $shiftTargetSeconds,
            'remaining_seconds' => max(0, $shiftTargetSeconds - $sessionTime['net']),
        ];
    }
    $today = date('Y-m-d');
    $stmt = db()->prepare(
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
         WHERE a.student_pk = ? AND DATE(a.time_out) = ?
         ORDER BY a.time_out"
    );
    $stmt->execute([$uid, $today]);
    $todaySeconds = 0;
    $todaySessions = 0;
    $lastCompletedToday = null;
    foreach ($stmt->fetchAll() as $completedSession) {
        $completedTime = session_seconds($completedSession, $now);
        $creditedSeconds = (int)floor($completedTime['net'] / 3600) * 3600;
        $todaySeconds = min(ATTENDANCE_DAILY_SECONDS, $todaySeconds + min(ATTENDANCE_SHIFT_SECONDS, $creditedSeconds));
        $todaySessions++;
        if ($lastCompletedToday === null || strtotime((string)$completedSession['time_out']) > $lastCompletedToday['time_out_ts']) {
            $lastCompletedToday = [
                'time_in' => date('h:i A', $completedTime['start']),
                'time_out' => date('h:i A', strtotime((string)$completedSession['time_out'])),
                'time_out_ts' => strtotime((string)$completedSession['time_out']),
            ];
        }
    }
    $state['today'] = [
        'net_seconds' => $todaySeconds,
        'break_seconds' => 0,
        'sessions' => $todaySessions,
        'required_seconds' => ATTENDANCE_DAILY_SECONDS,
    ];
    $state['today']['last_time_in_label'] = $lastCompletedToday['time_in'] ?? null;
    $state['today']['last_time_out_label'] = $lastCompletedToday['time_out'] ?? null;
    $i = internship_for($uid);
    $state['completed_hours'] = (float)($i['completed_hours'] ?? 0);
    $state['required_hours'] = (float)($i['required_hours'] ?? 480);
    $state['events'] = today_events($uid);
    return $state;
}

/* Keeps the automatic daily-log row of an attendance session up to date. */
function sync_auto_log(int $uid, int $attendanceId): void {
    $stmt = db()->prepare('SELECT * FROM attendance WHERE id=? AND student_pk=? LIMIT 1');
    $stmt->execute([$attendanceId, $uid]);
    $a = $stmt->fetch();
    if (!$a) return;
    $date = date('Y-m-d', strtotime((string)$a['time_in']));
    $timeIn = date('H:i:s', strtotime((string)$a['time_in']));
    $timeOut = !empty($a['time_out']) ? date('H:i:s', strtotime((string)$a['time_out'])) : null;
    $breakMin = (int)round(((int)$a['break_seconds']) / 60);
    $hours = !empty($a['time_out']) ? round(((int)$a['duration_minutes']) / 60, 2) : 0.0;

    $find = db()->prepare('SELECT id FROM daily_logs WHERE attendance_id=? AND student_pk=? LIMIT 1');
    $find->execute([$attendanceId, $uid]);
    $logId = $find->fetchColumn();
    if ($logId) {
        db()->prepare('UPDATE daily_logs SET log_date=?, time_in=?, time_out=?, break_minutes=?, hours=? WHERE id=? AND student_pk=?')
            ->execute([$date, $timeIn, $timeOut, $breakMin, $hours, $logId, $uid]);
    } else {
        db()->prepare("INSERT INTO daily_logs(student_pk,log_date,title,description,hours,time_in,time_out,break_minutes,attendance_id,is_auto) VALUES(?,?,?,?,?,?,?,?,?,1)")
            ->execute([$uid, $date, '', '', $hours, $timeIn, $timeOut, $breakMin, $attendanceId]);
    }
}

/* ---------- uploads ---------- */

function save_evidence_upload(string $field, string $folder = 'daily_logs'): ?string {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('The photo could not be uploaded. Please try a smaller image.');
    if ($f['size'] > 5 * 1024 * 1024) throw new RuntimeException('The photo must be 5 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name']) === false) throw new RuntimeException('Photo evidence must be a JPG, PNG, WEBP, or GIF image.');
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('The upload folder could not be created.');
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('The photo could not be saved on the server.');
    return $name;
}

function evidence_url(?string $name): string {
    return $name ? 'uploads/daily_logs/' . rawurlencode(basename($name)) : '';
}

function save_journal_submission_pdf(string $field): string {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Create the PDF before submitting the journal.');
    }
    if (
        !is_array($file) ||
        !is_string($file['tmp_name'] ?? null) ||
        ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        throw new RuntimeException('The PDF could not be uploaded. Please try again.');
    }
    $size = (int)($file['size'] ?? 0);
    if ($size < 8 || $size > 25 * 1024 * 1024) {
        throw new RuntimeException('The PDF must be smaller than 25 MB.');
    }
    $handle = fopen($file['tmp_name'], 'rb');
    $signature = $handle ? fread($handle, 5) : false;
    $footer = false;
    if ($handle) {
        fseek($handle, max(0, $size - 2048));
        $footer = fread($handle, 2048);
    }
    if ($handle) fclose($handle);
    if ($signature !== '%PDF-' || !is_string($footer) || strpos($footer, '%%EOF') === false) {
        throw new RuntimeException('The submitted attachment is not a valid PDF.');
    }

    $dir = __DIR__ . '/../uploads/journal_submissions';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('The PDF storage folder could not be created.');
    }
    $name = bin2hex(random_bytes(24)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The PDF could not be saved on the server.');
    }
    return $name;
}

/* ---------- report helpers ---------- */

function report_period(string $type, string $date): array {
    $ts = strtotime($date) ?: time();
    if ($type === 'monthly') {
        $start = date('Y-m-01', $ts);
        $end = date('Y-m-t', $ts);
        $label = date('F Y', $ts);
        $range = date('D, F j, Y', strtotime($start)) . ' – ' . date('D, F j, Y', strtotime($end));
    } else {
        $start = date('Y-m-d', strtotime('monday this week', $ts));
        $end = date('Y-m-d', strtotime($start . ' +6 days'));
        $label = 'Week of ' . date('M j, Y', strtotime($start));
        $range = date('D, F j, Y', strtotime($start)) . ' – ' . date('D, F j, Y', strtotime($end));
    }
    return ['type' => $type === 'monthly' ? 'monthly' : 'weekly', 'start' => $start, 'end' => $end, 'label' => $label, 'range' => $range];
}

function philippine_holidays_for_year(int $year): array {
    $cache = db()->prepare('SELECT holidays_json,synced_at FROM student_holiday_calendar WHERE holiday_year=? LIMIT 1');
    $cache->execute([$year]);
    $cached = $cache->fetch();
    $holidayMap = $cached ? json_decode((string)$cached['holidays_json'], true) : null;
    if (!is_array($holidayMap)) $holidayMap = [];
    $stale = !$cached || strtotime((string)$cached['synced_at']) < strtotime('-1 day');
    $warning = null;

    if ($stale) {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 6,
                    'ignore_errors' => true,
                    'header' => "Accept: application/json\r\nUser-Agent: OJT360-Student-Dashboard/1.0\r\n",
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $response = @file_get_contents('https://date.nager.at/api/v3/PublicHolidays/' . $year . '/PH', false, $context);
            if ($response === false || !isset($http_response_header[0]) || !preg_match('/\s200\s/', $http_response_header[0])) {
                throw new RuntimeException('The public holiday calendar did not return a successful response.');
            }
            $records = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($records)) throw new RuntimeException('The public holiday calendar response was invalid.');
            $freshMap = [];
            foreach ($records as $record) {
                if (
                    !is_array($record) ||
                    ($record['countryCode'] ?? '') !== 'PH' ||
                    !empty($record['counties']) ||
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($record['date'] ?? '')) ||
                    (int)substr((string)$record['date'], 0, 4) !== $year ||
                    !in_array('Public', $record['types'] ?? [], true)
                ) continue;
                $freshMap[$record['date']] = (string)($record['localName'] ?? $record['name'] ?? 'Public holiday');
            }
            if (!$freshMap) throw new RuntimeException('The public holiday calendar returned no valid Philippine holidays.');
            ksort($freshMap);
            $save = db()->prepare(
                'INSERT INTO student_holiday_calendar(holiday_year,holidays_json,synced_at) VALUES(?,?,NOW())
                 ON DUPLICATE KEY UPDATE holidays_json=VALUES(holidays_json),synced_at=VALUES(synced_at)'
            );
            $save->execute([$year, json_encode($freshMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
            $holidayMap = $freshMap;
        } catch (Throwable $exception) {
            error_log('Unable to refresh Philippine public holidays for ' . $year . ': ' . $exception->getMessage());
            $warning = $holidayMap
                ? 'The Philippine holiday calendar for ' . $year . ' could not be refreshed. The saved list is shown, but automatic absence marking is paused for that year.'
                : 'The Philippine holiday calendar for ' . $year . ' is unavailable; automatic absence marking is paused for that year.';
        }
    }

    return ['dates' => $holidayMap, 'warning' => $warning];
}

function student_attendance_statuses(int $uid, string $start, string $end): array {
    $internship = internship_for($uid);
    if (!$internship) return ['days' => [], 'warnings' => []];

    $internshipStart = (string)($internship['start_date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $internshipStart)) {
        $earliest = db()->prepare('SELECT DATE(MIN(time_in)) FROM attendance WHERE student_pk=?');
        $earliest->execute([$uid]);
        $internshipStart = (string)($earliest->fetchColumn() ?: '');
        if ($internshipStart === '') {
            return ['days' => [], 'warnings' => ['Set an internship start date to enable automatic absence tracking.']];
        }
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $throughDate = min($today, preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($internship['end_date'] ?? '')) ? (string)$internship['end_date'] : $today);
    if ($internshipStart > $throughDate) return ['days' => [], 'warnings' => []];

    $holidays = [];
    $warnings = [];
    for ($year = (int)substr($internshipStart, 0, 4), $lastYear = (int)substr($throughDate, 0, 4); $year <= $lastYear; $year++) {
        $calendar = philippine_holidays_for_year($year);
        $holidays[$year] = $calendar['dates'];
        if ($calendar['warning']) $warnings[] = $calendar['warning'];
    }

    $attendanceQuery = db()->prepare(
        "SELECT a.*,
                COALESCE(
                    (SELECT MIN(ev.event_time)
                     FROM attendance_events ev
                     WHERE ev.attendance_id=a.id AND ev.student_pk=a.student_pk AND ev.event_type='time_in'),
                    a.time_in
                ) AS actual_time_in
         FROM attendance a
         WHERE a.student_pk=? AND a.time_in>=? AND a.time_in<?
         ORDER BY a.time_in,a.id"
    );
    $attendanceQuery->execute([$uid, $internshipStart . ' 00:00:00', date('Y-m-d', strtotime($throughDate . ' +1 day')) . ' 00:00:00']);
    $workByDate = [];
    foreach ($attendanceQuery->fetchAll() as $row) {
        $date = date('Y-m-d', strtotime((string)$row['time_in']));
        if (!isset($workByDate[$date])) $workByDate[$date] = ['seconds' => 0, 'sessions' => 0];
        $workByDate[$date]['seconds'] = min(ATTENDANCE_DAILY_SECONDS, $workByDate[$date]['seconds'] + session_seconds($row)['net']);
        $workByDate[$date]['sessions']++;
    }

    $existingQuery = db()->prepare('SELECT absence_date,status,reason FROM absences WHERE student_pk=? AND absence_date BETWEEN ? AND ?');
    $existingQuery->execute([$uid, $internshipStart, $throughDate]);
    $absenceByDate = [];
    foreach ($existingQuery->fetchAll() as $absence) $absenceByDate[(string)$absence['absence_date']] = $absence;

    $days = [];
    $date = new DateTimeImmutable($internshipStart);
    $lastDate = new DateTimeImmutable($throughDate);
    while ($date <= $lastDate) {
        $day = $date->format('Y-m-d');
        $year = (int)$date->format('Y');
        $weekday = (int)$date->format('N') <= 5;
        $hasAttendance = isset($workByDate[$day]);
        $autoAbsence = isset($absenceByDate[$day]) && str_starts_with((string)$absenceByDate[$day]['reason'], 'Automatically recorded by OJT360:');
        $calendarUnavailable = !isset($holidays[$year]) || (bool)array_filter($warnings, static fn(string $warning): bool => str_contains($warning, (string)$year));
        $isHoliday = !$calendarUnavailable && isset($holidays[$year][$day]);

        if ($autoAbsence && (!$weekday || $hasAttendance || (!$calendarUnavailable && $isHoliday))) {
            db()->prepare("DELETE FROM absences WHERE student_pk=? AND absence_date=? AND reason LIKE 'Automatically recorded by OJT360:%'")->execute([$uid, $day]);
            unset($absenceByDate[$day]);
            $autoAbsence = false;
        }

        if ($hasAttendance) {
            $seconds = (int)$workByDate[$day]['seconds'];
            $status = !$weekday || $isHoliday || $calendarUnavailable
                ? 'attendance'
                : ($day === $today && $seconds < ATTENDANCE_DAILY_SECONDS
                    ? 'progress'
                    : ($seconds < ATTENDANCE_DAILY_SECONDS ? 'incomplete' : 'complete'));
            $days[$day] = [
                'status' => $status,
                'hours' => round($seconds / 3600, 2),
                'sessions' => (int)$workByDate[$day]['sessions'],
                'absence_status' => null,
                'reason' => null,
            ];
            if ($autoAbsence) {
                db()->prepare("DELETE FROM absences WHERE student_pk=? AND absence_date=? AND reason LIKE 'Automatically recorded by OJT360:%'")->execute([$uid, $day]);
                unset($absenceByDate[$day]);
            }
        } elseif ($day < $today && $weekday && !$calendarUnavailable && !$isHoliday) {
            if (!isset($absenceByDate[$day])) {
                $reason = 'Automatically recorded by OJT360: no attendance on a scheduled workday.';
                db()->prepare(
                    'INSERT IGNORE INTO absences(student_pk,absence_date,reason,status)
                     SELECT ?,?,?,\'Unexcused\' WHERE NOT EXISTS
                       (SELECT 1 FROM absences WHERE student_pk=? AND absence_date=?)'
                )->execute([$uid, $day, $reason, $uid, $day]);
                $absenceByDate[$day] = ['absence_date' => $day, 'status' => 'Unexcused', 'reason' => $reason];
            }
            $days[$day] = [
                'status' => 'absent',
                'hours' => 0.0,
                'sessions' => 0,
                'absence_status' => (string)$absenceByDate[$day]['status'],
                'reason' => (string)$absenceByDate[$day]['reason'],
            ];
        }
        $date = $date->modify('+1 day');
    }

    $rangeStart = max($start, $internshipStart);
    $rangeEnd = min($end, $throughDate);
    if ($rangeStart > $rangeEnd) return ['days' => [], 'warnings' => array_values(array_unique($warnings))];
    $rangeDays = array_intersect_key($days, array_flip(array_filter(array_keys($days), static fn(string $day): bool => $day >= $rangeStart && $day <= $rangeEnd)));
    return ['days' => $rangeDays, 'warnings' => array_values(array_unique($warnings))];
}

function report_data(int $uid, string $start, string $end): array {
    $q = db()->prepare(
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
         WHERE a.student_pk=? AND DATE(a.time_in) BETWEEN ? AND ?
         ORDER BY a.time_in ASC, a.id ASC"
    );
    $q->execute([$uid, $start, $end]);
    $sessions = [];
    $daySeconds = [];
    foreach ($q->fetchAll() as $row) {
        $day = date('Y-m-d', strtotime((string)$row['time_in']));
        $seconds = session_seconds($row);
        $remaining = max(0, ATTENDANCE_DAILY_SECONDS - ($daySeconds[$day] ?? 0));
        $creditedSeconds = min($seconds['net'], $remaining);
        $daySeconds[$day] = ($daySeconds[$day] ?? 0) + $creditedSeconds;
        $sessions[] = [
            'log_date' => $day,
            'company_name' => (string)($row['company_name'] ?? ''),
            'time_in' => $row['actual_time_in'],
            'time_out' => $row['time_out'],
            'break_minutes' => (int)round($seconds['break'] / 60),
            'hours' => round($creditedSeconds / 3600, 2),
        ];
    }
    $hours = round(array_sum(array_column($sessions, 'hours')), 2);
    $attendanceDays = count($daySeconds);
    $statusData = student_attendance_statuses($uid, $start, $end);
    foreach ($sessions as &$session) {
        $session['status'] = $statusData['days'][$session['log_date']]['status'] ?? 'attendance';
    }
    unset($session);
    $rows = $sessions;
    foreach ($statusData['days'] as $date => $day) {
        if ($day['status'] === 'absent') {
            $rows[] = [
                'log_date' => $date,
                'company_name' => '',
                'time_in' => null,
                'time_out' => null,
                'break_minutes' => 0,
                'hours' => 0.0,
                'status' => $day['status'],
                'absence_status' => $day['absence_status'],
                'reason' => $day['reason'],
            ];
        }
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($a['log_date'], $b['log_date']) ?: strcmp((string)($a['time_in'] ?? ''), (string)($b['time_in'] ?? '')));
    return [
        'sessions' => $sessions,
        'rows' => $rows,
        'day_statuses' => $statusData['days'],
        'absent_days' => count(array_filter($statusData['days'], static fn(array $day): bool => $day['status'] === 'absent')),
        'incomplete_days' => count(array_filter($statusData['days'], static fn(array $day): bool => $day['status'] === 'incomplete')),
        'status_warnings' => $statusData['warnings'],
        'hours' => $hours,
        'attendance_days' => $attendanceDays,
        'daily_avg' => $attendanceDays ? round($hours / $attendanceDays, 2) : 0.0,
        'session_count' => count($sessions),
    ];
}

/* ---------- dashboard insights ---------- */
function attendance_insights(int $uid): array {
    $set = student_settings($uid);
    $calc = function (string $month) use ($uid, $set): array {
        $stmt = db()->prepare(
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
             ORDER BY a.time_in"
        );
        $stmt->execute([$uid, $month]);
        $total = 0; $ontime = 0; $break = 0; $byDay = []; $dailySeconds = [];
        foreach ($stmt->fetchAll() as $r) {
            $d = date('Y-m-d', strtotime($r['time_in']));
            $break += (int)$r['break_seconds'];
            $scheduledStart = strtotime((string)$r['time_in']);
            $actualStart = strtotime((string)$r['actual_time_in']) ?: $scheduledStart;
            $isOnTime = $actualStart <= $scheduledStart + (int)$set['grace_minutes'] * 60;
            $total++;
            if ($isOnTime) $ontime++;
            $byDay[$d] = ($byDay[$d] ?? true) && $isOnTime;
            $duration = session_seconds($r);
            $dailySeconds[$d] = min(ATTENDANCE_DAILY_SECONDS, ($dailySeconds[$d] ?? 0) + $duration['net']);
        }
        $pct = $total ? (int)round($ontime / $total * 100) : 0;
        return [
            'days' => $total,
            'ontime' => $ontime,
            'pct' => $pct,
            'late_pct' => $total ? 100 - $pct : 0,
            'break' => $break,
            'hours' => array_sum($dailySeconds),
            'byDay' => $byDay,
        ];
    };
    $cur = $calc(date('Y-m'));
    $prev = $calc(date('Y-m', strtotime('first day of last month')));
    $series = [];
    $q = db()->prepare(
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
         WHERE a.student_pk = ? AND a.time_in >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         ORDER BY a.time_in"
    );
    $q->execute([$uid]);
    $workMap = $breakMap = [];
    foreach ($q->fetchAll() as $r) {
        $d = date('Y-m-d', strtotime($r['time_in']));
        $session = session_seconds($r);
        $workMap[$d] = min(ATTENDANCE_DAILY_SECONDS, ($workMap[$d] ?? 0) + $session['net']);
        $breakMap[$d] = ($breakMap[$d] ?? 0) + $session['break'];
    }
    $work = $brk = $ot = $lt = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $work[] = round(($workMap[$d] ?? 0) / 3600, 2);
        $brk[] = round(($breakMap[$d] ?? 0) / 3600, 2);
        $ot[] = isset($cur['byDay'][$d]) ? ($cur['byDay'][$d] ? 100 : 0) : null;
        $lt[] = isset($cur['byDay'][$d]) ? ($cur['byDay'][$d] ? 0 : 100) : null;
    }
    return ['cur' => $cur, 'prev' => $prev, 'series' => ['work' => $work, 'break' => $brk, 'ontime' => $ot, 'late' => $lt], 'settings' => $set];
}

function trend_badge(float $now, float $before, bool $goodWhenUp = true, string $suffix = '%'): string {
    $diff = round($now - $before);
    if ($before == 0 && $now == 0) return '<span class="trend flat">— No change vs last month</span>';
    $up = $diff > 0; $flat = $diff == 0;
    $good = $flat ? true : ($up === $goodWhenUp);
    $cls = $flat ? 'flat' : ($good ? 'good' : 'bad');
    $arrow = $flat ? '•' : ($up ? '▲' : '▼');
    return '<span class="trend ' . $cls . '">' . $arrow . ' ' . ($up ? '+' : '') . (int)$diff . $suffix . ' vs last month</span>';
}

/* ---------- rich text (journal) sanitizer: DOM whitelist ---------- */
function sanitize_css(string $css): string {
    $allowed = ['text-align','font-family','font-size','font-weight','font-style','text-decoration','color','background-color','line-height','float','clear','width','height','max-width','vertical-align','margin','margin-left','margin-right','margin-top','margin-bottom','padding','display','position','left','top','z-index','text-indent','border','border-collapse','border-color','border-width','border-style','page-break-before','page-break-after','break-after','opacity','padding-left','list-style-type'];
    $out = [];
    foreach (explode(';', $css) as $decl) {
        $parts = explode(':', $decl, 2);
        if (count($parts) !== 2) continue;
        $prop = strtolower(trim($parts[0]));
        $val = trim($parts[1]);
        if (!in_array($prop, $allowed, true)) continue;
        if ($val === '' || strlen($val) > 140) continue;
        if (preg_match('/[<>`\\\\]|url\s*\(|expression|javascript|@import|behavior|binding/i', $val)) continue;
        if ($prop === 'position' && !in_array($val, ['absolute', 'relative', 'static'], true)) continue;
        if ($prop === 'display' && !in_array($val, ['block', 'inline', 'inline-block', 'table', 'table-cell', 'list-item'], true)) continue;
        $out[] = $prop . ':' . $val;
    }
    return implode(';', $out);
}

function sanitize_rich_attributes(DOMElement $el, string $tag, int $uid): void {
    $names = [];
    foreach ($el->attributes as $a) $names[] = $a->name;
    $classAllowed = ['jimg', 'jimg-inline', 'jimg-left', 'jimg-right', 'jimg-block', 'jimg-front', 'jimg-behind', 'page-break', 'doc-title'];
    foreach ($names as $name) {
        $lname = strtolower($name);
        $value = (string)$el->getAttribute($name);
        $keep = null;
        if ($lname === 'style') {
            $css = sanitize_css($value);
            $keep = $css !== '' ? $css : null;
        } elseif ($lname === 'class') {
            $tokens = array_values(array_filter(preg_split('/\s+/', $value) ?: [], fn($t) => in_array($t, $classAllowed, true)));
            $keep = $tokens ? implode(' ', $tokens) : null;
        } elseif ($lname === 'data-wrap' && preg_match('/^[a-z-]{1,16}$/', $value)) {
            $keep = $value;
        } elseif ($lname === 'src' && $tag === 'img') {
            $keep = preg_match('#^uploads/journal/' . $uid . '/[A-Za-z0-9._-]+$#', $value) ? $value : null;
        } elseif ($lname === 'href' && $tag === 'a') {
            $keep = preg_match('#^(https?://|mailto:)#i', $value) ? $value : null;
        } elseif (($lname === 'alt' || $lname === 'title') && in_array($tag, ['img', 'a'], true)) {
            $keep = mb_substr($value, 0, 160);
        } elseif (in_array($lname, ['colspan', 'rowspan'], true) && ctype_digit($value) && (int)$value > 0 && (int)$value < 30) {
            $keep = $value;
        }
        $el->removeAttribute($name);
        if ($keep !== null) $el->setAttribute($lname, $keep);
    }
    if ($tag === 'a' && $el->hasAttribute('href')) {
        $el->setAttribute('target', '_blank');
        $el->setAttribute('rel', 'noopener noreferrer');
    }
}

function sanitize_rich_node(DOMNode $node, int $uid): void {
    $drop = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'svg', 'math', 'base', 'audio', 'video', 'canvas', 'noscript', 'template'];
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'blockquote', 'div', 'span', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'hr'];
    $children = [];
    foreach ($node->childNodes as $c) $children[] = $c;
    foreach ($children as $child) {
        if ($child instanceof DOMComment) { $node->removeChild($child); continue; }
        if (!($child instanceof DOMElement)) continue;
        $tag = strtolower($child->tagName);
        if (in_array($tag, $drop, true)) { $node->removeChild($child); continue; }
        if (!in_array($tag, $allowed, true)) {
            sanitize_rich_node($child, $uid);
            while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
            $node->removeChild($child);
            continue;
        }
        sanitize_rich_attributes($child, $tag, $uid);
        if ($tag === 'img' && !$child->hasAttribute('src')) { $node->removeChild($child); continue; }
        sanitize_rich_node($child, $uid);
    }
}

function sanitize_rich_html(string $html, int $uid): string {
    $html = trim($html);
    if ($html === '') return '';
    if (!class_exists('DOMDocument')) {
        return trim(strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><blockquote><span>'));
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="ojt-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) return '';
    sanitize_rich_node($root, $uid);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $dom->saveHTML($c);
    return trim($out);
}

function rich_to_text(string $html, int $max = 190): string {
    $t = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '<br>', '</h1>', '</h2>', '</h3>', '</li>'], ' ', $html))) ?? '');
    return mb_strlen($t) > $max ? mb_substr($t, 0, $max - 1) . '…' : $t;
}

/* Build the compiled document HTML from the weekly drafts (oldest week first). */
function compilation_from_drafts(array $journals): string {
    $parts = [];
    foreach ($journals as $x) {
        $content = trim((string)$x['content']);
        if ($content === '') $content = '<p><br></p>';
        if (strip_tags($content) === $content) {
            $paras = preg_split('/\R\s*\R/', $content) ?: [$content];
            $content = '';
            foreach ($paras as $p) $content .= '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        $label = 'WEEK OF ' . strtoupper(date('F d, Y', strtotime($x['week_start'])));
        $parts[] = '<p style="font-size:9pt;color:#64788c;font-weight:700"><span style="letter-spacing:2px">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></p>'
                 . '<h2>' . htmlspecialchars((string)$x['title'], ENT_QUOTES, 'UTF-8') . '</h2>' . $content;
    }
    return implode('<hr>', $parts);
}
