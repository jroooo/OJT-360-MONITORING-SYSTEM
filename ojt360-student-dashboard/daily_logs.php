<?php
$page_title = 'Daily Logs';
$page_scripts = ['daily_logs.js'];
require_once 'includes/header.php';

$q = db()->prepare('SELECT * FROM daily_logs WHERE student_pk=? ORDER BY log_date DESC, COALESCE(time_in, "00:00:00") DESC, id DESC');
$q->execute([$uid]);
$logs = $q->fetchAll();
$totalHours = 0.0; $withEvidence = 0; $incomplete = 0;
$payload = [];
foreach ($logs as $r) {
    $totalHours += (float)$r['hours'];
    if (!empty($r['evidence_image'])) $withEvidence++;
    $inc = trim((string)$r['title']) === '' || trim((string)$r['description']) === '';
    if ($inc) $incomplete++;
    $payload[] = [
        'id' => (int)$r['id'], 'date' => $r['log_date'], 'title' => $r['title'], 'description' => $r['description'],
        'hours' => (float)$r['hours'], 'time_in' => $r['time_in'] ? substr($r['time_in'], 0, 5) : '', 'time_out' => $r['time_out'] ? substr($r['time_out'], 0, 5) : '',
        'break' => (int)$r['break_minutes'], 'auto' => (int)$r['is_auto'] === 1, 'evidence' => evidence_url($r['evidence_image']),
        'incomplete' => $inc, 'running' => $r['time_in'] && !$r['time_out'] && (int)$r['is_auto'] === 1,
        'created' => date('M j, Y g:i A', strtotime($r['created_at'])),
    ];
}
$set = student_settings($uid);
?>
<div class="page-head">
  <div>
    <span class="eyebrow">YOUR WORK LOG</span>
    <h1>Daily entries<span class="title-dot">.</span></h1>
    <p>Logs are created automatically when you time in. Add what you worked on and optional photo evidence.</p>
  </div>
  <div class="page-actions"><button class="btn primary lg" id="newLogBtn" type="button"><?= icon('plus') ?> New Entry</button></div>
</div>

<div class="grid-4 log-summary">
  <article class="stat"><div class="stat-ic"><?= icon('logs') ?></div><span>Entries</span><strong><b><?= count($logs) ?></b></strong><small>Total daily records</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Logged hours</span><strong><b><?= number_format($totalHours, 2) ?></b> <em>h</em></strong><small>From daily entries</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('image') ?></div><span>With evidence</span><strong><b><?= $withEvidence ?></b></strong><small>Photo-supported entries</small></article>
  <article class="stat <?= $incomplete ? 'stat-warn' : '' ?>"><div class="stat-ic"><?= icon('alert') ?></div><span>Need details</span><strong><b><?= $incomplete ?></b></strong><small>Missing task or description</small></article>
</div>

<section class="panel filters-panel">
  <div class="filter-head"><div class="filter-icon"><?= icon('filter') ?></div><div><h3>Filters</h3><p class="muted small">Search your daily logs</p></div></div>
  <div class="log-filters">
    <label class="f-search">Search<input id="logSearch" placeholder="Search task or descriptionΓÇª"></label>
    <label>From<input id="logFrom" type="date"></label>
    <label>To<input id="logTo" type="date"></label>
    <label>Sort<select id="logSort"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="hours">Most hours</option></select></label>
    <label>Per page<select id="logPerPage"><option>10</option><option>20</option><option>50</option></select></label>
  </div>
  <div class="filter-foot"><span id="logCount" class="muted small">0 entries</span><div class="filter-chips"><button class="chip" id="chipIncomplete" type="button">Needs details only</button><button class="link-btn" id="clearLogFilters" type="button">Clear Filters</button></div></div>
</section>

<div id="logList" class="log-list"></div>
<div class="pager" id="logPager"></div>

<!-- Entry modal -->
<div class="modal-backdrop" id="logModal" aria-hidden="true" data-static><div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="logModalTitle">
  <button class="modal-close" id="logModalClose" aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow">DAILY LOG</span><h2 id="logModalTitle">New Entry</h2>
  <form id="logForm" class="form" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" id="logId"><input type="hidden" name="action" value="create">
    <div class="form-grid-2">
      <label>Date<input type="date" name="log_date" id="logDate" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></label>
      <label>Task<input name="title" id="logTitle" maxlength="180" placeholder="e.g. Updated website" required></label>
    </div>
    <div class="form-grid-3" id="timeFields">
      <label>Time In<input type="time" name="time_in" id="logIn"><button type="button" class="btn small secondary" data-now="logIn">Current Time</button></label>
      <label>Time Out<input type="time" name="time_out" id="logOut"><button type="button" class="btn small secondary" data-now="logOut">Current Time</button></label>
      <label>Break (minutes)<input type="number" name="break_minutes" id="logBreak" min="0" max="720" step="5" value="0"></label>
    </div>
    <div class="rendered-box" id="renderedBox"><span>Rendered hours:</span> <b id="renderedVal">0.00 hrs</b><small id="autoNote" hidden>Time, break and hours come from your attendance and cannot be edited here.</small></div>
    <label>Tasks Accomplished<textarea name="description" id="logDescription" rows="5" placeholder="Describe what you worked on, what you learned, and what you accomplished."></textarea></label>
    <div class="photo-field">
      <label>Photo Evidence <span class="muted">(optional)</span><input type="file" name="evidence" id="logEvidence" accept="image/jpeg,image/png,image/webp,image/gif"></label>
      <p class="field-hint">JPG, PNG, WEBP, or GIF ┬╖ up to 5 MB. Saved securely on your server.</p>
      <div class="photo-preview" id="photoPreview" hidden><img id="photoPreviewImg" alt="Selected photo"><button type="button" class="btn small danger-soft" id="removePhoto"><?= icon('trash') ?> Remove photo</button></div>
      <input type="hidden" name="remove_evidence" id="removeEvidence" value="">
    </div>
    <div class="form-actions"><span id="logFormState" class="form-state"></span><button type="button" class="btn secondary" id="logCancel">Cancel</button><button class="btn primary" id="logSave">Save Entry</button></div>
  </form>
</div></div>
<div class="modal-backdrop lightbox" id="lightbox" aria-hidden="true"><img id="lightboxImg" alt="Evidence photo"></div>
<script>window.OJT_LOGS = <?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>; window.OJT_LOG_TODAY = <?= json_encode(date('Y-m-d')) ?>;</script>
<?php require_once 'includes/footer.php'; ?>
