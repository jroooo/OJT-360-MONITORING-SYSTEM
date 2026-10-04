<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Cache-Control: no-store');
$uid=(int)$_SESSION['user_id'];

if (($_GET['action'] ?? '') === 'export') {
    $stmt=db()->prepare('SELECT a.time_in,a.time_out,a.duration_minutes,c.name company_name FROM attendance a JOIN companies c ON c.id=a.company_id WHERE a.user_id=? ORDER BY a.time_in DESC');
    $stmt->execute([$uid]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ojt360-attendance.csv"');
    $out=fopen('php://output','w'); fputcsv($out,['Date','Company','Time In','Time Out','Duration Minutes']);
    while($r=$stmt->fetch()) fputcsv($out,[$r['time_in'],$r['company_name'],$r['time_in'],$r['time_out'],$r['duration_minutes']]);
    fclose($out); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method not allowed']); exit; }
verify_csrf();
$qr=trim($_POST['qr_value']??'');
if ($qr==='') { echo json_encode(['ok'=>false,'message'=>'QR value is required.']); exit; }

$stmt=db()->prepare('SELECT * FROM company_qr_codes WHERE qr_value=? AND is_active=1 LIMIT 1'); $stmt->execute([$qr]); $qrRow=$stmt->fetch();
if (!$qrRow) { echo json_encode(['ok'=>false,'message'=>'Invalid or inactive company QR code.']); exit; }

// If the student has no internship assignment yet, create a working demo assignment
// for the company represented by the valid QR. If already assigned elsewhere, reject it.
$internStmt=db()->prepare('SELECT * FROM internships WHERE user_id=? ORDER BY id DESC LIMIT 1');
$internStmt->execute([$uid]);
$internship=$internStmt->fetch();
if (!$internship) {
    db()->prepare("INSERT INTO internships(user_id,company_id,required_hours,completed_hours,start_date,end_date,status) VALUES(?,?,480,0,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY),'active')")->execute([$uid,(int)$qrRow['company_id']]);
} elseif ((int)$internship['company_id'] !== (int)$qrRow['company_id']) {
    echo json_encode(['ok'=>false,'message'=>'This QR belongs to a different company than your current OJT assignment.']); exit;
}

$active=db()->prepare('SELECT * FROM attendance WHERE user_id=? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1'); $active->execute([$uid]); $session=$active->fetch();

if ($session) {
    if ((int)$session['company_id'] !== (int)$qrRow['company_id']) { echo json_encode(['ok'=>false,'message'=>'Please use the same company QR used for Time In.']); exit; }
    $inTs=strtotime($session['time_in']); $outTs=time(); $minutes=max(1,(int)floor(($outTs-$inTs)/60));
    db()->prepare('UPDATE attendance SET time_out=NOW(),duration_minutes=? WHERE id=?')->execute([$minutes,$session['id']]);
    db()->prepare('UPDATE internships SET completed_hours=LEAST(required_hours, completed_hours + (? / 60)) WHERE user_id=?')->execute([$minutes,$uid]);
    db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Timed out and completed an attendance session']);
    db()->prepare('INSERT INTO notifications(user_id,title,message,icon) VALUES(?,?,?,?)')->execute([$uid,'Time Out recorded','Your attendance session was completed successfully.','✓']);
    echo json_encode(['ok'=>true,'action'=>'timeout','message'=>'Time Out recorded. Duration: '.floor($minutes/60).'h '.($minutes%60).'m.']); exit;
}

db()->prepare('INSERT INTO attendance(user_id,company_id,time_in) VALUES(?,?,NOW())')->execute([$uid,$qrRow['company_id']]);
db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Timed in using company QR']);
db()->prepare('INSERT INTO notifications(user_id,title,message,icon) VALUES(?,?,?,?)')->execute([$uid,'Time In recorded','Your attendance has been started successfully.','◷']);
echo json_encode(['ok'=>true,'action'=>'timein','message'=>'Time In recorded successfully.']);
