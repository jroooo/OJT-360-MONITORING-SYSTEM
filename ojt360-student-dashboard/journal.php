<?php
$page_title = 'Weekly Journal';
$page_scripts = ['editor.js', 'journal.js'];
require_once 'includes/header.php';
require_once 'includes/editor_shell.php';

$stmt = db()->prepare('SELECT * FROM journals WHERE student_pk=? ORDER BY week_start DESC');
$stmt->execute([$uid]);
$drafts = $stmt->fetchAll();

$currentWeek = date('Y-m-d', strtotime('monday this week'));
$current = null;
if (!empty($_GET['id'])) {
    foreach ($drafts as $d) if ((int)$d['id'] === (int)$_GET['id']) { $current = $d; break; }
} elseif (!empty($_GET['week']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['week'])) {
    $wk = week_start_of($_GET['week']);
    foreach ($drafts as $d) if ($d['week_start'] === $wk) { $current = $d; break; }
    if (!$current) $currentWeek = $wk;
} else {
    foreach ($drafts as $d) if ($d['week_start'] === $currentWeek) { $current = $d; break; }
}
$weekStart = $current['week_start'] ?? $currentWeek;
$title = $current['title'] ?? 'Weekly OJT Journal';
$content = $current ? (string)$current['content'] : '<p><br></p>';
if ($current && strip_tags($content) === $content) $content = '<p>' . nl2br(e($content)) . '</p>';
$orientation = $current['orientation'] ?? 'portrait';
$size = $current['page_size'] ?? 'A4';
$margin = $current['margin_preset'] ?? 'normal';
?>
<div class="page-head">
  <div>
    <span class="eyebrow">DOCUMENTATION</span>
    <h1>Weekly Journal<span class="title-dot">.</span></h1>
    <p>Write each week as a draft in a Word-style editor. Combine every draft in the Final Compilation to edit, print, or send to your adviser.</p>
  </div>
  <div class="page-actions"><a class="btn secondary" href="journal_compilation.php"><?= icon('layers') ?> Final Compilation</a></div>
</div>

<div class="journal-layout">
  <section class="panel journal-editor">
    <div class="panel-head">
      <div><span class="eyebrow">JOURNAL WORKSPACE</span><h3 id="wsTitle"><?= $current ? 'Continue this week' : 'Start this week' ?></h3></div>
      <span class="pill amber-pill" id="statusPill">Draft</span>
    </div>
    <form id="journalForm" class="journal-meta" onsubmit="return false">
      <input type="hidden" id="journalId" value="<?= (int)($current['id'] ?? 0) ?>">
      <label>Week title<input id="journalTitle" value="<?= e($title) ?>" maxlength="180" placeholder="Weekly OJT Journal"></label>
      <label>Week starting<input id="journalWeek" type="date" value="<?= e($weekStart) ?>"><span class="field-hint">Saved as the Monday of that week.</span></label>
    </form>
    <?php render_editor_shell('journalEditor', $content, $orientation, $size, $margin); ?>
    <div class="editor-foot">
      <p class="muted small">Tip: paste or drop pictures straight into the page. Click a picture to resize it, drag it to move it, and use the Picture tab for wrapping and cropping.</p>
      <div class="form-actions"><button type="button" class="btn secondary" id="newDraftBtn"><?= icon('plus') ?> New week</button><button type="button" class="btn secondary" id="printDraft"><?= icon('print') ?> Print</button><button type="button" class="btn primary" id="saveDraft"><?= icon('check') ?> Save Draft</button></div>
    </div>
  </section>

  <aside class="journal-side">
    <section class="panel">
      <div class="panel-head"><div><span class="eyebrow">DRAFTS</span><h3>Your Weekly Drafts</h3></div><span class="muted small"><?= count($drafts) ?> saved</span></div>
      <div class="draft-timeline">
        <?php if (!$drafts): ?><div class="dash-empty">No saved drafts yet. Your first draft appears here after you save.</div><?php endif; ?>
        <?php foreach ($drafts as $d): $active = $current && (int)$d['id'] === (int)$current['id']; ?>
          <div class="draft-item <?= $active ? 'active' : '' ?>">
            <a href="journal.php?id=<?= (int)$d['id'] ?>" class="draft-open"><i class="draft-dot"></i><div><strong><?= e($d['title']) ?></strong><span>Week of <?= e(date('M d, Y', strtotime($d['week_start']))) ?> · <?= e(date('M d, g:i A', strtotime($d['updated_at']))) ?></span><small><?= e(rich_to_text((string)$d['content'], 110)) ?></small></div></a>
            <button type="button" class="draft-del" data-del-draft="<?= (int)$d['id'] ?>" title="Delete draft" aria-label="Delete draft"><?= icon('trash') ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="panel guide-card">
      <div class="guide-icon"><?= icon('journal') ?></div>
      <span class="eyebrow">DOCUMENT WORKFLOW</span><h3>Write it like a report</h3>
      <p class="muted">Build each weekly entry with headings, paragraphs, lists, tables, and supporting photos.</p>
      <ul class="check-list"><li>Rich text formatting</li><li>Pictures: resize, move, wrap, crop</li><li>Portrait or landscape pages</li><li>Print or save as PDF</li></ul>
    </section>
    <section class="panel compile-card">
      <div class="guide-icon alt"><?= icon('layers') ?></div>
      <h3>Ready for final compilation?</h3>
      <p class="muted">Combine every weekly draft into one editable document before printing, saving as PDF, or submitting to your adviser.</p>
      <a class="btn primary wide" href="journal_compilation.php">Open Compilation</a>
    </section>
  </aside>
</div>
<?php render_editor_modals(); ?>
<?php require_once 'includes/footer.php'; ?>
