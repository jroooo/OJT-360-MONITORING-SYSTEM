<?php
$page_title = 'Settings';
$page_scripts = ['settings.js'];
require_once 'includes/header.php';
$internship = internship_for($uid);
$req = (float)($internship['required_hours'] ?? 480);
?>
<div class="page-head">
  <div>
    <span class="eyebrow">PREFERENCES</span>
    <h1>Settings<span class="title-dot">.</span></h1>
    <p>Personalize the portal, set your work schedule, and manage your account.</p>
  </div>
</div>

<div class="settings-layout">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">APPEARANCE</span><h3>Theme</h3></div></div>
    <div class="theme-cards" id="themeCards" role="radiogroup" aria-label="Theme">
      <button type="button" class="theme-card" data-theme-pref="light" role="radio"><span class="tc-preview light"><i></i><i></i><i></i></span><b><?= icon('sun') ?> Light</b><small>Bright and clean</small></button>
      <button type="button" class="theme-card" data-theme-pref="dark" role="radio"><span class="tc-preview dark"><i></i><i></i><i></i></span><b><?= icon('moon') ?> Dark</b><small>Easy on the eyes</small></button>
      <button type="button" class="theme-card" data-theme-pref="system" role="radio"><span class="tc-preview sys"><i></i><i></i><i></i></span><b><?= icon('monitor') ?> System</b><small>Match my device</small></button>
    </div>
    <div class="settings-rows">
      <div class="settings-row"><div class="settings-icon"><?= icon('menu') ?></div><div class="settings-copy"><strong>Keep sidebar open</strong><span>Turn off to use the compact icon-only sidebar on desktop.</span></div><label class="switch"><input type="checkbox" id="sidebarToggle"><span></span></label></div>
      <div class="settings-row"><div class="settings-icon"><?= icon('zap') ?></div><div class="settings-copy"><strong>Reduce motion</strong><span>Calms animations such as the live wave and logo spin.</span></div><label class="switch"><input type="checkbox" id="motionToggle"><span></span></label></div>
    </div>
  </section>

  <section class="panel" id="goal">
    <div class="panel-head"><div><span class="eyebrow">WORK SCHEDULE</span><h3>Attendance & hours goal</h3></div></div>
    <p class="muted small" style="margin-bottom:14px">Your shift start decides whether a Time In counts as on time on the dashboard.</p>
    <form class="form" id="scheduleForm">
      <div class="form-grid-3">
        <label>Shift starts<input type="time" name="shift_start" value="<?= e(substr($settingsRow['shift_start'], 0, 5)) ?>" required></label>
        <label>Grace period (min)<input type="number" name="grace_minutes" min="0" max="120" value="<?= (int)$settingsRow['grace_minutes'] ?>" required></label>
        <label>Daily target (hours)<input type="number" name="daily_target" min="1" max="16" step="0.5" value="<?= e((string)$settingsRow['daily_target']) ?>" required></label>
      </div>
      <div class="form-actions"><span class="form-state"></span><button class="btn primary">Save schedule</button></div>
    </form>
    <hr class="sep-line">
    <form class="form" id="goalForm">
      <label>Required OJT hours<input type="number" name="required_hours" min="1" max="2000" step="1" value="<?= e((string)(int)$req) ?>" required><span class="field-hint">Defined by your school. Used for your progress ring and goal bar.</span></label>
      <div class="form-actions"><span class="form-state"></span><button class="btn primary">Update goal</button></div>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">SECURITY</span><h3>Change password</h3></div></div>
    <form class="form" id="passwordForm" autocomplete="off">
      <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
      <div class="form-grid-2"><label>New password<input type="password" name="new" minlength="8" required autocomplete="new-password"></label><label>Confirm new password<input type="password" name="confirm" minlength="8" required autocomplete="new-password"></label></div>
      <div class="form-actions"><span class="form-state"></span><button class="btn primary">Update password</button></div>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">ACCOUNT</span><h3>Quick links</h3></div></div>
    <div class="settings-links">
      <a href="profile.php"><?= icon('user') ?><span>Profile & profile picture</span><?= icon('chevR') ?></a>
      <a href="journal.php"><?= icon('journal') ?><span>Weekly journal</span><?= icon('chevR') ?></a>
      <a href="tasks.php"><?= icon('tasks') ?><span>My tasks</span><?= icon('chevR') ?></a>
      <a href="help.php"><?= icon('help') ?><span>Help & support</span><?= icon('chevR') ?></a>
      <a href="setup-check.php"><?= icon('shield') ?><span>Run setup check</span><?= icon('chevR') ?></a>
    </div>
  </section>
</div>
<?php require_once 'includes/footer.php'; ?>