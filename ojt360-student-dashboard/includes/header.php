<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if (!$user) { header('Location: ../login.php'); exit; }
$page = basename($_SERVER['PHP_SELF']);
$flashes = get_flashes();
$uid = (int)$user['id'];
$profileImage = '';
if (!empty($user['profile_image'])) {
    $profileImageFile = __DIR__ . '/../uploads/profile/' . basename((string)$user['profile_image']);
    if (is_file($profileImageFile)) $profileImage = 'uploads/profile/' . basename((string)$user['profile_image']) . '?v=' . filemtime($profileImageFile);
}
$unreadStmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE student_pk=? AND is_read=0');
$unreadStmt->execute([$uid]);
$unread = (int)$unreadStmt->fetchColumn();
$trackerState = tracker_state($uid);
$settingsRow = student_settings($uid);
$page_title = $page_title ?? 'Dashboard';
$mainNav = [
    ['index.php', 'dashboard', 'Dashboard', ['index.php']],
    ['attendance.php', 'clock', 'Attendance', ['attendance.php']],
    ['journal.php', 'journal', 'Journal', ['journal.php', 'journal_compilation.php']],
    ['reports.php', 'reports', 'Reports', ['reports.php']],
];
$subNav = [
    ['help.php', 'help', 'Help & Support', ['help.php']],
    ['settings.php', 'settings', 'Settings', ['settings.php']],
];
$avatarInner = $profileImage ? '<img src="' . e($profileImage) . '" alt="Profile picture">' : e(initials($user['full_name']));
$bodyClass = 'page-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(basename($page, '.php')));
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title) ?> | OJT360</title>
<script>(function(){try{var t=localStorage.getItem('ojt360-theme');if(!t){t=localStorage.getItem('ojt360-dark')==='1'?'dark':'light';}var d=t==='dark'||(t==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var h=document.documentElement;h.setAttribute('data-theme',d?'dark':'light');h.setAttribute('data-theme-pref',t);if(localStorage.getItem('ojt360-sidebar')==='collapsed')h.classList.add('sb-collapsed');if(localStorage.getItem('ojt360-motion')==='reduce')h.classList.add('reduce-motion');}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=24">
<script>window.OJT360=<?= json_encode([
    'csrf' => csrf_token(),
    'userId' => $uid,
    'base' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') === '/' ? '' : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'),
    'profileImage' => $profileImage,
    'userName' => $user['full_name'],
    'tracker' => $trackerState,
    'schedule' => $settingsRow,
    'icons' => icon_paths(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
</head>
<body class="<?= e($bodyClass) ?>">
<div class="app-shell" id="appShell">
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
  <a class="brand" href="index.php" title="OJT360 dashboard">
    <?= logo_mark() ?>
    <div class="brand-copy"><strong>OJT360</strong><span>STUDENT PORTAL</span></div>
  </a>
  <nav class="nav">
    <?php foreach ($mainNav as $n): ?>
      <a class="<?= in_array($page, $n[3], true) ? 'active' : '' ?>" href="<?= e($n[0]) ?>" title="<?= e($n[2]) ?>"><?= icon($n[1]) ?><em><?= e($n[2]) ?></em></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-bottom">
    <nav class="nav nav-sub">
      <?php foreach ($subNav as $n): ?>
        <a class="<?= in_array($page, $n[3], true) ? 'active' : '' ?>" href="<?= e($n[0]) ?>" title="<?= e($n[2]) ?>"><?= icon($n[1]) ?><em><?= e($n[2]) ?></em></a>
      <?php endforeach; ?>
    </nav>
    <a class="mini-card <?= $page === 'profile.php' ? 'active' : '' ?>" href="profile.php" title="Open your profile">
      <div class="avatar"><?= $avatarInner ?></div>
      <div class="mini-user"><strong><?= e($user['full_name']) ?></strong><span><?= e($user['email']) ?></span></div>
    </a>
    <button type="button" class="logout-link" data-logout title="Logout"><?= icon('logout') ?><span>Logout</span></button>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="content">
<header class="topbar">
  <div class="top-left">
    <button type="button" class="icon-btn" id="menuBtn" aria-label="Toggle navigation" title="Toggle navigation"><?= icon('menu') ?></button>
    <div class="crumbs"><span>OJT360</span><i>/</i><strong><?= e($page_title) ?></strong></div>
  </div>
  <div class="top-actions">
    <a class="track-chip" id="trackChip" href="index.php" hidden><i class="live-dot"></i><span data-chip-label>Working</span><b data-chip-time>00:00:00</b></a>
    <span class="net-pill" id="netPill"><i></i><span>Online</span></span>
    <button type="button" class="icon-btn" id="themeQuick" aria-label="Switch light or dark mode" title="Switch light / dark"><span class="ic-sun"><?= icon('sun') ?></span><span class="ic-moon"><?= icon('moon') ?></span></button>
    <a class="icon-btn notification-btn" href="notifications.php" title="Notifications" aria-label="Notifications"><?= icon('bell') ?><?php if ($unread > 0): ?><b class="notif-dot"><?= $unread > 9 ? '9+' : $unread ?></b><?php endif; ?></a>
    <div class="profile-menu">
      <button type="button" class="profile-chip" id="profileMenuButton" aria-label="Open profile menu" aria-haspopup="true" aria-expanded="false"><div class="avatar small" data-avatar><?= $avatarInner ?></div></button>
      <div class="profile-dropdown" id="profileDropdown" role="menu">
        <div class="profile-dropdown-head"><div class="avatar small" data-avatar><?= $avatarInner ?></div><div><strong><?= e($user['full_name']) ?></strong><span><?= e($user['student_no']) ?></span></div></div>
        <a href="profile.php" role="menuitem"><?= icon('user') ?> View Profile</a>
        <button type="button" id="headerUploadProfile" role="menuitem"><?= icon('camera') ?> Upload Photo</button>
        <button type="button" id="headerDeleteProfile" class="profile-delete" role="menuitem"><?= icon('trash') ?> Delete Photo</button>
      </div>
      <input type="file" id="headerProfileInput" accept="image/jpeg,image/png,image/webp" hidden>
    </div>
  </div>
</header>
<main class="main">
<?php foreach ($flashes as $f): ?><div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div><?php endforeach; ?>
