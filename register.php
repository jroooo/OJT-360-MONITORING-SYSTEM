<?php
$pageTitle = "OJT360 - Create Account";
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
                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-cyan-300">Join OJT360 Today</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight text-white tracking-tight">
                    Start tracking your internship journey.
                </h1>
                <p class="text-blue-100 text-sm sm:text-base leading-relaxed opacity-90 font-normal">
                    Connect with coordinators, log your rendered hours, and submit daily requirements seamlessly.
                </p>
            </div>

            <div class="flex items-center space-x-12 pt-6 border-t border-blue-400/20 max-w-lg">
                <div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white">2,400+</div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">Active Students</div>
                </div>
                <div class="h-10 w-px bg-blue-400/20"></div>
                <div>
                    <div class="text-2xl sm:text-4xl font-extrabold text-white">98%</div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Right Floating Box (Accurate Information Box UI from Screenshot) -->
        <div class="w-full md:w-[540px] bg-white rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] p-8 sm:p-10 text-slate-800 border border-slate-100">
            
            <div class="space-y-4">
                
                <!-- Card Header -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-sky-500">Get Started</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">Create your account</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Join a connected internship experience.</p>
                </div>

                <!-- Role Selector Pills -->
                <div class="bg-slate-100/80 p-1.5 rounded-2xl flex items-center justify-between text-xs font-semibold text-slate-500">
                    <button type="button" onclick="selectRole('student')" id="tab-student" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 tab-active text-sky-600 font-bold">Student</button>
                    <button type="button" onclick="selectRole('employee')" id="tab-employee" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 hover:text-slate-800">Employee</button>
                    <button type="button" onclick="selectRole('company')" id="tab-company" class="w-1/3 py-2.5 rounded-xl transition-all duration-200 hover:text-slate-800">Company</button>
                </div>

                <!-- Registration Form -->
                <form action="" method="POST" class="space-y-3 pt-1">
                    <input type="hidden" name="role" id="selected-role" value="student">
                    
                    <!-- First, Middle, Last Name Grid -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div>
                            <label for="firstname" class="block text-[11px] font-bold text-slate-700 mb-1">First name</label>
                            <input type="text" id="firstname" name="firstname" required placeholder="First name"
                                class="w-full px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                        <div>
                            <label for="middlename" class="block text-[11px] font-bold text-slate-700 mb-1">Middle name</label>
                            <input type="text" id="middlename" name="middlename" placeholder="Middle name"
                                class="w-full px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                        <div>
                            <label for="lastname" class="block text-[11px] font-bold text-slate-700 mb-1">Last name</label>
                            <input type="text" id="lastname" name="lastname" required placeholder="Last name"
                                class="w-full px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                    </div>

                    <!-- Course & Mobile Number Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="course" class="block text-[11px] font-bold text-slate-700 mb-1">Course</label>
                            <input type="text" id="course" name="course" value="BSIT" required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-sky-50/50 border border-sky-100 text-sky-700 font-bold text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                        <div>
                            <label for="mobile" class="block text-[11px] font-bold text-slate-700 mb-1">Mobile number</label>
                            <input type="text" id="mobile" name="mobile" required placeholder="+63 9XX XXX XXXX"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                    </div>

                    <!-- School Name -->
                    <div>
                        <label for="school" class="block text-[11px] font-bold text-slate-700 mb-1">School name</label>
                        <input type="text" id="school" name="school" required placeholder="Enter school or institution"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                    </div>

                    <!-- Complete Address -->
                    <div>
                        <label for="address" class="block text-[11px] font-bold text-slate-700 mb-1">Complete address</label>
                        <input type="text" id="address" name="address" required placeholder="Street, barangay, city, province"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                    </div>

                    <!-- Student ID / Email -->
                    <div>
                        <label for="email" class="block text-[11px] font-bold text-slate-700 mb-1">Student ID or institutional email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                                @
                            </div>
                            <input type="text" id="email" name="email" required placeholder="2021-00001 or name@lnu.edu.ph"
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-[11px] font-bold text-slate-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required placeholder="Enter your password"
                                class="w-full pl-9 pr-14 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[11px] font-semibold text-sky-600 hover:text-sky-700 transition">
                                Show
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full py-3 bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs rounded-xl transition duration-200 shadow-md shadow-sky-500/25 flex items-center justify-center space-x-2 mt-2">
                        <span>Create account</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Footer Link -->
                <div class="pt-2 text-center">
                    <p class="text-xs text-slate-500">
                        Already have an account? 
                        <a href="login.php" class="font-bold text-sky-500 hover:text-sky-600 transition">Sign in</a>
                    </p>
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