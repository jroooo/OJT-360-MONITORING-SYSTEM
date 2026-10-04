<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/helpers.php'; require_login();
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'message'=>'POST required.']);exit;}
verify_csrf(); $uid=(int)$_SESSION['user_id']; $id=(int)($_POST['task_id']??0);
if(!$id){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Task not found.']);exit;}
try{db()->exec("CREATE TABLE IF NOT EXISTS task_updates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,task_id INT UNSIGNED NOT NULL,user_id INT UNSIGNED NOT NULL,status ENUM('pending','in_progress','completed') NOT NULL,comment TEXT DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY task_user(task_id,user_id),FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");db()->exec("CREATE TABLE IF NOT EXISTS company_notifications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id INT UNSIGNED NOT NULL,task_id INT UNSIGNED NULL,user_id INT UNSIGNED NULL,title VARCHAR(180) NOT NULL,message TEXT NOT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(company_id,is_read),FOREIGN KEY(company_id) REFERENCES companies(id) ON DELETE CASCADE,FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE SET NULL,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");}catch(Throwable $e){}
$check=db()->prepare('SELECT id,title,company_id,status FROM tasks WHERE id=? AND user_id=? LIMIT 1');$check->execute([$id,$uid]);$task=$check->fetch();
if(!$task){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Task not found.']);exit;}
$status=$_POST['status']??$task['status']; if(!in_array($status,['pending','in_progress','completed'],true))$status=$task['status']; $comment=trim((string)($_POST['comment']??'')); if(mb_strlen($comment)>2000)$comment=mb_substr($comment,0,2000);
$up=db()->prepare('INSERT INTO task_updates(task_id,user_id,status,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),comment=VALUES(comment),updated_at=NOW()');$up->execute([$id,$uid,$status,$comment]);
db()->prepare('UPDATE tasks SET status=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$status,$id,$uid]);
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,$status==='completed'?'Completed an OJT task':($status==='in_progress'?'Updated an OJT task to in progress':'Updated an OJT task')]);
if(!empty($task['company_id'])){try{$msg='Student updated “'.$task['title'].'” to '.ucwords(str_replace('_',' ',$status)).'.'.($comment!==''?' Comment: '.$comment:'');$n=db()->prepare('INSERT INTO company_notifications(company_id,task_id,user_id,title,message) VALUES(?,?,?,?,?)');$n->execute([(int)$task['company_id'],$id,$uid,'Task update from student',$msg]);}catch(Throwable $e){}}
// Also keep the student informed through the existing notification system.
db()->prepare('INSERT INTO notifications(user_id,title,message,icon) VALUES(?,?,?,?)')->execute([$uid,'Task update saved','Your progress update for “'.$task['title'].'” was saved.',$status==='completed'?'✓':'↻']);
echo json_encode(['ok'=>true,'status'=>$status,'message'=>'Task update saved.']);
