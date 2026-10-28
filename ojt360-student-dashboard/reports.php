<?php
$page_title = 'Reports';
$page_scripts = ['reports.js'];
require_once 'includes/header.php';

$type = ($_GET['type'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['date'] ?? '')) ? (string)$_GET['date'] : date('Y-m-d');
$per = report_period($type, $date);
$data = report_data($uid, $per['start'], $per['end']);
$ins = attendance_insights($uid);
$internship = internship_for($uid);
$required = (float)($internship['required_hours'] ?? 480);
$completed = (float)($internship['completed_hours'] ?? 0);
$pct = $required > 0 ? min(100, round($completed / $required * 100, 1)) : 0;

// last 6 months of rendered hours
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = 0.0;
$q = db()->prepare("SELECT DATE_FORMAT(time_in,'%Y-%m') m, SUM(duration_minutes) mins FROM attendance WHERE student_pk=? AND time_out IS NOT NULL AND time_in >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY m");
$q->execute([$uid]);
foreach ($q->fetchAll() as $r) if (isset($months[$r['m']])) $months[$r['m']] = round(((int)$r['mins']) / 60, 2);
$maxM = max(1, max($months));
$prevDate = date('Y-m-d', strtotime($per['start'] . ($type === 'monthly' ? ' -1 month' : ' -7 days')));
$nextDate = date('Y-m-d', strtotime($per['start'] . ($type === 'monthly' ? ' +1 month' : ' +7 days')));
$qs = fn($t, $d) => 'reports.php?type=' . $t . '&date=' . $d;
$unit = $type === 'monthly' ? 'month' : 'week';
?>
<div class="page-head">
  <div>
    <span class="eyebrow">OJT DOCUMENTATION</span>
    <h1>Reports<span class="title-dot">.</span></h1>
    <p>Review your rendered hours, accomplishments, attendance, and documented OJT activity.</p>
  </div>
</div>

<div class="rep-top">
  <div class="tabs">
    <a class="tab <?= $type === 'weekly' ? 'active' : '' ?>" href="<?= e($qs('weekly', $date)) ?>"><?= icon('calendar') ?> Weekly</a>
    <a class="tab <?= $type === 'monthly' ? 'active' : '' ?>" href="<?= e($qs('monthly', $date)) ?>"><?= icon('calendar') ?> Monthly</a>
  </div>
</div>

<section class="panel rep-select">
  <div><span class="eyebrow">SELECT <?= strtoupper($unit) ?></span><h3><?= ucfirst($type) ?> OJT Report</h3></div>
  <div class="rep-picker">
    <a class="icon-btn" href="<?= e($qs($type, $prevDate)) ?>" aria-label="Previous <?= $unit ?>"><?= icon('chevL') ?></a>
    <label>Choose any date in the <?= $unit ?><input type="date" id="repDate" value="<?= e($date) ?>" data-type="<?= e($type) ?>"></label>
    <a class="icon-btn" href="<?= e($qs($type, $nextDate)) ?>" aria-label="Next <?= $unit ?>"><?= icon('chevR') ?></a>
  </div>
</section>
<div class="period-banner"><span>REPORT PERIOD</span><strong><?= e($per['range']) ?></strong></div>

<div class="grid-4 rep-stats">
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Rendered Hours</span><strong><b><?= number_format($data['hours'], 2) ?></b></strong><small>hours this <?= $unit ?></small></article>
  <article class="stat"><div class="stat-ic"><?= icon('journal') ?></div><span>Logged Days</span><strong><b><?= (int)$data['logged_days'] ?></b></strong><small>days documented</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('activity') ?></div><span>Daily Average</span><strong><b><?= number_format($data['daily_avg'], 2) ?></b></strong><small>hours per logged day</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('check') ?></div><span>Attendance</span><strong><b><?= (int)$data['attendance_days'] ?></b></strong><small>completed days</small></article>
</div>

<section class="panel report-doc" id="reportDoc">
  <div class="report-head">
    <div class="doc-brand"><?= logo_mark() ?><div><span class="eyebrow">OJT360</span><h3><?= ucfirst($type) ?> OJT Accomplishment Report</h3></div></div>
    <div class="report-student"><span>STUDENT</span><strong><?= e($user['full_name']) ?></strong><small><?= e($user['student_no']) ?> ┬╖ <?= e($internship['company_name'] ?? '') ?></small></div>
  </div>
  <p class="report-period-line"><?= e($per['range']) ?></p>
  <?php if (!$data['logs']): ?>
    <div class="empty-state"><div class="empty-icon"><?= icon('file') ?></div><h3>No records for this <?= $unit ?></h3><p>Add daily logs to generate your <?= $type ?> report.</p></div>
  <?php else: ?>
    <div class="table-wrap"><table class="report-table">
      <thead><tr><th>Date</th><th>Time</th><th>Task</th><th>Accomplishments</th><th>Hours</th></tr></thead>
      <tbody>
      <?php foreach ($data['logs'] as $l): ?>
        <tr>
          <td><b><?= e(date('D, M j', strtotime($l['log_date']))) ?></b></td>
          <td><?= $l['time_in'] ? e(date('g:i A', strtotime($l['time_in']))) . ($l['time_out'] ? ' ΓÇô ' . e(date('g:i A', strtotime($l['time_out']))) : '') : 'ΓÇö' ?><?= (int)$l['break_minutes'] ? '<br><small class="muted">' . (int)$l['break_minutes'] . ' min break</small>' : '' ?></td>
          <td><b><?= e($l['title'] ?: 'ΓÇö') ?></b></td>
          <td><?= nl2br(e($l['description'] ?: 'ΓÇö')) ?><?php if (!empty($l['evidence_image'])): ?><br><img class="rep-img" src="<?= e(evidence_url($l['evidence_image'])) ?>" alt="Evidence"><?php endif; ?></td>
          <td class="num"><b><?= number_format((float)$l['hours'], 2) ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="4" class="tr">Total rendered hours</td><td class="num"><b><?= number_format($data['hours'], 2) ?></b></td></tr></tfoot>
    </table></div>
  <?php endif; ?>
  <div class="sign-row"><div><span></span><small>Student signature</small></div><div><span></span><small>Company supervisor</small></div><div><span></span><small>OJT adviser</small></div></div>
</section>

<section class="panel export-bar">
  <div><strong>Export <?= ucfirst($type) ?> Report</strong><p class="muted small">Download or print your documentation.</p></div>
  <div class="export-btns">
    <a class="btn primary" href="api/report_export.php?format=word&type=<?= e($type) ?>&date=<?= e($date) ?>"><?= icon('file') ?> Word</a>
    <button class="btn secondary" type="button" id="printReport"><?= icon('print') ?> PDF / Print</button>
    <a class="btn secondary" href="api/report_export.php?format=csv&type=<?= e($type) ?>&date=<?= e($date) ?>"><?= icon('table') ?> CSV</a>
  </div>
</section>

<div class="grid-main">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">ATTENDANCE</span><h3>Monthly work hours</h3></div><span class="muted small">Last 6 months</span></div>
    <div class="bars">
      <?php foreach ($months as $m => $h): ?>
        <div class="bar-col"><span class="bar-val"><?= number_format($h, 1) ?>h</span><div class="bar-track"><i style="height:<?= max(3, round($h / $maxM * 100)) ?>%"></i></div><span class="bar-lbl"><?= e(date('M', strtotime($m . '-01'))) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">OVERALL</span><h3>Internship overview</h3></div><span class="pill green-pill"><?= $pct ?>%</span></div>
    <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
    <div class="ov-list">
      <div><span>Rendered</span><b><?= number_format($completed, 2) ?> h</b></div>
      <div><span>Required</span><b><?= number_format($required, 0) ?> h</b></div>
      <div><span>Remaining</span><b><?= number_format(max(0, $required - $completed), 2) ?> h</b></div>
      <div><span>On-time rate (this month)</span><b><?= (int)$ins['cur']['pct'] ?>%</b></div>
      <div><span>Break time (this month)</span><b><?= e(seconds_to_hm((int)$ins['cur']['break'])) ?></b></div>
    </div>
  </section>
</div>
<?php require_once 'includes/footer.php'; ?>
