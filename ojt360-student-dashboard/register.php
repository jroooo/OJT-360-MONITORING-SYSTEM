<?php
require_once 'config.php';
require_once 'includes/helpers.php';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $role = $_POST['role'] ?? '';

    if ($role === 'student') {
        $first_name = trim($_POST['student_firstname'] ?? '');
        $middle_name = trim($_POST['student_middlename'] ?? '');
        $last_name = trim($_POST['student_lastname'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $mobile_number = trim($_POST['student_mobile'] ?? '');
        $school_name = trim($_POST['school'] ?? '');
        $complete_address = trim($_POST['student_address'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $institutional_email = trim($_POST['student_email'] ?? '');
        $student_password = $_POST['student_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($course) || empty($mobile_number) || empty($school_name) || empty($complete_address) || empty($student_id) || empty($institutional_email) || empty($student_password)) {
            $errorMessage = 'Please fill in all required fields.';
        } elseif (!preg_match('/^[0-9]{7}$/', $student_id)) {
            $errorMessage = 'Student ID must contain exactly 7 digits.';
        } else {
            $checkStudent = db()->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
            $checkStudent->execute([$student_id]);
            $checkEmail = db()->prepare('SELECT id FROM students WHERE institutional_email = ? LIMIT 1');
            $checkEmail->execute([$institutional_email]);
            $checkUserStudent = db()->prepare('SELECT id FROM users WHERE student_no = ? OR email = ? LIMIT 1');
            $checkUserStudent->execute([$student_id, $institutional_email]);

            if ($checkStudent->fetch()) {
                $errorMessage = 'Student ID is already registered.';
            } elseif ($checkEmail->fetch()) {
                $errorMessage = 'Institutional email is already registered.';
            } elseif ($checkUserStudent->fetch()) {
                $errorMessage = 'This student account is already connected to OJT360.';
            } else {
                try {
                    db()->beginTransaction();
                    $password_hash = password_hash($student_password, PASSWORD_DEFAULT);
                    $stmt = db()->prepare('INSERT INTO students (first_name,middle_name,last_name,course,mobile_number,school_name,complete_address,student_id,institutional_email,password_hash) VALUES (?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$first_name,$middle_name,$last_name,$course,$mobile_number,$school_name,$complete_address,$student_id,$institutional_email,$password_hash]);

                    $full_name = trim($first_name . ' ' . ($middle_name !== '' ? $middle_name . ' ' : '') . $last_name);
                    $userStmt = db()->prepare('INSERT INTO users (full_name,email,password_hash,student_no,course,section,phone,address,role) VALUES (?,?,?,?,?,?,?,?,\'student\')');
                    $userStmt->execute([$full_name,$institutional_email,$password_hash,$student_id,$course,'AI23',$mobile_number,$complete_address]);
                    $userId = (int)db()->lastInsertId();

                    db()->prepare('INSERT INTO notifications (user_id,title,message,icon) VALUES (?,?,?,?)')->execute([$userId,'Welcome to OJT360','Your student account is ready. Complete your internship profile and start tracking your OJT journey.','✦']);
                    db()->prepare('INSERT INTO activity_logs (user_id,action_text) VALUES (?,?)')->execute([$userId,'Created OJT360 student account']);

                    db()->commit();
                    header('Location: login.php?registered=1');
                    exit;
                } catch (Throwable $e) {
                    if (db()->inTransaction()) db()->rollBack();
                    $errorMessage = 'Registration failed. Please try again.';
                }
            }
        }
    } elseif ($role === 'employee') {
        $employee_id = trim($_POST['emp_id'] ?? '');
        $first_name = trim($_POST['emp_firstname'] ?? '');
        $middle_name = trim($_POST['emp_middlename'] ?? '');
        $last_name = trim($_POST['emp_lastname'] ?? '');
        $contact_number = trim($_POST['emp_contact'] ?? '');
        $email = trim($_POST['emp_personal_email'] ?? '');
        $department = trim($_POST['emp_department'] ?? '');
        $position = trim($_POST['emp_position'] ?? '');
        $username = trim($_POST['emp_account_username'] ?? '');
        $employee_password = $_POST['emp_password'] ?? '';
        $confirm_password = $_POST['emp_confirm_password'] ?? '';

        if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($contact_number) || empty($email) || empty($department) || empty($position) || empty($username) || empty($employee_password) || empty($confirm_password)) {
            $errorMessage = 'Please fill in all required employee fields.';
        } elseif ($employee_password !== $confirm_password) {
            $errorMessage = 'Passwords do not match.';
        } else {
            $checkEmployee = db()->prepare('SELECT id FROM employees WHERE employee_id = ? LIMIT 1'); $checkEmployee->execute([$employee_id]);
            $checkEmail = db()->prepare('SELECT id FROM employees WHERE email = ? LIMIT 1'); $checkEmail->execute([$email]);
            $checkUsername = db()->prepare('SELECT id FROM employees WHERE username = ? LIMIT 1'); $checkUsername->execute([$username]);
            if ($checkEmployee->fetch()) $errorMessage='Employee ID is already registered.';
            elseif ($checkEmail->fetch()) $errorMessage='Email is already registered.';
            elseif ($checkUsername->fetch()) $errorMessage='Username is already registered.';
            else {
                $password_hash=password_hash($employee_password,PASSWORD_DEFAULT);
                $stmt=db()->prepare('INSERT INTO employees (employee_id,first_name,middle_name,last_name,contact_number,email,department,position,username,password_hash) VALUES (?,?,?,?,?,?,?,?,?,?)');
                if ($stmt->execute([$employee_id,$first_name,$middle_name,$last_name,$contact_number,$email,$department,$position,$username,$password_hash])) $successMessage='Employee account created successfully!';
                else $errorMessage='Employee registration failed. Please try again.';
            }
        }
    } elseif ($role === 'company') {
        $company_name = trim($_POST['company_name'] ?? '');
        $rep_first_name = trim($_POST['comp_rep_firstname'] ?? '');
        $rep_middle_name = trim($_POST['comp_rep_middlename'] ?? '');
        $rep_last_name = trim($_POST['comp_rep_lastname'] ?? '');
        $rep_position = trim($_POST['comp_rep_position'] ?? '');
        $contact_number = trim($_POST['comp_rep_contact'] ?? '');
        $representative_email = trim($_POST['comp_rep_email'] ?? '');
        $company_username = trim($_POST['comp_account_email'] ?? '');
        $company_password = $_POST['comp_password'] ?? '';
        $confirm_password = $_POST['comp_confirm_password'] ?? '';

        if (empty($company_name) || empty($rep_first_name) || empty($rep_last_name) || empty($rep_position) || empty($contact_number) || empty($representative_email) || empty($company_username) || empty($company_password) || empty($confirm_password)) {
            $errorMessage='Please fill in all required company fields.';
        } elseif ($company_password !== $confirm_password) {
            $errorMessage='Passwords do not match.';
        } else {
            $checkUsername=db()->prepare('SELECT id FROM companies WHERE company_username = ? LIMIT 1'); $checkUsername->execute([$company_username]);
            $checkEmail=db()->prepare('SELECT id FROM companies WHERE representative_email = ? LIMIT 1'); $checkEmail->execute([$representative_email]);
            if ($checkUsername->fetch()) $errorMessage='Company username is already registered.';
            elseif ($checkEmail->fetch()) $errorMessage='Representative email is already registered.';
            else {
                $password_hash=password_hash($company_password,PASSWORD_DEFAULT);
                $stmt=db()->prepare('INSERT INTO companies (name,company_name,rep_first_name,rep_middle_name,rep_last_name,rep_position,contact_number,representative_email,company_username,password_hash,address,contact_email) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                if ($stmt->execute([$company_name,$company_name,$rep_first_name,$rep_middle_name,$rep_last_name,$rep_position,$contact_number,$representative_email,$company_username,$password_hash,'',$representative_email])) $successMessage='Company account created successfully!';
                else $errorMessage='Company registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'OJT360 - Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= e($pageTitle) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--accent-gold:#e8b84b;--accent-primary:#ff2e7e;--paper:#fff8f0;--ink:#0f172a}body{font-family:'Plus Jakarta Sans',sans-serif}.hero-bg{background:radial-gradient(circle at 78% 30%,#0d3b66 0%,#081c33 50%,#0f172a 100%)}.tab-active{background-color:#fff;color:#0284c7;font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,.1)}.hero-orbit-wrapper{position:absolute;top:50%;left:25%;transform:translate(-50%,-50%);z-index:0;pointer-events:none;opacity:.85}.orbit-visual{position:relative;width:480px;height:480px;max-width:90vw;max-height:90vw}.orbit-glow{position:absolute;inset:40px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.25) 0%,rgba(14,165,233,.12) 50%,transparent 75%);filter:blur(20px);animation:orbit-pulse 3.6s ease-in-out infinite}.orbit-ring{position:absolute;top:50%;left:50%;border-radius:50%;border:1px dashed rgba(56,189,248,.25);transform:translate(-50%,-50%)}.ring-outer{width:480px;height:480px}.ring-inner{width:320px;height:320px}.orbit-track{--radius-size:var(--radius,240px);position:absolute;top:50%;left:50%;width:calc(var(--radius-size)*2);height:calc(var(--radius-size)*2);transform-origin:center;animation:orbit-spin var(--dur,16s) linear infinite}@keyframes orbit-spin{from{transform:translate(-50%,-50%) rotate(var(--start,0deg))}to{transform:translate(-50%,-50%) rotate(calc(var(--start,0deg) + 360deg))}}.orbit-dot{position:absolute;top:0;left:50%;width:12px;height:12px;border-radius:50%;transform:translate(-50%,-50%)}.orbit-dot.small{width:8px;height:8px}.dot-gold{background:#38bdf8;box-shadow:0 0 12px rgba(56,189,248,.9)}.dot-pink{background:#22d3ee;box-shadow:0 0 12px rgba(34,211,238,.9)}.dot-white{background:#fff;box-shadow:0 0 10px rgba(255,255,255,.8)}@keyframes orbit-pulse{0%,100%{opacity:.6;transform:scale(1)}50%{opacity:.9;transform:scale(1.05)}}.orbit-center{position:absolute;top:50%;left:50%;width:110px;height:110px;transform:translate(-50%,-50%) rotate(45deg);background:linear-gradient(145deg,rgba(15,23,42,.8),rgba(8,28,51,.9));border:1px solid rgba(56,189,248,.3);border-radius:26px;box-shadow:0 0 30px rgba(14,165,233,.2);backdrop-filter:blur(4px)}.orbit-center-mark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;transform:rotate(-45deg);gap:6px}.orbit-center-mark span{width:10px;height:10px;border-radius:50%;border:2px solid #38bdf8}.orbit-center-mark span:nth-child(2){border-color:#22d3ee}.orbit-center-mark span:nth-child(3){border-color:#fff;margin-left:-22px;margin-top:18px}
</style></head>
<body class="hero-bg min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-8 overflow-x-hidden antialiased relative">
<div class="hero-orbit-wrapper hidden md:block"><div class="orbit-visual"><div class="orbit-glow"></div><div class="orbit-ring ring-outer"></div><div class="orbit-ring ring-inner"></div><div class="orbit-track" style="--radius:240px;--dur:20s;--start:0deg"><div class="orbit-dot dot-gold"></div></div><div class="orbit-track" style="--radius:240px;--dur:20s;--start:120deg"><div class="orbit-dot small dot-pink"></div></div><div class="orbit-track" style="--radius:240px;--dur:20s;--start:240deg"><div class="orbit-dot small dot-white"></div></div><div class="orbit-track" style="--radius:160px;--dur:14s;--start:60deg"><div class="orbit-dot small dot-pink"></div></div><div class="orbit-track" style="--radius:160px;--dur:14s;--start:240deg"><div class="orbit-dot dot-gold"></div></div><div class="orbit-center"><div class="orbit-center-mark"><span></span><span></span><span></span></div></div></div></div>
<div class="w-full max-w-7xl flex flex-col md:flex-row items-center justify-between gap-8 lg:gap-16 relative z-10 my-auto">
<div class="w-full md:w-1/2 text-white flex flex-col justify-between items-start space-y-8 lg:space-y-12 p-2 sm:p-4"><div class="flex items-center space-x-3 drop-shadow-md"><div class="flex items-center justify-center"><svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div><span class="font-extrabold text-3xl sm:text-4xl lg:text-5xl tracking-tight text-white">OJT<span class="text-cyan-400">360</span></span></div><div class="space-y-4 max-w-xl"><span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-cyan-300">Join OJT360 Today</span><h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-white tracking-tight drop-shadow-lg">Start tracking your internship journey.</h1><p class="text-blue-100 text-sm sm:text-base lg:text-lg leading-relaxed opacity-95 font-normal drop-shadow-sm">Connect with coordinators, log your rendered hours, and submit daily requirements seamlessly.</p></div><div class="flex items-center space-x-12 pt-6 border-t border-blue-400/20 max-w-xl w-full"><div><div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">2,400+</div><div class="text-xs sm:text-sm text-blue-200 mt-0.5">Active Students</div></div><div class="h-10 w-px bg-blue-400/20"></div><div><div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">98%</div><div class="text-xs sm:text-sm text-blue-200 mt-0.5">Success Rate</div></div></div></div>
<div class="w-full md:w-[540px] lg:w-[560px] bg-white rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] p-6 sm:p-8 text-slate-800 border border-slate-100 my-auto"><div class="space-y-4"><div class="space-y-0.5"><span class="text-[10px] font-bold uppercase tracking-widest text-sky-500">Get Started</span><h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Create your account</h2><p class="text-xs text-slate-500">Join a connected internship experience.</p></div>
<div class="bg-slate-100/80 p-1 rounded-2xl flex items-center justify-between text-xs font-semibold text-slate-500"><button type="button" onclick="selectRole('student')" id="tab-student" class="w-1/3 py-2 rounded-xl transition-all duration-200 tab-active">Student</button><button type="button" onclick="selectRole('employee')" id="tab-employee" class="w-1/3 py-2 rounded-xl transition-all duration-200 hover:text-slate-800">Employee</button><button type="button" onclick="selectRole('company')" id="tab-company" class="w-1/3 py-2 rounded-xl transition-all duration-200 hover:text-slate-800">Company</button></div>
<?php if ($successMessage): ?><div class="p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-semibold"><?= e($successMessage) ?></div><?php endif; ?><?php if ($errorMessage): ?><div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold"><?= e($errorMessage) ?></div><?php endif; ?>
<form action="" method="POST" class="space-y-2.5 pt-0.5"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="role" id="selected-role" value="student">
<div id="form-student" class="space-y-2.5"><div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">First name</label><input type="text" name="student_firstname" placeholder="First name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Middle name</label><input type="text" name="student_middlename" placeholder="Middle name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Last name</label><input type="text" name="student_lastname" placeholder="Last name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Course</label><input type="text" name="course" value="BSIT" class="w-full px-3 py-2 rounded-xl bg-sky-50/50 border border-sky-100 text-sky-700 font-bold text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Mobile number</label><input type="text" name="student_mobile" placeholder="+63 9XX XXX XXXX" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">School name</label><input type="text" name="school" placeholder="Enter school or institution" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Complete address</label><input type="text" name="student_address" placeholder="Street, barangay, city, province" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Student ID</label><input type="text" name="student_id" placeholder="ex. 1234567" maxlength="7" pattern="[0-9]{7}" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,7)" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Institutional Email</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-semibold">@</div><input type="email" name="student_email" placeholder="juan.delacruz@lnu.edu.ph" class="w-full pl-8 pr-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Password</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></div><input type="password" id="student_pwd" name="student_password" placeholder="Enter your password" class="w-full pl-8 pr-12 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"><button type="button" onclick="togglePassword('student_pwd',this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-semibold text-sky-600 hover:text-sky-700 transition">Show</button></div></div></div>
<div id="form-employee" class="space-y-2.5 hidden"><div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 pt-0.5">Personal Information</div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Employee ID</label><input type="text" name="emp_id" placeholder="EMP-2026-XXXX" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">First name</label><input type="text" name="emp_firstname" placeholder="First name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Middle name</label><input type="text" name="emp_middlename" placeholder="Middle name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Last name</label><input type="text" name="emp_lastname" placeholder="Last name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Contact Number</label><input type="text" name="emp_contact" placeholder="+63 9XX XXX XXXX" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Email</label><input type="email" name="emp_personal_email" placeholder="name@domain.com" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Department</label><input type="text" name="emp_department" placeholder="e.g. IT Department" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Position</label><input type="text" name="emp_position" placeholder="e.g. Instructor / Coordinator" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 pt-1">Account Information</div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Username / Email</label><input type="text" name="emp_account_username" placeholder="instructor@lnu.edu.ph" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Password</label><input type="password" id="emp_pwd" name="emp_password" placeholder="Create password" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Confirm Password</label><input type="password" id="emp_pwd_confirm" name="emp_confirm_password" placeholder="Confirm password" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div></div>
<div id="form-company" class="space-y-2.5 hidden"><div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 pt-0.5">Company Representative</div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Company Name</label><input type="text" name="company_name" placeholder="Enter registered company name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">First Name</label><input type="text" name="comp_rep_firstname" placeholder="First name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Middle Name</label><input type="text" name="comp_rep_middlename" placeholder="Middle name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Last Name</label><input type="text" name="comp_rep_lastname" placeholder="Last name" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Position</label><input type="text" name="comp_rep_position" placeholder="e.g. HR Manager" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Contact Number</label><input type="text" name="comp_rep_contact" placeholder="+63 9XX XXX XXXX" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Representative Email</label><input type="email" name="comp_rep_email" placeholder="rep@company.com" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 pt-1">Account Information</div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Company Email / Username</label><input type="text" name="comp_account_email" placeholder="company@corporate.com" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-start"><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Password</label><input type="password" id="comp_pwd" name="comp_password" placeholder="Create password" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div><div><label class="block text-[10px] font-bold text-slate-700 mb-0.5">Confirm Password</label><input type="password" id="comp_pwd_confirm" name="comp_confirm_password" placeholder="Confirm password" class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"></div></div></div>
<button type="submit" class="w-full py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs rounded-xl transition duration-200 shadow-md shadow-sky-500/25 flex items-center justify-center space-x-2 mt-3"><span>Create account</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></button>
</form><div class="pt-1 text-center"><p class="text-xs text-slate-500">Already have an account? <a href="login.php" class="font-bold text-sky-500 hover:text-sky-600 transition">Sign in</a></p></div></div></div></div>
<script>
function togglePassword(inputId,btn){const pwdInput=document.getElementById(inputId);if(pwdInput.type==='password'){pwdInput.type='text';btn.textContent='Hide'}else{pwdInput.type='password';btn.textContent='Show'}}
function selectRole(role){document.getElementById('selected-role').value=role;['student','employee','company'].forEach(r=>{const btn=document.getElementById('tab-'+r),form=document.getElementById('form-'+r);if(r===role){btn.classList.add('tab-active');btn.classList.remove('hover:text-slate-800');form.classList.remove('hidden')}else{btn.classList.remove('tab-active');btn.classList.add('hover:text-slate-800');form.classList.add('hidden')}})}
</script></body></html>
