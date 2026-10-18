<?php
/* Expects: $company (profile array), $pageKey, $pageTitle, $eyebrow */
$stats = company_stats($company['id']);
$nav = [
    'dashboard'   => ['company_dashboard.php', 'Overview',            'fa-solid fa-table-cells-large', null],
    'students'    => ['assigned_students.php', 'Assigned Students',   'fa-solid fa-user-group',        $stats['interns']],
    'evaluations' => ['evaluations.php',       'Evaluations',         'fa-solid fa-clipboard-check',   $stats['pending']],
    'qr'          => ['dynamic.php',           'Dynamic QR Code',     'fa-solid fa-qrcode',            null],
    'messages'    => ['messages.php',          'Messages',            'fa-regular fa-comment-dots',    null],
    'reports'     => ['reports.php',           'Reports & Analytics', 'fa-solid fa-chart-line',        null],
];
$flashes = flash_get();
?><!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OJT360 | <?= e($pageTitle) ?></title>
<script>(function(){try{var t=localStorage.getItem('ojt360-theme')||'light';document.documentElement.setAttribute('data-theme',t);if(localStorage.getItem('ojt360-sidebar')==='collapsed'){document.documentElement.classList.add('sb-collapsed');}}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/company.css">
</head>
<body>
<div class="shell">
<aside class="sidebar" id="sidebar" aria-label="Company navigation">
  <a class="brand" href="company_dashboard.php" aria-label="OJT360 Company Portal home">
    <svg width="34" height="34" viewBox="0 0 34 34" fill="none" aria-hidden="true"><circle cx="11" cy="12" r="6" stroke="#22d3ee" stroke-width="2.4"/><circle cx="23" cy="12" r="6" stroke="#60a5fa" stroke-width="2.4"/><circle cx="17" cy="23" r="6" stroke="#38bdf8" stroke-width="2.4"/></svg>
    <span class="brand-copy"><strong>OJT360</strong><small>COMPANY PORTAL</small></span>
  </a>
  <div class="nav-label">MAIN MENU</div>
  <nav class="nav">
    <?php foreach ($nav as $key => [$href, $label, $icon, $badge]): ?>
      <a href="<?= e($href) ?>" class="<?= $pageKey === $key ? 'active' : '' ?>" <?= $pageKey === $key ? 'aria-current="page"' : '' ?> title="<?= e($label) ?>">
        <i class="<?= e($icon) ?>"></i><span class="lbl"><?= e($label) ?></span>
        <?php if ($badge !== null && $badge > 0): ?><b class="nav-badge <?= $key === 'evaluations' ? 'warn' : '' ?>"><?= (int)$badge ?></b><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-bottom">
    <a class="nav-link <?= $pageKey === 'help' ? 'active' : '' ?>" href="help.php" title="Help & Support"><i class="fa-regular fa-circle-question"></i><span class="lbl">Help &amp; Support</span></a>
    <a class="nav-link <?= $pageKey === 'settings' ? 'active' : '' ?>" href="settings.php" title="Settings"><i class="fa-solid fa-gear"></i><span class="lbl">Settings</span></a>
    <a class="user-card" href="settings.php" title="Account settings">
      <span class="avatar"><?= e($company['initials']) ?></span>
      <span class="uc-copy lbl"><strong><?= e($company['representative']) ?></strong><small><?= e($company['role']) ?></small></span>
    </a>
    <button type="button" class="nav-link logout" data-modal-open="logoutModal" title="Log out"><i class="fa-solid fa-arrow-right-from-bracket"></i><span class="lbl">Logout</span></button>
  </div>
</aside>
<div class="sb-overlay" id="sbOverlay" hidden></div>
<div class="main">
  <header class="topbar">
    <button type="button" class="icon-btn" id="sbToggle" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
    <div class="crumbs"><span>OJT360</span><span class="sep">/</span><strong><?= e($pageTitle) ?></strong></div>
    <div class="top-actions">
      <button type="button" class="icon-btn" id="themeToggle" aria-label="Toggle light/dark theme" title="Toggle theme"><i class="fa-solid fa-moon t-moon"></i><i class="fa-solid fa-sun t-sun"></i></button>
      <a class="icon-btn" href="evaluations.php" aria-label="Pending evaluations: <?= (int)$stats['pending'] ?>" title="Pending evaluations">
        <i class="fa-regular fa-bell"></i><?php if ($stats['pending'] > 0): ?><b class="dot"><?= $stats['pending'] > 9 ? '9+' : (int)$stats['pending'] ?></b><?php endif; ?>
      </a>
      <a class="avatar sm" href="settings.php" title="<?= e($company['representative']) ?>"><?= e($company['initials']) ?></a>
    </div>
  </header>
  <main class="content" id="content">
    <?php foreach ($flashes as [$ft, $fm]): ?>
      <div class="alert <?= $ft === 'success' ? 'ok' : ($ft === 'info' ? 'info' : 'err') ?>" role="<?= $ft === 'error' ? 'alert' : 'status' ?>"><?= e($fm) ?></div>
    <?php endforeach; ?>
