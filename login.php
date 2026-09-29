<?php
$pageTitle = "OJT360 - Sign In";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-bg {
            background: radial-gradient(circle at 78% 30%, #0d3b66 0%, #081c33 50%, #0f172a 100%);
        }
        .tab-active {
            background-color: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .orbit-ring {
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 50%;
        }
    </style>
</head>
<body class="hero-bg min-h-screen w-full flex items-center justify-center p-4 sm:p-8 lg:p-12 overflow-x-hidden antialiased relative">

    <!-- Background Constellation Effect -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[18%] left-[45%] w-3 h-3 rounded-full bg-cyan-400 shadow-[0_0_18px_#22d3ee] opacity-90 animate-pulse"></div>
        <div class="absolute top-[38%] left-[52%] w-2 h-2 rounded-full bg-cyan-300 shadow-[0_0_12px_#67e8f9] opacity-70"></div>
        <div class="absolute bottom-[28%] left-[35%] w-2.5 h-2.5 rounded-full bg-blue-400 shadow-[0_0_15px_#60a5fa] opacity-80"></div>
        <div class="absolute -top-10 left-[20%] w-[500px] h-[500px] orbit-ring"></div>
        <div class="absolute top-1/4 left-[15%] w-[700px] h-[700px] orbit-ring"></div>
    </div>

    <!-- Main Content Container -->
    <div class="w-full max-w-6xl flex flex-col md:flex-row items-center justify-between gap-8 md:gap-12 relative z-10 my-auto">

        <!-- Left Hero Section (Unchanged) -->
        <div class="w-full md:w-1/2 text-white flex flex-col justify-between space-y-8 md:space-y-12 p-2 sm:p-4">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center">
                    <svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <span class="font-extrabold text-3xl sm:text-4xl tracking-tight text-white">OJT<span class="text-cyan-400">360</span></span>
            </div>

            <div class="space-y-4 max-w-lg">
                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-cyan-300">Built for BSIT Internships</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight text-white tracking-tight">
                    Grow with every hour, task, and lesson.
                </h1>
                <p class="text-blue-100 text-sm sm:text-base leading-relaxed opacity-90 font-normal">
                    Track progress, share meaningful work, and stay connected with everyone guiding your BSIT internship journey.
                </p>
            </div>

            <div class="flex items-center space-x-12 pt-6 border-t border-blue-400/20 max-w-lg">
                <div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white">2,400+</div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">Students</div>
                </div>
                <div class="h-10 w-px bg-blue-400/20"></div>
                <div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white">98%</div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Right Floating Box (Updated Information Box UI) -->
        <div class="w-full md:w-[480px] bg-white rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] p-8 sm:p-10 text-slate-800 border border-slate-100">
            
            <div class="space-y-5">
                
                <!-- Card Header -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-sky-500">Welcome Back</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">Sign in to your account</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Continue your internship journey with OJT360.</p>
                </div>

                <!-- Role Selector Pills -->
                <div class="bg-slate-100/80 p-1.5 rounded-2xl flex items-center justify-between text-xs font-semibold text-slate-500">
                    <button type="button" onclick="selectRole('student')" id="tab-student" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 tab-active text-sky-600 font-bold">Student</button>
                    <button type="button" onclick="selectRole('employee')" id="tab-employee" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 hover:text-slate-800">Employee</button>
                    <button type="button" onclick="selectRole('company')" id="tab-company" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 hover:text-slate-800">Company</button>
                </div>

                <!-- Form -->
                <form action="" method="POST" class="space-y-4 pt-1">
                    <input type="hidden" name="role" id="selected-role" value="student">
                    
                    <!-- Email / ID Input -->
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-bold text-slate-700">Student ID or institutional email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                @
                            </div>
                            <input type="text" id="username" name="username" required
                                placeholder="2021-00001 or name@lnu.edu.ph"
                                class="w-full pl-9 pr-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition shadow-sm">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-700">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                placeholder="Enter your password"
                                class="w-full pl-9 pr-16 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition shadow-sm">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-semibold text-sky-600 hover:text-sky-700 transition">
                                Show
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center space-x-2 cursor-pointer text-slate-500 font-medium">
                            <input type="checkbox" class="w-4 h-4 rounded border-slate-300 text-sky-500 focus:ring-sky-500">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="font-semibold text-sky-500 hover:text-sky-600 transition">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full py-3.5 bg-sky-500 hover:bg-sky-600 text-white font-bold text-sm rounded-xl transition duration-200 shadow-md shadow-sky-500/25 flex items-center justify-center space-x-2">
                        <span>Sign in securely</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Footer Link -->
                <div class="pt-2 text-center">
                    <p class="text-xs text-slate-500">
                        New to OJT360? 
                        <a href="register.php" class="font-bold text-sky-500 hover:text-sky-600 transition">Create an account</a>
                    </p>
                </div>

                <!-- Prototype Banner -->
                <div class="mt-4 pt-3 border-t border-dashed border-slate-200 flex items-center justify-between text-xs text-slate-400">
                    <span>Prototype access</span>
                    <a href="#" class="text-slate-600 font-semibold hover:text-sky-600 transition">Open admin demo</a>
                </div>

            </div>

        </div>

    </div>

    <!-- Interactive Scripts -->
    <script>
        function togglePassword() {
            const pwdInput = document.getElementById('password');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
            } else {
                pwdInput.type = 'password';
            }
        }

        function selectRole(role) {
            document.getElementById('selected-role').value = role;
            ['student', 'employee', 'company'].forEach(r => {
                const btn = document.getElementById('tab-' + r);
                if (r === role) {
                    btn.classList.add('tab-active', 'text-sky-600', 'font-bold');
                    btn.classList.remove('hover:text-slate-800');
                } else {
                    btn.classList.remove('tab-active', 'text-sky-600', 'font-bold');
                    btn.classList.add('hover:text-slate-800');
                }
            });
        }
    </script>
</body>
</html>