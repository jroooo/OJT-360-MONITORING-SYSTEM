<?php
$page_title = 'Help & Support';
$page_scripts = ['help.js'];
require_once 'includes/header.php';
$faqs = [
  ['Getting started', 'How do I time in?', 'Press Time In on the Dashboard or Attendance page. The QR scanner opens, you scan your company\'s OJT360 QR code, and your live timer starts. A daily log for today is created automatically.'],
  ['Getting started', 'How do I take a break?', 'While you are timed in, press Take a Break. Your work timer pauses and the break is counted separately. Press Resume Work when you are back. A break that is still open is closed automatically when you time out.'],
  ['Getting started', 'How do I time out?', 'Press Time Out and scan the same company QR again. Your rendered hours (time in to time out minus breaks) are saved, and you are taken to today\'s daily log to describe what you did.'],
  ['Hours & logs', 'Why does my daily log say "Needs details"?', 'Daily logs are created for you when you time in, but only you can describe the work. Open it, add the task and what you accomplished, and optionally attach a photo.'],
  ['Hours & logs', 'Can I add a log for a day I forgot to time in?', 'Yes. Use New Entry on Daily Logs and enter the times. Manual entries appear in your reports, but only QR attendance counts toward your required OJT hours.'],
  ['Hours & logs', 'Where can I see past days?', 'Open Attendance. The Calendar shows every day colour-coded; click a date to see your attendance, log and photo. History and Timeline tabs list every session and break.'],
  ['Journal', 'How does the weekly journal work?', 'Each week is a draft written in a Word-style editor. Insert pictures, then click a picture to resize, drag, wrap text, crop, rotate, or flip it. Drafts autosave as you type.'],
  ['Journal', 'What is the Final Compilation?', 'It combines all your weekly drafts into one editable document. Edit it, print it, save it as a PDF, or submit it to your adviser.'],
  ['Journal', 'How do I save the journal as a PDF?', 'Open Final Compilation, press Save as PDF, and choose "Save as PDF" as the destination in your browser\'s print window.'],
  ['Account', 'How do I change my profile picture?', 'Open Profile and click your picture. You can view it, upload a new one (drag and zoom to crop), or delete it. The top-right profile menu has the same options.'],
  ['Account', 'Dark mode is hard to read, what should I do?', 'Open Settings and pick Light, Dark, or System. If something looks wrong, press Ctrl+F5 to refresh the cached styles and tell us through the form on this page.'],
];
$cats = array_values(array_unique(array_column($faqs, 0)));
$mine = db()->prepare('SELECT * FROM support_requests WHERE student_pk=? ORDER BY created_at DESC LIMIT 5');
$mine->execute([$uid]);
$requests = $mine->fetchAll();
?>
<div class="page-head">
  <div><span class="eyebrow">ASSISTANCE</span><h1>Help &amp; Support<span class="title-dot">.</span></h1><p>Find quick answers, ask the OJT360 Assistant, or send a request to the support team.</p></div>
  <div class="page-actions"><button class="btn secondary" type="button" id="helpAsk"><?= icon('zap') ?> Ask the Assistant</button></div>
</div>

<div class="help-search"><?= icon('search') ?><input id="faqSearch" placeholder="Search help topics… e.g. break, journal, QR" autocomplete="off"></div>

<div class="grid-3 quick-help">
  <a class="qh-card" href="attendance.php"><div class="stat-ic"><?= icon('clock') ?></div><b>Attendance guide</b><span>Time in, breaks, and the calendar</span></a>
  <a class="qh-card" href="journal.php"><div class="stat-ic"><?= icon('journal') ?></div><b>Journal editor</b><span>Pictures, wrapping, and cropping</span></a>
  <a class="qh-card" href="qr-test.php"><div class="stat-ic"><?= icon('qr') ?></div><b>Test QR code</b><span>Practice with the demo QR</span></a>
</div>

<div class="grid-main">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">FAQ</span><h3>Frequently asked questions</h3></div></div>
    <div class="tabs faq-tabs"><button class="tab active" data-cat="all">All</button><?php foreach ($cats as $c): ?><button class="tab" data-cat="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?></div>
    <div class="faq-list" id="faqList">
      <?php foreach ($faqs as $f): ?>
        <details data-cat="<?= e($f[0]) ?>"><summary><?= e($f[1]) ?></summary><p><?= e($f[2]) ?></p></details>
      <?php endforeach; ?>
      <div class="dash-empty" id="faqEmpty" hidden>No matching answers. Send us a request on the right.</div>
    </div>
  </section>
  <aside class="stack">
    <section class="panel">
      <div class="panel-head"><div><span class="eyebrow">CONTACT</span><h3>Send a request</h3></div></div>
      <form class="form" id="supportForm">
        <label>Subject<input name="subject" maxlength="180" required placeholder="e.g. Attendance issue"></label>
        <label>Message<textarea name="message" rows="6" maxlength="5000" required placeholder="Describe your concern…"></textarea></label>
        <div class="form-actions"><span class="form-state"></span><button class="btn primary"><?= icon('send') ?> Submit request</button></div>
      </form>
    </section>
    <section class="panel">
      <div class="panel-head"><div><span class="eyebrow">HISTORY</span><h3>Your requests</h3></div></div>
      <?php if (!$requests): ?><div class="dash-empty">No requests yet.</div><?php endif; ?>
      <div class="mini-list"><?php foreach ($requests as $r): $cls = $r['status'] === 'resolved' ? 'green-pill' : ($r['status'] === 'in_progress' ? 'blue-pill' : 'amber-pill'); ?>
        <div class="mini-row"><div class="mini-main"><strong><?= e($r['subject']) ?></strong><small><?= e(date('M d, Y', strtotime($r['created_at']))) ?></small></div><span class="pill <?= $cls ?>"><?= e(ucwords(str_replace('_', ' ', $r['status']))) ?></span></div>
      <?php endforeach; ?></div>
    </section>
  </aside>
</div>
<?php require_once 'includes/footer.php'; ?>
