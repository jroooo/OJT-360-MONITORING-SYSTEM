<?php
$page_title = 'Profile';
$page_scripts = ['profile.js'];
require_once 'includes/header.php';
$internship = internship_for($uid);
$stats = db()->prepare("SELECT (SELECT COUNT(*) FROM attendance WHERE student_pk=? AND time_out IS NOT NULL) sessions, (SELECT COUNT(*) FROM daily_logs WHERE student_pk=?) logs, (SELECT COUNT(*) FROM journals WHERE student_pk=?) journals");
$stats->execute([$uid, $uid, $uid]);
$st = $stats->fetch();
$progress = $internship && (float)$internship['required_hours'] > 0 ? min(100, round($internship['completed_hours'] / $internship['required_hours'] * 100, 1)) : 0;
?>
<div class="profile-cover"><div class="cover-art"></div></div>
<section class="profile-hero">
  <div class="profile-avatar-wrap">
    <button type="button" class="avatar xl" id="profileAvatarBtn" data-avatar-menu="avatarMenu" data-avatar-xl aria-label="Profile picture options"><?= $avatarInner ?></button>
    <button type="button" class="avatar-cam" data-avatar-menu="avatarMenu" aria-label="Change profile picture"><?= icon('camera') ?></button>
    <div class="avatar-menu" id="avatarMenu" role="menu">
      <button type="button" data-photo-action="view" role="menuitem"><?= icon('eye') ?> View photo</button>
      <button type="button" data-photo-action="upload" role="menuitem"><?= icon('upload') ?> Upload new photo</button>
      <button type="button" data-photo-action="delete" class="danger" role="menuitem"><?= icon('trash') ?> Delete photo</button>
    </div>
    <input type="file" id="profilePageInput" accept="image/jpeg,image/png,image/webp" hidden>
  </div>
  <div class="profile-id">
    <h1><?= e($user['full_name']) ?></h1>
    <p class="muted"><?= e($user['course']) ?> ┬╖ <?= e($user['school_name']) ?></p>
    <div class="profile-tags"><span class="pill blue-pill"><?= icon('user') ?> Student</span><span class="pill gray-pill"><?= e($user['student_no']) ?></span><?php if ($internship): ?><span class="pill green-pill"><?= icon('building') ?> <?= e($internship['company_name']) ?></span><?php endif; ?></div>
  </div>
  <div class="profile-actions"><button class="btn secondary" type="button" data-photo-action="upload"><?= icon('camera') ?> Change photo</button></div>
</section>

<div class="grid-4 profile-stats">
  <article class="stat"><div class="stat-ic"><?= icon('target') ?></div><span>OJT progress</span><strong><b><?= $progress ?></b><em>%</em></strong><small><?= number_format((float)($internship['completed_hours'] ?? 0), 1) ?> of <?= number_format((float)($internship['required_hours'] ?? 480), 0) ?> hours</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Attendance</span><strong><b><?= (int)$st['sessions'] ?></b></strong><small>completed sessions</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('logs') ?></div><span>Daily logs</span><strong><b><?= (int)$st['logs'] ?></b></strong><small>entries recorded</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('journal') ?></div><span>Journals</span><strong><b><?= (int)$st['journals'] ?></b></strong><small>weekly drafts</small></article>
</div>

<div class="grid-main">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">PERSONAL DETAILS</span><h3>Edit information</h3></div></div>
    <form class="form" id="profileForm" autocomplete="off">
      <div class="form-grid-3">
        <label>First name<input name="first_name" value="<?= e($user['first_name']) ?>" maxlength="50" required></label>
        <label>Middle name<input name="middle_name" value="<?= e($user['middle_name'] ?? '') ?>" maxlength="50"></label>
        <label>Last name<input name="last_name" value="<?= e($user['last_name']) ?>" maxlength="50" required></label>
      </div>
      <div class="form-grid-2"><label>Institutional email<input value="<?= e($user['institutional_email']) ?>" disabled></label><label>Student ID<input value="<?= e($user['student_id']) ?>" disabled></label></div>
      <div class="form-grid-2"><label>Mobile number<input name="mobile_number" value="<?= e($user['mobile_number']) ?>" maxlength="20" inputmode="tel" required></label><label>Course<input value="<?= e($user['course']) ?>" disabled></label></div>
      <label>School<input value="<?= e($user['school_name']) ?>" disabled></label>
      <label>Complete address<textarea name="complete_address" rows="3" maxlength="1000" required><?= e($user['complete_address']) ?></textarea></label>
      <div class="form-actions"><span class="form-state" id="profileState"></span><button class="btn primary">Save changes</button></div>
    </form>
  </section>
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">INTERNSHIP</span><h3>OJT assignment</h3></div></div>
    <?php if ($internship): ?>
    <div class="ov-list">
      <div><span>Company</span><b><?= e($internship['company_name']) ?></b></div>
      <div><span>Address</span><b><?= e($internship['company_address'] ?? 'ΓÇö') ?></b></div>
      <div><span>Start date</span><b><?= $internship['start_date'] ? e(date('M d, Y', strtotime($internship['start_date']))) : 'ΓÇö' ?></b></div>
      <div><span>Expected end</span><b><?= $internship['end_date'] ? e(date('M d, Y', strtotime($internship['end_date']))) : 'ΓÇö' ?></b></div>
      <div><span>Required hours</span><b><?= number_format((float)$internship['required_hours'], 0) ?> h</b></div>
    </div>
    <?php else: ?><div class="dash-empty">Your internship record is created the first time you time in with your company QR.</div><?php endif; ?>
    <div class="photo-help"><b>Profile picture tips</b><p class="muted small">Use a clear, front-facing photo. JPG, PNG or WEBP. You can drag and zoom to crop it before saving.</p></div>
  </section>
</div>
<?php require_once 'includes/footer.php'; ?>
