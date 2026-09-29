<?php
  $pageTitle = "OJT360 - Login & Registration";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .hero-bg {
      background: radial-gradient(circle at 70% 30%, #0d3b66 0%, #081c33 50%, #040e1a 100%);
    }
    .tab-active {
      background-color: #ffffff;
      color: #0f172a;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      font-weight: 600;
    }
    .orbit-ring {
      border: 1px dashed rgba(255, 255, 255, 0.15);
      border-radius: 50%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
  </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 flex flex-col md:flex-row antialiased">

  <!-- Left Hero Panel -->
  <div class="hero-bg md:w-1/2 min-h-[340px] md:min-h-screen p-8 md:p-12 flex flex-col justify-between text-white relative overflow-hidden">
    <div class="z-10 flex items-center space-x-3">
      <div class="flex items-center space-x-1">
        <span class="w-3 h-3 rounded-full bg-cyan-400 inline-block"></span>
        <span class="w-3 h-3 rounded-full bg-teal-300 inline-block"></span>
        <span class="w-3 h-3 rounded-full border-2 border-white inline-block"></span>
      </div>
      <span class="font-bold text-xl tracking-tight">OJT360</span>
    </div>

    <!-- Orbit Graphics -->
    <div class="absolute inset-0 flex items-center justify-center opacity-80 pointer-events-none">
      <div class="orbit-ring w-64 h-64 md:w-80 md:h-80"></div>
      <div class="orbit-ring w-96 h-96 md:w-[480px] md:h-[480px]"></div>
      <div class="w-24 h-24 md:w-32 md:h-32 bg-slate-900/60 backdrop-blur-md rounded-2xl border border-white/20 transform rotate-45 flex items-center justify-center shadow-2xl">
        <div class="transform -rotate-45 flex items-center space-x-1">
          <span class="w-4 h-4 rounded-full bg-cyan-400"></span>
          <span class="w-4 h-4 rounded-full bg-teal-300"></span>
          <span class="w-4 h-4 rounded-full border-2 border-white"></span>
        </div>
      </div>
    </div>

    <!-- Hero Content -->
    <div class="z-10 max-w-md my-auto">
      <span class="text-xs font-semibold uppercase tracking-widest text-cyan-400 mb-2 block">Built for BSIT Internships</span>
      <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-4">Grow with every hour, task, and lesson.</h1>
      <p class="text-slate-300 text-sm md:text-base leading-relaxed mb-8">
        Track progress, share meaningful work, and stay connected with everyone guiding your BSIT internship journey.
      </p>
      <div class="flex items-center space-x-8 pt-4 border-t border-white/10">
        <div>
          <div class="text-2xl font-bold">2,400+</div>
          <div class="text-xs text-slate-400">Student journeys</div>
        </div>
        <div>
          <div class="text-2xl font-bold">98%</div>
          <div class="text-xs text-slate-400">On-time completion</div>
        </div>
      </div>
    </div>

    <div class="z-10 text-xs text-slate-500 hidden md:block">
      &copy; <?php echo date('Y'); ?> OJT360. All rights reserved.
    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="md:w-1/2 p-6 md:p-12 lg:p-16 flex flex-col justify-between min-h-screen bg-white">
    <div class="max-w-md w-full mx-auto my-auto">
      
      <!-- Top Title Header -->
      <div class="mb-6">
        <span id="headerSubtitle" class="text-xs font-bold uppercase tracking-wider text-cyan-600 mb-1 block">Welcome Back</span>
        <h2 id="headerTitle" class="text-2xl md:text-3xl font-bold text-slate-900">Sign in to your account</h2>
        <p id="headerDescription" class="text-sm text-slate-500 mt-1">Continue your internship journey with OJT360.</p>
      </div>

      <!-- Role Selector Tabs -->
      <div class="bg-slate-100 p-1 rounded-xl flex space-x-1 mb-6 text-sm font-medium">
        <button type="button" onclick="setRole('student')" id="tab-student" class="flex-1 py-2 text-center rounded-lg transition-all tab-active">Student</button>
        <button type="button" onclick="setRole('employee')" id="tab-employee" class="flex-1 py-2 text-center rounded-lg text-slate-500 hover:text-slate-800 transition-all">Employee</button>
        <button type="button" onclick="setRole('company')" id="tab-company" class="flex-1 py-2 text-center rounded-lg text-slate-500 hover:text-slate-800 transition-all">Company</button>
      </div>

      <!-- LOGIN FORM -->
      <form id="loginForm" action="index.php" method="POST" onsubmit="handleFormSubmit(event)" class="space-y-4">
        <input type="hidden" name="role" id="loginRoleInput" value="student">
        <div>
          <label id="loginIdLabel" class="block text-xs font-semibold text-slate-700 mb-1">Student ID or institutional email</label>
          <input type="text" name="identifier" required placeholder="2021-00001 or name@lnu.edu.ph" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
          <div class="relative">
            <input type="password" name="password" id="loginPassword" required placeholder="Enter your password" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all pr-16" />
            <button type="button" onclick="togglePassword('loginPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 font-medium">Show</button>
          </div>
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center text-slate-600 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 mr-2">
            Remember me
          </label>
          <a href="#" class="text-cyan-600 font-medium hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-3 px-4 rounded-lg shadow-sm shadow-cyan-200 transition-all flex items-center justify-center space-x-2">
          <span>Sign in securely</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      <!-- REGISTER FORM -->
      <form id="registerForm" action="index.php" method="POST" onsubmit="handleFormSubmit(event)" class="space-y-3 hidden">
        <input type="hidden" name="role" id="regRoleInput" value="student">
        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">First name</label>
            <input type="text" name="firstname" required placeholder="First name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Middle name</label>
            <input type="text" name="middlename" placeholder="Middle name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Last name</label>
            <input type="text" name="lastname" required placeholder="Last name" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Course</label>
            <input type="text" name="course" value="BSIT" readonly class="w-full px-3 py-2 text-sm bg-slate-50 font-semibold text-cyan-700 border border-slate-200 rounded-lg" />
          </div>
          <div class="col-span-2">
            <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile number</label>
            <input type="tel" name="mobile" required placeholder="+63 9XX XXX XXXX" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">School / Institution</label>
          <input type="text" name="school" required placeholder="Enter school or institution" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Complete address</label>
          <input type="text" name="address" required placeholder="Street, barangay, city, province" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
        </div>

        <div>
          <label id="regIdLabel" class="block text-xs font-semibold text-slate-700 mb-1">Student ID or institutional email</label>
          <input type="text" name="identifier" required placeholder="2021-00001 or name@lnu.edu.ph" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
          <div class="relative">
            <input type="password" name="password" id="regPassword" required placeholder="Enter your password" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 pr-16" />
            <button type="button" onclick="togglePassword('regPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 font-medium">Show</button>
          </div>
        </div>

        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm shadow-cyan-200 transition-all flex items-center justify-center space-x-2 mt-4">
          <span>Create account</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      <!-- View Switcher Link -->
      <div class="mt-6 text-center text-xs text-slate-500">
        <span id="toggleQuestion">New to OJT360?</span>
        <button type="button" onclick="switchView()" id="toggleBtn" class="text-cyan-600 font-semibold hover:underline ml-1">Create an account</button>
      </div>
    </div>

    <div class="text-center text-[10px] text-slate-400 mt-6">
      Powered by enterprise-grade security &bull; Privacy &bull; Help
    </div>
  </div>

  <script>
    let currentRole = 'student';
    let currentView = 'login'; 

    function setRole(role) {
      currentRole = role;
      document.getElementById('loginRoleInput').value = role;
      document.getElementById('regRoleInput').value = role;

      ['student', 'employee', 'company'].forEach(r => {
        const btn = document.getElementById(`tab-${r}`);
        if (r === role) {
          btn.className = "flex-1 py-2 text-center rounded-lg transition-all tab-active";
        } else {
          btn.className = "flex-1 py-2 text-center rounded-lg text-slate-500 hover:text-slate-800 transition-all";
        }
      });

      const loginLabel = document.getElementById('loginIdLabel');
      const regLabel = document.getElementById('regIdLabel');
      
      if (role === 'student') {
        loginLabel.innerText = 'Student ID or institutional email';
        if (regLabel) regLabel.innerText = 'Student ID or institutional email';
      } else if (role === 'employee') {
        loginLabel.innerText = 'Employee ID or work email';
        if (regLabel) regLabel.innerText = 'Employee ID or work email';
      } else {
        loginLabel.innerText = 'Company ID or corporate email';
        if (regLabel) regLabel.innerText = 'Company ID or corporate email';
      }
    }

    function switchView() {
      const loginForm = document.getElementById('loginForm');
      const registerForm = document.getElementById('registerForm');
      const headerTitle = document.getElementById('headerTitle');
      const headerSubtitle = document.getElementById('headerSubtitle');
      const headerDescription = document.getElementById('headerDescription');
      const toggleQuestion = document.getElementById('toggleQuestion');
      const toggleBtn = document.getElementById('toggleBtn');

      if (currentView === 'login') {
        currentView = 'register';
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        headerSubtitle.innerText = 'GET STARTED';
        headerTitle.innerText = 'Create your account';
        headerDescription.innerText = 'Join a connected internship experience.';
        toggleQuestion.innerText = 'Already have an account?';
        toggleBtn.innerText = 'Sign in';
      } else {
        currentView = 'login';
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        headerSubtitle.innerText = 'WELCOME BACK';
        headerTitle.innerText = 'Sign in to your account';
        headerDescription.innerText = 'Continue your internship journey with OJT360.';
        toggleQuestion.innerText = 'New to OJT360?';
        toggleBtn.innerText = 'Create an account';
      }
    }

    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const btn = input.nextElementSibling;
      if (input.type === 'password') {
        input.type = 'text';
        btn.innerText = 'Hide';
      } else {
        input.type = 'password';
        btn.innerText = 'Show';
      }
    }

    function handleFormSubmit(e) {
      e.preventDefault();
      alert(`Sprint 1 PHP Demo: ${currentView.toUpperCase()} attempt as [${currentRole.toUpperCase()}] submitted!`);
    }
  </script>
</body>
</html>