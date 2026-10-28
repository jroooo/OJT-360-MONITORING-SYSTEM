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
    if ($action === 'rebuild') {
        // Re-assemble every weekly draft into one document (used by "Rebuild from drafts").
        $q = db()->prepare('SELECT week_start,title,content FROM journals WHERE student_pk=? ORDER BY week_start ASC');
        $q->execute([$uid]);
        $html = compilation_from_drafts($q->fetchAll());
        json_out(['ok' => true, 'html' => $html]);
    }

    $orientation = ($_POST['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait';
    $size = ($_POST['page_size'] ?? 'A4') === 'Letter' ? 'Letter' : 'A4';
    $margin = in_array($_POST['margin_preset'] ?? 'normal', ['normal', 'narrow', 'wide'], true) ? (string)$_POST['margin_preset'] : 'normal';
    $title = trim((string)($_POST['title'] ?? ''));
    $content = sanitize_rich_html((string)($_POST['content'] ?? ''), $uid);
    if ($title === '') json_out(['ok' => false, 'message' => 'Please enter a document title.'], 422);
    if (mb_strlen($title) > 180) json_out(['ok' => false, 'message' => 'The document title is too long.'], 422);
    if (trim(strip_tags($content, '<img>')) === '') json_out(['ok' => false, 'message' => 'The compilation is empty.'], 422);
    if (mb_strlen($content) > 900000) json_out(['ok' => false, 'message' => 'The compilation is too large.'], 422);

    $find = db()->prepare('SELECT id FROM journal_compilations WHERE student_pk=?');
    $find->execute([$uid]);
    $id = $find->fetchColumn();
    if ($id) {
        db()->prepare('UPDATE journal_compilations SET title=?,content=?,orientation=?,page_size=?,margin_preset=?,updated_at=NOW() WHERE id=? AND student_pk=?')->execute([$title, $content, $orientation, $size, $margin, $id, $uid]);
    } else {
        db()->prepare('INSERT INTO journal_compilations(student_pk,title,content,orientation,page_size,margin_preset) VALUES(?,?,?,?,?,?)')->execute([$uid, $title, $content, $orientation, $size, $margin]);
        $id = db()->lastInsertId();
    }

    if ($action === 'submit') {
        // Snapshot for the adviser. The adviser dashboard will read journal_submissions (adviser_id is filled in there).
        db()->prepare("INSERT INTO journal_submissions(student_pk,title,content,orientation,page_size,margin_preset,status) VALUES(?,?,?,?,?,?,'pending')")
            ->execute([$uid, $title, $content, $orientation, $size, $margin]);
        $subId = (int)db()->lastInsertId();
        add_activity($uid, 'Submitted the final journal compilation to the adviser');
        add_notification($uid, 'Journal submitted', 'Your final journal compilation was sent to your adviser for review.', 'Γûñ');
        json_out(['ok' => true, 'message' => 'Submitted to your adviser. You can still edit and resubmit later.', 'submission_id' => $subId, 'submitted_at' => date('M j, Y g:i A')]);
    }

    add_activity($uid, 'Saved final journal compilation');
    json_out(['ok' => true, 'message' => 'Final compilation saved.', 'id' => (int)$id, 'saved_at' => date('g:i A')]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'message' => 'Could not save: ' . $e->getMessage()], 500);
}
