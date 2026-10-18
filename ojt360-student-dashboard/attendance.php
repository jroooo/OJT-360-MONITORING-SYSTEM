<?php
$page_title = 'Attendance';
$page_scripts = ['attendance.js'];
require_once 'includes/header.php';
$internship = internship_for($uid);
$required = (float)($internship['required_hours'] ?? 480);
$completed = (float)($internship['completed_hours'] ?? 0);
?>
<div class="page-head">
  <div>
    <span class="eyebrow">TIME TRACKING</span>
    <h1>Attendance<span class="title-dot">.</span></h1>
    <p>Time in with your company QR, take breaks, and review your calendar, history, and timeline.</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="daily_logs.php"><?= icon('logs') ?> Daily Logs</a>
    <a class="btn secondary" href="api/attendance.php?action=export"><?= icon('download') ?> Export CSV</a>
  </div>
</div>

<section class="att-bar" id="trackerCard" data-state="<?= e($trackerState['status']) ?>">
  <div class="att-status">
    <span class="att-dot"></span>
    <div><strong id="attStatusText">Not timed in</strong><small id="attStatusSub">Scan your company QR to begin.</small></div>
    <b class="att-clock" id="attClock">00:00:00</b>
  </div>
  <div class="tracker-btns compact">
    <button class="btn success" data-track="timein" type="button"><span class="btn-ic"><?= icon('login') ?></span> Time In</button>
    <button class="btn warn" data-track="break" type="button" disabled><span class="btn-ic"><?= icon('coffee') ?></span> <span class="lbl">Take a Break</span></button>
    <button class="btn danger" data-track="timeout" type="button" disabled><span class="btn-ic"><?= icon('logout') ?></span> Time Out</button>
  </div>
</section>

<div class="grid-3 att-metrics">
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Today</span><strong id="mToday">0h 00m</strong><small>Rendered today</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('target') ?></div><span>Total achieved</span><strong id="mTotal"><?= number_format($completed, 2) ?> h</strong><small>OJT hours recorded</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('trend') ?></div><span>Remaining</span><strong id="mRemain"><?= number_format(max(0, $required - $completed), 2) ?> h</strong><small>Until <?= number_format($required, 0) ?> required hours</small></article>
</div>

<section class="panel att-panel">
  <div class="att-head">
    <div class="tabs" role="tablist">
      <button class="tab active" data-view="calendar" role="tab"><?= icon('calendar') ?> Calendar</button>
      <button class="tab" data-view="history" role="tab"><?= icon('table') ?> History</button>
      <button class="tab" data-view="timeline" role="tab"><?= icon('activity') ?> Timeline</button>
    </div>
    <div class="month-nav">
      <button class="icon-btn" id="monthPrev" aria-label="Previous month"><?= icon('chevL') ?></button>
      <div class="month-title"><h3 id="monthTitle">—</h3><button class="link-btn" id="monthToday">Today</button></div>
      <button class="icon-btn" id="monthNext" aria-label="Next month"><?= icon('chevR') ?></button>
    </div>
  </div>

  <div id="viewCalendar" class="att-view">
    <div class="cal-legend"><span><i class="lgd both"></i>Log + Attendance</span><span><i class="lgd att"></i>Attendance</span><span><i class="lgd log"></i>Log</span><span><i class="lgd incomplete"></i>Incomplete</span><span><i class="lgd progress"></i>In progress</span></div>
    <div class="cal-week"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
    <div class="cal-grid" id="calGrid"></div>
  </div>
  <div id="viewHistory" class="att-view" hidden></div>
  <div id="viewTimeline" class="att-view" hidden></div>
</section>

<!-- Day details -->
<div class="modal-backdrop" id="dayModal" aria-hidden="true"><div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="dayTitle">
  <button class="modal-close" data-close-modal aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow">DATE DETAILS</span><h2 id="dayTitle">Selected date</h2>
  <div id="dayBody" class="day-body"></div>
  <div class="modal-actions"><a class="btn secondary" id="dayAddLog" href="daily_logs.php"><?= icon('plus') ?> Add daily log</a><button class="btn primary" data-close-modal>Close</button></div>
</div></div>
<div class="modal-backdrop lightbox" id="lightbox" aria-hidden="true"><img id="lightboxImg" alt="Evidence photo"></div>
<?php require_once 'includes/footer.php'; ?>
