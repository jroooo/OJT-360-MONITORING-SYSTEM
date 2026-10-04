<?php
$page_title = 'Notifications';
require_once 'includes/header.php';
$uid=(int)$user['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]); flash('success','All notifications marked as read.'); header('Location: notifications.php'); exit; }
$stmt=db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC'); $stmt->execute([$uid]); $items=$stmt->fetchAll();
?>
<div class="page-intro"><div><span class="eyebrow">UPDATES</span><h2>Notifications</h2><p>Stay updated with attendance, journals, and adviser announcements.</p></div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn secondary">Mark all as read</button></form></div>
<section class="panel notification-list">
<?php foreach ($items as $n): ?>
<div class="notification-item <?= !$n['is_read']?'unread':'' ?>"><div class="notification-icon"><?= e($n['icon'] ?: '•') ?></div><div><strong><?= e($n['title']) ?></strong><p><?= e($n['message']) ?></p><small><?= e(date('M d, Y · h:i A', strtotime($n['created_at']))) ?></small></div></div>
<?php endforeach; ?>
<?php if (!$items): ?><div class="empty">No notifications yet.</div><?php endif; ?>
</section>
<?php require_once 'includes/footer.php'; ?>
