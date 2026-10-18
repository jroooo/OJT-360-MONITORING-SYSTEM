<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'POST required.'], 405);
verify_csrf();
$uid = (int)$_SESSION['student_pk'];
$action = (string)($_POST['action'] ?? 'update');

try {
    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $due = trim((string)($_POST['due_date'] ?? ''));
        if ($title === '') json_out(['ok' => false, 'message' => 'Please enter a task title.'], 422);
        if (mb_strlen($title) > 180 || mb_strlen($desc) > 3000) json_out(['ok' => false, 'message' => 'The task is too long.'], 422);
        if ($due !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) json_out(['ok' => false, 'message' => 'Invalid due date.'], 422);
        db()->prepare("INSERT INTO tasks(student_pk,title,description,due_date,status,source) VALUES(?,?,?,?,'pending','personal')")
            ->execute([$uid, $title, $desc, $due !== '' ? $due : null]);
        add_activity($uid, 'Added a personal task');
        json_out(['ok' => true, 'message' => 'Task added.']);
    }

    $id = (int)($_POST['task_id'] ?? 0);
    if (!$id) json_out(['ok' => false, 'message' => 'Task not found.'], 422);
    $chk = db()->prepare('SELECT id,title,status,source FROM tasks WHERE id=? AND student_pk=? LIMIT 1');
    $chk->execute([$id, $uid]);
    $task = $chk->fetch();
    if (!$task) json_out(['ok' => false, 'message' => 'Task not found.'], 404);

    if ($action === 'delete') {
        if (($task['source'] ?? 'company') !== 'personal') json_out(['ok' => false, 'message' => 'Only your own tasks can be deleted.'], 403);
        db()->prepare('DELETE FROM tasks WHERE id=? AND student_pk=?')->execute([$id, $uid]);
        json_out(['ok' => true, 'message' => 'Task deleted.']);
    }

    $status = (string)($_POST['status'] ?? $task['status']);
    if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) $status = $task['status'];
    $comment = mb_substr(trim((string)($_POST['comment'] ?? '')), 0, 2000);
    db()->prepare('INSERT INTO task_updates(task_id,student_pk,status,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),comment=VALUES(comment),updated_at=NOW()')->execute([$id, $uid, $status, $comment]);
    db()->prepare('UPDATE tasks SET status=?,updated_at=NOW() WHERE id=? AND student_pk=?')->execute([$status, $id, $uid]);
    add_activity($uid, $status === 'completed' ? 'Completed an OJT task' : 'Updated an OJT task');
    json_out(['ok' => true, 'status' => $status, 'message' => 'Task update saved.']);
} catch (Throwable $e) {
    json_out(['ok' => false, 'message' => 'Could not save the task: ' . $e->getMessage()], 500);
}
