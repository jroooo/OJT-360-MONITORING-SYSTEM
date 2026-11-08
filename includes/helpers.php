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
    $defaults = ['shift_start' => '08:00:00', 'grace_minutes' => 15, 'daily_target' => 8.0];
    try {
        $stmt = db()->prepare('SELECT shift_start, grace_minutes, daily_target FROM student_settings WHERE student_pk=? LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if ($row) return ['shift_start' => (string)$row['shift_start'], 'grace_minutes' => (int)$row['grace_minutes'], 'daily_target' => (float)$row['daily_target']];
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

function active_session(int $uid): ?array {
    $stmt = db()->prepare('SELECT * FROM attendance WHERE student_pk=? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* total / break / net seconds of one attendance row, live when the session is still open */
function session_seconds(array $s, ?int $now = null): array {
    $now = $now ?? time();
    $in = strtotime((string)$s['time_in']);
    $end = !empty($s['time_out']) ? strtotime((string)$s['time_out']) : $now;
    $break = (int)($s['break_seconds'] ?? 0);
    if (!empty($s['break_started_at']) && empty($s['time_out'])) {
        $break += max(0, $now - strtotime((string)$s['break_started_at']));
    }
    $total = max(0, $end - $in);
    return ['total' => $total, 'break' => min($break, $total), 'net' => max(0, $total - $break)];
}

function log_attendance_event(int $attendanceId, int $uid, string $type, string $time): void {
    db()->prepare('INSERT INTO attendance_events(attendance_id,student_pk,event_type,event_time) VALUES(?,?,?,?)')->execute([$attendanceId, $uid, $type, $time]);
}

function today_events(int $uid): array {
    $stmt = db()->prepare('SELECT event_type, event_time FROM attendance_events WHERE student_pk=? AND DATE(event_time)=? ORDER BY event_time ASC, id ASC');
    $stmt->execute([$uid, date('Y-m-d')]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $out[] = ['type' => $r['event_type'], 'ts' => strtotime($r['event_time']), 'label' => date('h:i A', strtotime($r['event_time']))];
    }
    return $out;
}

function tracker_state(int $uid): array {
    $now = time();
    $s = active_session($uid);
    $state = ['status' => 'idle', 'server_now' => $now, 'session' => null];
    if ($s) {
        $onBreak = !empty($s['break_started_at']);
        $state['status'] = $onBreak ? 'break' : 'working';
        $state['session'] = [
            'id' => (int)$s['id'],
            'time_in_ts' => strtotime((string)$s['time_in']),
            'time_in_label' => date('h:i A', strtotime((string)$s['time_in'])),
            'break_seconds' => (int)$s['break_seconds'],
            'break_started_ts' => $onBreak ? strtotime((string)$s['break_started_at']) : null,
        ];
    }
    $stmt = db()->prepare('SELECT COALESCE(SUM(duration_minutes),0) m, COALESCE(SUM(break_seconds),0) b, COUNT(*) c FROM attendance WHERE student_pk=? AND DATE(time_in)=? AND time_out IS NOT NULL');
    $stmt->execute([$uid, date('Y-m-d')]);
    $t = $stmt->fetch();
    $state['today'] = ['net_seconds' => (int)$t['m'] * 60, 'break_seconds' => (int)$t['b'], 'sessions' => (int)$t['c']];
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

function report_data(int $uid, string $start, string $end): array {
    $q = db()->prepare('SELECT * FROM daily_logs WHERE student_pk=? AND log_date BETWEEN ? AND ? ORDER BY log_date ASC, time_in ASC, id ASC');
    $q->execute([$uid, $start, $end]);
    $logs = $q->fetchAll();
    $a = db()->prepare('SELECT COUNT(DISTINCT DATE(time_in)) days FROM attendance WHERE student_pk=? AND time_out IS NOT NULL AND DATE(time_in) BETWEEN ? AND ?');
    $a->execute([$uid, $start, $end]);
    $attDays = (int)$a->fetchColumn();
    $hours = 0.0; $days = [];
    foreach ($logs as $l) { $hours += (float)$l['hours']; $days[$l['log_date']] = true; }
    $loggedDays = count($days);
    return [
        'logs' => $logs,
        'hours' => round($hours, 2),
        'logged_days' => $loggedDays,
        'daily_avg' => $loggedDays ? round($hours / $loggedDays, 2) : 0.0,
        'attendance_days' => $attDays,
    ];
}

/* ---------- dashboard insights ---------- */
function attendance_insights(int $uid): array {
    $set = student_settings($uid);
    $limit = strtotime('1970-01-01 ' . $set['shift_start']) + $set['grace_minutes'] * 60;
    $calc = function (string $month) use ($uid, $limit): array {
        $stmt = db()->prepare("SELECT time_in, break_seconds FROM attendance WHERE student_pk=? AND DATE_FORMAT(time_in,'%Y-%m')=? ORDER BY time_in");
        $stmt->execute([$uid, $month]);
        $total = 0; $ontime = 0; $break = 0; $byDay = [];
        foreach ($stmt->fetchAll() as $r) {
            $d = date('Y-m-d', strtotime($r['time_in']));
            $break += (int)$r['break_seconds'];
            if (isset($byDay[$d])) continue; // first time-in of the day decides punctuality
            $tod = strtotime('1970-01-01 ' . date('H:i:s', strtotime($r['time_in'])));
            $byDay[$d] = $tod <= $limit;
            $total++; if ($tod <= $limit) $ontime++;
        }
        $pct = $total ? (int)round($ontime / $total * 100) : 0;
        return ['days' => $total, 'ontime' => $ontime, 'pct' => $pct, 'late_pct' => $total ? 100 - $pct : 0, 'break' => $break, 'byDay' => $byDay];
    };
    $cur = $calc(date('Y-m'));
    $prev = $calc(date('Y-m', strtotime('first day of last month')));
    $series = [];
    $q = db()->prepare("SELECT DATE(time_in) d, SUM(duration_minutes) m, SUM(break_seconds) b FROM attendance WHERE student_pk=? AND time_in >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(time_in)");
    $q->execute([$uid]);
    $map = [];
    foreach ($q->fetchAll() as $r) $map[$r['d']] = ['m' => (int)$r['m'], 'b' => (int)$r['b']];
    $work = $brk = $ot = $lt = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $work[] = round(($map[$d]['m'] ?? 0) / 60, 2);
        $brk[] = round(($map[$d]['b'] ?? 0) / 60, 2);
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
