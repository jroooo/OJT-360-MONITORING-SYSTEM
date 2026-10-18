<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
$uid = (int)$_SESSION['student_pk'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Invalid request.'], 405);
verify_csrf();

function hm_to_minutes(string $t): ?int {
    if (!preg_match('/^(\d{1,2}):(\d{2})/', trim($t), $m)) return null;
    return (int)$m[1] * 60 + (int)$m[2];
}
function drop_evidence(?string $name): void {
    if (!$name) return;
    $p = __DIR__ . '/../uploads/daily_logs/' . basename($name);
    if (is_file($p)) @unlink($p);
}

try {
    $action = (string)($_POST['action'] ?? 'create');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $q = db()->prepare('SELECT evidence_image, attendance_id FROM daily_logs WHERE id=? AND student_pk=?');
        $q->execute([$id, $uid]);
        $old = $q->fetch();
        if (!$old) json_out(['ok' => false, 'message' => 'Entry not found.'], 404);
        db()->prepare('DELETE FROM daily_logs WHERE id=? AND student_pk=?')->execute([$id, $uid]);
        drop_evidence($old['evidence_image']);
        add_activity($uid, 'Deleted a daily log');
        json_out(['ok' => true, 'message' => 'Entry deleted.']);
    }

    $date = (string)($_POST['log_date'] ?? date('Y-m-d'));
    $title = trim((string)($_POST['title'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Please choose a valid date.');
    if ($date > date('Y-m-d')) throw new RuntimeException('You cannot log work for a future date.');
    if ($title === '') throw new RuntimeException('Please enter the task or activity title.');
    if (mb_strlen($title) > 180) throw new RuntimeException('The task title is too long (180 characters max).');
    if (mb_strlen($desc) > 6000) throw new RuntimeException('The description is too long.');

    $existing = null;
    if ($id) {
        $q = db()->prepare('SELECT * FROM daily_logs WHERE id=? AND student_pk=?');
        $q->execute([$id, $uid]);
        $existing = $q->fetch();
        if (!$existing) throw new RuntimeException('Entry not found.');
    }
    $isAuto = $existing && (int)$existing['is_auto'] === 1;

    $photo = save_evidence_upload('evidence');
    $remove = !empty($_POST['remove_evidence']);

    if ($isAuto) {
        // time, break and hours come from attendance; only the story is editable
        $sets = 'title=?, description=?'; $args = [$title, $desc];
    } else {
        $tin = trim((string)($_POST['time_in'] ?? '')); $tout = trim((string)($_POST['time_out'] ?? ''));
        $break = max(0, min(720, (int)($_POST['break_minutes'] ?? 0)));
        $hours = 0.0;
        if ($tin !== '' && $tout !== '') {
            $a = hm_to_minutes($tin); $b = hm_to_minutes($tout);
            if ($a === null || $b === null) throw new RuntimeException('Please enter valid times.');
            if ($b <= $a) throw new RuntimeException('Time out must be later than time in.');
            $net = $b - $a - $break;
            if ($net < 0) throw new RuntimeException('The break is longer than the time worked.');
            $hours = round($net / 60, 2);
        } elseif ($tin !== '' || $tout !== '') {
            throw new RuntimeException('Enter both a time in and a time out, or leave both empty.');
        }
        $sets = 'log_date=?, title=?, description=?, hours=?, time_in=?, time_out=?, break_minutes=?';
        $args = [$date, $title, $desc, $hours, $tin !== '' ? $tin . ':00' : null, $tout !== '' ? $tout . ':00' : null, $break];
        if ($tin !== '' && strlen($tin) === 8) { $args[4] = $tin; }
        if ($tout !== '' && strlen($tout) === 8) { $args[5] = $tout; }
    }

    if ($existing) {
        if ($photo) { $sets .= ', evidence_image=?'; $args[] = $photo; drop_evidence($existing['evidence_image']); }
        elseif ($remove) { $sets .= ', evidence_image=NULL'; drop_evidence($existing['evidence_image']); }
        $args[] = $id; $args[] = $uid;
        db()->prepare("UPDATE daily_logs SET $sets WHERE id=? AND student_pk=?")->execute($args);
        add_activity($uid, 'Updated a daily log');
        json_out(['ok' => true, 'message' => 'Entry updated.', 'id' => $id]);
    }

    db()->prepare("INSERT INTO daily_logs(student_pk,log_date,title,description,hours,time_in,time_out,break_minutes,evidence_image,is_auto) VALUES(?,?,?,?,?,?,?,?,?,0)")
        ->execute([$uid, $date, $title, $desc, $args[3], $args[4], $args[5], $args[6], $photo]);
    add_activity($uid, 'Added a daily log');
    json_out(['ok' => true, 'message' => 'Entry saved.', 'id' => (int)db()->lastInsertId()]);
} catch (Throwable $e) {
    json_out(['ok' => false, 'message' => $e->getMessage()], 400);
}
