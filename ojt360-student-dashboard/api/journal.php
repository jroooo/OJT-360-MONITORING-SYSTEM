<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function clean_journal_html(string $html, int $uid): string {
    $html = trim($html);
    if ($html === '') return '';
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><blockquote><div><span><a><img>');
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace_callback('/\s(href|src)\s*=\s*(["\'])(.*?)\2/i', function($m) use ($uid) {
        $attr = strtolower($m[1]);
        $url = trim($m[3]);
        if ($attr === 'src') {
            $allowedLocal = 'uploads/journal/' . $uid . '/';
            if (str_starts_with($url, $allowedLocal) || preg_match('#^https?://#i', $url)) return ' src=' . $m[2] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $m[2];
            return '';
        }
        if (preg_match('#^(https?://|mailto:)#i', $url)) return ' href=' . $m[2] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $m[2];
        return '';
    }, $html) ?? $html;
    $html = preg_replace_callback('/\sstyle\s*=\s*([\"\'])(.*?)\1/i', function($m) { $allowed=['text-align','font-family','font-size','line-height','float','width','height','vertical-align','margin','display','transform']; $out=[]; foreach(explode(';',$m[2]) as $decl){$parts=explode(':',$decl,2); if(count($parts)!==2) continue; $prop=strtolower(trim($parts[0])); $val=trim($parts[1]); if(!in_array($prop,$allowed,true) || preg_match('/[<>`]/',$val)) continue; $out[]=$prop.':'.$val;} return $out ? ' style="'.htmlspecialchars(implode(';',$out),ENT_QUOTES,'UTF-8').'"' : ''; }, $html) ?? $html;
    return trim($html);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method not allowed']); exit; }
verify_csrf();
// Keep the schema compatible with existing OJT360 databases.
try { $cols=db()->query("SHOW COLUMNS FROM journals LIKE 'orientation'")->fetchAll(); if(!$cols){db()->exec("ALTER TABLE journals ADD COLUMN orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait' AFTER status");} } catch(Throwable $e) {}
$uid=(int)$_SESSION['user_id'];
$id=(int)($_POST['journal_id']??0);
$week=trim((string)($_POST['week_start']??''));
$title=trim((string)($_POST['title']??''));
$content=clean_journal_html((string)($_POST['content']??''), $uid);
$submit=(int)($_POST['submit']??0);
$orientation=($_POST['orientation']??'portrait')==='landscape'?'landscape':'portrait';

if (!$week || !$title || !$content) { echo json_encode(['ok'=>false,'message'=>'Week, title, and journal content are required.']); exit; }
if (mb_strlen($title)>180) { echo json_encode(['ok'=>false,'message'=>'The journal title is too long.']); exit; }
if (mb_strlen($content)>60000) { echo json_encode(['ok'=>false,'message'=>'The journal entry is too large. Please use fewer or smaller images.']); exit; }
$status=$submit?'submitted':'draft';
if ($id) {
    $check=db()->prepare('SELECT id FROM journals WHERE id=? AND user_id=?');$check->execute([$id,$uid]);
    if(!$check->fetch()){echo json_encode(['ok'=>false,'message'=>'Journal not found.']);exit;}
    db()->prepare('UPDATE journals SET week_start=?,title=?,content=?,status=?,orientation=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$week,$title,$content,$status,$orientation,$id,$uid]);
} else {
    $same=db()->prepare('SELECT id FROM journals WHERE user_id=? AND week_start=? LIMIT 1');$same->execute([$uid,$week]);$existing=$same->fetch();
    if($existing){db()->prepare('UPDATE journals SET title=?,content=?,status=?,orientation=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$title,$content,$status,$orientation,$existing['id'],$uid]);$id=(int)$existing['id'];}
    else {db()->prepare('INSERT INTO journals(user_id,week_start,title,content,status,orientation) VALUES(?,?,?,?,?,?)')->execute([$uid,$week,$title,$content,$status,$orientation]);$id=(int)db()->lastInsertId();}
}
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,$submit?'Submitted weekly journal':'Saved weekly journal draft']);
if($submit) db()->prepare('INSERT INTO notifications(user_id,title,message,icon) VALUES(?,?,?,?)')->execute([$uid,'Journal submitted','Your weekly journal has been submitted.','▤']);
echo json_encode(['ok'=>true,'message'=>$submit?'Journal submitted successfully.':'Draft saved.','journal_id'=>$id]);
