<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'POST required.'], 405);
verify_csrf();
$uid = (int)$_SESSION['student_pk'];
$action = (string)($_POST['action'] ?? 'save');

try {
    if ($action === 'delete') {
        $id = (int)($_POST['journal_id'] ?? 0);
        $chk = db()->prepare('SELECT id FROM journals WHERE id=? AND student_pk=?');
        $chk->execute([$id, $uid]);
        if (!$chk->fetch()) json_out(['ok' => false, 'message' => 'Draft not found.'], 404);
        db()->prepare('DELETE FROM journals WHERE id=? AND student_pk=?')->execute([$id, $uid]);
        add_activity($uid, 'Deleted a weekly journal draft');
        json_out(['ok' => true, 'message' => 'Draft deleted.']);
    }

    $id = (int)($_POST['journal_id'] ?? 0);
    $week = trim((string)($_POST['week_start'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $content = sanitize_rich_html((string)($_POST['content'] ?? ''), $uid);
    $orientation = ($_POST['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
    $size = ($_POST['page_size'] ?? 'A4') === 'Letter' ? 'Letter' : 'A4';
    $margin = in_array($_POST['margin_preset'] ?? 'normal', ['normal', 'narrow', 'wide'], true) ? (string)$_POST['margin_preset'] : 'normal';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) json_out(['ok' => false, 'message' => 'Please choose the week starting date.']);
    if ($title === '') json_out(['ok' => false, 'message' => 'Please enter a week title.']);
    if (mb_strlen($title) > 180) json_out(['ok' => false, 'message' => 'The journal title is too long.']);
    if (trim(strip_tags($content, '<img>')) === '') json_out(['ok' => false, 'message' => 'Write something in the journal before saving the draft.']);
    if (mb_strlen($content) > 400000) json_out(['ok' => false, 'message' => 'The journal entry is too large.']);
    $week = week_start_of($week); // always store the Monday of that week

    if ($id) {
        $chk = db()->prepare('SELECT id FROM journals WHERE id=? AND student_pk=?');
        $chk->execute([$id, $uid]);
        if (!$chk->fetch()) json_out(['ok' => false, 'message' => 'Draft not found.'], 404);
        $dup = db()->prepare('SELECT id FROM journals WHERE student_pk=? AND week_start=? AND id<>? LIMIT 1');
        $dup->execute([$uid, $week, $id]);
        if ($dup->fetch()) json_out(['ok' => false, 'message' => 'You already have a draft for that week. Open it from your drafts list.']);
        db()->prepare("UPDATE journals SET week_start=?,title=?,content=?,status='draft',orientation=?,page_size=?,margin_preset=?,updated_at=NOW() WHERE id=? AND student_pk=?")->execute([$week, $title, $content, $orientation, $size, $margin, $id, $uid]);
    } else {
        $same = db()->prepare('SELECT id FROM journals WHERE student_pk=? AND week_start=? LIMIT 1');
        $same->execute([$uid, $week]);
        $existing = $same->fetchColumn();
        if ($existing) {
            $id = (int)$existing;
            db()->prepare("UPDATE journals SET title=?,content=?,status='draft',orientation=?,page_size=?,margin_preset=?,updated_at=NOW() WHERE id=? AND student_pk=?")->execute([$title, $content, $orientation, $size, $margin, $id, $uid]);
        } else {
            db()->prepare("INSERT INTO journals(student_pk,week_start,title,content,status,orientation,page_size,margin_preset) VALUES(?,?,?,?, 'draft',?,?,?)")->execute([$uid, $week, $title, $content, $orientation, $size, $margin]);
            $id = (int)db()->lastInsertId();
        }
    }
    if (empty($_POST['autosave'])) add_activity($uid, 'Saved weekly journal draft');
    json_out(['ok' => true, 'message' => 'Draft saved.', 'journal_id' => $id, 'week_start' => $week, 'saved_at' => date('g:i A')]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'message' => 'Could not save: ' . $e->getMessage()], 500);
}
