<?php

// =========================================================
// DATABASE CONNECTION
// =========================================================

$host = "localhost";
$username = "root";
$password = "";
$database = "ojt360";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


// =========================================================
// SESSION
// =========================================================

session_start();


// =========================================================
// LOGIN VARIABLES
// =========================================================

$errorMessage = "";


// =========================================================
// LOGIN PROCESS
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST["role"] ?? "";

    // =====================================================
    // STUDENT LOGIN
    // =====================================================

    if ($role === "student") {

        $student_id = trim(
            $_POST["student_username"] ?? ""
        );

        $student_password =
            $_POST["student_password"] ?? "";

        if (
            empty($student_id) ||
            empty($student_password)
        ) {

            $errorMessage =
                "Please enter your Student ID and password.";

        } else {

            $stmt = $conn->prepare(
                "SELECT
                    id,
                    student_id,
                    first_name,
                    middle_name,
                    last_name,
                    institutional_email,
                    password_hash
                 FROM students
                 WHERE student_id = ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "s",
                $student_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $student = $result->fetch_assoc();

                if (
                    password_verify(
                        $student_password,
                        $student["password_hash"]
                    )
                ) {

                    // =====================================
                    // CREATE SESSION
                    // =====================================

                    $_SESSION["logged_in"] = true;

                    $_SESSION["role"] = "student";

                    $_SESSION["user_id"] =
                        $student["id"];

                    $_SESSION["student_id"] =
                        $student["student_id"];

                    $_SESSION["first_name"] =
                        $student["first_name"];

                    $_SESSION["middle_name"] =
                        $student["middle_name"];

                    $_SESSION["last_name"] =
                        $student["last_name"];

                    $_SESSION["email"] =
                        $student["institutional_email"];

                    // =====================================
                    // REDIRECT
                    // =====================================

                    header("Location: index.php");
                    exit;

                } else {

                    $errorMessage =
                        "Invalid Student ID or password.";

                }

            } else {

                $errorMessage =
                    "Invalid Student ID or password.";

            }

            $stmt->close();
        }
    }


    // =====================================================
    // EMPLOYEE LOGIN
    // =====================================================

    elseif ($role === "employee") {

        $employee_email = trim(
            $_POST["employee_username"] ?? ""
        );

        $employee_password =
            $_POST["employee_password"] ?? "";

        if (
            empty($employee_email) ||
            empty($employee_password)
        ) {

            $errorMessage =
                "Please enter your institutional email and password.";

        } else {

            $stmt = $conn->prepare(
                "SELECT
                    id,
                    employee_id,
                    first_name,
                    middle_name,
                    last_name,
                    contact_number,
                    email,
                    department,
                    position,
                    username,
                    password_hash
                 FROM employees
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "s",
                $employee_email
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $employee = $result->fetch_assoc();

                if (
                    password_verify(
                        $employee_password,
                        $employee["password_hash"]
                    )
                ) {

                    // =====================================
                    // CREATE SESSION
                    // =====================================

                    $_SESSION["logged_in"] = true;

                    $_SESSION["role"] = "employee";

                    $_SESSION["user_id"] =
                        $employee["id"];

                    $_SESSION["employee_id"] =
                        $employee["employee_id"];

                    $_SESSION["first_name"] =
                        $employee["first_name"];

                    $_SESSION["middle_name"] =
                        $employee["middle_name"];

                    $_SESSION["last_name"] =
                        $employee["last_name"];

                    $_SESSION["email"] =
                        $employee["email"];

                    $_SESSION["department"] =
                        $employee["department"];

                    $_SESSION["position"] =
                        $employee["position"];

                    // =====================================
                    // REDIRECT
                    // =====================================

                    header("Location: index.php");
                    exit;

                } else {

                    $errorMessage =
                        "Invalid institutional email or password.";

                }

            } else {

                $errorMessage =
                    "Invalid institutional email or password.";

            }

            $stmt->close();
        }
    }


    // =====================================================
    // COMPANY LOGIN
    // =====================================================

    elseif ($role === "company") {

        $company_username = trim(
            $_POST["company_username"] ?? ""
        );

        $company_password =
            $_POST["company_password"] ?? "";

        if (
            empty($company_username) ||
            empty($company_password)
        ) {

            $errorMessage =
                "Please enter your company email and password.";

        } else {

            $stmt = $conn->prepare(
                "SELECT
                    id,
                    company_name,
                    rep_first_name,
                    rep_middle_name,
                    rep_last_name,
                    rep_position,
                    contact_number,
                    representative_email,
                    company_username,
                    password_hash
                 FROM companies
                 WHERE company_username = ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "s",
                $company_username
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $company = $result->fetch_assoc();

                if (
                    password_verify(
                        $company_password,
                        $company["password_hash"]
                    )
                ) {

                    // =====================================
                    // CREATE SESSION
                    // =====================================

                    $_SESSION["logged_in"] = true;

                    $_SESSION["role"] = "company";

                    $_SESSION["user_id"] =
                        $company["id"];

                    $_SESSION["company_name"] =
                        $company["company_name"];

                    $_SESSION["rep_first_name"] =
                        $company["rep_first_name"];

                    $_SESSION["rep_middle_name"] =
                        $company["rep_middle_name"];

                    $_SESSION["rep_last_name"] =
                        $company["rep_last_name"];

                    $_SESSION["rep_position"] =
                        $company["rep_position"];

                    $_SESSION["contact_number"] =
                        $company["contact_number"];

                    $_SESSION["representative_email"] =
                        $company["representative_email"];

                    $_SESSION["company_username"] =
                        $company["company_username"];

                    // =====================================
                    // REDIRECT
                    // =====================================

                    header("Location: index.php");
                    exit;

                } else {

                    $errorMessage =
                        "Invalid company email or password.";

                }

            } else {

                $errorMessage =
                    "Invalid company email or password.";

            }

            $stmt->close();
        }
    }


    // =====================================================
    // INVALID ROLE
    // =====================================================

    else {

        $errorMessage =
            "Please select a valid account type.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $pageTitle = "OJT360 - Sign In" ?></title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* =========================================================
   BACKGROUND
========================================================= */

.hero-bg {
    background:
        radial-gradient(
            circle at 75% 35%,
            #0d3b66 0%,
            #081c33 48%,
            #071426 100%
        );
}

/* =========================================================
   ORBIT VISUAL
========================================================= */

.orbit-visual {
    position: absolute;

    width: 560px;
    height: 560px;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    pointer-events: none;

    z-index: 0;

    opacity: .75;
}

/* =========================================================
   ORBIT GLOW
========================================================= */

.orbit-glow {

    position: absolute;

    inset: 80px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(34,211,238,.20) 0%,
            rgba(14,165,233,.12) 40%,
            transparent 75%
        );

    filter: blur(18px);

    animation:
        pulse 3.4s ease-in-out infinite;
}

/* =========================================================
   ORBIT RINGS
========================================================= */

.orbit-ring {

    position: absolute;

    top: 50%;
    left: 50%;

    transform: translate(-50%, -50%);

    border-radius: 50%;

    border:
        1px solid rgba(34,211,238,.16);
}

.ring-outer {
    width: 540px;
    height: 540px;
}

.ring-inner {

    width: 370px;
    height: 370px;

    border-color:
        rgba(96,165,250,.18);
}

.ring-small {

    width: 245px;
    height: 245px;

    border-color:
        rgba(34,211,238,.10);
}

/* =========================================================
   ORBIT TRACK
========================================================= */

.orbit-track {

    --radius: 270px;

    position: absolute;

    top: 50%;
    left: 50%;

    width:
        calc(var(--radius) * 2);

    height:
        calc(var(--radius) * 2);

    transform-origin: center;

    animation:
        spin var(--duration, 15s)
        linear infinite;
}

/* =========================================================
   ORBIT DOT
========================================================= */

.orbit-dot {

    position: absolute;

    top: 0;
    left: 50%;

    width: 12px;
    height: 12px;

    border-radius: 50%;

    transform:
        translate(-50%, -50%);
}

.orbit-dot.small {

    width: 8px;
    height: 8px;
}

/* =========================================================
   DOT COLORS
========================================================= */

.dot-cyan {

    background: #22d3ee;

    box-shadow:
        0 0 8px #22d3ee,
        0 0 20px rgba(34,211,238,.5);
}

.dot-blue {

    background: #60a5fa;

    box-shadow:
        0 0 8px #60a5fa,
        0 0 18px rgba(96,165,250,.5);
}

.dot-white {

    background: #e0f2fe;

    box-shadow:
        0 0 8px #e0f2fe,
        0 0 16px rgba(224,242,254,.5);
}

/* =========================================================
   ORBIT CENTER
========================================================= */

.orbit-center {

    position: absolute;

    top: 50%;
    left: 50%;

    width: 90px;
    height: 90px;

    transform:
        translate(-50%, -50%)
        rotate(45deg);

    background:
        linear-gradient(
            145deg,
            #102a43,
            #071426
        );

    border:
        1px solid rgba(34,211,238,.35);

    border-radius: 22px;

    box-shadow:
        0 0 30px
        rgba(34,211,238,.18);
}

.orbit-center-mark {

    position: absolute;

    inset: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    transform:
        rotate(-45deg);
}

.orbit-center-mark span {

    width: 10px;
    height: 10px;

    border-radius: 50%;

    border:
        2px solid #22d3ee;

    box-shadow:
        0 0 8px
        rgba(34,211,238,.5);
}

.orbit-center-mark span:nth-child(2) {

    border-color:
        #60a5fa;
}

.orbit-center-mark span:nth-child(3) {

    border-color:
        #e0f2fe;

    margin-left: -22px;

    margin-top: 18px;
}

/* =========================================================
   PARTICLES
========================================================= */

.orbit-particle {

    position: absolute;

    width: 4px;
    height: 4px;

    border-radius: 50%;

    background: #22d3ee;

    box-shadow:
        0 0 10px #22d3ee;

    animation:
        float 4s ease-in-out infinite;
}

.p1 {
    top: 20%;
    left: 20%;
}

.p2 {

    top: 68%;
    left: 76%;

    animation-delay: 1s;
}

.p3 {

    top: 78%;
    left: 30%;

    animation-delay: 2s;
}

.p4 {

    top: 18%;
    left: 70%;

    animation-delay: .5s;
}

/* =========================================================
   ANIMATIONS
========================================================= */

@keyframes spin {

    from {

        transform:
            translate(-50%, -50%)
            rotate(0deg);
    }

    to {

        transform:
            translate(-50%, -50%)
            rotate(360deg);
    }
}

@keyframes pulse {

    0%, 100% {

        opacity: .4;

        transform:
            scale(1);
    }

    50% {

        opacity: .9;

        transform:
            scale(1.08);
    }
}

@keyframes float {

    0%, 100% {

        opacity: .3;

        transform:
            translateY(0)
            scale(.8);
    }

    50% {

        opacity: 1;

        transform:
            translateY(-12px)
            scale(1.2);
    }
}

/* =========================================================
   LOGIN TAB
========================================================= */

.tab-active {

    background: white;

    color:
        #0284c7 !important;

    box-shadow:
        0 1px 4px
        rgba(0,0,0,.1);
}

/* =========================================================
   FORM LABEL
========================================================= */

.label {

    display: block;

    font-size: 12px;

    font-weight: 700;

    color:
        #334155;

    margin-bottom: 6px;
}

/* =========================================================
   FORM INPUT
========================================================= */

.input {

    width: 100%;

    padding:
        12px 14px;

    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    color:
        #1e293b;

    font-size: 14px;

    outline: none;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,.04);

    transition:
        .2s;
}

.input:focus {

    border-color:
        #0ea5e9;

    box-shadow:
        0 0 0 2px
        rgba(14,165,233,.15);
}

/* =========================================================
   SHOW PASSWORD BUTTON
========================================================= */

.show-btn {

    position: absolute;

    right: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        #0284c7;

    font-size: 12px;

    font-weight: 600;
}

/* =========================================================
   OPTIONS
========================================================= */

.options {

    display: flex;

    align-items: center;

    justify-content: space-between;

    font-size: 12px;

    color:
        #64748b;
}

.options label {

    display: flex;

    align-items: center;

    gap: 7px;
}

.options a {

    color:
        #0ea5e9;

    font-weight: 600;
}

/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-btn {

    width: 100%;

    padding: 14px;

    background:
        #0ea5e9;

    color: white;

    font-size: 14px;

    font-weight: 700;

    border-radius: 12px;

    box-shadow:
        0 6px 15px
        rgba(14,165,233,.25);

    transition:
        .2s;
}

.login-btn:hover {

    background:
        #0284c7;

    transform:
        translateY(-1px);
}

/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 768px) {

    .orbit-visual {

        width: 500px;

        height: 500px;

        opacity: .25;
    }

    .ring-outer {

        width: 470px;

        height: 470px;
    }

    .ring-inner {

        width: 320px;

        height: 320px;
    }

    .ring-small {

        width: 210px;

        height: 210px;
    }
}

</style>

</head>

<body
    class="hero-bg min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-8 overflow-x-hidden antialiased relative"
>

<!-- =========================================================
     MAIN CONTAINER
========================================================= -->

<div
    class="w-full max-w-7xl flex flex-col md:flex-row items-center justify-between gap-8 lg:gap-16 relative z-10 my-auto"
>

    <!-- =====================================================
         LEFT SIDE
    ====================================================== -->

    <section
        class="w-full md:w-1/2 text-white flex flex-col justify-between items-start space-y-8 lg:space-y-12 p-2 sm:p-4 relative"
    >

        <!-- =================================================
             ORBIT BACKGROUND
        ================================================== -->

        <div class="orbit-visual">

            <div class="orbit-glow"></div>

            <div class="orbit-ring ring-outer"></div>

            <div class="orbit-ring ring-inner"></div>

            <div class="orbit-ring ring-small"></div>

            <div
                class="orbit-track"
                style="--radius:270px;--duration:18s"
            >

                <div class="orbit-dot dot-cyan"></div>

            </div>

            <div
                class="orbit-track"
                style="--radius:270px;--duration:24s"
            >

                <div class="orbit-dot small dot-blue"></div>

            </div>

            <div
                class="orbit-track"
                style="--radius:185px;--duration:12s"
            >

                <div class="orbit-dot small dot-white"></div>

            </div>

            <div
                class="orbit-track"
                style="--radius:185px;--duration:16s"
            >

                <div class="orbit-dot small dot-cyan"></div>

            </div>

            <div class="orbit-center">

                <div class="orbit-center-mark">

                    <span></span>

                    <span></span>

                    <span></span>

                </div>

            </div>

            <div class="orbit-particle p1"></div>

            <div class="orbit-particle p2"></div>

            <div class="orbit-particle p3"></div>

            <div class="orbit-particle p4"></div>

        </div>

        <!-- =================================================
             LEFT CONTENT
        ================================================== -->

        <div class="relative z-10 flex flex-col justify-between items-start space-y-8 lg:space-y-12">

            <div class="flex items-center gap-3 drop-shadow-md">

                <svg
                    class="w-10 h-10 text-cyan-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    ></path>

                </svg>

                <span class="font-extrabold text-3xl sm:text-4xl lg:text-5xl tracking-tight text-white">
                    OJT<span class="text-cyan-400">360</span>
                </span>

            </div>

            <div class="space-y-4 max-w-xl">

                <span class="text-xs sm:text-sm font-bold uppercase tracking-widest text-cyan-300">
                    JOIN OJT360 TODAY
                </span>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-white tracking-tight drop-shadow-lg">
                    Start tracking your internship journey.
                </h1>

                <p class="text-blue-100 text-sm sm:text-base lg:text-lg leading-relaxed opacity-95 font-normal drop-shadow-sm">
                    Connect with coordinators, log your rendered hours,
                    and submit daily requirements seamlessly.
                </p>

            </div>

            <div class="flex items-center space-x-12 pt-6 border-t border-blue-400/20 max-w-xl w-full">

                <div>
                    <div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">
                        2,400+
                    </div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">
                        Active Students
                    </div>
                </div>

                <div class="h-12 w-px bg-blue-400/20"></div>

                <div>
                    <div class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white">
                        98%
                    </div>
                    <div class="text-xs sm:text-sm text-blue-200 mt-0.5">
                        Success Rate
                    </div>
                </div>

            </div>

        </div>

    </section>

    <!-- =====================================================
         LOGIN CARD
    ====================================================== -->

    <section
        class="w-full md:w-[540px] lg:w-[560px] bg-white rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] p-6 sm:p-8 text-slate-800 border border-slate-100 relative z-20 my-auto"
    >

        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="space-y-0.5 mb-5">

            <span
                class="text-[10px] font-bold uppercase tracking-widest text-sky-500"
            >

                Welcome Back

            </span>

            <h2
                class="text-2xl font-extrabold text-slate-800 tracking-tight"
            >

                Sign in to your account

            </h2>

            <p
                class="text-xs text-slate-500"
            >

                Continue your internship journey with OJT360.

            </p>

        </div>

        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if (!empty($errorMessage)): ?>

            <div
                class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-xs font-semibold"
            >

                <?= htmlspecialchars($errorMessage) ?>

            </div>

        <?php endif; ?>

        <!-- =================================================
             ROLE TABS
        ================================================== -->

        <div
            class="bg-slate-100/80 p-1 rounded-2xl flex items-center justify-between text-xs font-semibold text-slate-500 mb-5"
        >

            <!-- STUDENT -->

            <button
                type="button"
                onclick="selectRole('student')"
                id="tab-student"
                class="tab-active w-1/3 py-2.5 rounded-xl"
            >

                Student

            </button>

            <!-- EMPLOYEE -->

            <button
                type="button"
                onclick="selectRole('employee')"
                id="tab-employee"
                class="w-1/3 py-2.5 rounded-xl hover:text-slate-800"
            >

                Employee

            </button>

            <!-- COMPANY -->

            <button
                type="button"
                onclick="selectRole('company')"
                id="tab-company"
                class="w-1/3 py-2.5 rounded-xl hover:text-slate-800"
            >

                Company

            </button>

        </div>


        <!-- =================================================
             FORMS
        ================================================== -->

        <div id="login-forms">

            <!-- =================================================
                 STUDENT FORM
            ================================================== -->

            <form
                id="student-form"
                class="space-y-4"
                method="POST"
            >

                <input
                    type="hidden"
                    name="role"
                    value="student"
                >

                <!-- STUDENT ID -->

                <div>

                    <label class="label">
                        Student ID
                    </label>

                    <input
                        type="text"
                        name="student_username"
                        placeholder="ex. 1234567"
                        required
                        class="input"
                    >

                </div>

                <!-- PASSWORD -->

                <div>

                    <label class="label">
                        Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            id="student-password"
                            name="student_password"
                            placeholder="Enter your password"
                            required
                            class="input pr-16"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('student')"
                            class="show-btn"
                        >

                            Show

                        </button>

                    </div>

                </div>

                <!-- OPTIONS -->

                <div class="options">

                    <label>

                        <input type="checkbox">

                        Remember me

                    </label>

                    <a href="#">
                        Forgot password?
                    </a>

                </div>

                <!-- BUTTON -->

                <button
                    type="submit"
                    class="login-btn"
                >

                    Sign in securely →

                </button>

            </form>


            <!-- =================================================
                 EMPLOYEE FORM
            ================================================== -->

            <form
                id="employee-form"
                class="hidden space-y-4"
                method="POST"
            >

                <input
                    type="hidden"
                    name="role"
                    value="employee"
                >

                <!-- EMAIL -->

                <div>

                    <label class="label">
                        Institutional email
                    </label>

                    <input
                        type="email"
                        name="employee_username"
                        placeholder="juan.delacruz@lnu.edu.ph"
                        required
                        class="input"
                    >

                </div>

                <!-- PASSWORD -->

                <div>

                    <label class="label">
                        Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            id="employee-password"
                            name="employee_password"
                            placeholder="Enter your password"
                            required
                            class="input pr-16"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('employee')"
                            class="show-btn"
                        >

                            Show

                        </button>

                    </div>

                </div>

                <!-- OPTIONS -->

                <div class="options">

                    <label>

                        <input type="checkbox">

                        Remember me

                    </label>

                    <a href="#">
                        Forgot password?
                    </a>

                </div>

                <!-- BUTTON -->

                <button
                    type="submit"
                    class="login-btn"
                >

                    Sign in securely →

                </button>

            </form>


            <!-- =================================================
                 COMPANY FORM
            ================================================== -->

            <form
                id="company-form"
                class="hidden space-y-4"
                method="POST"
            >

                <input
                    type="hidden"
                    name="role"
                    value="company"
                >

                <!-- COMPANY EMAIL -->

                <div>

                    <label class="label">
                        Company email
                    </label>

                    <input
                        type="email"
                        name="company_username"
                        placeholder="accountreports@bdo.com.ph"
                        required
                        class="input"
                    >

                </div>

                <!-- PASSWORD -->

                <div>

                    <label class="label">
                        Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            id="company-password"
                            name="company_password"
                            placeholder="Enter your password"
                            required
                            class="input pr-16"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('company')"
                            class="show-btn"
                        >

                            Show

                        </button>

                    </div>

                </div>

                <!-- OPTIONS -->

                <div class="options">

                    <label>

                        <input type="checkbox">

                        Remember me

                    </label>

                    <a href="#">
                        Forgot password?
                    </a>

                </div>

                <!-- BUTTON -->

                <button
                    type="submit"
                    class="login-btn"
                >

                    Sign in securely →

                </button>

            </form>

        </div>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="text-center mt-5">

            <p class="text-xs text-slate-500">

                New to OJT360?

                <a
                    href="register.php"
                    class="font-bold text-sky-500 hover:text-sky-600"
                >

                    Create an account

                </a>

            </p>

        </div>



    </section>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   TOGGLE PASSWORD
========================================================= */

function togglePassword(role) {

    const input =
        document.getElementById(
            role + '-password'
        );

    const button =
        document.querySelector(
            `#${role}-form .show-btn`
        );

    if (input.type === 'password') {

        input.type = 'text';

        button.textContent = 'Hide';

    } else {

        input.type = 'password';

        button.textContent = 'Show';

    }

}


/* =========================================================
   SELECT ROLE
========================================================= */

function selectRole(role) {

    const roles = [
        'student',
        'employee',
        'company'
    ];

    roles.forEach(function(r) {

        const tab =
            document.getElementById(
                'tab-' + r
            );

        const form =
            document.getElementById(
                r + '-form'
            );

        if (r === role) {

            tab.classList.add(
                'tab-active'
            );

            form.classList.remove(
                'hidden'
            );

        } else {

            tab.classList.remove(
                'tab-active'
            );

            form.classList.add(
                'hidden'
            );

        }

    });

}

</script>

</body>
</html>