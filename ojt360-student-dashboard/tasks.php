<?php
$page_title = 'My Tasks';
$page_scripts = ['tasks.js'];
require_once 'includes/header.php';
$stmt = db()->prepare("SELECT t.*, COALESCE(tu.comment,'') student_comment FROM tasks t LEFT JOIN task_updates tu ON tu.task_id=t.id AND tu.student_pk=t.student_pk WHERE t.student_pk=? ORDER BY (t.status='completed'), t.due_date IS NULL, t.due_date ASC, t.created_at DESC");
$stmt->execute([$uid]);
$tasks = $stmt->fetchAll();
$cnt = ['all' => count($tasks), 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];
foreach ($tasks as $t) { $cnt[$t['status']]++; if ($t['status'] !== 'completed' && $t['due_date'] && $t['due_date'] < date('Y-m-d')) $cnt['overdue']++; }
$donePct = $cnt['all'] ? round($cnt['completed'] / $cnt['all'] * 100) : 0;
?>
<div class="page-head">
  <div>
    <span class="eyebrow">WORK PLAN</span>
    <h1>My Tasks<span class="title-dot">.</span></h1>
    <p>Tasks assigned to you during your OJT, plus your own to-dos. Your progress updates are saved for your supervisor and adviser.</p>
  </div>
  <div class="page-actions"><button class="btn primary lg" id="addTaskBtn" type="button"><?= icon('plus') ?> Add my own task</button></div>
</div>

<div class="grid-4 task-stats">
  <article class="stat"><div class="stat-ic"><?= icon('tasks') ?></div><span>All tasks</span><strong><b><?= $cnt['all'] ?></b></strong><small><?= $donePct ?>% completed</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('clock') ?></div><span>Not started</span><strong><b><?= $cnt['pending'] ?></b></strong><small>Waiting for you</small></article>
  <article class="stat"><div class="stat-ic"><?= icon('refresh') ?></div><span>In progress</span><strong><b><?= $cnt['in_progress'] ?></b></strong><small>Being worked on</small></article>
  <article class="stat <?= $cnt['overdue'] ? 'stat-warn' : '' ?>"><div class="stat-ic"><?= icon('alert') ?></div><span>Overdue</span><strong><b><?= $cnt['overdue'] ?></b></strong><small>Past the due date</small></article>
</div>

<div class="tabs task-tabs" role="tablist">
  <button class="tab active" data-filter="all">All <b><?= $cnt['all'] ?></b></button>
  <button class="tab" data-filter="open">Unfinished <b><?= $cnt['pending'] + $cnt['in_progress'] ?></b></button>
  <button class="tab" data-filter="completed">Completed <b><?= $cnt['completed'] ?></b></button>
</div>

<div class="task-list" id="taskList">
<?php if (!$tasks): ?><div class="empty-state"><div class="empty-icon"><?= icon('tasks') ?></div><h3>No tasks yet</h3><p>Tasks assigned to you appear here. You can also add your own to-dos.</p></div><?php endif; ?>
<?php foreach ($tasks as $t):
  $done = $t['status'] === 'completed';
  $overdue = !$done && !empty($t['due_date']) && $t['due_date'] < date('Y-m-d');
  $personal = ($t['source'] ?? 'company') === 'personal';
  $mine = (int)$t['student_pk'] === $uid;
  $stLabel = $done ? 'Completed' : ($overdue ? 'Overdue' : ($t['status'] === 'in_progress' ? 'In progress' : 'Not started'));
  $stCls = $done ? 'green-pill' : ($overdue ? 'red-pill' : ($t['status'] === 'in_progress' ? 'blue-pill' : 'amber-pill'));
?>
  <article class="task-card <?= $done ? 'task-done' : '' ?>" data-status="<?= e($t['status']) ?>" data-task="<?= (int)$t['id'] ?>">
    <button type="button" class="task-check <?= $done ? 'checked' : '' ?>" data-toggle="<?= (int)$t['id'] ?>" aria-label="Mark complete" <?= $mine ? '' : 'disabled' ?>><?= icon('check') ?></button>
    <div class="task-main">
      <div class="task-title-row"><h3><?= e($t['title']) ?></h3><span class="pill <?= $stCls ?>" data-pill><?= $stLabel ?></span><?php if ($personal): ?><span class="pill gray-pill">My task</span><?php endif; ?></div>
      <p><?= nl2br(e($t['description'] ?: 'No description provided.')) ?></p>
      <div class="task-meta"><span><?= icon('building') ?> <?= e($personal ? 'My task' : ($t['company_name'] ?: 'OJT company')) ?></span><?php if ($t['due_date']): ?><span class="<?= $overdue ? 'warn-text' : '' ?>"><?= icon('calendar') ?> Due <?= e(date('M d, Y', strtotime($t['due_date']))) ?></span><?php endif; ?><span>Created <?= e(date('M d, Y', strtotime($t['created_at']))) ?></span></div>
      <?php if ($mine): ?>
      <details class="task-update"><summary>Update progress</summary>
        <div class="task-update-box">
          <label>Status<select data-status-sel><option value="pending" <?= $t['status'] === 'pending' ? 'selected' : '' ?>>Not started</option><option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option><option value="completed" <?= $done ? 'selected' : '' ?>>Completed</option></select></label>
          <label>Comment / details<textarea data-comment rows="2" placeholder="Add a progress update or explain a problem..."><?= e($t['student_comment']) ?></textarea></label>
          <div class="task-update-actions"><button type="button" class="btn small primary" data-save="<?= (int)$t['id'] ?>">Save update</button><?php if ($personal): ?><button type="button" class="btn small danger-soft" data-delete="<?= (int)$t['id'] ?>"><?= icon('trash') ?> Delete</button><?php endif; ?><span class="form-state" data-state></span></div>
        </div>
      </details>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>

<div class="modal-backdrop" id="taskModal" aria-hidden="true"><div class="modal modal-md">
  <button class="modal-close" data-close-modal aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow">NEW TASK</span><h2>Add my own task</h2>
  <form class="form" id="taskForm" autocomplete="off">
    <label>Title<input name="title" maxlength="180" required placeholder="e.g. Finish the login page"></label>
    <label>Details <span class="muted">(optional)</span><textarea name="description" rows="3"></textarea></label>
    <label>Due date <span class="muted">(optional)</span><input type="date" name="due_date"></label>
    <div class="modal-actions"><button type="button" class="btn secondary" data-close-modal>Cancel</button><button class="btn primary">Add task</button></div>
  </form>
</div></div>
<?php require_once 'includes/footer.php'; ?>
