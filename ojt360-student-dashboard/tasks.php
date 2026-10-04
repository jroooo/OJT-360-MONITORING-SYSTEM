<?php
$page_title = 'My Tasks';
require_once 'includes/header.php';
$uid=(int)$user['id'];
try{db()->exec("CREATE TABLE IF NOT EXISTS task_updates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,task_id INT UNSIGNED NOT NULL,user_id INT UNSIGNED NOT NULL,status ENUM('pending','in_progress','completed') NOT NULL,comment TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY task_user(task_id,user_id),FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");}catch(Throwable $e){}
$stmt=db()->prepare("SELECT t.*, COALESCE(c.name,'Company') company_name, COALESCE(tu.comment,'') student_comment FROM tasks t LEFT JOIN companies c ON c.id=t.company_id LEFT JOIN task_updates tu ON tu.task_id=t.id AND tu.user_id=? WHERE t.user_id=? OR (t.user_id IS NULL AND t.company_id=(SELECT company_id FROM internships WHERE user_id=? ORDER BY id DESC LIMIT 1)) ORDER BY (t.status='completed'), t.due_date IS NULL, t.due_date ASC, t.created_at DESC");
$stmt->execute([$uid,$uid,$uid]); $tasks=$stmt->fetchAll();
$pending=count(array_filter($tasks,fn($t)=>$t['status']!=='completed'));
?>
<div class="page-intro"><div><span class="eyebrow">WORK PLAN</span><h2>My Tasks</h2><p>Review tasks assigned by your OJT company and keep track of what still needs to be completed.</p></div><span class="pill <?= $pending?'orange-pill':'green-pill' ?>"><?= $pending ?> unfinished</span></div>
<div class="task-list">
<?php if(!$tasks): ?><section class="panel empty-state"><div class="empty-icon">✓</div><h3>No tasks assigned yet</h3><p>Company-created OJT tasks will appear here when they are assigned to you.</p></section><?php endif; ?>
<?php foreach($tasks as $t): $done=$t['status']==='completed'; $overdue=!$done && !empty($t['due_date']) && $t['due_date']<date('Y-m-d'); ?>
<section class="panel task-card <?= $done?'task-done':'' ?>">
 <div class="task-check <?= $done?'checked':'' ?>" data-task-id="<?= (int)$t['id'] ?>" title="Mark task complete"><?= $done?'✓':'' ?></div>
 <div class="task-main"><div class="task-title-row"><h3><?= e($t['title']) ?></h3><span class="pill <?= $done?'green-pill':($overdue?'red-pill':'orange-pill') ?>"><?= $done?'Completed':($overdue?'Overdue':ucwords(str_replace('_',' ',$t['status']))) ?></span></div>
 <p><?= nl2br(e($t['description'] ?? 'No description provided.')) ?></p><div class="task-update-box"><label>Status<select class="task-status-select" data-task-id="<?= (int)$t['id'] ?>"><option value="pending" <?=$t['status']==='pending'?'selected':''?>>Not started</option><option value="in_progress" <?=$t['status']==='in_progress'?'selected':''?>>In progress</option><option value="completed" <?=$t['status']==='completed'?'selected':''?>>Completed</option></select></label><label>Comment / details<textarea class="task-comment" data-task-id="<?= (int)$t['id'] ?>" rows="2" placeholder="Add a progress update or explain a problem..."><?=e($t['student_comment']??'')?></textarea></label><button type="button" class="btn secondary small task-update-save" data-task-id="<?= (int)$t['id'] ?>">Save Update</button><span class="task-update-state" data-task-state="<?= (int)$t['id'] ?>"></span></div><div class="task-meta"><span>⌂ <?= e($t['company_name']) ?></span><?php if($t['due_date']): ?><span>⌛ Due <?= e(date('M d, Y',strtotime($t['due_date']))) ?></span><?php endif; ?><span>Created <?= e(date('M d, Y',strtotime($t['created_at']))) ?></span></div></div>
</section>
<?php endforeach; ?></div>
<?php require_once 'includes/footer.php'; ?>
