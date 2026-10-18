<?php
$page_title = 'Final Compilation';
$page_scripts = ['editor.js', 'compilation.js'];
require_once 'includes/header.php';
require_once 'includes/editor_shell.php';

$j = db()->prepare('SELECT week_start,title,content FROM journals WHERE student_pk=? ORDER BY week_start ASC');
$j->execute([$uid]);
$drafts = $j->fetchAll();
$c = db()->prepare('SELECT * FROM journal_compilations WHERE student_pk=? LIMIT 1');
$c->execute([$uid]);
$comp = $c->fetch();
$title = $comp['title'] ?? 'OJT Weekly Journal Compilation';
$compiled = $comp ? (string)$comp['content'] : compilation_from_drafts($drafts);
if ($compiled === '') $compiled = '<p><br></p>';
$orientation = $comp['orientation'] ?? 'portrait';
$size = $comp['page_size'] ?? 'A4';
$margin = $comp['margin_preset'] ?? 'normal';
$subs = db()->prepare('SELECT id,title,status,submitted_at,adviser_remarks FROM journal_submissions WHERE student_pk=? ORDER BY submitted_at DESC LIMIT 6');
$subs->execute([$uid]);
$submissions = $subs->fetchAll();
$newerDrafts = 0;
if ($comp) {
    $n = db()->prepare('SELECT COUNT(*) FROM journals WHERE student_pk=? AND updated_at > ?');
    $n->execute([$uid, $comp['updated_at']]);
    $newerDrafts = (int)$n->fetchColumn();
}
?>
<div class="page-head">
  <div>
    <span class="eyebrow">FINAL DOCUMENT</span>
    <h1>Journal Compilation<span class="title-dot">.</span></h1>
    <p>Every weekly draft in one document. Edit it like Word, then print, save as PDF, or submit it to your adviser.</p>
  </div>
  <div class="page-actions">
    <a class="btn secondary" href="journal.php"><?= icon('chevL') ?> Back to Journal</a>
    <button class="btn secondary" id="printCompilation" type="button"><?= icon('print') ?> Print</button>
    <button class="btn secondary" id="pdfCompilation" type="button"><?= icon('file') ?> Save as PDF</button>
    <button class="btn primary" id="submitCompilation" type="button"><?= icon('send') ?> Submit to Adviser</button>
  </div>
</div>

<?php if ($newerDrafts > 0): ?>
<div class="notice"><?= icon('alert') ?><div><strong><?= $newerDrafts ?> weekly draft<?= $newerDrafts > 1 ? 's were' : ' was' ?> updated after you last saved this compilation.</strong><span>Use “Rebuild from drafts” to bring them in. This replaces the text below with the latest drafts.</span></div></div>
<?php endif; ?>

<section class="panel compilation-panel">
  <div class="comp-head">
    <div class="doc-brand"><?= logo_mark() ?><div><strong>OJT360</strong><span><?= e($user['full_name']) ?> · <?= e($user['student_no']) ?></span></div></div>
    <div class="comp-tools">
      <label class="comp-title">Document title<input id="compTitle" value="<?= e($title) ?>" maxlength="180"></label>
      <button type="button" class="btn secondary small" id="rebuildBtn"><?= icon('refresh') ?> Rebuild from drafts</button>
      <button type="button" class="btn primary small" id="saveCompilation"><?= icon('check') ?> Save</button>
    </div>
  </div>
  <?php render_editor_shell('compEditor', $compiled, $orientation, $size, $margin); ?>
</section>

<section class="panel">
  <div class="panel-head"><div><span class="eyebrow">ADVISER</span><h3>Submission history</h3></div><span class="muted small"><?= count($drafts) ?> weekly draft<?= count($drafts) === 1 ? '' : 's' ?> in your journal</span></div>
  <?php if (!$submissions): ?><div class="dash-empty">You have not submitted a compilation yet. When you submit, a snapshot is sent to your adviser's dashboard for review.</div><?php endif; ?>
  <div class="mini-list">
    <?php foreach ($submissions as $s): $cls = $s['status'] === 'reviewed' ? 'green-pill' : ($s['status'] === 'returned' ? 'red-pill' : 'amber-pill'); ?>
      <div class="mini-row"><div class="mini-check"><?= icon('send') ?></div><div class="mini-main"><strong><?= e($s['title']) ?></strong><small>Submitted <?= e(date('M d, Y · g:i A', strtotime($s['submitted_at']))) ?><?= $s['adviser_remarks'] ? ' · “' . e(mb_strimwidth($s['adviser_remarks'], 0, 90, '…')) . '”' : '' ?></small></div><span class="pill <?= $cls ?>"><?= $s['status'] === 'pending' ? 'Awaiting review' : e(ucfirst($s['status'])) ?></span></div>
    <?php endforeach; ?>
  </div>
</section>
<?php render_editor_modals(); ?>
<?php require_once 'includes/footer.php'; ?>
