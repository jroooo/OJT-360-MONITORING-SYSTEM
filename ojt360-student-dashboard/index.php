<?php
$page_title = 'Student Dashboard';
require_once 'includes/header.php';

$uid = (int)$user['id'];
$intern = db()->prepare('SELECT i.*, c.name company_name, c.address company_address
                         FROM internships i JOIN companies c ON c.id=i.company_id
                         WHERE i.user_id=? ORDER BY i.id DESC LIMIT 1');
$intern->execute([$uid]);
$internship = $intern->fetch();

$totalHours = (float)($internship['required_hours'] ?? 480);
$completedHours = (float)($internship['completed_hours'] ?? 0);
$progress = $totalHours > 0 ? min(100, round(($completedHours / $totalHours) * 100, 1)) : 0;
$daysRemaining = !empty($internship['end_date']) ? max(0, (int)ceil((strtotime($internship['end_date']) - strtotime(date('Y-m-d'))) / 86400)) : 0;

$weekStmt = db()->prepare("SELECT COUNT(*) FROM journals WHERE user_id=? AND YEARWEEK(week_start,1)=YEARWEEK(CURDATE(),1) AND status='submitted'");
$weekStmt->execute([$uid]);
$journalDone = (int)$weekStmt->fetchColumn() > 0;

$attendanceStmt = db()->prepare("SELECT * FROM attendance WHERE user_id=? ORDER BY time_in DESC LIMIT 1");
$attendanceStmt->execute([$uid]);
$latestAttendance = $attendanceStmt->fetch();
$activeSession = $latestAttendance && empty($latestAttendance['time_out']) ? $latestAttendance : null;

$recent = db()->prepare("SELECT action_text, created_at FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recent->execute([$uid]);
$activities = $recent->fetchAll();

$announcement = db()->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 1")->fetch();
$taskStmt=db()->prepare("SELECT t.*, COALESCE(c.name,'Company') company_name FROM tasks t LEFT JOIN companies c ON c.id=t.company_id WHERE (t.user_id=? OR (t.user_id IS NULL AND t.company_id=(SELECT company_id FROM internships WHERE user_id=? ORDER BY id DESC LIMIT 1))) AND t.status<>'completed' ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC LIMIT 4"); $taskStmt->execute([$uid,$uid]); $dashboardTasks=$taskStmt->fetchAll();
?>
<section class="welcome-card">
    <div>
        <div class="eyebrow light">WELCOME BACK</div>
        <h2>Welcome back!</h2>
        <p>Track your OJT progress, attendance, journals, and requirements in one place.</p>
    </div>
    <div class="welcome-actions">
        <a class="btn light-btn" href="attendance.php">Scan QR / Time In</a>
        <a class="btn outline-light" href="journal.php">Open Journal</a>
    </div>
</section>

<div class="stats-grid">
    <article class="stat-card ojt-hours-card<?= $activeSession ? ' is-live' : '' ?>" data-ojt-hours-card<?= $activeSession ? ' data-time-in="'.e($activeSession['time_in']).'" data-completed-hours="'.e((string)$completedHours).'" data-required-hours="'.e((string)$totalHours).'"' : '' ?>>
        <div class="stat-icon purple">◷</div>
        <div class="stat-content">
            <span>OJT Hours</span>
            <strong data-ojt-total><?= e(number_format($completedHours, 1)) ?> / <?= e(number_format($totalHours, 0)) ?> hrs</strong>
            <small data-ojt-progress><?= e($progress) ?>% completed</small>
            <?php if ($activeSession): ?><div class="ojt-live-time"><span class="live-dot"></span><span>Time in running</span><strong data-ojt-live>0h 00m</strong></div><?php endif; ?>
        </div>
        <?php if ($activeSession): ?><div class="ojt-wave" aria-label="Live OJT time is running"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div><?php endif; ?>
    </article>
    <article class="stat-card">
        <div class="stat-icon blue">▤</div>
        <div><span>Weekly Journal</span><strong><?= $journalDone ? 'Submitted' : 'Not Submitted' ?></strong><small><?= $journalDone ? 'This week is complete' : 'Remember to submit this week' ?></small></div>
    </article>
    <article class="stat-card">
        <div class="stat-icon green">✓</div>
        <div><span>Attendance</span><strong><?= $latestAttendance && !$latestAttendance['time_out'] ? 'Timed In' : 'Up to date' ?></strong><small><?= $latestAttendance ? e(date('M d, Y', strtotime($latestAttendance['time_in']))) : 'No records yet' ?></small></div>
    </article>
    <article class="stat-card">
        <div class="stat-icon orange">⌛</div>
        <div><span>Days Remaining</span><strong><?= e((string)$daysRemaining) ?></strong><small><?= e($internship['end_date'] ?? 'Not set') ?></small></div>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel progress-panel">
        <div class="panel-head"><div><span class="eyebrow">INTERNSHIP</span><h3>OJT Progress</h3></div><span class="pill green-pill"><?= e($progress) ?>%</span></div>
        <div class="progress-ring" style="--progress: <?= e($progress) ?>%"><div><strong><?= e($progress) ?>%</strong><span>Completed</span></div></div>
        <div class="progress-meta">
            <div><span>Completed</span><strong><?= e(number_format($completedHours, 1)) ?> hrs</strong></div>
            <div><span>Required</span><strong><?= e(number_format($totalHours, 0)) ?> hrs</strong></div>
            <div><span>Company</span><strong><?= e($internship['company_name'] ?? 'Not assigned') ?></strong></div>
        </div>
    </section>

    <section class="panel quick-panel">
        <div class="panel-head"><div><span class="eyebrow">QUICK ACTION</span><h3>Attendance</h3></div><span class="status-dot"></span></div>
        <?php if ($latestAttendance && !$latestAttendance['time_out']): ?>
            <div class="active-time">
                <strong>You are currently timed in.</strong>
                <span>Started <?= e(date('h:i A', strtotime($latestAttendance['time_in']))) ?></span>
            </div>
            <a class="btn danger wide" href="attendance.php">Time Out</a>
        <?php else: ?>
            <p class="muted">Use your company's QR code to securely time in and out.</p>
            <a class="btn primary wide" href="attendance.php">Scan Company QR</a>
        <?php endif; ?>
    </section>
</div>

<div class="dashboard-grid lower">
    <section class="panel">
        <div class="panel-head"><div><span class="eyebrow">ACTIVITY</span><h3>Recent Activity</h3></div><a href="notifications.php" class="text-link">View all</a></div>
        <div class="activity-list">
            <?php if (!$activities): ?><div class="empty">No activity yet.</div><?php endif; ?>
            <?php foreach ($activities as $a): ?>
                <div class="activity-item"><div class="activity-icon">•</div><div><strong><?= e($a['action_text']) ?></strong><span><?= e(date('M d, Y · h:i A', strtotime($a['created_at']))) ?></span></div></div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel announcement">
        <div class="panel-head"><div><span class="eyebrow">ADVISER</span><h3>Announcement</h3></div></div>
        <?php if ($announcement): ?>
            <span class="pill blue-pill"><?= e($announcement['category']) ?></span>
            <h4><?= e($announcement['title']) ?></h4>
            <p><?= nl2br(e($announcement['message'])) ?></p>
            <small><?= e(date('M d, Y', strtotime($announcement['created_at']))) ?></small>
        <?php else: ?><div class="empty">No announcements.</div><?php endif; ?>
    </section>
</div>

<div class="dashboard-grid lower">
<section class="panel">
<div class="panel-head"><div><span class="eyebrow">COMPANY TASKS</span><h3>Unfinished Tasks</h3></div><a href="tasks.php" class="text-link">View all</a></div>
<div class="dashboard-task-list">
<?php if(!$dashboardTasks): ?><div class="empty">No unfinished tasks right now.</div><?php else: foreach($dashboardTasks as $t): $overdue=!empty($t['due_date'])&&$t['due_date']<date('Y-m-d'); ?>
<div class="dashboard-task"><div class="task-mini-icon">✓</div><div><strong><?=e($t['title'])?></strong><span><?=e($t['company_name'])?><?php if($t['due_date']): ?> · Due <?=e(date('M d',strtotime($t['due_date'])))?><?php endif;?></span></div><span class="pill <?=$overdue?'red-pill':'orange-pill'?>"><?=$overdue?'Overdue':'Open'?></span></div>
<?php endforeach; endif; ?></div></section>
<section class="panel"><div class="panel-head"><div><span class="eyebrow">JOURNAL</span><h3>Weekly Documentation</h3></div><a href="journal.php" class="text-link">Open Journal</a></div><p class="muted dashboard-copy">Keep your weekly drafts organized in the timeline, then use the final compilation to prepare the complete document for printing or PDF.</p><a class="btn secondary wide" href="journal_compilation.php">Open Final Compilation</a></section>
</div>
<?php require_once 'includes/footer.php'; ?>
