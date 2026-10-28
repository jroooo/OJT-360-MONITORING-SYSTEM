<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
$uid = (int)$_SESSION['student_pk'];
$user = current_user();
$type = ($_GET['type'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date'] ?? '')) ? (string)$_GET['date'] : date('Y-m-d');
$format = ($_GET['format'] ?? 'csv') === 'word' ? 'word' : 'csv';
$per = report_period($type, $date);
$data = report_data($uid, $per['start'], $per['end']);
$slug = $type . '-ojt-report-' . $per['start'];

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Date', 'Time In', 'Time Out', 'Break (min)', 'Task', 'Accomplishments', 'Hours']);
    foreach ($data['logs'] as $l) {
        fputcsv($out, [$l['log_date'], $l['time_in'] ? date('h:i A', strtotime($l['time_in'])) : '', $l['time_out'] ? date('h:i A', strtotime($l['time_out'])) : '', (int)$l['break_minutes'], $l['title'], $l['description'], number_format((float)$l['hours'], 2, '.', '')]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Total hours', '', '', '', '', '', number_format($data['hours'], 2, '.', '')]);
    fclose($out);
    exit;
}

/* Word (.doc): HTML that Microsoft Word opens natively, photos embedded as data URIs */
header('Content-Type: application/msword; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $slug . '.doc"');
$internship = internship_for($uid);
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><title>' . e(ucfirst($type)) . ' OJT Report</title>';
echo '<style>@page{size:A4;margin:2cm}body{font-family:Calibri,Arial,sans-serif;font-size:11pt;color:#111}h1{font-size:20pt;margin:0}h2{font-size:12pt;color:#0369a1;margin:2pt 0 14pt}table{border-collapse:collapse;width:100%}th{background:#e6f5fd;text-align:left;font-size:10pt}td,th{border:1px solid #9aa7b4;padding:5pt;vertical-align:top;font-size:10pt}.r{text-align:right}img{max-width:220px}.sig td{border:0;padding-top:34pt;text-align:center;font-size:9pt}</style></head><body>';
echo '<h1>OJT360 ' . e(ucfirst($type)) . ' Accomplishment Report</h1><h2>' . e($per['range']) . '</h2>';
echo '<p><b>Student:</b> ' . e($user['full_name']) . ' (' . e($user['student_no']) . ')<br><b>Company:</b> ' . e($internship['company_name'] ?? 'ΓÇö') . '<br><b>Total rendered hours:</b> ' . number_format($data['hours'], 2) . ' &nbsp; <b>Days logged:</b> ' . (int)$data['logged_days'] . ' &nbsp; <b>Daily average:</b> ' . number_format($data['daily_avg'], 2) . '</p>';
echo '<table><tr><th>Date</th><th>Time</th><th>Task</th><th>Accomplishments</th><th class="r">Hours</th></tr>';
foreach ($data['logs'] as $l) {
    $time = $l['time_in'] ? date('g:i A', strtotime($l['time_in'])) . ($l['time_out'] ? ' ΓÇô ' . date('g:i A', strtotime($l['time_out'])) : '') : 'ΓÇö';
    $img = '';
    if (!empty($l['evidence_image'])) {
        $p = __DIR__ . '/../uploads/daily_logs/' . basename($l['evidence_image']);
        if (is_file($p) && filesize($p) < 2_500_000) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($p);
            $img = '<br><img src="data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($p)) . '" width="220">';
        }
    }
    echo '<tr><td>' . e(date('D, M j, Y', strtotime($l['log_date']))) . '</td><td>' . e($time) . '</td><td>' . e($l['title']) . '</td><td>' . nl2br(e($l['description'])) . $img . '</td><td class="r">' . number_format((float)$l['hours'], 2) . '</td></tr>';
}
if (!$data['logs']) echo '<tr><td colspan="5">No records for this period.</td></tr>';
echo '<tr><td colspan="4" class="r"><b>Total rendered hours</b></td><td class="r"><b>' . number_format($data['hours'], 2) . '</b></td></tr></table>';
echo '<table class="sig"><tr><td>______________________<br>Student signature</td><td>______________________<br>Company supervisor</td><td>______________________<br>OJT adviser</td></tr></table></body></html>';
