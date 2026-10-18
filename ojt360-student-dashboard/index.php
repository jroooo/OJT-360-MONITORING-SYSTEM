<?php
$page_title = 'Dashboard';
$page_scripts = ['dashboard.js'];
require_once 'includes/header.php';

$internship = internship_for($uid);
$totalHours = (float)($internship['required_hours'] ?? 480);
$completedHours = (float)($internship['completed_hours'] ?? 0);
$progress = $totalHours > 0 ? min(100, round(($completedHours / $totalHours) * 100, 1)) : 0;
$daysRemaining = !empty($internship['end_date']) ? max(0, (int)ceil((strtotime($internship['end_date']) - strtotime(date('Y-m-d'))) / 86400)) : 0;

$firstName = explode(' ', trim($user['full_name']))[0];
$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$month = date('Y-m');
$q = db()->prepare("SELECT COUNT(*) c, SUM(title='' OR description='' OR title IS NULL OR description IS NULL) inc FROM daily_logs WHERE student_pk=? AND DATE_FORMAT(log_date,'%Y-%m')=?");
$q->execute([$uid, $month]); $lm = $q->fetch();
$logsThisMonth = (int)$lm['c']; $incomplete = (int)$lm['inc'];
$q = db()->prepare("SELECT COUNT(DISTINCT DATE(time_in)) FROM attendance WHERE student_pk=? AND DATE_FORMAT(time_in,'%Y-%m')=?");
$q->execute([$uid, $month]); $attDays = (int)$q->fetchColumn();
$q = db()->prepare("SELECT COUNT(*) FROM daily_logs WHERE student_pk=? AND title<>'' AND description<>''");
$q->execute([$uid]); $documented = (int)$q->fetchColumn();

$ins = attendance_insights($uid);
$q = db()->prepare("SELECT * FROM daily_logs WHERE student_pk=? ORDER BY log_date DESC, created_at DESC LIMIT 4");
$q->execute([$uid]); $recentLogs = $q->fetchAll();
$q = db()->prepare("SELECT action_text, created_at FROM activity_logs WHERE student_pk=? ORDER BY created_at DESC LIMIT 5");
$q->execute([$uid]); $activities = $q->fetchAll();
$announcement = db()->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 1")->fetch();
$taskStmt = db()->prepare("SELECT t.* FROM tasks t WHERE t.student_pk=? AND t.status<>'completed' ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC LIMIT 4");
$taskStmt->execute([$uid]); $dashboardTasks = $taskStmt->fetchAll();
$wj = db()->prepare("SELECT status FROM journals WHERE student_pk=? AND week_start=? LIMIT 1");
$wj->execute([$uid, date('Y-m-d', strtotime('monday this week'))]); $weekJournal = $wj->fetchColumn();
?>
<div class="page-head">
  <div>
    <span class="eyebrow">OJT TRACKER</span>
    <h1><?= e($greet) ?>, <?= e($firstName) ?><span class="title-dot">.</span></h1>
    <p>Record your attendance, daily tasks, and rendered internship hours.</p>
  </div>
  <div class="page-actions"><a class="btn primary lg" href="daily_logs.php?new=1"><?= icon('plus') ?> Add Daily Entry</a></div>
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
      <button class="btn warn lg" data-track="break" type="button" disabled><span class="btn-ic"><?= icon('coffee') ?></span> <span class="lbl">Take a Break</span></button>
      <button class="btn danger lg" data-track="timeout" type="button" disabled><span class="btn-ic"><?= icon('logout') ?></span> Time Out</button>
    </div>
    <div class="tracker-mini">
      <div><span>Time in</span><strong id="tmIn">—</strong></div>
      <div><span>Break today</span><strong id="tmBreak">0h 00m</strong></div>
      <div><span>Rendered today</span><strong id="tmToday">0h 00m</strong></div>
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
    <div class="insight-info"><span>On-time percentage</span><small><?= e(date('F')) ?></small><strong><?= (int)$ins['cur']['pct'] ?>%</strong><?= trend_badge($ins['cur']['pct'], $ins['prev']['pct'], true) ?></div>
    <svg class="spark" data-series="<?= e(json_encode($ins['series']['ontime'])) ?>" data-color="green" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
  <article class="insight">
    <div class="insight-info"><span>Late percentage</span><small><?= e(date('F')) ?></small><strong><?= (int)$ins['cur']['late_pct'] ?>%</strong><?= trend_badge($ins['cur']['late_pct'], $ins['prev']['late_pct'], false) ?></div>
    <svg class="spark" data-series="<?= e(json_encode($ins['series']['late'])) ?>" data-color="red" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
  <article class="insight">
    <?php $bs = (int)$ins['cur']['break']; ?>
    <div class="insight-info"><span>Total break hours</span><small><?= e(date('F')) ?></small><strong><?= intdiv($bs, 3600) ?>h <?= str_pad((string)intdiv($bs % 3600, 60), 2, '0', STR_PAD_LEFT) ?>m</strong><?= trend_badge(round($bs / 60), round($ins['prev']['break'] / 60), false, ' min') ?></div>
    <svg class="spark" data-series="<?= e(json_encode($ins['series']['break'])) ?>" data-color="amber" viewBox="0 0 160 70" preserveAspectRatio="none"></svg>
  </article>
</div>
<p class="insight-note">On time means timing in by <?= e(date('g:i A', strtotime($ins['settings']['shift_start']))) ?> (+<?= (int)$ins['settings']['grace_minutes'] ?> min grace). You can change this in <a class="text-link" href="settings.php">Settings</a>.</p>

<!-- PROGRESS -->
<div class="section-row"><div><span class="eyebrow">AT A GLANCE</span><h3>Your progress</h3></div><a class="text-link" href="settings.php#goal">Edit goal</a></div>
<div class="grid-3">
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Rendered hours</span><strong><b id="statRendered"><?= number_format($completedHours, 2) ?></b> <em>hrs</em></strong><small id="statRemaining"><?= number_format(max(0, $totalHours - $completedHours), 2) ?> hours remaining</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('target') ?></div><span>Required hours</span><strong><b><?= number_format($totalHours, 0) ?></b> <em>hrs</em></strong><small>OJT requirement</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('journal') ?></div><span>Daily entries</span><strong><b><?= $documented ?></b></strong><small>Days documented</small></article>
</div>
<div class="grid-3 strip">
  <a class="strip-item" href="daily_logs.php"><span>Logs this month</span><b><?= $logsThisMonth ?></b></a>
  <a class="strip-item" href="attendance.php"><span>Attendance days</span><b><?= $attDays ?></b></a>
  <a class="strip-item" href="daily_logs.php?filter=incomplete"><span>Incomplete</span><b class="<?= $incomplete ? 'warn-text' : '' ?>"><?= $incomplete ?></b></a>
</div>

<div class="grid-main">
  <section class="panel progress-panel">
    <div class="panel-head"><div><span class="eyebrow">INTERNSHIP</span><h3>OJT Progress</h3></div><span class="pill green-pill" id="pctPill"><?= e((string)$progress) ?>%</span></div>
    <div class="progress-ring" id="progressRing" style="--progress:<?= e((string)$progress) ?>%"><div><strong id="ringPct"><?= e((string)$progress) ?>%</strong><span>Completed</span></div></div>
    <div class="progress-meta">
      <div><span>Completed</span><strong id="metaDone"><?= number_format($completedHours, 1) ?> hrs</strong></div>
      <div><span>Required</span><strong><?= number_format($totalHours, 0) ?> hrs</strong></div>
      <div><span>Company</span><strong><?= e($internship['company_name'] ?? 'Not assigned') ?></strong></div>
    </div>
    <div class="goal-bar"><div class="goal-top"><strong>Hours goal</strong><b id="goalPct"><?= e((string)$progress) ?>%</b></div><div class="bar"><i id="goalFill" style="width:<?= e((string)$progress) ?>%"></i></div><div class="goal-bottom"><span id="goalDone"><?= number_format($completedHours, 2) ?> rendered</span><span><?= (int)$daysRemaining ?> days left</span></div></div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">DAILY LOGS</span><h3>Recent entries</h3></div><a href="daily_logs.php" class="text-link">View all</a></div>
    <?php if (!$recentLogs): ?><div class="dash-empty">No daily logs yet.<br><small>They appear here automatically when you time in.</small></div><?php endif; ?>
    <div class="mini-list">
      <?php foreach ($recentLogs as $r): $inc = trim((string)$r['title']) === ''; ?>
        <a class="mini-row" href="daily_logs.php?edit=<?= (int)$r['id'] ?>">
          <div class="mini-date"><b><?= e(date('d', strtotime($r['log_date']))) ?></b><span><?= e(date('M', strtotime($r['log_date']))) ?></span></div>
          <div class="mini-main"><strong><?= $inc ? 'Add what you worked on' : e($r['title']) ?></strong><small><?= $r['time_in'] ? e(date('g:i A', strtotime($r['time_in']))) . ($r['time_out'] ? ' – ' . e(date('g:i A', strtotime($r['time_out']))) : ' · in progress') : 'Manual entry' ?></small></div>
          <span class="pill <?= $inc ? 'amber-pill' : 'blue-pill' ?>"><?= $inc ? 'Needs details' : number_format((float)$r['hours'], 2) . ' h' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="grid-3 lower">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">COMPANY TASKS</span><h3>Unfinished tasks</h3></div><a href="tasks.php" class="text-link">View all</a></div>
    <?php if (!$dashboardTasks): ?><div class="dash-empty">No unfinished tasks right now.</div><?php endif; ?>
    <div class="mini-list">
      <?php foreach ($dashboardTasks as $t): $overdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d'); ?>
        <a class="mini-row" href="tasks.php"><div class="mini-check"><?= icon('tasks') ?></div><div class="mini-main"><strong><?= e($t['title']) ?></strong><small><?= e(($t['source'] ?? '') === 'personal' ? 'My task' : ($t['company_name'] ?: 'OJT company')) ?><?= $t['due_date'] ? ' · Due ' . e(date('M d', strtotime($t['due_date']))) : '' ?></small></div><span class="pill <?= $overdue ? 'red-pill' : 'amber-pill' ?>"><?= $overdue ? 'Overdue' : 'Open' ?></span></a>
      <?php endforeach; ?>
    </div>
  </section>
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
<script>window.OJT_DASH = <?= json_encode(['required' => $totalHours, 'completed' => $completedHours], JSON_HEX_TAG) ?>;</script>
<?php require_once 'includes/footer.php'; ?>
