<?php
$page_title = 'Help & Support';
require_once 'includes/header.php';
$uid=(int)$user['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $subject=trim($_POST['subject']??''); $message=trim($_POST['message']??'');
    if ($subject && $message) {
        db()->prepare('INSERT INTO support_requests(user_id,subject,message) VALUES(?,?,?)')->execute([$uid,$subject,$message]);
        db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Submitted a support request']);
        flash('success','Your support request has been submitted.');
    } else flash('error','Please complete the subject and message.');
    header('Location: help.php'); exit;
}
$faqs=[
['How do I time in?','Open Attendance, start the QR scanner, and scan your assigned company QR code. The server validates the QR before creating the attendance record.'],
['How do I time out?','If you have an active attendance session, scan the same valid company QR again. OJT360 calculates the session duration automatically.'],
['How do I submit my journal?','Open Journal, write your weekly entry, then select Submit Journal. You can save a draft first.'],
['Can I print my journal?','Yes. Use Print / Save PDF on the Journal page and choose Save as PDF in your browser print dialog.'],
['Where can I check my hours?','Your dashboard shows completed versus required hours. Attendance sessions contribute to your completed hours after Time Out.'],
];
?>
<div class="page-intro"><div><span class="eyebrow">ASSISTANCE</span><h2>Help & Support</h2><p>Find quick answers or send a request to your OJT adviser/support team.</p></div></div>
<div class="help-grid">
<section class="panel"><div class="panel-head"><div><span class="eyebrow">FAQ</span><h3>Frequently Asked Questions</h3></div></div>
<div class="faq-list"><?php foreach($faqs as $n=>$f): ?><details <?= $n===0?'open':'' ?>><summary><?= e($f[0]) ?></summary><p><?= e($f[1]) ?></p></details><?php endforeach; ?></div></section>
<section class="panel"><div class="panel-head"><div><span class="eyebrow">SUPPORT</span><h3>Send a Request</h3></div></div>
<form method="post" class="form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Subject<input name="subject" required placeholder="e.g. Attendance issue"></label><label>Message<textarea name="message" rows="9" required placeholder="Describe your concern..."></textarea></label><button class="btn primary">Submit Request</button></form></section>
</div>
<?php require_once 'includes/footer.php'; ?>
