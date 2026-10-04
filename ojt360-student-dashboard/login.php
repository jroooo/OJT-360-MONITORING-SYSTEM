<?php
require_once 'config.php';
require_once 'includes/helpers.php';

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? 'student') === 'student') {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$selectedRole = $_POST['role'] ?? 'student';

if (isset($_GET['logged_out'])) {
    $errorMessage = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? '';
    $identifier = trim($_POST['identifier'] ?? '');
    $selectedRole = $role;

    if (!in_array($role, ['student', 'employee', 'company'], true)) {
        $errorMessage = 'Invalid account type.';
    } elseif ($identifier === '' || $password === '') {
        $errorMessage = 'Please enter your username/ID and password.';
    } else {
        if ($role === 'student') {
            if (!preg_match('/^[0-9]{7}$/', $identifier)) {
                $errorMessage = 'Student ID must contain exactly 7 digits.';
            } else {
                $stmt = db()->prepare('SELECT id, first_name, middle_name, last_name, course, mobile_number, school_name, complete_address, student_id, institutional_email, password_hash FROM students WHERE student_id = ? LIMIT 1');
                $stmt->execute([$identifier]);
                $account = $stmt->fetch();

                if ($account && password_verify($password, $account['password_hash'])) {
                    // Make sure the student exists in the dashboard's users table.
                    $userStmt = db()->prepare('SELECT id FROM users WHERE student_no = ? OR email = ? LIMIT 1');
                    $userStmt->execute([$account['student_id'], $account['institutional_email']]);
                    $userId = $userStmt->fetchColumn();

                    $fullName = trim($account['first_name'] . ' ' . ($account['middle_name'] ? $account['middle_name'] . ' ' : '') . $account['last_name']);

                    if (!$userId) {
                        $createUser = db()->prepare('INSERT INTO users (full_name, email, password_hash, student_no, course, section, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'student\')');
                        $createUser->execute([
                            $fullName,
                            $account['institutional_email'],
                            $account['password_hash'],
                            $account['student_id'],
                            $account['course'],
                            'AI23',
                            $account['mobile_number'],
                            $account['complete_address']
                        ]);
                        $userId = (int)db()->lastInsertId();
                    } else {
                        $userId = (int)$userId;
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = 'student';
                    $_SESSION['student_id'] = $account['student_id'];
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));

                    header('Location: index.php');
                    exit;
                }

                $errorMessage = 'Invalid student ID or password.';
            }
        } elseif ($role === 'employee') {
            $stmt = db()->prepare('SELECT id, first_name, middle_name, last_name, employee_id, username, email, password_hash FROM employees WHERE username = ? OR email = ? OR employee_id = ? LIMIT 1');
            $stmt->execute([$identifier, $identifier, $identifier]);
            $account = $stmt->fetch();

            if ($account && password_verify($password, $account['password_hash'])) {
                $errorMessage = 'Employee account verified. The Employee Dashboard is not included in this Student Dashboard package.';
            } else {
                $errorMessage = 'Invalid employee username/ID or password.';
            }
        } else {
            $stmt = db()->prepare('SELECT id, company_name, company_username, representative_email, password_hash FROM companies WHERE company_username = ? OR representative_email = ? LIMIT 1');
            $stmt->execute([$identifier, $identifier]);
            $account = $stmt->fetch();

            if ($account && password_verify($password, $account['password_hash'])) {
                $errorMessage = 'Company account verified. The Company Dashboard is not included in this Student Dashboard package.';
            } else {
                $errorMessage = 'Invalid company username/email or password.';
            }
        }
    }
}

$pageTitle = 'OJT360 - Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent-gold:#e8b84b; --accent-primary:#ff2e7e; --paper:#fff8f0; --ink:#0f172a; }
        body { font-family:'Plus Jakarta Sans',sans-serif; }
        .hero-bg { background:radial-gradient(circle at 78% 30%,#0d3b66 0%,#081c33 50%,#0f172a 100%); }
        .tab-active { background-color:#fff;color:#0284c7;font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .hero-orbit-wrapper { position:absolute;top:50%;left:25%;transform:translate(-50%,-50%);z-index:0;pointer-events:none;opacity:.85; }
        .orbit-visual { position:relative;width:480px;height:480px;max-width:90vw;max-height:90vw; }
        .orbit-glow { position:absolute;inset:40px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.25) 0%,rgba(14,165,233,.12) 50%,transparent 75%);filter:blur(20px);animation:orbit-pulse 3.6s ease-in-out infinite; }
        .orbit-ring { position:absolute;top:50%;left:50%;border-radius:50%;border:1px dashed rgba(56,189,248,.25);transform:translate(-50%,-50%); }
        .ring-outer{width:480px;height:480px}.ring-inner{width:320px;height:320px}
        .orbit-track { --radius-size:var(--radius,240px);position:absolute;top:50%;left:50%;width:calc(var(--radius-size)*2);height:calc(var(--radius-size)*2);transform-origin:center;animation:orbit-spin var(--dur,16s) linear infinite; }
        @keyframes orbit-spin { from{transform:translate(-50%,-50%) rotate(var(--start,0deg))} to{transform:translate(-50%,-50%) rotate(calc(var(--start,0deg) + 360deg))} }
        .orbit-dot{position:absolute;top:0;left:50%;width:12px;height:12px;border-radius:50%;transform:translate(-50%,-50%)}
        .orbit-dot.small{width:8px;height:8px}.dot-gold{background:#38bdf8;box-shadow:0 0 12px rgba(56,189,248,.9)}.dot-pink{background:#22d3ee;box-shadow:0 0 12px rgba(34,211,238,.9)}.dot-white{background:#fff;box-shadow:0 0 10px rgba(255,255,255,.8)}
        @keyframes orbit-pulse{0%,100%{opacity:.6;transform:scale(1)}50%{opacity:.9;transform:scale(1.05)}}
        .orbit-center{position:absolute;top:50%;left:50%;width:110px;height:110px;transform:translate(-50%,-50%) rotate(45deg);background:linear-gradient(145deg,rgba(15,23,42,.8),rgba(8,28,51,.9));border:1px solid rgba(56,189,248,.3);border-radius:26px;box-shadow:0 0 30px rgba(14,165,233,.2);backdrop-filter:blur(4px)}
        .orbit-center-mark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;transform:rotate(-45deg);gap:6px}.orbit-center-mark span{width:10px;height:10px;border-radius:50%;border:2px solid #38bdf8}.orbit-center-mark span:nth-child(2){border-color:#22d3ee}.orbit-center-mark span:nth-child(3){border-color:#fff;margin-left:-22px;margin-top:18px}
    </style>
</head>
<body class="hero-bg min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-8 overflow-x-hidden antialiased relative">
    <div class="hero-orbit-wrapper hidden md:block">
        <div class="orbit-visual">
            <div class="orbit-glow"></div><div class="orbit-ring ring-outer"></div><div class="orbit-ring ring-inner"></div>
            <div class="orbit-track" style="--radius:240px;--dur:20s;--start:0deg"><div class="orbit-dot dot-gold"></div></div>
            <div class="orbit-track" style="--radius:240px;--dur:20s;--start:120deg"><div class="orbit-dot small dot-pink"></div></div>
            <div class="orbit-track" style="--radius:240px;--dur:20s;--start:240deg"><div class="orbit-dot small dot-white"></div></div>
            <div class="orbit-track" style="--radius:160px;--dur:14s;--start:60deg"><div class="orbit-dot small dot-pink"></div></div>
            <div class="orbit-track" style="--radius:160px;--dur:14s;--start:240deg"><div class="orbit-dot dot-gold"></div></div>
            <div class="orbit-center"><div class="orbit-center-mark"><span></span><span></span><span></span></div></div>
        </div>
    </div>

    <div class="w-full max-w-7xl flex flex-col md:flex-row items-center justify-between gap-8 lg:gap-16 relative z-10 my-auto">
        <div class="w-full md:w-1/2 text-white flex flex-col justify-between items-start space-y-8 lg:space-y-12 p-2 sm:p-4">
            <div class="flex items-center space-x-3 drop-shadow-md">
                <div class="flex items-center justify-center"><svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                <span class="font-extrabold text-3xl sm:text-4xl lg:text-5xl tracking-tight text-white">OJT<span class="text-cyan-400">360</span></span>
            </div>
            <div class="space-y-4 max-w-xl">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-cyan-300">Welcome Back</span>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-white tracking-tight drop-shadow-lg">Continue your internship journey.</h1>
                <p class="text-blue-100 text-sm sm:text-base lg:text-lg leading-relaxed opacity-95 font-normal drop-shadow-sm">Access your OJT records, attendance, weekly journals, progress, and internship requirements in one place.</p>
            </div>
            <div class="flex items-center space-x-12 pt-6 border-t border-blue-400/20 max-w-xl w-full">
                <div><div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">OJT</div><div class="text-xs sm:text-sm text-blue-200 mt-0.5">Internship Portal</div></div>
                <div class="h-10 w-px bg-blue-400/20"></div>
                <div><div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">360°</div><div class="text-xs sm:text-sm text-blue-200 mt-0.5">Journey Tracking</div></div>
            </div>
        </div>

        <div class="w-full md:w-[540px] lg:w-[560px] bg-white rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] p-6 sm:p-8 text-slate-800 border border-slate-100 my-auto">
            <div class="space-y-4">
                <div class="space-y-0.5"><span class="text-[10px] font-bold uppercase tracking-widest text-sky-500">Get Started</span><h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Sign in to OJT360</h2><p class="text-xs text-slate-500">Choose your account type and continue.</p></div>

                <div class="bg-slate-100/80 p-1 rounded-2xl flex items-center justify-between text-xs font-semibold text-slate-500">
                    <button type="button" onclick="selectRole('student')" id="tab-student" class="w-1/3 py-2 rounded-xl transition-all duration-200 <?= $selectedRole === 'student' ? 'tab-active' : 'hover:text-slate-800' ?>">Student</button>
                    <button type="button" onclick="selectRole('employee')" id="tab-employee" class="w-1/3 py-2 rounded-xl transition-all duration-200 <?= $selectedRole === 'employee' ? 'tab-active' : 'hover:text-slate-800' ?>">Employee</button>
                    <button type="button" onclick="selectRole('company')" id="tab-company" class="w-1/3 py-2 rounded-xl transition-all duration-200 <?= $selectedRole === 'company' ? 'tab-active' : 'hover:text-slate-800' ?>">Company</button>
                </div>

                <?php if (!empty($errorMessage)): ?><div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold"><?= e($errorMessage) ?></div><?php endif; ?>
                <?php if (isset($_GET['registered'])): ?><div class="p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-semibold">Account created successfully. You can now sign in.</div><?php endif; ?>

                <form method="POST" action="login.php" class="space-y-4" id="login-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="role" id="selected-role" value="<?= e($selectedRole) ?>">
                    <div>
                        <label id="identifier-label" class="block text-[10px] font-bold text-slate-700 mb-1">Student ID</label>
                        <div class="relative"><input type="text" name="identifier" id="identifier" value="<?= e($_POST['identifier'] ?? '') ?>" required maxlength="80" placeholder="1234567" class="w-full px-3 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-700 mb-1">Password</label>
                        <div class="relative"><input type="password" id="login_pwd" name="password" required placeholder="Enter your password" class="w-full pl-3 pr-12 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"><button type="button" onclick="togglePassword('login_pwd', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-semibold text-sky-600 hover:text-sky-700 transition">Show</button></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500"><label class="flex items-center gap-2"><input type="checkbox" class="rounded border-slate-300 text-sky-500 focus:ring-sky-500"><span>Remember me</span></label><span>Secure sign in</span></div>
                    <button type="submit" class="w-full py-3 bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs rounded-xl transition duration-200 shadow-md shadow-sky-500/25 flex items-center justify-center space-x-2"><span>Sign in</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></button>
                </form>

                <div class="pt-1 text-center"><p class="text-xs text-slate-500">Don't have an account? <a href="register.php" class="font-bold text-sky-500 hover:text-sky-600 transition">Create one</a></p></div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const pwdInput = document.getElementById(inputId);
            if (pwdInput.type === 'password') { pwdInput.type = 'text'; btn.textContent = 'Hide'; }
            else { pwdInput.type = 'password'; btn.textContent = 'Show'; }
        }
        function selectRole(role) {
            document.getElementById('selected-role').value = role;
            const label = document.getElementById('identifier-label');
            const input = document.getElementById('identifier');
            if (role === 'student') { label.textContent='Student ID'; input.placeholder='1234567'; input.maxLength=7; input.inputMode='numeric'; }
            else if (role === 'employee') { label.textContent='Username / Employee ID'; input.placeholder='instructor@lnu.edu.ph'; input.maxLength=80; input.inputMode='text'; }
            else { label.textContent='Company Email / Username'; input.placeholder='company@corporate.com'; input.maxLength=80; input.inputMode='email'; }
            ['student','employee','company'].forEach(r => {
                const btn=document.getElementById('tab-'+r);
                if (r===role) { btn.classList.add('tab-active'); btn.classList.remove('hover:text-slate-800'); }
                else { btn.classList.remove('tab-active'); btn.classList.add('hover:text-slate-800'); }
            });
        }
        selectRole(<?= json_encode($selectedRole) ?>);
    </script>
</body>
</html>
