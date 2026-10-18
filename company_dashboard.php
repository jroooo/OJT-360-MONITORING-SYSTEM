<?php
require_once __DIR__ . '/includes/bootstrap.php';
$company = require_company();
$cid = $company['id'];
$stats = company_stats($cid);
$pdo = db();

$st = $pdo->prepare("SELECT s.*, (SELECT e.status FROM evaluations e WHERE e.student_id = s.id AND e.company_id = s.company_id ORDER BY e.id DESC LIMIT 1) AS eval_status
                     FROM students s WHERE s.company_id = ? ORDER BY s.last_name, s.first_name LIMIT 6");
$st->execute([$cid]);
$interns = $st->fetchAll();

$st = $pdo->prepare("SELECT a.time_in, a.time_out, s.first_name, s.last_name, s.avatar FROM attendance a JOIN students s ON s.id = a.student_id
                     WHERE a.company_id = ? AND a.date = ? ORDER BY a.id DESC LIMIT 6");
$st->execute([$cid, date('Y-m-d')]);
$todayLog = $st->fetchAll();

$h = (int)date('G');
$greet = $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
$firstName = $company['first'] !== '' ? $company['first'] : $company['representative'];

$pageKey = 'dashboard'; $pageTitle = 'Dashboard'; $eyebrow = 'COMPANY OVERVIEW';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="page-head">
  <div>
    <div class="eyebrow">COMPANY OVERVIEW</div>
    <h1 class="title"><?= e($greet) ?>, <?= e($firstName) ?><span class="dotc">.</span></h1>
    <p class="sub"><?= e(date('l, F j, Y')) ?> &middot; Monitor your interns, review their progress and manage assignments in one place.</p>
  </div>
  <a class="btn dark" href="assigned_students.php?add=1"><i class="fa-solid fa-user-plus"></i> Add Intern</a>
</div>

<section class="hero">
  <div>
    <span class="tag"><?= e($company['name']) ?></span>
    <h2>Internship supervision at a glance</h2>
    <p>Track intern hours, attendance and evaluations for your company.</p>
  </div>
  <div class="row-actions">
    <a class="btn light" href="assigned_students.php"><i class="fa-solid fa-user-group"></i> View interns</a>
    <a class="btn ghost" href="reports.php"><i class="fa-solid fa-chart-line"></i> View reports</a>
  </div>
</section>

<section class="stats" aria-label="Key metrics">
  <div class="stat"><div class="ic"><i class="fa-solid fa-user-group"></i></div><b><?= (int)$stats['interns'] ?></b><span>Active interns</span></div>
  <div class="stat"><div class="ic warn"><i class="fa-solid fa-clipboard-check"></i></div><b><?= (int)$stats['pending'] ?></b><span>Pending evaluations</span></div>
  <div class="stat"><div class="ic ok"><i class="fa-solid fa-chart-line"></i></div><b><?= (int)$stats['progress'] ?>%</b><span>Average completion</span>
    <div class="bar" style="margin-top:8px"><i style="width:<?= (int)$stats['progress'] ?>%"></i></div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-clock"></i></div><b><?= (int)$stats['today'] ?></b><span>Checked in today</span></div>
</section>

<div class="grid-2">
  <section class="card">
    <div class="card-head"><div><h2>Intern progress</h2><p>Rendered hours against required hours</p></div><a class="btn ghost sm" href="assigned_students.php">View all</a></div>
    <?php if (!$interns): ?>
      <div class="empty"><i class="fa-solid fa-user-group"></i><b>No interns yet</b>Add your first intern to start tracking hours.<div class="row-actions center"><a class="btn" href="assigned_students.php?add=1">Add Intern</a></div></div>
    <?php else: ?>
      <div class="table-wrap"><table>
        <thead><tr><th>Intern</th><th>Progress</th><th>Evaluation</th></tr></thead>
        <tbody>
        <?php foreach ($interns as $s): $p = pct((int)$s['completed_hours'], (int)$s['required_hours']); ?>
          <tr>
            <td><div class="who"><span class="avatar sm"><?= e($s['avatar'] ?: initials($s['first_name'], $s['last_name'])) ?></span><div><b><?= e($s['first_name'] . ' ' . $s['last_name']) ?></b><small><?= e($s['program']) ?></small></div></div></td>
            <td><div class="bar"><i style="width:<?= $p ?>%"></i></div><small class="muted"><?= (int)$s['completed_hours'] ?> / <?= (int)$s['required_hours'] ?> hrs (<?= $p ?>%)</small></td>
            <td><span class="pill <?= ($s['eval_status'] ?? '') === 'Completed' ? 'ok' : 'warn' ?>"><?= e(($s['eval_status'] ?? '') === 'Completed' ? 'Completed' : 'Pending') ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><div><h2>Today's attendance</h2><p><?= e(date('F j, Y')) ?></p></div><a class="btn ghost sm" href="dynamic.php">Manage</a></div>
    <?php if (!$todayLog): ?>
      <div class="empty"><i class="fa-regular fa-clock"></i><b>No check-ins yet today</b>Records appear here once an intern is timed in.</div>
    <?php else: foreach ($todayLog as $l): ?>
      <div class="who" style="padding:9px 0;border-top:1px solid var(--line)">
        <span class="avatar sm"><?= e($l['avatar'] ?: initials($l['first_name'], $l['last_name'])) ?></span>
        <div><b><?= e($l['first_name'] . ' ' . $l['last_name']) ?></b><small>In <?= e(date('g:i A', strtotime($l['time_in']))) ?><?= $l['time_out'] ? ' &middot; Out ' . e(date('g:i A', strtotime($l['time_out']))) : ' &middot; still on duty' ?></small></div>
      </div>
    <?php endforeach; endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
