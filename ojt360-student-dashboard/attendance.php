<?php
$page_title = 'Attendance';
require_once 'includes/header.php';
$uid = (int)$user['id'];
$activeStmt = db()->prepare('SELECT * FROM attendance WHERE user_id=? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1');
$activeStmt->execute([$uid]);
$active = $activeStmt->fetch();
$stmt = db()->prepare('SELECT a.*, c.name company_name FROM attendance a JOIN companies c ON c.id=a.company_id WHERE a.user_id=? ORDER BY a.time_in DESC');
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();
$todayStmt=db()->prepare("SELECT COALESCE(SUM(duration_minutes),0) FROM attendance WHERE user_id=? AND DATE(time_in)=CURDATE()");$todayStmt->execute([$uid]);$todayMinutes=(int)$todayStmt->fetchColumn();
$internStmt=db()->prepare('SELECT required_hours,completed_hours FROM internships WHERE user_id=? ORDER BY id DESC LIMIT 1');$internStmt->execute([$uid]);$internship=$internStmt->fetch();
$requiredHours=(float)($internship['required_hours']??480);$completedHours=(float)($internship['completed_hours']??0);$remainingHours=max(0,$requiredHours-$completedHours);
?>
<div class="page-intro"><div><span class="eyebrow">TIME TRACKING</span><h2>Attendance</h2><p id="attendanceInstruction">Scan the company QR code to record a secure Time In or Time Out.</p></div></div>

<div class="attendance-summary"><div class="attendance-metric"><span>Today</span><strong><?=e(floor($todayMinutes/60).'h '.($todayMinutes%60).'m')?></strong><small>Completed today</small></div><div class="attendance-metric"><span>Total achieved</span><strong><?=e(number_format($completedHours,2))?> h</strong><small>OJT hours recorded</small></div><div class="attendance-metric"><span>Remaining</span><strong><?=e(number_format($remainingHours,2))?> h</strong><small>Until <?=e(number_format($requiredHours,0))?> required hours</small></div></div>
<div class="attendance-grid">
<section class="panel scanner-panel">
    <div class="scanner-head"><div class="scanner-icon">▣</div><div><h3>Company QR Scanner</h3><p>Allow camera access, then point it at your company's OJT360 QR code.</p></div></div>
    <div id="reader" class="qr-reader"></div>
    <div id="scanStatus" class="scan-status"><?= $active ? 'Ready for Time Out scan.' : 'Ready for Time In scan.' ?></div>
    <div class="scanner-actions"><button class="btn primary" id="startScan"><?= $active ? 'Scan Time Out QR' : 'Scan Time In QR' ?></button><button class="btn secondary" id="stopScan">Stop</button></div>
    <div class="manual-qr">
        <label>Testing / manual QR value<input id="manualQr" placeholder="e.g. OJT360-COMPANY-001"></label>
        <button class="btn secondary" id="manualScan">Validate QR</button>
    </div>
    <div class="secure-note">QR validation is performed by PHP on the server before attendance is saved.</div>
</section>

<section class="panel current-panel">
    <div class="panel-head"><div><span class="eyebrow">TODAY</span><h3>Current Status</h3></div></div>
    <?php if ($active): ?>
        <div class="current-status active"><span class="big-status">●</span><strong>Timed In</strong><small><?= e(date('M d, Y · h:i A', strtotime($active['time_in']))) ?></small></div>
        <div class="duration-card active-duration"><div class="duration-top"><div><span>Current session</span><small class="duration-state"><i></i> Time is running</small></div><span class="live-badge"><i></i> LIVE</span></div><div class="duration-live-row"><div><strong id="liveDuration">Calculating...</strong><small>Today total: <b id="todayLive">0h 00m</b></small></div><div class="wave-progress" aria-label="Live time progress"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></div><em>Time tracking is active and updating live.</em></div>
    <?php else: ?>
        <div class="current-status"><span class="big-status">○</span><strong>Not Timed In</strong><small>Scan your company QR code to begin.</small></div>
    <?php endif; ?>
</section>
</div>

<section class="panel table-panel">
<div class="panel-head"><div><span class="eyebrow">HISTORY</span><h3>Attendance History</h3></div><a class="btn secondary small" href="api/attendance.php?action=export">Export CSV</a></div>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Company</th><th>Time In</th><th>Time Out</th><th>Duration</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e(date('M d, Y', strtotime($r['time_in']))) ?></td><td><?= e($r['company_name']) ?></td><td><?= e(date('h:i A', strtotime($r['time_in']))) ?></td><td><?= $r['time_out'] ? e(date('h:i A', strtotime($r['time_out']))) : '—' ?></td><td><?= $r['duration_minutes'] !== null ? e(floor($r['duration_minutes']/60).'h '.($r['duration_minutes']%60).'m') : '—' ?></td><td><span class="pill <?= $r['time_out'] ? 'green-pill' : 'orange-pill' ?>"><?= $r['time_out'] ? 'Completed' : 'Active' ?></span></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">No attendance records yet.</td></tr><?php endif; ?>
</tbody></table></div>
</section>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
(function(){
    const el=document.getElementById("liveDuration");
    const started=<?= $active ? json_encode(strtotime($active['time_in'])) : 'null' ?>;
    if(!el || !started) return;
    function tick(){
        const seconds=Math.max(0, Math.floor(Date.now()/1000)-started);
        const h=Math.floor(seconds/3600), m=Math.floor((seconds%3600)/60), sec=seconds%60;
        el.textContent=h+"h "+String(m).padStart(2,"0")+"m "+String(sec).padStart(2,"0")+"s";
        const today=document.getElementById("todayLive"); if(today){const base=<?=json_encode($todayMinutes)?>; const total=base+Math.floor(seconds/60); today.textContent=Math.floor(total/60)+"h "+String(total%60).padStart(2,"0")+"m";}
    }
    tick(); setInterval(tick,1000);
})();
</script>
<?php require_once 'includes/footer.php'; ?>
