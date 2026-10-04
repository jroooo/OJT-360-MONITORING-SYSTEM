<?php
declare(strict_types=1);
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_login();
$user=current_user();
$qr='OJT360-COMPANY-001';
$stmt=db()->prepare('SELECT c.name,c.address,q.is_active FROM company_qr_codes q JOIN companies c ON c.id=q.company_id WHERE q.qr_value=? LIMIT 1');
$stmt->execute([$qr]);
$company=$stmt->fetch();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>OJT360 QR Test</title><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="simple-page"><div class="simple-card"><div class="brand centered"><div class="brand-mark">O</div><strong>OJT360 QR Test</strong></div><h1>Company QR Test</h1><p>This is the demo QR used to test the Attendance scanner. Scanning it from Attendance performs the normal server-side attendance validation.</p><div class="qr-code-box"><img src="assets/ojt360-demo-qr.png" alt="OJT360 demo company QR code" style="width:220px;height:220px;image-rendering:pixelated"></div><code><?= e($qr) ?></code><?php if($company): ?><p><strong><?= e($company['name']) ?></strong><br><?= e($company['address'] ?? '') ?><br><span class="pill <?= $company['is_active'] ? 'green-pill' : 'orange-pill' ?>"><?= $company['is_active'] ? 'QR Active' : 'QR Inactive' ?></span></p><?php else: ?><p class="flash error">Demo QR is not installed in the database. Run the database schema/setup.</p><?php endif; ?><a class="btn primary wide" href="attendance.php">Back to Attendance</a><a class="btn secondary wide" href="setup-check.php">Run Setup Check</a></div></body></html>
