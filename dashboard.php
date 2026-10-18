<?php
/*
|--------------------------------------------------------------------------
| OJT360 COMPANY PORTAL
| Company Dashboard
|--------------------------------------------------------------------------
| PHP 8+
| Tailwind CSS CDN
| Font Awesome CDN
|--------------------------------------------------------------------------
*/

// -------------------------------------------------------------------------
// DEMO COMPANY DATA
// Replace these arrays with database queries when your backend is ready.
// -------------------------------------------------------------------------

$company = [
    'name' => 'TechNova Solutions',
    'representative' => 'Maya Cruz',
    'role' => 'Company Administrator',
    'initials' => 'MC'
];

$students = [
    [
        'name' => 'Angela Mercado',
        'id' => '2023-00124',
        'program' => 'BSIT - Web Development',
        'department' => 'IT Department',
        'building' => 'Main Building',
        'hours' => 342,
        'required' => 486,
        'attendance' => 'Good',
        'evaluation' => 'Pending',
        'avatar' => 'AM'
    ],
    [
        'name' => 'Joshua Reyes',
        'id' => '2023-00187',
        'program' => 'BSIT - Network Administration',
        'department' => 'IT Department',
        'building' => 'Tech Building',
        'hours' => 401,
        'required' => 486,
        'attendance' => 'Excellent',
        'evaluation' => 'Completed',
        'avatar' => 'JR'
    ],
    [
        'name' => 'Camille Dizon',
        'id' => '2023-00213',
        'program' => 'BSIT - Web Development',
        'department' => 'Software Development',
        'building' => 'Main Building',
        'hours' => 276,
        'required' => 486,
        'attendance' => 'At Risk',
        'evaluation' => 'Pending',
        'avatar' => 'CD'
    ],
    [
        'name' => 'Nathan Flores',
        'id' => '2023-00245',
        'program' => 'BSIT - Information Systems',
        'department' => 'Operations',
        'building' => 'Operations Building',
        'hours' => 450,
        'required' => 486,
        'attendance' => 'Good',
        'evaluation' => 'Completed',
        'avatar' => 'NF'
    ]
];

// Dashboard metrics
$activeStudents = count($students);
$pendingReviews = count(
    array_filter($students, fn($student) => $student['evaluation'] === 'Pending')
);

$totalCompletion = array_sum(
    array_map(
        fn($student) => ($student['hours'] / $student['required']) * 100,
        $students
    )
);

$averageCompletion = round($totalCompletion / max(count($students), 1));

$attendanceAlerts = count(
    array_filter($students, fn($student) => $student['attendance'] === 'At Risk')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>OJT360 | Company Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        brand: {
                            50: '#eff8ff',
                            100: '#dff1ff',
                            500: '#139bd7',
                            600: '#087fbd',
                            700: '#07689d',
                            900: '#102f49'
                        }
                    }
                }
            }
        }
    </script>

    <style>

        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-transition {
            transition: width 0.25s ease, transform 0.25s ease;
        }

        .content-transition {
            transition: margin-left 0.25s ease;
        }

        .modal-enter {
            animation: modalIn 0.2s ease-out;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(.96) translateY(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .nav-item {
            transition: all 0.15s ease;
        }

        .nav-item:hover {
            transform: translateX(2px);
        }

        .notification-pulse {
            animation: pulseNotification 2s infinite;
        }

        @keyframes pulseNotification {
            0%, 100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

    </style>

</head>

<body class="bg-slate-50 text-slate-800">

<!-- ================================================================
     MOBILE OVERLAY
================================================================ -->
<div
    id="sidebarOverlay"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden lg:hidden"
    onclick="closeMobileSidebar()"
></div>


<!-- ================================================================
     SIDEBAR
================================================================ -->
<aside
    id="sidebar"
    class="
        fixed left-0 top-0 bottom-0 z-50
        w-[280px]
        bg-white
        border-r border-slate-200
        sidebar-transition
        flex flex-col
    "
>

    <!-- BRAND -->
    <div class="h-[88px] px-7 flex items-center border-b border-slate-100">

        <div class="flex items-center gap-3">

            <!-- Logo -->
            <div class="relative w-11 h-11">

                <div class="
                    absolute
                    left-0 top-3
                    w-4 h-4
                    rounded-full
                    border-2 border-brand-600
                "></div>

                <div class="
                    absolute
                    left-5 top-0
                    w-4 h-4
                    rounded-full
                    border-2 border-cyan-400
                "></div>

                <div class="
                    absolute
                    left-5 top-6
                    w-4 h-4
                    rounded-full
                    border-2 border-emerald-400
                "></div>

            </div>

            <div>
                <div class="text-xl font-extrabold tracking-tight text-slate-900">
                    OJT360
                </div>

                <div class="
                    text-[10px]
                    font-semibold
                    tracking-[0.2em]
                    text-brand-600
                ">
                    COMPANY PORTAL
                </div>
            </div>

        </div>

    </div>


    <!-- NAVIGATION -->
    <div class="flex-1 overflow-y-auto px-4 py-6">

        <p class="
            px-3 mb-3
            text-[10px]
            font-bold
            uppercase
            tracking-[0.15em]
            text-slate-400
        ">
            Main Menu
        </p>

        <nav class="space-y-1">

            <!-- Overview -->
            <a
                href="dashboard.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    bg-sky-50
                    text-brand-700
                    font-semibold
                "
            >
                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                <span>Overview</span>
            </a>


            <!-- Assigned Students -->
            <a
                href="assigned_students.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    hover:text-slate-900
                    font-medium
                "
            >
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span>Assigned Students</span>

                <span class="
                    ml-auto
                    min-w-6 h-6
                    px-1.5
                    rounded-full
                    bg-slate-100
                    text-slate-500
                    text-xs
                    font-semibold
                    flex items-center justify-center
                ">
                    <?= $activeStudents ?>
                </span>
            </a>


            <!-- Evaluations -->
            <a
                href="evaluations.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    hover:text-slate-900
                    font-medium
                "
            >
                <i class="fa-regular fa-clipboard w-5 text-center"></i>
                <span>Evaluations</span>

                <?php if ($pendingReviews > 0): ?>
                    <span class="
                        ml-auto
                        min-w-6 h-6
                        px-1.5
                        rounded-full
                        bg-amber-100
                        text-amber-700
                        text-xs
                        font-bold
                        flex items-center justify-center
                    ">
                        <?= $pendingReviews ?>
                    </span>
                <?php endif; ?>

            </a>


            <!-- Dynamic QR -->
            <a
                href="dynamic.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    hover:text-slate-900
                    font-medium
                "
            >
                <i class="fa-solid fa-qrcode w-5 text-center"></i>
                <span>Dynamic QR Code</span>
            </a>


            <!-- Messages -->
            <a
                href="messages.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    hover:text-slate-900
                    font-medium
                "
            >
                <i class="fa-regular fa-message w-5 text-center"></i>
                <span>Messages</span>

                <span class="
                    ml-auto
                    w-2 h-2
                    rounded-full
                    bg-rose-500
                "></span>

            </a>


            <!-- Reports -->
            <a
                href="reports.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    hover:text-slate-900
                    font-medium
                "
            >
                <i class="fa-solid fa-chart-line w-5 text-center"></i>
                <span>Reports & Analytics</span>
            </a>

        </nav>

    </div>


    <!-- BOTTOM LINKS -->
    <div class="px-4 pb-4">

        <div class="border-t border-slate-100 pt-4 space-y-1">

            <a
                href="help.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    font-medium
                "
            >
                <i class="fa-regular fa-circle-question w-5 text-center"></i>
                <span>Help & Support</span>
            </a>

            <a
                href="settings.php"
                class="
                    nav-item
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-slate-600
                    hover:bg-slate-50
                    font-medium
                "
            >
                <i class="fa-solid fa-gear w-5 text-center"></i>
                <span>Settings</span>
            </a>

        </div>


        <!-- USER -->
        <div class="
            mt-4
            p-3
            rounded-2xl
            bg-slate-50
            border border-slate-100
        ">

            <div class="flex items-center gap-3">

                <div class="
                    w-10 h-10
                    rounded-xl
                    bg-gradient-to-br
                    from-sky-400
                    to-blue-600
                    text-white
                    flex items-center justify-center
                    font-bold
                    text-sm
                ">
                    <?= $company['initials'] ?>
                </div>

                <div class="min-w-0 flex-1">

                    <p class="
                        text-sm
                        font-bold
                        text-slate-800
                        truncate
                    ">
                        <?= htmlspecialchars($company['representative']) ?>
                    </p>

                    <p class="
                        text-xs
                        text-slate-500
                        truncate
                    ">
                        Company Administrator
                    </p>

                </div>

                <button
                    onclick="logout()"
                    title="Logout"
                    class="
                        text-slate-400
                        hover:text-rose-500
                        transition
                    "
                >
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </button>

            </div>

        </div>

    </div>

</aside>


<!-- ================================================================
     MAIN AREA
================================================================ -->
<div
    id="mainContent"
    class="
        min-h-screen
        lg:ml-[280px]
        content-transition
    "
>


    <!-- ============================================================
         TOPBAR
    ============================================================= -->
    <header
        class="
            sticky top-0 z-30
            h-[72px]
            bg-white/95
            backdrop-blur
            border-b border-slate-200
            flex items-center
            justify-between
            px-5 lg:px-8
        "
    >

        <div class="flex items-center gap-4">

            <!-- Mobile Menu -->
            <button
                onclick="openMobileSidebar()"
                class="
                    lg:hidden
                    w-10 h-10
                    rounded-xl
                    border border-slate-200
                    text-slate-600
                    hover:bg-slate-50
                "
            >
                <i class="fa-solid fa-bars"></i>
            </button>


            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm">

                <span class="font-medium text-slate-400">
                    OJT360
                </span>

                <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>

                <span class="font-bold text-slate-800">
                    Dashboard
                </span>

            </div>

        </div>


        <div class="flex items-center gap-3">

            <!-- Online Status -->
            <div class="
                hidden sm:flex
                items-center gap-2
                px-3 py-2
                rounded-full
                bg-emerald-50
                text-emerald-700
                text-xs
                font-semibold
            ">
                <span class="
                    w-2 h-2
                    rounded-full
                    bg-emerald-500
                    notification-pulse
                "></span>

                Online
            </div>


            <!-- Theme -->
            <button
                onclick="toggleTheme()"
                class="
                    w-10 h-10
                    rounded-xl
                    border border-slate-200
                    text-slate-600
                    hover:bg-slate-50
                    transition
                "
                title="Toggle theme"
            >
                <i id="themeIcon" class="fa-solid fa-sun"></i>
            </button>


            <!-- Notifications -->
            <div class="relative">

                <button
                    onclick="toggleNotifications()"
                    class="
                        relative
                        w-10 h-10
                        rounded-xl
                        border border-slate-200
                        text-slate-600
                        hover:bg-slate-50
                    "
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="
                        absolute
                        -top-1
                        -right-1
                        w-5 h-5
                        rounded-full
                        bg-rose-500
                        text-white
                        text-[10px]
                        font-bold
                        flex items-center justify-center
                        border-2 border-white
                    ">
                        3
                    </span>

                </button>


                <!-- Notification Dropdown -->
                <div
                    id="notificationDropdown"
                    class="
                        hidden
                        absolute
                        right-0
                        mt-3
                        w-80
                        bg-white
                        rounded-2xl
                        border border-slate-200
                        shadow-xl
                        overflow-hidden
                    "
                >

                    <div class="
                        px-5 py-4
                        border-b border-slate-100
                        flex justify-between
                    ">

                        <span class="font-bold text-slate-900">
                            Notifications
                        </span>

                        <button class="
                            text-xs
                            text-brand-600
                            font-semibold
                        ">
                            Mark all read
                        </button>

                    </div>


                    <div class="divide-y divide-slate-100">

                        <div class="p-4 flex gap-3 hover:bg-slate-50">

                            <div class="
                                w-9 h-9
                                rounded-xl
                                bg-amber-100
                                text-amber-600
                                flex items-center justify-center
                            ">
                                <i class="fa-regular fa-clipboard"></i>
                            </div>

                            <div>
                                <p class="text-sm font-semibold">
                                    Evaluation pending
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    Angela Mercado needs an evaluation.
                                </p>

                                <p class="text-[10px] text-slate-400 mt-1">
                                    15 minutes ago
                                </p>
                            </div>

                        </div>


                        <div class="p-4 flex gap-3 hover:bg-slate-50">

                            <div class="
                                w-9 h-9
                                rounded-xl
                                bg-rose-100
                                text-rose-600
                                flex items-center justify-center
                            ">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>

                            <div>

                                <p class="text-sm font-semibold">
                                    Attendance alert
                                </p>

                                <p class="text-xs text-slate-500 mt-1">
                                    Camille Dizon has attendance concerns.
                                </p>

                                <p class="text-[10px] text-slate-400 mt-1">
                                    1 hour ago
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Profile -->
            <button
                onclick="toggleProfileMenu()"
                class="
                    hidden sm:flex
                    items-center gap-2
                    pl-2
                "
            >

                <div class="
                    w-10 h-10
                    rounded-xl
                    bg-gradient-to-br
                    from-sky-400
                    to-blue-600
                    text-white
                    flex items-center justify-center
                    font-bold
                    text-sm
                ">
                    MC
                </div>

            </button>

        </div>

    </header>


    <!-- ============================================================
         PAGE CONTENT
    ============================================================= -->
    <main class="p-5 lg:p-8 max-w-[1700px] mx-auto">


        <!-- PAGE TITLE -->
        <div class="
            flex
            flex-col
            lg:flex-row
            lg:items-end
            lg:justify-between
            gap-5
            mb-7
        ">

            <div>

                <p class="
                    text-xs
                    uppercase
                    tracking-[0.18em]
                    font-bold
                    text-brand-600
                    mb-2
                ">
                    Company Overview
                </p>

                <h1 class="
                    text-3xl
                    lg:text-4xl
                    font-extrabold
                    tracking-tight
                    text-slate-900
                ">
                    Good afternoon, Maya<span class="text-brand-600">.</span>
                </h1>

                <p class="
                    mt-2
                    text-sm
                    lg:text-base
                    text-slate-500
                    max-w-2xl
                ">
                    Monitor your interns, review their progress, and manage
                    workplace assignments from one place.
                </p>

            </div>


            <div class="flex items-center gap-3">

                <button
                    onclick="openAssignmentModal()"
                    class="
                        px-4 py-2.5
                        rounded-xl
                        bg-slate-900
                        text-white
                        text-sm
                        font-semibold
                        hover:bg-slate-800
                        shadow-sm
                        transition
                        flex items-center gap-2
                    "
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Assign Student
                </button>

            </div>

        </div>


        <!-- ========================================================
             HERO BANNER
        ========================================================= -->
        <section
            class="
                relative
                overflow-hidden
                rounded-3xl
                bg-gradient-to-br
                from-[#102f49]
                via-[#124b70]
                to-[#087fbd]
                p-7 lg:p-9
                mb-7
                shadow-sm
            "
        >

            <!-- Decorative circles -->
            <div class="
                absolute
                -right-20
                -top-28
                w-80 h-80
                rounded-full
                border-[40px]
                border-white/5
            "></div>

            <div class="
                absolute
                right-20
                -bottom-32
                w-72 h-72
                rounded-full
                bg-cyan-400/10
            "></div>


            <div class="relative z-10 max-w-3xl">

                <div class="
                    inline-flex
                    items-center
                    gap-2
                    px-3 py-1.5
                    rounded-full
                    bg-white/10
                    border border-white/10
                    text-white/80
                    text-xs
                    font-semibold
                    mb-5
                ">
                    <i class="fa-solid fa-building text-cyan-300"></i>
                    <?= htmlspecialchars($company['name']) ?>
                </div>


                <h2 class="
                    text-2xl
                    lg:text-3xl
                    font-extrabold
                    text-white
                    tracking-tight
                ">
                    Internship supervision at a glance
                </h2>


                <p class="
                    mt-3
                    text-sm
                    lg:text-base
                    leading-7
                    text-blue-100
                    max-w-2xl
                ">
                    Keep track of intern attendance, workplace assignments,
                    evaluations, and overall OJT progress without jumping
                    between different systems.
                </p>


                <div class="mt-6 flex flex-wrap gap-3">

                    <a
                        href="assigned-students.php"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            px-4 py-2.5
                            rounded-xl
                            bg-white
                            text-slate-900
                            text-sm
                            font-bold
                            hover:bg-slate-50
                            transition
                        "
                    >
                        View students
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>

                    <a
                        href="reports.php"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            px-4 py-2.5
                            rounded-xl
                            bg-white/10
                            border border-white/20
                            text-white
                            text-sm
                            font-semibold
                            hover:bg-white/15
                            transition
                        "
                    >
                        <i class="fa-solid fa-chart-line"></i>
                        View reports
                    </a>

                </div>

            </div>

        </section>


        <!-- ========================================================
             METRIC CARDS
        ========================================================= -->
        <section class="
            grid
            grid-cols-1
            sm:grid-cols-2
            xl:grid-cols-4
            gap-5
            mb-7
        ">


            <!-- ACTIVE STUDENTS -->
            <div class="
                bg-white
                border border-slate-200
                rounded-2xl
                p-5
                shadow-sm
                hover:shadow-md
                transition
            ">

                <div class="flex items-start justify-between">

                    <div class="
                        w-11 h-11
                        rounded-xl
                        bg-sky-50
                        text-brand-600
                        flex items-center justify-center
                    ">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <span class="
                        text-xs
                        font-semibold
                        text-emerald-600
                    ">
                        +2 this month
                    </span>

                </div>

                <div class="mt-5">

                    <p class="text-sm font-medium text-slate-500">
                        Active Students
                    </p>

                    <h3 class="
                        text-3xl
                        font-extrabold
                        text-slate-900
                        mt-1
                    ">
                        <?= $activeStudents ?>
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Currently assigned to your company
                    </p>

                </div>

            </div>


            <!-- PENDING REVIEWS -->
            <div class="
                bg-white
                border border-slate-200
                rounded-2xl
                p-5
                shadow-sm
                hover:shadow-md
                transition
            ">

                <div class="flex items-start justify-between">

                    <div class="
                        w-11 h-11
                        rounded-xl
                        bg-amber-50
                        text-amber-600
                        flex items-center justify-center
                    ">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>

                    <span class="
                        px-2 py-1
                        rounded-full
                        bg-amber-50
                        text-amber-700
                        text-[10px]
                        font-bold
                    ">
                        ACTION NEEDED
                    </span>

                </div>

                <div class="mt-5">

                    <p class="text-sm font-medium text-slate-500">
                        Pending Reviews
                    </p>

                    <h3 class="
                        text-3xl
                        font-extrabold
                        text-slate-900
                        mt-1
                    ">
                        <?= $pendingReviews ?>
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Evaluations waiting for your review
                    </p>

                </div>

            </div>


            <!-- COMPLETION -->
            <div class="
                bg-white
                border border-slate-200
                rounded-2xl
                p-5
                shadow-sm
                hover:shadow-md
                transition
            ">

                <div class="flex items-start justify-between">

                    <div class="
                        w-11 h-11
                        rounded-xl
                        bg-emerald-50
                        text-emerald-600
                        flex items-center justify-center
                    ">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

                    <span class="
                        text-xs
                        font-semibold
                        text-emerald-600
                    ">
                        Overall
                    </span>

                </div>

                <div class="mt-5">

                    <p class="text-sm font-medium text-slate-500">
                        Average Completion
                    </p>

                    <h3 class="
                        text-3xl
                        font-extrabold
                        text-slate-900
                        mt-1
                    ">
                        <?= $averageCompletion ?>%
                    </h3>

                    <div class="
                        mt-3
                        h-1.5
                        rounded-full
                        bg-slate-100
                        overflow-hidden
                    ">
                        <div
                            class="progress-bar h-full rounded-full bg-emerald-500"
                            style="width: <?= $averageCompletion ?>%"
                        ></div>
                    </div>

                </div>

            </div>


            <!-- ATTENDANCE -->
            <div class="
                bg-white
                border border-slate-200
                rounded-2xl
                p-5
                shadow-sm
                hover:shadow-md
                transition
            ">

                <div class="flex items-start justify-between">

                    <div class="
                        w-11 h-11
                        rounded-xl
                        bg-rose-50
                        text-rose-600
                        flex items-center justify-center
                    ">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <?php if ($attendanceAlerts > 0): ?>

                        <span class="
                            px-2 py-1
                            rounded-full
                            bg-rose-50
                            text-rose-600
                            text-[10px]
                            font-bold
                        ">
                            ATTENTION
                        </span>

                    <?php endif; ?>

                </div>

                <div class="mt-5">

                    <p class="text-sm font-medium text-slate-500">
                        Attendance Alerts
                    </p>

                    <h3 class="
                        text-3xl
                        font-extrabold
                        text-slate-900
                        mt-1
                    ">
                        <?= $attendanceAlerts ?>
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Students requiring attention
                    </p>

                </div>

            </div>

        </section>


        <!-- ========================================================
             MAIN GRID
        ========================================================= -->
        <section class="
            grid
            grid-cols-1
            xl:grid-cols-[minmax(0,1fr)_350px]
            gap-6
        ">


            <!-- ====================================================
                 ASSIGNED STUDENTS
            ===================================================== -->
            <div class="
                bg-white
                border border-slate-200
                rounded-2xl
                shadow-sm
                overflow-hidden
            ">

                <!-- Header -->
                <div class="
                    p-5 lg:p-6
                    border-b border-slate-100
                ">

                    <div class="
                        flex
                        flex-col
                        lg:flex-row
                        lg:items-center
                        lg:justify-between
                        gap-4
                    ">

                        <div>

                            <h2 class="
                                text-lg
                                font-bold
                                text-slate-900
                            ">
                                Assigned Students
                            </h2>

                            <p class="
                                text-xs
                                text-slate-500
                                mt-1
                            ">
                                Students currently completing their OJT
                                at your company.
                            </p>

                        </div>


                        <div class="flex gap-2">

                            <!-- Search -->
                            <div class="relative">

                                <i class="
                                    fa-solid fa-magnifying-glass
                                    absolute
                                    left-3
                                    top-1/2
                                    -translate-y-1/2
                                    text-slate-400
                                    text-xs
                                "></i>

                                <input
                                    id="studentSearch"
                                    type="text"
                                    placeholder="Search students..."
                                    oninput="filterStudents()"
                                    class="
                                        w-full
                                        sm:w-52
                                        pl-9 pr-3
                                        py-2.5
                                        rounded-xl
                                        border border-slate-200
                                        bg-slate-50
                                        text-sm
                                        outline-none
                                        focus:ring-2
                                        focus:ring-brand-500/20
                                        focus:border-brand-500
                                    "
                                >

                            </div>


                            <button
                                onclick="window.location.href='assigned-students.php'"
                                class="
                                    px-3
                                    rounded-xl
                                    border border-slate-200
                                    text-slate-600
                                    hover:bg-slate-50
                                "
                                title="View all"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            </button>

                        </div>

                    </div>

                </div>


                <!-- TABLE -->
                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1050px]">

                        <thead class="bg-slate-50/80">

                            <tr class="
                                text-left
                                text-[10px]
                                uppercase
                                tracking-wider
                                text-slate-400
                            ">

                                <th class="px-6 py-4 font-bold">
                                    Student
                                </th>

                                <th class="px-4 py-4 font-bold">
                                    Program
                                </th>

                                <th class="px-4 py-4 font-bold">
                                    Assignment
                                </th>

                                <th class="px-4 py-4 font-bold">
                                    Progress
                                </th>

                                <th class="px-4 py-4 font-bold">
                                    Attendance
                                </th>

                                <th class="px-4 py-4 font-bold">
                                    Evaluation
                                </th>

                                <th class="px-6 py-4 font-bold text-right">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody id="studentTable" class="divide-y divide-slate-100">

                        <?php foreach ($students as $student): ?>

                            <?php

                                $completion = round(
                                    ($student['hours'] / $student['required']) * 100
                                );

                                $attendanceClasses = match ($student['attendance']) {

                                    'Excellent' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'Good' =>
                                        'bg-sky-50 text-sky-700',

                                    'At Risk' =>
                                        'bg-rose-50 text-rose-700',

                                    default =>
                                        'bg-slate-50 text-slate-600'
                                };


                                $evaluationClasses = match ($student['evaluation']) {

                                    'Completed' =>
                                        'bg-emerald-50 text-emerald-700',

                                    'Pending' =>
                                        'bg-amber-50 text-amber-700',

                                    default =>
                                        'bg-slate-50 text-slate-600'
                                };

                            ?>

                            <tr
                                class="
                                    student-row
                                    hover:bg-slate-50/70
                                    transition
                                "
                                data-search="<?= strtolower(
                                    htmlspecialchars(
                                        $student['name'] . ' ' .
                                        $student['id'] . ' ' .
                                        $student['program']
                                    )
                                ) ?>"
                            >

                                <!-- Student -->
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="
                                            w-10 h-10
                                            shrink-0
                                            rounded-xl
                                            bg-gradient-to-br
                                            from-sky-100
                                            to-blue-100
                                            text-brand-700
                                            flex items-center justify-center
                                            font-bold
                                            text-xs
                                        ">
                                            <?= $student['avatar'] ?>
                                        </div>

                                        <div>

                                            <p class="
                                                text-sm
                                                font-bold
                                                text-slate-800
                                            ">
                                                <?= htmlspecialchars($student['name']) ?>
                                            </p>

                                            <p class="
                                                text-[11px]
                                                text-slate-400
                                                mt-0.5
                                            ">
                                                <?= htmlspecialchars($student['id']) ?>
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <!-- Program -->
                                <td class="px-4 py-4">

                                    <p class="
                                        text-xs
                                        font-semibold
                                        text-slate-700
                                    ">
                                        <?= htmlspecialchars($student['program']) ?>
                                    </p>

                                </td>


                                <!-- Assignment -->
                                <td class="px-4 py-4">

                                    <div>

                                        <p class="
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        ">
                                            <?= htmlspecialchars($student['department']) ?>
                                        </p>

                                        <p class="
                                            text-[11px]
                                            text-slate-400
                                            mt-0.5
                                        ">
                                            <?= htmlspecialchars($student['building']) ?>
                                        </p>

                                    </div>

                                </td>


                                <!-- Progress -->
                                <td class="px-4 py-4 min-w-[160px]">

                                    <div class="flex justify-between mb-1.5">

                                        <span class="
                                            text-xs
                                            font-bold
                                            text-slate-700
                                        ">
                                            <?= $student['hours'] ?>
                                            /
                                            <?= $student['required'] ?> hrs
                                        </span>

                                        <span class="
                                            text-[10px]
                                            text-slate-400
                                        ">
                                            <?= $completion ?>%
                                        </span>

                                    </div>

                                    <div class="
                                        h-1.5
                                        bg-slate-100
                                        rounded-full
                                        overflow-hidden
                                    ">

                                        <div
                                            class="
                                                h-full
                                                bg-brand-500
                                                rounded-full
                                                progress-bar
                                            "
                                            style="width: <?= $completion ?>%"
                                        ></div>

                                    </div>

                                </td>


                                <!-- Attendance -->
                                <td class="px-4 py-4">

                                    <span class="
                                        inline-flex
                                        items-center
                                        gap-1.5
                                        px-2.5 py-1.5
                                        rounded-full
                                        text-[10px]
                                        font-bold
                                        <?= $attendanceClasses ?>
                                    ">

                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>

                                        <?= htmlspecialchars($student['attendance']) ?>

                                    </span>

                                </td>


                                <!-- Evaluation -->
                                <td class="px-4 py-4">

                                    <span class="
                                        inline-flex
                                        items-center
                                        px-2.5 py-1.5
                                        rounded-full
                                        text-[10px]
                                        font-bold
                                        <?= $evaluationClasses ?>
                                    ">

                                        <?= htmlspecialchars($student['evaluation']) ?>

                                    </span>

                                </td>


                                <!-- Action -->
                                <td class="px-6 py-4 text-right">

                                    <button
                                        onclick='openEditModal(
                                            <?= json_encode($student, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
                                        )'
                                        class="
                                            inline-flex
                                            items-center
                                            gap-2
                                            px-3
                                            py-2
                                            rounded-lg
                                            border border-slate-200
                                            text-xs
                                            font-semibold
                                            text-slate-600
                                            hover:border-brand-300
                                            hover:text-brand-700
                                            hover:bg-brand-50
                                            transition
                                        "
                                    >

                                        <i class="fa-solid fa-pen-to-square text-[10px]"></i>

                                        <?= empty($student['department'])
                                            ? 'Assign Department'
                                            : 'Edit Assignment'
                                        ?>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- TABLE FOOTER -->
                <div class="
                    px-6 py-4
                    border-t border-slate-100
                    flex
                    items-center
                    justify-between
                ">

                    <p class="text-xs text-slate-400">

                        Showing
                        <span
                            id="visibleCount"
                            class="font-bold text-slate-600"
                        >
                            <?= count($students) ?>
                        </span>

                        of
                        <?= count($students) ?>
                        students

                    </p>

                    <a
                        href="assigned-students.php"
                        class="
                            text-xs
                            font-bold
                            text-brand-600
                            hover:text-brand-700
                        "
                    >
                        View all students
                        <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>

                </div>

            </div>


            <!-- ====================================================
                 RIGHT COLUMN
            ===================================================== -->
            <div class="space-y-6">


                <!-- TODAY -->
                <div class="
                    bg-white
                    border border-slate-200
                    rounded-2xl
                    p-5
                    shadow-sm
                ">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="
                                font-bold
                                text-slate-900
                            ">
                                Today's Overview
                            </h3>

                            <p class="
                                text-xs
                                text-slate-400
                                mt-1
                            ">
                                October 7, 2026
                            </p>

                        </div>

                        <div class="
                            w-10 h-10
                            rounded-xl
                            bg-sky-50
                            text-brand-600
                            flex items-center justify-center
                        ">
                            <i class="fa-regular fa-calendar"></i>
                        </div>

                    </div>


                    <div class="mt-5 space-y-4">

                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-3">

                                <div class="
                                    w-8 h-8
                                    rounded-lg
                                    bg-emerald-50
                                    text-emerald-600
                                    flex items-center justify-center
                                    text-xs
                                ">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>

                                <span class="text-sm text-slate-600">
                                    Checked in
                                </span>

                            </div>

                            <span class="font-bold text-slate-800">
                                3
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-3">

                                <div class="
                                    w-8 h-8
                                    rounded-lg
                                    bg-amber-50
                                    text-amber-600
                                    flex items-center justify-center
                                    text-xs
                                ">
                                    <i class="fa-solid fa-clock"></i>
                                </div>

                                <span class="text-sm text-slate-600">
                                    On break
                                </span>

                            </div>

                            <span class="font-bold text-slate-800">
                                1
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-3">

                                <div class="
                                    w-8 h-8
                                    rounded-lg
                                    bg-slate-100
                                    text-slate-500
                                    flex items-center justify-center
                                    text-xs
                                ">
                                    <i class="fa-solid fa-user-xmark"></i>
                                </div>

                                <span class="text-sm text-slate-600">
                                    Not checked in
                                </span>

                            </div>

                            <span class="font-bold text-slate-800">
                                0
                            </span>

                        </div>

                    </div>

                </div>


                <!-- QUICK ACTIONS -->
                <div class="
                    bg-white
                    border border-slate-200
                    rounded-2xl
                    p-5
                    shadow-sm
                ">

                    <h3 class="
                        font-bold
                        text-slate-900
                    ">
                        Quick Actions
                    </h3>

                    <p class="
                        text-xs
                        text-slate-400
                        mt-1
                    ">
                        Frequently used tools
                    </p>


                    <div class="mt-4 space-y-2">

                        <button
                            onclick="openAssignmentModal()"
                            class="
                                w-full
                                flex items-center
                                gap-3
                                p-3
                                rounded-xl
                                hover:bg-slate-50
                                text-left
                                transition
                            "
                        >

                            <div class="
                                w-9 h-9
                                rounded-lg
                                bg-sky-50
                                text-brand-600
                                flex items-center justify-center
                            ">
                                <i class="fa-solid fa-user-plus text-xs"></i>
                            </div>

                            <div class="flex-1">

                                <p class="
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                ">
                                    Assign a student
                                </p>

                                <p class="
                                    text-[11px]
                                    text-slate-400
                                ">
                                    Add workplace assignment
                                </p>

                            </div>

                            <i class="
                                fa-solid fa-chevron-right
                                text-[10px]
                                text-slate-300
                            "></i>

                        </button>


                        <a
                            href="evaluations.php"
                            class="
                                w-full
                                flex items-center
                                gap-3
                                p-3
                                rounded-xl
                                hover:bg-slate-50
                                text-left
                                transition
                            "
                        >

                            <div class="
                                w-9 h-9
                                rounded-lg
                                bg-amber-50
                                text-amber-600
                                flex items-center justify-center
                            ">
                                <i class="fa-regular fa-clipboard text-xs"></i>
                            </div>

                            <div class="flex-1">

                                <p class="
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                ">
                                    Review evaluations
                                </p>

                                <p class="
                                    text-[11px]
                                    text-slate-400
                                ">
                                    <?= $pendingReviews ?> pending reviews
                                </p>

                            </div>

                            <i class="
                                fa-solid fa-chevron-right
                                text-[10px]
                                text-slate-300
                            "></i>

                        </a>


                        <a
                            href="qr-code.php"
                            class="
                                w-full
                                flex items-center
                                gap-3
                                p-3
                                rounded-xl
                                hover:bg-slate-50
                                text-left
                                transition
                            "
                        >

                            <div class="
                                w-9 h-9
                                rounded-lg
                                bg-violet-50
                                text-violet-600
                                flex items-center justify-center
                            ">
                                <i class="fa-solid fa-qrcode text-xs"></i>
                            </div>

                            <div class="flex-1">

                                <p class="
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                ">
                                    Open company QR
                                </p>

                                <p class="
                                    text-[11px]
                                    text-slate-400
                                ">
                                    Attendance check-in
                                </p>

                            </div>

                            <i class="
                                fa-solid fa-chevron-right
                                text-[10px]
                                text-slate-300
                            "></i>

                        </a>

                    </div>

                </div>


                <!-- ATTENTION -->
                <div class="
                    rounded-2xl
                    border border-rose-100
                    bg-rose-50/60
                    p-5
                ">

                    <div class="flex gap-3">

                        <div class="
                            w-9 h-9
                            shrink-0
                            rounded-xl
                            bg-rose-100
                            text-rose-600
                            flex items-center justify-center
                        ">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div>

                            <h3 class="
                                text-sm
                                font-bold
                                text-rose-900
                            ">
                                Attendance needs attention
                            </h3>

                            <p class="
                                text-xs
                                leading-5
                                text-rose-700
                                mt-1
                            ">
                                Camille Dizon currently has an attendance
                                status marked as at risk.
                            </p>

                            <a
                                href="assigned-students.php"
                                class="
                                    inline-block
                                    mt-3
                                    text-xs
                                    font-bold
                                    text-rose-700
                                    hover:text-rose-900
                                "
                            >
                                Review student
                                <i class="fa-solid fa-arrow-right ml-1"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>


<!-- ================================================================
     ASSIGNMENT MODAL
================================================================ -->
<div
    id="assignmentModal"
    class="
        fixed inset-0
        z-[100]
        hidden
        items-center justify-center
        p-4
    "
>

    <div
        class="
            absolute inset-0
            bg-slate-950/50
            backdrop-blur-sm
        "
        onclick="closeAssignmentModal()"
    ></div>


    <div
        class="
            relative
            w-full
            max-w-lg
            bg-white
            rounded-3xl
            shadow-2xl
            modal-enter
            overflow-hidden
        "
    >

        <!-- Modal Header -->
        <div class="
            px-6 py-5
            border-b border-slate-100
            flex items-center justify-between
        ">

            <div>

                <h2 class="
                    text-lg
                    font-bold
                    text-slate-900
                ">
                    Assign Student
                </h2>

                <p class="
                    text-xs
                    text-slate-400
                    mt-1
                ">
                    Create or update a workplace assignment.
                </p>

            </div>

            <button
                onclick="closeAssignmentModal()"
                class="
                    w-9 h-9
                    rounded-xl
                    hover:bg-slate-100
                    text-slate-400
                "
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <!-- Modal Body -->
        <form
            id="assignmentForm"
            onsubmit="saveAssignment(event)"
            class="p-6 space-y-5"
        >

            <!-- Student -->
            <div>

                <label class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                ">
                    Student
                </label>

                <select
                    id="modalStudent"
                    required
                    class="
                        w-full
                        px-4 py-3
                        rounded-xl
                        border border-slate-200
                        bg-white
                        text-sm
                        outline-none
                        focus:border-brand-500
                        focus:ring-2
                        focus:ring-brand-500/10
                    "
                >

                    <option value="">
                        Select student
                    </option>

                    <?php foreach ($students as $student): ?>

                        <option value="<?= htmlspecialchars($student['id']) ?>">
                            <?= htmlspecialchars($student['name']) ?>
                            — <?= htmlspecialchars($student['id']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Department -->
            <div>

                <label class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                ">
                    Department
                </label>

                <select
                    id="modalDepartment"
                    required
                    class="
                        w-full
                        px-4 py-3
                        rounded-xl
                        border border-slate-200
                        bg-white
                        text-sm
                        outline-none
                        focus:border-brand-500
                        focus:ring-2
                        focus:ring-brand-500/10
                    "
                >

                    <option value="">
                        Select department
                    </option>

                    <option>
                        IT Department
                    </option>

                    <option>
                        Software Development
                    </option>

                    <option>
                        Operations
                    </option>

                    <option>
                        Human Resources
                    </option>

                    <option>
                        Marketing
                    </option>

                </select>

            </div>


            <!-- Building -->
            <div>

                <label class="
                    block
                    text-xs
                    font-bold
                    text-slate-600
                    mb-2
                ">
                    Building / Workplace
                </label>

                <select
                    id="modalBuilding"
                    required
                    class="
                        w-full
                        px-4 py-3
                        rounded-xl
                        border border-slate-200
                        bg-white
                        text-sm
                        outline-none
                        focus:border-brand-500
                        focus:ring-2
                        focus:ring-brand-500/10
                    "
                >

                    <option value="">
                        Select building
                    </option>

                    <option>
                        Main Building
                    </option>

                    <option>
                        Tech Building
                    </option>

                    <option>
                        Operations Building
                    </option>

                </select>

            </div>


            <!-- Buttons -->
            <div class="
                pt-2
                flex
                justify-end
                gap-3
            ">

                <button
                    type="button"
                    onclick="closeAssignmentModal()"
                    class="
                        px-4 py-2.5
                        rounded-xl
                        border border-slate-200
                        text-sm
                        font-semibold
                        text-slate-600
                        hover:bg-slate-50
                    "
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="
                        px-5 py-2.5
                        rounded-xl
                        bg-slate-900
                        text-white
                        text-sm
                        font-bold
                        hover:bg-slate-800
                        flex items-center gap-2
                    "
                >
                    <i class="fa-solid fa-check"></i>
                    Save Assignment
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ================================================================
     TOAST
================================================================ -->
<div
    id="toast"
    class="
        fixed
        right-5
        bottom-5
        z-[200]
        hidden
        max-w-sm
        bg-slate-900
        text-white
        rounded-2xl
        px-4 py-3
        shadow-xl
    "
>

    <div class="flex items-center gap-3">

        <div
            id="toastIcon"
            class="
                w-8 h-8
                rounded-lg
                bg-emerald-500
                flex items-center justify-center
                text-xs
            "
        >
            <i class="fa-solid fa-check"></i>
        </div>

        <div>

            <p
                id="toastTitle"
                class="text-sm font-bold"
            >
                Success
            </p>

            <p
                id="toastMessage"
                class="text-xs text-slate-300 mt-0.5"
            >
                Action completed.
            </p>

        </div>

    </div>

</div>


<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>

    // ---------------------------------------------------------------
    // MOBILE SIDEBAR
    // ---------------------------------------------------------------

    function openMobileSidebar() {

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');

    }


    function closeMobileSidebar() {

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');

    }


    // ---------------------------------------------------------------
    // NOTIFICATIONS
    // ---------------------------------------------------------------

    function toggleNotifications() {

        const dropdown =
            document.getElementById('notificationDropdown');

        dropdown.classList.toggle('hidden');

    }


    document.addEventListener('click', function(event) {

        const dropdown =
            document.getElementById('notificationDropdown');

        const notificationButton =
            event.target.closest(
                '[onclick="toggleNotifications()"]'
            );

        if (!notificationButton &&
            !dropdown.contains(event.target)) {

            dropdown.classList.add('hidden');

        }

    });


    // ---------------------------------------------------------------
    // THEME
    // ---------------------------------------------------------------

    function toggleTheme() {

        document.documentElement.classList.toggle('dark');

        const icon =
            document.getElementById('themeIcon');

        if (
            document.documentElement.classList.contains('dark')
        ) {

            icon.className = 'fa-solid fa-moon';

            showToast(
                'Theme changed',
                'Dark mode is ready for the next UI pass.'
            );

        } else {

            icon.className = 'fa-solid fa-sun';

        }

    }


    // ---------------------------------------------------------------
    // ASSIGNMENT MODAL
    // ---------------------------------------------------------------

    function openAssignmentModal() {

        const modal =
            document.getElementById('assignmentModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.classList.add('overflow-hidden');

    }


    function closeAssignmentModal() {

        const modal =
            document.getElementById('assignmentModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        document.body.classList.remove('overflow-hidden');

    }


    function openEditModal(student) {

        openAssignmentModal();

        document.getElementById('modalStudent').value =
            student.id;

        document.getElementById('modalDepartment').value =
            student.department;

        document.getElementById('modalBuilding').value =
            student.building;

    }


    function saveAssignment(event) {

        event.preventDefault();

        const student =
            document.getElementById('modalStudent').value;

        const department =
            document.getElementById('modalDepartment').value;

        const building =
            document.getElementById('modalBuilding').value;


        if (!student || !department || !building) {

            showToast(
                'Incomplete form',
                'Please fill out all required fields.',
                true
            );

            return;

        }


        closeAssignmentModal();

        showToast(
            'Assignment saved',
            'The workplace assignment has been updated.'
        );

    }


    // ---------------------------------------------------------------
    // STUDENT SEARCH
    // ---------------------------------------------------------------

    function filterStudents() {

        const search =
            document
                .getElementById('studentSearch')
                .value
                .toLowerCase()
                .trim();

        const rows =
            document.querySelectorAll('.student-row');

        let visible = 0;


        rows.forEach(row => {

            const data =
                row.dataset.search;

            if (data.includes(search)) {

                row.style.display = '';
                visible++;

            } else {

                row.style.display = 'none';

            }

        });


        document.getElementById('visibleCount').textContent =
            visible;

    }


    // ---------------------------------------------------------------
    // TOAST
    // ---------------------------------------------------------------

    let toastTimer;


    function showToast(
        title,
        message,
        error = false
    ) {

        const toast =
            document.getElementById('toast');

        const icon =
            document.getElementById('toastIcon');

        document.getElementById('toastTitle')
            .textContent = title;

        document.getElementById('toastMessage')
            .textContent = message;


        icon.className =
            error
                ? 'w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center text-xs'
                : 'w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-xs';


        icon.innerHTML =
            error
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-check"></i>';


        toast.classList.remove('hidden');


        clearTimeout(toastTimer);

        toastTimer = setTimeout(() => {

            toast.classList.add('hidden');

        }, 3500);

    }


    // ---------------------------------------------------------------
    // LOGOUT
    // ---------------------------------------------------------------

    function logout() {

        if (
            confirm(
                'Are you sure you want to logout?'
            )
        ) {

            window.location.href = 'logout.php';

        }

    }


    // ---------------------------------------------------------------
    // ESCAPE KEY
    // ---------------------------------------------------------------

    document.addEventListener(
        'keydown',
        function(event) {

            if (event.key === 'Escape') {

                closeAssignmentModal();

                document
                    .getElementById('notificationDropdown')
                    .classList.add('hidden');

            }

        }
    );


    // ---------------------------------------------------------------
    // INITIAL MOBILE STATE
    // ---------------------------------------------------------------

    function initializeSidebar() {

        const sidebar =
            document.getElementById('sidebar');

        if (window.innerWidth < 1024) {

            sidebar.classList.add('-translate-x-full');

        }

    }


    initializeSidebar();


    window.addEventListener(
        'resize',
        function() {

            const sidebar =
                document.getElementById('sidebar');

            if (window.innerWidth >= 1024) {

                sidebar.classList.remove('-translate-x-full');

            }

        }
    );

</script>

</body>
</html>