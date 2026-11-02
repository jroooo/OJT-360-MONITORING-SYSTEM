<?php
$page_title = 'Dashboard';
$page_scripts = ['dashboard.js'];
require_once 'includes/header.php';

$internship = internship_for($uid);
$totalHours = (float)($internship['required_hours'] ?? 480);
$daysRemaining = !empty($internship['end_date']) ? max(0, (int)ceil((strtotime($internship['end_date']) - strtotime(date('Y-m-d'))) / 86400)) : 0;
$companyStmt = db()->prepare("SELECT company_name FROM internships WHERE student_pk=? AND NULLIF(TRIM(company_name),'') IS NOT NULL ORDER BY CASE WHEN status='active' THEN 0 ELSE 1 END, id DESC LIMIT 1");
$companyStmt->execute([$uid]);
$assignedCompany = $companyStmt->fetchColumn() ?: ($internship['company_name'] ?? 'Not assigned');

$firstName = explode(' ', trim($user['full_name']))[0];
$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$month = date('Y-m');
$today = date('Y-m-d');
$settings = student_settings($uid);
$graceSeconds = (int)$settings['grace_minutes'] * 60;
$attendanceStmt = db()->prepare(
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
     WHERE a.student_pk = ?
     ORDER BY a.time_in"
);
$attendanceStmt->execute([$uid]);
$allHoursByDate = $monthHoursByDate = $todayHoursByDate = $punctualByMonth = [];
$monthShiftCounts = $monthOnTimeCounts = [];
$todayLastTimeIn = null;
foreach ($attendanceStmt->fetchAll() as $attendanceRow) {
    $actualStart = strtotime((string)$attendanceRow['actual_time_in']) ?: strtotime((string)$attendanceRow['time_in']);
    $actualDate = date('Y-m-d', $actualStart);
    $actualMonth = date('Y-m', $actualStart);
    $scheduledStart = strtotime((string)$attendanceRow['time_in']);
    $isOnTime = $actualStart <= $scheduledStart + $graceSeconds;
    $monthShiftCounts[$actualMonth] = ($monthShiftCounts[$actualMonth] ?? 0) + 1;
    $monthOnTimeCounts[$actualMonth] = ($monthOnTimeCounts[$actualMonth] ?? 0) + ($isOnTime ? 1 : 0);
    $punctualByMonth[$actualMonth][$actualDate] = ($punctualByMonth[$actualMonth][$actualDate] ?? true) && $isOnTime;

    if (!empty($attendanceRow['time_out'])) {
        $checkoutDate = date('Y-m-d', strtotime((string)$attendanceRow['time_out']));
        $checkoutMonth = date('Y-m', strtotime((string)$attendanceRow['time_out']));
        $sessionHours = min(4, (int)floor(session_seconds($attendanceRow)['net'] / 3600));
        $allHoursByDate[$checkoutDate] = min(8, ($allHoursByDate[$checkoutDate] ?? 0) + $sessionHours);
        $monthHoursByDate[$checkoutMonth][$checkoutDate] = min(8, ($monthHoursByDate[$checkoutMonth][$checkoutDate] ?? 0) + $sessionHours);
        if ($checkoutDate === $today) {
            $todayHoursByDate[$checkoutDate] = min(8, ($todayHoursByDate[$checkoutDate] ?? 0) + $sessionHours);
            if ($todayLastTimeIn === null || strtotime((string)$attendanceRow['time_out']) > $todayLastTimeIn['checkout_ts']) {
                $todayLastTimeIn = [
                    'checkout_ts' => strtotime((string)$attendanceRow['time_out']),
                    'label' => date('h:i A', $actualStart),
                    'time_out' => date('h:i A', strtotime((string)$attendanceRow['time_out'])),
                ];
            }
        }
    }
}
$completedHours = min($totalHours, (float)array_sum($allHoursByDate));
$progress = $totalHours > 0 ? min(100, round(($completedHours / $totalHours) * 100, 1)) : 0;
$attDays = count($punctualByMonth[$month] ?? []);
$previousMonth = date('Y-m', strtotime('first day of last month'));
$currentShiftCount = (int)($monthShiftCounts[$month] ?? 0);
$previousShiftCount = (int)($monthShiftCounts[$previousMonth] ?? 0);
$currentOnTime = $currentShiftCount ? (int)round(($monthOnTimeCounts[$month] / $currentShiftCount) * 100) : 0;
$previousOnTime = $previousShiftCount ? (int)round(($monthOnTimeCounts[$previousMonth] / $previousShiftCount) * 100) : 0;
$currentMonthHours = (int)array_sum($monthHoursByDate[$month] ?? []);
$previousMonthHours = (int)array_sum($monthHoursByDate[$previousMonth] ?? []);
$dashboardWorkSeries = $dashboardOnTimeSeries = $dashboardLateSeries = [];
for ($dayOffset = 13; $dayOffset >= 0; $dayOffset--) {
    $seriesDate = date('Y-m-d', strtotime('-' . $dayOffset . ' days'));
    $dashboardWorkSeries[] = (int)($monthHoursByDate[date('Y-m', strtotime($seriesDate))][$seriesDate] ?? 0);
    $dayStatus = $punctualByMonth[date('Y-m', strtotime($seriesDate))][$seriesDate] ?? null;
    $dashboardOnTimeSeries[] = $dayStatus === null ? null : ($dayStatus ? 100 : 0);
    $dashboardLateSeries[] = $dayStatus === null ? null : ($dayStatus ? 0 : 100);
}
$todayCompletedHours = (int)($todayHoursByDate[$today] ?? 0);
$todayLastTimeInLabel = $todayLastTimeIn['label'] ?? null;
$todayLastTimeOutLabel = $todayLastTimeIn['time_out'] ?? null;
$q = db()->prepare("SELECT action_text, created_at FROM activity_logs WHERE student_pk=? ORDER BY created_at DESC LIMIT 5");
$q->execute([$uid]); $activities = $q->fetchAll();
$announcement = db()->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 1")->fetch();
$wj = db()->prepare("SELECT status FROM journals WHERE student_pk=? AND week_start=? LIMIT 1");
$wj->execute([$uid, date('Y-m-d', strtotime('monday this week'))]); $weekJournal = $wj->fetchColumn();
?>
<div class="page-head">
  <div>
    <span class="eyebrow">OJT TRACKER</span>
    <h1><?= e($greet) ?>, <?= e($firstName) ?><span class="title-dot">.</span></h1>
    <p>Stay informed about your attendance and monitor your progress toward completing your internship hours.</p>
  </div>
</div>

<!-- LIVE TRACKER -->
<section class="tracker-card" id="trackerCard" data-state="<?= e($trackerState['status']) ?>">
  <div class="tracker-main">
    <span class="eyebrow">TODAY'S ATTENDANCE</span>
    <h2><?= e(date('D, F j, Y')) ?></h2>
    <p class="tracker-sub" id="trackerSub">You haven't timed in yet.</p>
    <div class="tracker-clock" aria-live="off"><b id="liveClock">00:00:00</b><span id="liveClockLabel">Ready when you are</span></div>
    <div class="tracker-btns">
      <button class="btn success lg" data-track="timein" type="button"><span class="btn-ic"><?= icon('login') ?></span> Time In</button>
      <button class="btn danger lg" data-track="timeout" type="button" disabled><span class="btn-ic"><?= icon('logout') ?></span> Time Out</button>
    </div>
    <div class="tracker-mini">
      <div><span>Time in</span><strong id="tmIn">—</strong></div>
      <div><span>Time out</span><strong id="tmOut">—</strong></div>
      <div><span>Today (8h total)</span><strong id="tmToday">0h 00m / 8h</strong></div>
    </div>
  </div>
  <div class="tracker-viz">
    <div class="viz-head"><span class="viz-state" id="vizState"><i class="live-dot"></i><em>Idle</em></span><span class="viz-note" id="vizNote">Scan the company QR to start your timer.</span></div>
    <canvas id="waveCanvas" height="170" aria-label="Live work activity wave"></canvas>
    <div class="timeline-mini" id="timelineMini"></div>
  </div>
</section>

<!-- INSIGHTS (graphs / waves) -->
<span class="eyebrow section-label">INSIGHTS</span>
<div class="grid-3 insights">
  <article class="insight">
    <div class="insight-info"><span>On-time percentage</span><small><?= e(date('F')) ?> · <?= $currentShiftCount ?> recorded shifts</small><strong><?= $currentOnTime ?>%</strong><?= trend_badge($currentOnTime, $previousOnTime, true) ?></div>
    <svg class="spark" data-series="<?= e(json_encode($dashboardOnTimeSeries)) ?>" data-color="green" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
  <article class="insight">
    <div class="insight-info"><span>Late percentage</span><small><?= e(date('F')) ?> · <?= $currentShiftCount ?> recorded shifts</small><strong><?= $currentShiftCount ? 100 - $currentOnTime : 0 ?>%</strong><?= trend_badge($currentShiftCount ? 100 - $currentOnTime : 0, $previousShiftCount ? 100 - $previousOnTime : 0, false) ?></div>
    <svg class="spark" data-series="<?= e(json_encode($dashboardLateSeries)) ?>" data-color="red" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
  <article class="insight">
    <div class="insight-info"><span>Total hours</span><small><?= e(date('F')) ?> · full hours credited</small><strong><?= $currentMonthHours ?>h 00m</strong><?= trend_badge($currentMonthHours, $previousMonthHours, true, ' h') ?></div>
    <svg class="spark" data-series="<?= e(json_encode($dashboardWorkSeries)) ?>" data-color="amber" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
</div>
<!-- PROGRESS -->
<div class="section-row"><div><span class="eyebrow">AT A GLANCE</span><h3>Your progress</h3></div><a class="text-link" href="settings.php#goal">Edit goal</a></div>
<div class="grid-2">
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Rendered hours</span><strong><b id="statRendered"><?= number_format($completedHours, 0) ?></b> <em>hrs</em></strong><small id="statRemaining"><?= number_format(max(0, $totalHours - $completedHours), 0) ?> hours remaining</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('target') ?></div><span>Required hours</span><strong><b><?= number_format($totalHours, 0) ?></b> <em>hrs</em></strong><small>OJT requirement</small></article>
</div>
<div class="grid-3 strip">
  <a class="strip-item" style="grid-column:1 / -1" href="attendance.php"><span>Attendance days</span><b><?= $attDays ?></b></a>
</div>

<div class="grid-main">
  <section class="panel progress-panel" style="grid-column:1 / -1">
    <div class="panel-head"><div><span class="eyebrow">INTERNSHIP</span><h3>OJT Progress</h3></div><span class="pill green-pill" id="pctPill"><?= e((string)$progress) ?>%</span></div>
    <div class="progress-ring" id="progressRing" style="--progress:<?= e((string)$progress) ?>%"><div><strong id="ringPct"><?= e((string)$progress) ?>%</strong><span>Completed</span></div></div>
    <div class="progress-meta">
      <div><span>Completed</span><strong id="metaDone"><?= number_format($completedHours, 0) ?> hrs</strong></div>
      <div><span>Required</span><strong><?= number_format($totalHours, 0) ?> hrs</strong></div>
      <div><span>Company</span><strong><?= e($assignedCompany) ?></strong></div>
    </div>
    <div class="goal-bar"><div class="goal-top"><strong>Hours goal</strong><b id="goalPct"><?= e((string)$progress) ?>%</b></div><div class="bar"><i id="goalFill" style="width:<?= e((string)$progress) ?>%"></i></div><div class="goal-bottom"><span id="goalDone"><?= number_format($completedHours, 0) ?> rendered</span><span><?= (int)$daysRemaining ?> days left</span></div></div>
  </section>
</div>

<div class="grid-2 lower">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">ACTIVITY</span><h3>Recent activity</h3></div><a href="notifications.php" class="text-link">View all</a></div>
    <?php if (!$activities): ?><div class="dash-empty">No activity yet.</div><?php endif; ?>
    <div class="mini-list">
      <?php foreach ($activities as $a): ?><div class="mini-row"><div class="mini-check dot"></div><div class="mini-main"><strong><?= e($a['action_text']) ?></strong><small><?= e(date('M d, Y · h:i A', strtotime($a['created_at']))) ?></small></div></div><?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">ADVISER</span><h3>Announcement</h3></div></div>
    <?php if ($announcement): ?>
      <span class="pill blue-pill"><?= e($announcement['category']) ?></span>
      <h4 class="ann-title"><?= e($announcement['title']) ?></h4>
      <p class="muted"><?= nl2br(e($announcement['message'])) ?></p>
      <small class="muted"><?= e(date('M d, Y', strtotime($announcement['created_at']))) ?></small>
    <?php else: ?><div class="dash-empty">No announcements.</div><?php endif; ?>
    <div class="journal-nudge"><span class="pill <?= $weekJournal === 'submitted' ? 'green-pill' : 'amber-pill' ?>">Weekly journal: <?= $weekJournal ? e(ucfirst($weekJournal)) : 'Not started' ?></span><a class="text-link" href="journal.php">Open Journal</a></div>
  </section>
</div>
<script>window.OJT_DASH = <?= json_encode(['required' => $totalHours, 'completed' => $completedHours, 'todaySeconds' => $todayCompletedHours * 3600, 'lastTimeIn' => $todayLastTimeInLabel, 'lastTimeOut' => $todayLastTimeOutLabel], JSON_HEX_TAG) ?>;</script>
<?php require_once 'includes/footer.php'; ?>
