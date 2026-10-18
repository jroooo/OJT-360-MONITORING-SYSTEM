<?php
$page_title = 'Notifications';
$page_scripts = ['notifications.js'];
require_once 'includes/header.php';
$stmt = db()->prepare('SELECT * FROM notifications WHERE student_pk=? ORDER BY created_at DESC LIMIT 200');
$stmt->execute([$uid]);
$items = $stmt->fetchAll();
?>
<div class="page-head">
  <div><span class="eyebrow">UPDATES</span><h1>Notifications<span class="title-dot">.</span></h1><p>Attendance, journals, and adviser announcements.</p></div>
  <div class="page-actions"><button class="btn secondary" id="readAll" type="button"><?= icon('check') ?> Mark all as read</button></div>
</div>
<section class="panel notification-list">
  <?php foreach ($items as $n): ?>
    <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>" data-id="<?= (int)$n['id'] ?>">
      <div class="notification-icon"><?= icon('bell') ?></div>
      <div class="notif-body"><strong><?= e($n['title']) ?></strong><p><?= e($n['message']) ?></p><small><?= e(date('M d, Y · h:i A', strtotime($n['created_at']))) ?></small></div>
      <?php if (!$n['is_read']): ?><i class="unread-dot" title="Unread"></i><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><div class="empty-state"><div class="empty-icon"><?= icon('bell') ?></div><h3>You're all caught up</h3><p>New notifications will appear here.</p></div><?php endif; ?>
</section>
<?php require_once 'includes/footer.php'; ?>
