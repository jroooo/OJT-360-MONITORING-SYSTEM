<?php
$page_title = 'Settings';
require_once 'includes/header.php';
?>
<div class="page-intro"><div><span class="eyebrow">PREFERENCES</span><h2>Settings</h2><p>Personalize how your OJT360 student portal looks and behaves.</p></div></div>
<div class="settings-grid">
<section class="panel settings-card">
  <div class="settings-row"><div class="settings-icon">◐</div><div class="settings-copy"><strong>Dark Mode</strong><span>Switch between the normal light dashboard and a darker interface.</span></div><label class="switch"><input type="checkbox" id="darkModeToggle"><span></span></label></div>
  <div class="settings-row"><div class="settings-icon">☰</div><div class="settings-copy"><strong>Keep Sidebar Open</strong><span>Keep the navigation panel open by default. Turn this off to use the hidden-sidebar layout.</span></div><label class="switch"><input type="checkbox" id="compactSidebarToggle"><span></span></label></div>
  <div class="settings-row"><div class="settings-icon">✦</div><div class="settings-copy"><strong>OJT360 Assistant</strong><span>Use the floating assistant for OJT questions, records, journal help, and general questions.</span></div><span class="pill blue-pill">Available</span></div>
</section>
<section class="panel settings-card">
  <div class="panel-head"><div><span class="eyebrow">ACCOUNT</span><h3>Quick Settings</h3></div></div>
  <div class="settings-links"><a href="profile.php">◎ <span>Manage profile & profile picture</span><b>›</b></a><a href="journal.php">▤ <span>Weekly journal</span><b>›</b></a><a href="tasks.php">✓ <span>My assigned tasks</span><b>›</b></a></div>
</section>
</div>
<?php require_once 'includes/footer.php'; ?>
