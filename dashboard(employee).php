<?php
session_start();

/* =========================================================
   EMPLOYEE AUTHENTICATION
========================================================= */

if (
    !isset($_SESSION["employee_logged_in"]) ||
    $_SESSION["employee_logged_in"] !== true ||
    empty($_SESSION["employee_id"])
) {
    header("Location: login.php");
    exit;
}

/* =========================================================
   DATABASE CONNECTION
========================================================= */

$host = "localhost";
$username = "root";
$password = "";
$database = "ojt360";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* =========================================================
   GET LOGGED-IN EMPLOYEE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        employee_id,
        first_name,
        middle_name,
        last_name,
        contact_number,
        email,
        department,
        position,
        username
    FROM employees
    WHERE employee_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to load employee information.");
}

$stmt->bind_param("s", $_SESSION["employee_id"]);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$employee = $result->fetch_assoc();
$stmt->close();

/* =========================================================
   EMPLOYEE INFORMATION
========================================================= */

$employeeId = $employee["employee_id"];
$firstName = $employee["first_name"];
$middleName = $employee["middle_name"] ?? "";
$lastName = $employee["last_name"];

$fullName = trim(
    $firstName . " " .
    ($middleName !== "" ? $middleName . " " : "") .
    $lastName
);

$employeeContact = $employee["contact_number"];
$employeeEmail = $employee["email"];
$employeeDepartment = $employee["department"];
$employeePosition = $employee["position"];
$employeeUsername = $employee["username"];

$initials = strtoupper(
    substr($firstName, 0, 1) .
    substr($lastName, 0, 1)
);

/* =========================================================
   SAFE DISPLAY VALUES
========================================================= */

$displayName = htmlspecialchars($fullName, ENT_QUOTES, "UTF-8");
$displayEmail = htmlspecialchars($employeeEmail, ENT_QUOTES, "UTF-8");
$displayContact = htmlspecialchars($employeeContact, ENT_QUOTES, "UTF-8");
$displayDepartment = htmlspecialchars($employeeDepartment, ENT_QUOTES, "UTF-8");
$displayPosition = htmlspecialchars($employeePosition, ENT_QUOTES, "UTF-8");
$displayUsername = htmlspecialchars($employeeUsername, ENT_QUOTES, "UTF-8");
$displayEmployeeId = htmlspecialchars($employeeId, ENT_QUOTES, "UTF-8");
$displayInitials = htmlspecialchars($initials, ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="brand-text">OJT360</div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar" title="Close sidebar">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-link active" data-page="overview">
                <span class="nav-icon">▦</span>
                <span>Overview</span>
            </button>

            <button class="nav-link" data-page="interns">
                <span class="nav-icon">♙</span>
                <span>Interns</span>
            </button>

            <button class="nav-link" data-page="evaluations">
                <span class="nav-icon">▤</span>
                <span>Evaluations</span>
            </button>

            <button class="nav-link" data-page="messages">
                <span class="nav-icon">➤</span>
                <span>Messages</span>
            </button>

            <button class="nav-link" data-page="reports">
                <span class="nav-icon">▥</span>
                <span>Reports &amp; Analytics</span>
            </button>
        </nav>

        <div class="sidebar-bottom">
            <button class="nav-link help-link" data-page="support">
                <span class="nav-icon">?</span>
                <span>Help &amp; Support</span>
            </button>

            <button class="nav-link" id="signOutBtn">
                <span class="nav-icon">↪</span>
                <span>Sign out</span>
            </button>

            <div class="profile" id="sidebarProfile" role="button" tabindex="0" title="Open profile">
                <div class="avatar"><?= $displayInitials ?></div>
                <div>
                    <div class="profile-name"><?= $displayName ?></div>
                    <div class="profile-role"><?= $displayPosition ?></div>
                </div>
            </div>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- =========================================================
         MAIN
    ========================================================== -->
    <main class="main">

        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="false" title="Toggle sidebar">
                <span class="hamburger"><span></span><span></span><span></span></span>
            </button>

            <div class="topbar-brand" aria-label="OJT360">
                <div class="brand-mark">
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="brand-text">OJT360</div>
            </div>

            <div class="sync">
                <span class="sync-dot"></span>
                <span>All changes synced</span>
            </div>

            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Enable dark mode" title="Enable dark mode">
                <span id="themeToggleIcon" aria-hidden="true">☾</span>
            </button>

            <div class="notification-wrapper">
                <button class="icon-button" id="notificationButton" type="button" title="Notifications" aria-label="Notifications" aria-controls="notificationPanel" aria-expanded="false">
                </button>
                <section class="notification-panel" id="notificationPanel" aria-label="Notifications" hidden>
                    <div class="notification-panel-header">Notifications</div>
                    <p class="notification-empty">You're all caught up.</p>
                </section>
            </div>

            <button class="top-profile profile-trigger" id="topProfileBtn" title="Open profile">
                <div class="avatar"><?= $displayInitials ?></div>
                <div>
                    <div class="profile-name" style="color:#29465e"><?= $displayName ?></div>
                    <div class="profile-role"><?= $displayPosition ?></div>
                </div>
            </button>
        </header>

        <div class="content">

            <!-- =================================================
                 OVERVIEW
            ================================================== -->
            <section class="page active" id="page-overview">

                <div class="hero">
                    <div class="eyebrow">Employee Workspace</div>
                    <h1>Manage companies and student assignments.</h1>
                    <p>Manage partner companies, monitor company status, and assign students to their internship companies.</p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">A</div>
                        <div>
                            <div class="stat-label">Active Companies</div>
                            <div class="stat-value" id="activeCompanyCount">2</div>
                            <div class="stat-sub">Available for assignment</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">A</div>
                        <div>
                            <div class="stat-label">Archived Companies</div>
                            <div class="stat-value" id="archivedCompanyCount">1</div>
                            <div class="stat-sub">Historical partners</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Assigned Students</div>
                            <div class="stat-value" id="assignedCount">2</div>
                            <div class="stat-sub"><span id="overviewStudentTotal">4</span> students total</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">U</div>
                        <div>
                            <div class="stat-label">Unassigned Students</div>
                            <div class="stat-value" id="unassignedCount">2</div>
                            <div class="stat-sub">Awaiting company placement</div>
                        </div>
                    </div>
                </div>

                <!-- COMPANY MANAGEMENT IS ONLY HERE ON OVERVIEW -->
                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Company Management</div>
                            <h2 class="section-title">Companies</h2>
                            <p class="section-sub">Manage registered internship companies and their current status.</p>
                        </div>
                        <button class="btn btn-primary" id="addCompanyBtn">+ Add Company</button>
                    </div>

                    <div class="toolbar">
                        <input class="search" id="companySearch" placeholder="Search companies...">

                        <div class="filter-group">
                            <button class="filter active" data-company-filter="all">All</button>
                            <button class="filter" data-company-filter="active">Active</button>
                            <button class="filter" data-company-filter="archived">Archived</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="companyTable"></tbody>
                        </table>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Placement Management</div>
                            <h2 class="section-title">Student Assignments</h2>
                            <p class="section-sub">Assign students to their internship company.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Program</th>
                                    <th>Assigned Company</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="assignmentTable"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 INTERNS
            ================================================== -->
            <section class="page" id="page-interns">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Placement Management</div>
                        <h1>Intern Assignments</h1>
                        <p>View and manage student internship information, placement status, and completed hours.</p>
                    </div>

                    <button class="btn btn-primary" id="addInternBtn">+ Add Intern Student</button>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">T</div>
                        <div>
                            <div class="stat-label">Total Interns</div>
                            <div class="stat-value" id="internTotalCount">4</div>
                            <div class="stat-sub">Registered internship students</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Assigned Students</div>
                            <div class="stat-value" id="internAssignedCount">2</div>
                            <div class="stat-sub">Placed with a company</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">U</div>
                        <div>
                            <div class="stat-label">Unassigned Students</div>
                            <div class="stat-value" id="internUnassignedCount">2</div>
                            <div class="stat-sub">Awaiting company placement</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">A</div>
                        <div>
                            <div class="stat-label">Average Progress</div>
                            <div class="stat-value" id="internAverageProgress">50%</div>
                            <div class="stat-sub">Across all internship hours</div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Student Information</div>
                            <h2 class="section-title">Student Assignments</h2>
                            <p class="section-sub">Student details, internship company, status, and completed hours.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Program</th>
                                    <th>Assigned Company</th>
                                    <th>Status</th>
                                    <th>Working Hours</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="internTable"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 EVALUATIONS
            ================================================== -->
            <section class="page" id="page-evaluations">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Performance</div>
                        <h1>Evaluations</h1>
                        <p>Review intern progress and submit structured performance feedback.</p>
                    </div>

                    <button class="btn btn-primary" id="startEvaluationBtn">Start evaluation</button>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Intern Roster</div>
                            <h2 class="section-title">Attendance &amp; evaluation status</h2>
                            <p class="section-sub">Monitor internship hours and evaluation progress.</p>
                        </div>

                        <div class="action-row">
                            <input class="search" id="evalSearch" style="width:180px" placeholder="Search intern...">
                            <button class="small-btn">Filter</button>
                            <button class="small-btn">Export</button>
                        </div>
                    </div>

                    <div class="evaluation-roster" id="evaluationRoster"></div>

                    <div class="feedback">
                        <div>
                            <h3>Structured feedback that supports growth</h3>
                            <p>Rate technical proficiency, communication, reliability, and initiative. Drafts are saved automatically until submission.</p>
                        </div>
                        <button class="btn btn-primary" id="feedbackBtn">Start evaluation</button>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 MESSAGES
            ================================================== -->
            <section class="page" id="page-messages">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Communication</div>
                        <h1>Company coordination</h1>
                        <p>Documented conversations between company partners and OJT employees.</p>
                    </div>

                    <button class="btn btn-primary" id="newMessageBtn">+ New message</button>
                </div>

                <div class="section-card message-layout">
                    <div class="conversation-list">
                        <div class="conversation-search">
                            <input class="search" style="width:100%" placeholder="Search conversations...">
                        </div>

                        <div class="conversation active" data-chat="Northstar Digital Labs" data-receiver-type="company" data-receiver-id="Northstar Digital Labs">
                            <div class="conversation-top">
                                <div class="conversation-name">Northstar Digital Labs</div>
                                <div class="conversation-time">9:41 AM</div>
                            </div>
                            <div class="conversation-msg">Can we confirm the schedule?</div>
                        </div>

                        <div class="conversation" data-chat="Byteworks Solutions" data-receiver-type="company" data-receiver-id="Byteworks Solutions">
                            <div class="conversation-top">
                                <div class="conversation-name">Byteworks Solutions</div>
                                <div class="conversation-time">Yesterday</div>
                            </div>
                            <div class="conversation-msg">The evaluation form is ready.</div>
                        </div>

                        <div class="conversation" data-chat="Pacific Tech Systems" data-receiver-type="company" data-receiver-id="Pacific Tech Systems">
                            <div class="conversation-top">
                                <div class="conversation-name">Pacific Tech Systems</div>
                                <div class="conversation-time">Mon</div>
                            </div>
                            <div class="conversation-msg">Thanks for the update.</div>
                        </div>
                    </div>

                    <div class="chat">
                        <div class="chat-head">
                            <div>
                                <div class="chat-name" id="chatName">Northstar Digital Labs</div>
                                <div class="chat-status">● Active now • 1 assigned intern</div>
                            </div>
                            <button class="small-btn">⋮</button>
                        </div>

                        <div class="chat-body" id="chatBody">
                            <div class="bubble">Good morning! Can we confirm the adjusted schedule for this week?</div>
                            <div class="bubble me">Yes, the schedule was approved. I have updated the placement record as well.</div>
                            <div class="bubble">Perfect, thank you. We will make sure her attendance logs follow the new hours.</div>
                        </div>

                        <div class="chat-input">
                            <button class="small-btn">＋</button>
                            <input id="messageInput" placeholder="Write a message...">
                            <button class="btn btn-primary" id="sendMessageBtn">Send</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 REPORTS
            ================================================== -->
            <section class="page" id="page-reports">

                <div class="page-heading">
                    <div>
                        <div class="eyebrow" style="color:#1688ba">Analytics</div>
                        <h1>Reports &amp; insights</h1>
                        <p>Track internship outcomes and generate administration-ready reports.</p>
                    </div>

                    <button class="btn btn-primary" id="exportReportBtn">+ Export report</button>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">H</div>
                        <div>
                            <div class="stat-label">Hours Logged</div>
                            <div class="stat-value">8,214</div>
                            <div class="stat-sub">+12.5% this semester</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">J</div>
                        <div>
                            <div class="stat-label">Journal Submission</div>
                            <div class="stat-value">92%</div>
                            <div class="stat-sub">Above 85% target</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">E</div>
                        <div>
                            <div class="stat-label">Evaluation Avg.</div>
                            <div class="stat-value">4.6 / 5</div>
                            <div class="stat-sub">Across 24 interns</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">C</div>
                        <div>
                            <div class="stat-label">Completion Forecast</div>
                            <div class="stat-value">96%</div>
                            <div class="stat-sub">On-time projection</div>
                        </div>
                    </div>
                </div>

                <div class="report-grid">
                    <div class="chart-card">
                        <div class="chart-title">Internship progress</div>
                        <div class="chart-sub">Hours logged by month</div>

                        <div class="bars">
                            <div class="bar-col"><div class="bar" style="height:32%"></div><div class="bar-label">Oct</div></div>
                            <div class="bar-col"><div class="bar" style="height:45%"></div><div class="bar-label">Nov</div></div>
                            <div class="bar-col"><div class="bar" style="height:41%"></div><div class="bar-label">Dec</div></div>
                            <div class="bar-col"><div class="bar" style="height:62%"></div><div class="bar-label">Jan</div></div>
                            <div class="bar-col"><div class="bar" style="height:58%"></div><div class="bar-label">Feb</div></div>
                            <div class="bar-col"><div class="bar" style="height:78%"></div><div class="bar-label">Mar</div></div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-title">Submission health</div>
                        <div class="chart-sub">Current journal submissions</div>

                        <div class="donut-wrap">
                            <div class="donut">
                                <div class="donut-text">92%</div>
                            </div>
                        </div>

                        <div class="legend">
                            <span>Submitted 184</span>
                            <span>Late 12</span>
                            <span>Not submitted 4</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =================================================
                 SUPPORT
            ================================================== -->
            <section class="page" id="page-support">

                <div class="support-hero">
                    <div class="eyebrow">OJT360 Support</div>
                    <h1>How can we help?</h1>
                    <p>Find answers about attendance, journals, evaluations, and account access.</p>
                    <input class="support-search" placeholder="Search help articles...">
                </div>

                <div class="support-links">
                    <div class="support-link-card">
                        <strong>QR attendance</strong>
                        <span>Connecting scanning and internship attendance.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Journal guide</strong>
                        <span>Daily logs, journal entries, and submission.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Evaluations</strong>
                        <span>Performance reviews and feedback.</span>
                    </div>
                    <div class="support-link-card">
                        <strong>Account &amp; access</strong>
                        <span>Login, passwords, and profile settings.</span>
                    </div>
                </div>

                <div class="section-card" style="padding:16px">
                    <div class="support-form">
                        <div>
                            <div class="form-label">Still need help?</div>
                            <h3 style="margin:0 0 4px;font-size:13px">Send a request to the support team</h3>
                            <p style="font-size:9px;color:#8597a6;margin:0">Describe the issue and we will respond within one working day.</p>
                        </div>

                        <div>
                            <label class="form-label">Issue type</label>
                            <select class="field">
                                <option>Choose an issue type</option>
                                <option>Attendance</option>
                                <option>Student assignment</option>
                                <option>Evaluation</option>
                                <option>Account access</option>
                            </select>

                            <label class="form-label" style="margin-top:9px">What happened?</label>
                            <textarea class="field" placeholder="Tell us what happened..."></textarea>

                            <button class="btn btn-primary" style="width:100%;margin-top:8px" id="supportBtn">Send request</button>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- =============================================================
     USER PROFILE MODAL
============================================================== -->
<div class="modal-backdrop" id="profileModal">
    <div class="modal profile-modal">
        <div class="profile-cover">
            <div class="profile-avatar-large" id="profileAvatarLarge">DE</div>
        </div>

        <div class="profile-header-info">
            <h2 id="profileDisplayName">Dr. Elena Santos</h2>
            <p id="profileDisplayRole">Employee • OJT360</p>
        </div>

        <form id="profileForm">
            <div class="profile-section">
                <div class="profile-section-title">Personal information</div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full name</label>
                        <input class="field" id="profileName" name="profileName" value="<?= $displayName ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input class="field" id="profileRole" name="profileRole" value="Employee" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="field" id="profileEmail" name="profileEmail" type="email" value="<?= $displayEmail ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact number</label>
                        <input class="field" id="profileContact" name="profileContact" value="<?= $displayContact ?>">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Department / Office</label>
                        <input class="field" id="profileDepartment" name="profileDepartment" value="<?= $displayDepartment ?>">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="profileModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<!-- =============================================================
     OJT360 AI ASSISTANT
============================================================== -->
<button class="ai-launcher" id="aiLauncher" aria-label="Open OJT360 Assistant" title="OJT360 Assistant">
    <span class="ai-launcher-icon">✦</span>
</button>

<section class="ai-panel" id="aiPanel" aria-label="OJT360 Assistant">
    <div class="ai-head">
        <div class="ai-title-wrap">
            <div class="ai-avatar">✦</div>
            <div>
                <div class="ai-title">OJT360 Assistant</div>
                <div class="ai-online">
                    <span class="ai-online-dot"></span>
                    <span>Online</span>
                </div>
            </div>
        </div>

        <button class="ai-close" id="aiClose" aria-label="Close assistant">&times;</button>
    </div>

    <div class="ai-body" id="aiBody">
        <div class="ai-row">
            <div class="ai-mini-avatar">✦</div>
            <div class="ai-bubble">
                Hi! I can help with OJT guidelines, attendance, journals, evaluations, and student placement. What do you need?
            </div>
        </div>

        <div class="ai-suggestions">
            <button class="ai-suggestion" data-question="What are the final journal requirements?">Final journal requirements</button>
            <button class="ai-suggestion" data-question="How does QR attendance work?">QR scan help</button>
            <button class="ai-suggestion" data-question="How do I assign a student to a company?">Student assignment</button>
        </div>
    </div>

    <form class="ai-foot" id="aiForm">
        <input class="ai-input" id="aiInput" autocomplete="off" placeholder="Ask OJT360...">
        <button class="ai-send" type="submit" aria-label="Send message">↑</button>
    </form>
</section>

<div class="toast" id="toast"></div>

<!-- =============================================================
     ADD COMPANY MODAL
============================================================== -->
<div class="modal-backdrop" id="companyModal">
    <div class="modal">
        <div class="modal-head">
            <h2>Add Company</h2>
            <button class="close" data-close="companyModal">&times;</button>
        </div>

        <form id="companyForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Company name</label>
                        <input class="field" name="company" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact person</label>
                        <input class="field" name="contact" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact email</label>
                        <input class="field" type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="field" name="status">
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="companyModal">Cancel</button>
                <button class="btn btn-primary">Add Company</button>
            </div>
        </form>
    </div>
</div>

<!-- =============================================================
     COMPANY DETAILS MODAL
============================================================== -->
<div class="modal-backdrop" id="companyDetailsModal">
    <section class="modal company-details-modal" aria-labelledby="companyDetailsName">
        <header class="company-details-head">
            <div>
                <div class="assignment-kicker">Company Details</div>
                <h2 id="companyDetailsName"></h2>
            </div>
            <button class="close" type="button" data-close="companyDetailsModal" aria-label="Close">&times;</button>
        </header>

        <div class="company-details-body">
            <div class="company-details-summary">
                <div class="company-detail-item">
                    <label>Contact Person</label>
                    <strong id="companyDetailsContact"></strong>
                </div>
                <div class="company-detail-item">
                    <label>Status</label>
                    <span id="companyDetailsStatus"></span>
                </div>
                <div class="company-detail-item">
                    <label>Contact Information</label>
                    <strong id="companyDetailsEmail"></strong>
                    <div class="company-detail-secondary" id="companyDetailsPhone"></div>
                </div>
                <div class="company-detail-item">
                    <label>Assigned Students</label>
                    <strong id="companyDetailsCount"></strong>
                </div>
                <div class="company-detail-item">
                    <label>Address</label>
                    <strong id="companyDetailsLocation"></strong>
                </div>
            </div>

            <h3 class="company-assigned-title">Assigned Students</h3>
            <div class="company-student-list" id="companyDetailsStudents"></div>
        </div>
    </section>
</div>

<!-- =============================================================
     ADD INTERN MODAL
============================================================== -->
<div class="modal-backdrop" id="internModal">
    <div class="modal">
        <div class="modal-head">
            <h2>Add Intern Student</h2>
            <button class="close" data-close="internModal">&times;</button>
        </div>

        <form id="internForm">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Student name</label>
                        <input class="field" name="name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <input class="field" name="studentId" maxlength="20" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Program</label>
                        <input class="field" name="program" placeholder="BSIT - Web Development" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assigned company</label>
                        <select class="field" name="company">
                            <option value="">Not Assigned</option>
                            <option>ABC Technology Solutions</option>
                            <option>XYZ Corporation</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Internship start date</label>
                        <input class="field" type="date" name="startDate">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Required hours</label>
                        <input class="field" type="number" name="requiredHours" value="360" min="1" required>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Completed hours</label>
                        <input class="field" type="number" name="completedHours" value="0" min="0">
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="internModal">Cancel</button>
                <button class="btn btn-primary">Add Intern Student</button>
            </div>
        </form>
    </div>
</div>

<!-- =============================================================
     STUDENT ASSIGNMENT MODAL
============================================================== -->
<div class="modal-backdrop" id="assignmentModal">
    <div class="modal assignment-modal">
        <div class="assignment-head">
            <div>
                <div class="assignment-kicker">Student Placement</div>
                <h2>Assign Student to Company</h2>
            </div>
            <button class="close" type="button" data-close="assignmentModal" aria-label="Close">&times;</button>
        </div>

        <form id="assignmentForm">
            <div class="assignment-body">
                <div class="assignment-student">
                    <div class="assignment-student-avatar" id="assignmentStudentAvatar"></div>
                    <div>
                        <div class="assignment-student-name" id="assignmentStudentName"></div>
                        <div class="assignment-student-detail" id="assignmentStudentId"></div>
                        <div class="assignment-student-detail" id="assignmentStudentProgram"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="assignmentCompany">Company</label>
                    <select class="field" id="assignmentCompany" name="company" required></select>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="assignmentModal">Cancel</button>
                <button class="btn btn-primary" id="saveAssignmentBtn" type="submit">Assign Student</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
/* ================================================================
   SUPABASE MESSAGING CONFIGURATION
   Keep using MySQL for employee login/profile.
   Supabase is used only for company messaging.

   IMPORTANT:
   - Use your Supabase PROJECT URL here.
   - Use your Supabase PUBLISHABLE key here.
   - NEVER put your sb_secret_... key in this file.
================================================================ */
const SUPABASE_URL = "https://wezbyqghndbrbcvzbxvx.supabase.co";
const SUPABASE_PUBLISHABLE_KEY = "sb_publishable_FVmCuHfywH-ygqSTiFNseA_4NWVVIux";

let supabaseClient = null;
let supabaseReady = false;

if (
    SUPABASE_URL &&
    SUPABASE_URL !== "YOUR_SUPABASE_PROJECT_URL" &&
    SUPABASE_PUBLISHABLE_KEY &&
    SUPABASE_PUBLISHABLE_KEY !== "YOUR_SUPABASE_PUBLISHABLE_KEY" &&
    window.supabase
) {
    supabaseClient = window.supabase.createClient(
        SUPABASE_URL,
        SUPABASE_PUBLISHABLE_KEY
    );
    supabaseReady = true;
} else {
    console.warn("Supabase messaging is not configured yet. Add your project URL and publishable key.");
}

const CURRENT_EMPLOYEE_ID = <?= json_encode($employeeId) ?>;
const CURRENT_EMPLOYEE_TYPE = "employee";

let currentConversation = null;
let currentRealtimeChannel = null;
const locallyInsertedMessageIds = new Set();

/* ================================================================
   SAMPLE DATA
   Replace this with PHP/MySQL data later.
================================================================ */

let companies = [
    {
        id:1,
        name:"ABC Technology Solutions",
        location:"Tacloban City, Leyte",
        contact:"Maria Santos",
        email:"maria@abctechcompany.com",
        students:1,
        status:"active"
    },
    {
        id:2,
        name:"XYZ Corporation",
        location:"Palo, Leyte",
        contact:"John Reyes",
        email:"john@xyzcorp.com",
        students:1,
        status:"active"
    },
    {
        id:3,
        name:"ABC Trading",
        location:"Ormoc City, Leyte",
        contact:"Michael Cruz",
        email:"michael@abctrading.com",
        students:0,
        status:"archived"
    }
];

let students = [
    {
        id:1,
        name:"Angela Mercado",
        studentId:"2026-001",
        program:"BSIT - Web Development",
        company:"ABC Technology Solutions",
        status:"Assigned",
        completed:48,
        required:360,
        week:"Week 12",
        evaluation:"On track"
    },
    {
        id:2,
        name:"Joshua Reyes",
        studentId:"2026-002",
        program:"BSIT - Networking",
        company:"Not Assigned",
        status:"Unassigned",
        completed:16,
        required:360,
        week:"Week 11",
        evaluation:"Needs placement"
    },
    {
        id:3,
        name:"Camille Dizon",
        studentId:"2026-003",
        program:"BSIT - Web Development",
        company:"XYZ Corporation",
        status:"Assigned",
        completed:201,
        required:360,
        week:"Week 14",
        evaluation:"On track"
    },
    {
        id:4,
        name:"Nathan Flores",
        studentId:"2026-004",
        program:"BSIT - Systems Development",
        company:"Not Assigned",
        status:"Unassigned",
        completed:65,
        required:360,
        week:"Week 10",
        evaluation:"Needs placement"
    }
];

/* ================================================================
   HELPERS
================================================================ */

function initials(name){
    return name
        .split(" ")
        .map(part => part[0])
        .join("")
        .slice(0,2)
        .toUpperCase();
}

function escapeHtml(value){
    return String(value)
        .replaceAll("&","&amp;")
        .replaceAll("<","&lt;")
        .replaceAll(">","&gt;")
        .replaceAll('"',"&quot;")
        .replaceAll("'","&#039;");
}

function progressPercent(completed, required){
    if(!required || required <= 0) return 0;
    return Math.min(100, Math.round((completed / required) * 100));
}

/* ================================================================
   SIDEBAR / PAGE NAVIGATION
================================================================ */

const navLinks = document.querySelectorAll(".nav-link[data-page]");
const pages = document.querySelectorAll(".page");
const app = document.querySelector(".app");
const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuToggle = document.getElementById("menuToggle");
const themeToggle = document.getElementById("themeToggle");
const themeToggleIcon = document.getElementById("themeToggleIcon");

function setDashboardTheme(theme){
    const isDark = theme === "dark";
    document.body.dataset.theme = isDark ? "dark" : "light";
    themeToggleIcon.textContent = isDark ? "☀" : "☾";
    themeToggle.setAttribute("aria-label", isDark ? "Enable light mode" : "Enable dark mode");
    themeToggle.title = isDark ? "Enable light mode" : "Enable dark mode";
}

let savedTheme = "light";
try{
    savedTheme = localStorage.getItem("ojt360-employee-theme") === "dark" ? "dark" : "light";
}catch(error){
    console.error("Unable to load the saved dashboard theme.", error);
}
setDashboardTheme(savedTheme);

themeToggle.addEventListener("click", () => {
    const nextTheme = document.body.dataset.theme === "dark" ? "light" : "dark";
    setDashboardTheme(nextTheme);
    try{
        localStorage.setItem("ojt360-employee-theme", nextTheme);
    }catch(error){
        console.error("Unable to save the dashboard theme preference.", error);
    }
});

function closeSidebar(){
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("show");
    menuToggle.setAttribute("aria-expanded", "false");
}

function openSidebar(){
    sidebar.classList.add("open");
    sidebarOverlay.classList.add("show");
    menuToggle.setAttribute("aria-expanded", "true");
}

function showPage(pageName){
    pages.forEach(page => {
        page.classList.toggle("active", page.id === "page-" + pageName);
    });

    navLinks.forEach(link => {
        link.classList.toggle("active", link.dataset.page === pageName);
    });

    closeSidebar();
    window.scrollTo({top:0,behavior:"smooth"});
}

navLinks.forEach(link => {
    link.addEventListener("click", () => showPage(link.dataset.page));
});

menuToggle.addEventListener("click", () => {
    if(sidebar.classList.contains("open")){
        closeSidebar();
    }else{
        openSidebar();
    }
});

document.getElementById("sidebarClose").addEventListener("click", closeSidebar);
sidebarOverlay.addEventListener("click", closeSidebar);


/* ================================================================
   OVERVIEW - COMPANY TABLE
================================================================ */

let companyFilter = "all";

function renderCompanies(){
    const search = document.getElementById("companySearch").value.trim().toLowerCase();
    const tbody = document.getElementById("companyTable");

    let filtered = companies.filter(company => {
        const matchesSearch =
            company.name.toLowerCase().includes(search) ||
            company.contact.toLowerCase().includes(search) ||
            company.email.toLowerCase().includes(search);

        const matchesFilter =
            companyFilter === "all" ||
            company.status === companyFilter;

        return matchesSearch && matchesFilter;
    });

    if(!filtered.length){
        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="empty">No companies found.</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(company => `
        <tr>
            <td>
                <div class="person">
                    <div class="mini-avatar">${initials(company.name)}</div>
                    <div>
                        <div class="person-name">${escapeHtml(company.name)}</div>
                        <div class="person-sub">${escapeHtml(company.location)}</div>
                    </div>
                </div>
            </td>

            <td>${escapeHtml(company.contact)}</td>
            <td>${escapeHtml(company.email)}</td>
            <td>${company.students} ${company.students === 1 ? "Student" : "Students"}</td>

            <td>
                <span class="badge ${company.status === "active" ? "green" : "yellow"}">
                    ${company.status === "active" ? "Active" : "Archived"}
                </span>
            </td>

            <td>
                <div class="action-row">
                    <button class="small-btn" onclick="viewCompany(${company.id})">View</button>

                    ${
                        company.status === "active"
                        ? `<button class="small-btn" onclick="archiveCompany(${company.id})">Archive</button>`
                        : `<button class="small-btn" onclick="activateCompany(${company.id})">Activate</button>`
                    }
                </div>
            </td>
        </tr>
    `).join("");
}

document.getElementById("companySearch").addEventListener("input", renderCompanies);

document.querySelectorAll("[data-company-filter]").forEach(button => {
    button.addEventListener("click", () => {
        document.querySelectorAll("[data-company-filter]").forEach(b => b.classList.remove("active"));
        button.classList.add("active");
        companyFilter = button.dataset.companyFilter;
        renderCompanies();
    });
});

function viewCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    const assignedStudents = students.filter(student =>
        student.company === company.name && student.status === "Assigned"
    );

    document.getElementById("companyDetailsName").textContent = company.name;
    document.getElementById("companyDetailsContact").textContent = company.contact || "Not provided";
    document.getElementById("companyDetailsEmail").textContent = company.email || "Not provided";
    document.getElementById("companyDetailsPhone").textContent = company.phone || company.contactNumber || "";
    document.getElementById("companyDetailsCount").textContent = assignedStudents.length;
    document.getElementById("companyDetailsLocation").textContent = company.location || "Not provided";
    document.getElementById("companyDetailsStatus").innerHTML =
        `<span class="badge ${company.status === "active" ? "green" : "yellow"}">` +
        `${company.status === "active" ? "Active" : "Archived"}</span>`;

    document.getElementById("companyDetailsStudents").innerHTML = assignedStudents.length
        ? assignedStudents.map(student => `
            <div class="company-student-row">
                <div class="company-student-field">
                    <strong class="company-student-name">${escapeHtml(student.name)}</strong>
                    <div class="company-student-sub">${escapeHtml(student.studentId)} · ${escapeHtml(student.program)}</div>
                </div>
                <div class="company-student-field">
                    <label>Department</label>
                    <span>${escapeHtml(student.department || student.program || "Not provided")}</span>
                </div>
                <div class="company-student-field">
                    <label>Building</label>
                    <span>${escapeHtml(student.building || "Not provided")}</span>
                </div>
                <span class="badge green">Assigned</span>
            </div>
        `).join("")
        : `<div class="company-details-empty">No students are assigned to this company.</div>`;

    document.getElementById("companyDetailsModal").classList.add("show");
}

function archiveCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    if(!confirm("Archive " + company.name + "?")) return;

    company.status = "archived";
    renderCompanies();
    updateOverviewStats();
}

function activateCompany(id){
    const company = companies.find(c => c.id === id);
    if(!company) return;

    company.status = "active";
    renderCompanies();
    updateOverviewStats();
}

function updateOverviewStats(){
    const active = companies.filter(c => c.status === "active").length;
    const archived = companies.filter(c => c.status === "archived").length;
    const assigned = students.filter(s => s.status === "Assigned").length;
    const unassigned = students.filter(s => s.status !== "Assigned").length;

    document.getElementById("activeCompanyCount").textContent = active;
    document.getElementById("archivedCompanyCount").textContent = archived;
    document.getElementById("assignedCount").textContent = assigned;
    document.getElementById("unassignedCount").textContent = unassigned;
}

/* ================================================================
   OVERVIEW - ASSIGNMENTS
================================================================ */

function renderAssignments(){
    const tbody = document.getElementById("assignmentTable");

    if(!students.length){
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty">No students found.</div></td></tr>`;
        return;
    }

    tbody.innerHTML = students.map(student => `
        <tr>
            <td>
                <div class="person">
                    <div class="mini-avatar">${initials(student.name)}</div>
                    <div>
                        <div class="person-name">${escapeHtml(student.name)}</div>
                    </div>
                </div>
            </td>

            <td>${escapeHtml(student.studentId)}</td>
            <td>${escapeHtml(student.program)}</td>
            <td>${escapeHtml(student.company)}</td>

            <td>
                <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                    ${student.status}
                </span>
            </td>

            <td>
                ${
                    student.status === "Assigned"
                    ? `<button class="small-btn" onclick="assignCompany(${student.id})">Manage Assignment</button>`
                    : `<button class="small-btn primary" onclick="assignCompany(${student.id})">Assign Company</button>`
                }
            </td>
        </tr>
    `).join("");
}

function assignCompany(id){
    const student = students.find(s => s.id === id);
    if(!student) return;

    const activeCompanies = companies.filter(c => c.status === "active");
    const companySelect = document.getElementById("assignmentCompany");
    const currentCompanyIsActive = activeCompanies.some(company => company.name === student.company);

    document.getElementById("assignmentStudentAvatar").textContent = initials(student.name);
    document.getElementById("assignmentStudentName").textContent = student.name;
    document.getElementById("assignmentStudentId").textContent = "Student ID: " + student.studentId;
    document.getElementById("assignmentStudentProgram").textContent = student.program;
    companySelect.innerHTML = `<option value="">Select Active Company</option>` +
        activeCompanies.map(company =>
            `<option value="${escapeHtml(company.name)}">${escapeHtml(company.name)}</option>`
        ).join("");
    companySelect.value = currentCompanyIsActive ? student.company : "";
    document.getElementById("saveAssignmentBtn").disabled = activeCompanies.length === 0;
    document.getElementById("assignmentModal").dataset.studentId = student.id;
    document.getElementById("assignmentModal").classList.add("show");
}

document.getElementById("assignmentForm").addEventListener("submit", event => {
    event.preventDefault();
    const modal = document.getElementById("assignmentModal");
    const student = students.find(s => s.id === Number(modal.dataset.studentId));
    const selectedCompany = companies.find(c =>
        c.name === document.getElementById("assignmentCompany").value &&
        c.status === "active"
    );
    if(!student || !selectedCompany) return;

    if(student.company !== selectedCompany.name){
        const previousCompany = companies.find(c => c.name === student.company);
        if(previousCompany && previousCompany.students > 0) previousCompany.students--;
        selectedCompany.students++;
    }

    student.company = selectedCompany.name;
    student.status = "Assigned";
    closeModal("assignmentModal");
    renderAssignments();
    renderInterns();
    renderCompanies();
    renderEvaluations();
    updateOverviewStats();
});

/* ================================================================
   INTERN PAGE
================================================================ */

function renderInterns(){
    const tbody = document.getElementById("internTable");

    updateInternStats();

    if(!students.length){
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty">No students found.</div></td></tr>`;
        return;
    }

    tbody.innerHTML = students.map(student => {
        const pct = progressPercent(student.completed, student.required);

        return `
            <tr>
                <td>
                    <div class="person">
                        <div class="mini-avatar">${initials(student.name)}</div>
                        <div>
                            <div class="person-name">${escapeHtml(student.name)}</div>
                            <div class="person-sub">${escapeHtml(student.studentId)}</div>
                        </div>
                    </div>
                </td>

                <td>${escapeHtml(student.studentId)}</td>
                <td>${escapeHtml(student.program)}</td>
                <td>${escapeHtml(student.company)}</td>

                <td>
                    <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                        ${student.status}
                    </span>
                </td>

                <td>
                    <div class="hours-box">
                        <div class="hours-line">
                            <strong>${student.completed} hrs / ${student.required} hrs</strong>
                            <span>${pct}%</span>
                        </div>
                        <div class="progress">
                            <span style="width:${pct}%"></span>
                        </div>
                        <div class="progress-meta">${student.required - student.completed > 0 ? (student.required - student.completed) + " hrs remaining" : "Completed"}</div>
                    </div>
                </td>

                <td>
                    <div class="intern-action-row">
                        ${
                            student.status === "Assigned"
                            ? `<button class="small-btn intern-assignment-action" onclick="assignCompany(${student.id})">Manage Assignment</button>`
                            : `<button class="small-btn primary intern-assignment-action" onclick="assignCompany(${student.id})">Assign Company</button>`
                        }
                        <button class="small-btn danger intern-remove-action" onclick="removeStudent(${student.id})">Remove</button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

function updateInternStats(){
    const assigned = students.filter(student => student.status === "Assigned").length;
    const totalProgress = students.reduce((sum, student) =>
        sum + progressPercent(student.completed, student.required), 0
    );
    const averageProgress = students.length ? Math.round(totalProgress / students.length) : 0;

    document.getElementById("internTotalCount").textContent = students.length;
    document.getElementById("internAssignedCount").textContent = assigned;
    document.getElementById("internUnassignedCount").textContent = students.length - assigned;
    document.getElementById("internAverageProgress").textContent = averageProgress + "%";
    document.getElementById("overviewStudentTotal").textContent = students.length;
}

function removeStudent(id){
    const student = students.find(s => s.id === id);
    if(!student) return;

    if(!confirm("Remove " + student.name + " from the student list? This cannot be undone.")) return;

    const company = companies.find(c => c.name === student.company);
    if(company && company.students > 0) company.students--;
    students = students.filter(s => s.id !== id);

    renderInterns();
    renderAssignments();
    renderCompanies();
    renderEvaluations();
    updateOverviewStats();
}

/* ================================================================
   ADD COMPANY
================================================================ */

document.getElementById("addCompanyBtn").addEventListener("click", () => {
    document.getElementById("companyModal").classList.add("show");
});

document.getElementById("companyForm").addEventListener("submit", e => {
    e.preventDefault();

    const form = new FormData(e.target);

    const company = {
        id:Date.now(),
        name:form.get("company"),
        location:"Location not set",
        contact:form.get("contact"),
        email:form.get("email"),
        students:0,
        status:form.get("status")
    };

    companies.push(company);

    e.target.reset();
    closeModal("companyModal");

    renderCompanies();
    updateOverviewStats();
});

/* ================================================================
   ADD INTERN
================================================================ */

document.getElementById("addInternBtn").addEventListener("click", () => {
    document.getElementById("internModal").classList.add("show");
});

document.getElementById("internForm").addEventListener("submit", e => {
    e.preventDefault();

    const form = new FormData(e.target);

    const required = Number(form.get("requiredHours")) || 360;
    const completed = Math.max(0, Number(form.get("completedHours")) || 0);
    const company = form.get("company") || "Not Assigned";

    const student = {
        id:Date.now(),
        name:form.get("name"),
        studentId:form.get("studentId"),
        program:form.get("program"),
        company:company,
        status:company === "Not Assigned" ? "Unassigned" : "Assigned",
        completed:Math.min(completed,required),
        required:required,
        week:"Week 1",
        evaluation:"Not evaluated"
    };

    students.push(student);

    if(company !== "Not Assigned"){
        const existingCompany = companies.find(c => c.name === company);
        if(existingCompany) existingCompany.students++;
    }

    e.target.reset();

    document.querySelector('#internForm [name="requiredHours"]').value = 360;
    document.querySelector('#internForm [name="completedHours"]').value = 0;

    closeModal("internModal");

    renderInterns();
    renderAssignments();
    renderEvaluations();
    renderCompanies();
    updateOverviewStats();
});

/* ================================================================
   EVALUATIONS
================================================================ */

function renderEvaluations(){
    const roster = document.getElementById("evaluationRoster");

    roster.innerHTML = students.map(student => `
        <div class="eval-row">
            <div class="person">
                <div class="mini-avatar">${initials(student.name)}</div>
                <div>
                    <div class="person-name">${escapeHtml(student.name)}</div>
                    <div class="person-sub">${escapeHtml(student.program)}</div>
                </div>
            </div>

            <div class="eval-hours">${student.completed} / ${student.required} hrs</div>

            <div class="week">${student.week}</div>

            <div>
                <span class="badge ${student.status === "Assigned" ? "green" : "yellow"}">
                    ${student.status === "Assigned" ? "On track" : "Awaiting placement"}
                </span>
            </div>

            <button class="small-btn">Review</button>
        </div>
    `).join("");
}

document.getElementById("evalSearch").addEventListener("input", function(){
    const query = this.value.toLowerCase();

    document.querySelectorAll("#evaluationRoster .eval-row").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(query) ? "grid" : "none";
    });
});

document.getElementById("startEvaluationBtn").addEventListener("click", () => {
    alert("Evaluation workflow opened.");
});

document.getElementById("feedbackBtn").addEventListener("click", () => {
    alert("Evaluation form opened.");
});

/* ================================================================
   MESSAGES - SUPABASE
================================================================ */

const conversationElements = document.querySelectorAll(".conversation");

function getActiveConversation(){
    return document.querySelector(".conversation.active");
}

function getConversationInfo(conversation){
    if(!conversation) return null;

    return {
        name: conversation.dataset.chat || "Company",
        receiverType: conversation.dataset.receiverType || "company",
        receiverId: conversation.dataset.receiverId || conversation.dataset.chat || ""
    };
}

function setChatLoading(){
    document.getElementById("chatBody").innerHTML = `
        <div style="padding:24px;text-align:center;color:#8597a6;font-size:12px;">
            Loading messages...
        </div>
    `;
}

function setChatEmpty(){
    document.getElementById("chatBody").innerHTML = `
        <div style="padding:24px;text-align:center;color:#8597a6;font-size:12px;">
            No messages yet. Start the conversation.
        </div>
    `;
}

function appendMessageBubble(message, isMine, messageId = null){
    const body = document.getElementById("chatBody");

    if(messageId !== null && messageId !== undefined){
        const existing = body.querySelector(`[data-message-id="${CSS.escape(String(messageId))}"]`);
        if(existing) return;
    }

    const emptyMessage = body.querySelector('[data-empty-chat="true"]');
    if(emptyMessage) emptyMessage.remove();

    const bubble = document.createElement("div");
    bubble.className = "bubble" + (isMine ? " me" : "");
    bubble.textContent = message;

    if(messageId !== null && messageId !== undefined){
        bubble.dataset.messageId = String(messageId);
    }

    body.appendChild(bubble);
    body.scrollTop = body.scrollHeight;
}

function renderMessages(messages){
    const body = document.getElementById("chatBody");
    body.innerHTML = "";

    if(!messages.length){
        const empty = document.createElement("div");
        empty.dataset.emptyChat = "true";
        empty.style.cssText = "padding:24px;text-align:center;color:#8597a6;font-size:12px;";
        empty.textContent = "No messages yet. Start the conversation.";
        body.appendChild(empty);
        return;
    }

    messages.forEach(row => {
        const isMine =
            row.sender_type === CURRENT_EMPLOYEE_TYPE &&
            String(row.sender_id) === String(CURRENT_EMPLOYEE_ID);

        // Updated row.messages to match the Supabase table column
        appendMessageBubble(row.messages || "", isMine, row.id);
    });
}

async function loadMessages(conversationInfo){
    if(!supabaseReady || !supabaseClient){
        setChatEmpty();
        showToast("Supabase messaging is not configured yet.");
        return;
    }

    currentConversation = conversationInfo;
    setChatLoading();

    // Updated select query to fetch 'messages' column instead of 'message'
    const { data, error } = await supabaseClient
        .from("message")
        .select("id,sender_type,sender_id,receiver_type,receiver_id,messages,is_read,created_at")
        .or(
            `and(sender_type.eq.${CURRENT_EMPLOYEE_TYPE},sender_id.eq.${String(CURRENT_EMPLOYEE_ID)},receiver_type.eq.${conversationInfo.receiverType},receiver_id.eq.${conversationInfo.receiverId}),` +
            `and(sender_type.eq.${conversationInfo.receiverType},sender_id.eq.${conversationInfo.receiverId},receiver_type.eq.${CURRENT_EMPLOYEE_TYPE},receiver_id.eq.${String(CURRENT_EMPLOYEE_ID)})`
        )
        .order("created_at", { ascending: true });

    if(error){
        console.error("Supabase load messages error:", error);
        setChatEmpty();
        showToast("Unable to load messages.");
        return;
    }

    renderMessages(data || []);
}

function subscribeToMessages(){
    if(!supabaseReady || !supabaseClient) return;

    if(currentRealtimeChannel){
        supabaseClient.removeChannel(currentRealtimeChannel);
        currentRealtimeChannel = null;
    }

    currentRealtimeChannel = supabaseClient
        .channel("employee-messages-" + String(CURRENT_EMPLOYEE_ID))
        .on(
            "postgres_changes",
            {
                event: "INSERT",
                schema: "public",
                table: "message"
            },
            payload => {
                const row = payload.new;
                if(!currentConversation || !row) return;

                const isCurrentConversation =
                    (
                        String(row.sender_type) === CURRENT_EMPLOYEE_TYPE &&
                        String(row.sender_id) === String(CURRENT_EMPLOYEE_ID) &&
                        String(row.receiver_type) === String(currentConversation.receiverType) &&
                        String(row.receiver_id) === String(currentConversation.receiverId)
                    ) ||
                    (
                        String(row.sender_type) === String(currentConversation.receiverType) &&
                        String(row.sender_id) === String(currentConversation.receiverId) &&
                        String(row.receiver_type) === CURRENT_EMPLOYEE_TYPE &&
                        String(row.receiver_id) === String(CURRENT_EMPLOYEE_ID)
                    );

                if(!isCurrentConversation) return;

                // Updated row.messages to match the Supabase table column
                appendMessageBubble(
                    row.messages || "",
                    String(row.sender_type) === CURRENT_EMPLOYEE_TYPE &&
                    String(row.sender_id) === String(CURRENT_EMPLOYEE_ID),
                    row.id
                );
            }
        )
        .subscribe(status => {
            console.log("Supabase realtime status:", status);
        });
}

conversationElements.forEach(conversation => {
    conversation.addEventListener("click", async () => {
        conversationElements.forEach(c => c.classList.remove("active"));
        conversation.classList.add("active");

        const info = getConversationInfo(conversation);
        document.getElementById("chatName").textContent = info.name;

        await loadMessages(info);
    });
});

async function sendMessage(){
    const input = document.getElementById("messageInput");
    const value = input.value.trim();
    const activeConversation = getActiveConversation();
    const conversationInfo = getConversationInfo(activeConversation);

    if(!value) return;

    if(!supabaseReady || !supabaseClient){
        showToast("Configure Supabase first.");
        return;
    }

    if(!conversationInfo || !conversationInfo.receiverId){
        showToast("Please select a company conversation first.");
        return;
    }

    const sendButton = document.getElementById("sendMessageBtn");
    sendButton.disabled = true;

    // Updated insert payload and select field to 'messages'
    const { data, error } = await supabaseClient
        .from("message")
        .insert({
            sender_type: CURRENT_EMPLOYEE_TYPE,
            sender_id: String(CURRENT_EMPLOYEE_ID),
            receiver_type: conversationInfo.receiverType,
            receiver_id: String(conversationInfo.receiverId),
            messages: value,
            is_read: false
        })
        .select("id,sender_type,sender_id,receiver_type,receiver_id,messages,is_read,created_at")
        .single();

    sendButton.disabled = false;

    if(error){
        console.error("Supabase send message error:", error);
        showToast("Message was not sent.");
        return;
    }

    input.value = "";

    // If realtime is delayed, show the inserted row immediately using 'messages'
    if(data){
        appendMessageBubble(data.messages || "", true, data.id);
    }
}

document.getElementById("sendMessageBtn").addEventListener("click", sendMessage);

document.getElementById("messageInput").addEventListener("keydown", e => {
    if(e.key === "Enter"){
        e.preventDefault();
        sendMessage();
    }
});

/* Start realtime and load the first conversation. */
if(supabaseReady){
    subscribeToMessages();

    const firstConversation = getActiveConversation();
    if(firstConversation){
        const firstInfo = getConversationInfo(firstConversation);
        document.getElementById("chatName").textContent = firstInfo.name;
        loadMessages(firstInfo);
    }
}

document.getElementById("newMessageBtn").addEventListener("click", () => {
    const firstConversation = conversationElements[0];

    if(firstConversation){
        conversationElements.forEach(c => c.classList.remove("active"));
        firstConversation.classList.add("active");

        const info = getConversationInfo(firstConversation);
        document.getElementById("chatName").textContent = info.name;
        loadMessages(info);
    }

    document.getElementById("messageInput").focus();
});

/* ================================================================
   REPORTS
================================================================ */

document.getElementById("exportReportBtn").addEventListener("click", () => {
    alert("Report export started. Connect this button to your PHP export endpoint.");
});

/* ================================================================
   SUPPORT
================================================================ */

document.getElementById("supportBtn").addEventListener("click", () => {
    alert("Your support request has been submitted.");
});

/* ================================================================
   SIGN OUT
================================================================ */

document.getElementById("signOutBtn").addEventListener("click", () => {
    if(confirm("Are you sure you want to sign out?")){
        // Replace with your real logout URL.
        window.location.href = "logout.php";
    }
});

/* ================================================================
   USER PROFILE
================================================================ */

const profileModal = document.getElementById("profileModal");
const profileForm = document.getElementById("profileForm");
const profileName = document.getElementById("profileName");
const profileRole = document.getElementById("profileRole");
const profileEmail = document.getElementById("profileEmail");
const profileContact = document.getElementById("profileContact");
const profileDepartment = document.getElementById("profileDepartment");

function openProfile(){
    profileModal.classList.add("show");
}

document.getElementById("topProfileBtn").addEventListener("click", openProfile);
document.getElementById("sidebarProfile").addEventListener("click", openProfile);
document.getElementById("sidebarProfile").addEventListener("keydown", e => {
    if(e.key === "Enter" || e.key === " "){
        e.preventDefault();
        openProfile();
    }
});

profileForm.addEventListener("submit", e => {
    e.preventDefault();

    const name = profileName.value.trim();
    const role = profileRole.value.trim();
    const email = profileEmail.value.trim();
    const contact = profileContact.value.trim();
    const department = profileDepartment.value.trim();

    if(!name || !role || !email){
        showToast("Please complete the required profile fields.");
        return;
    }

    const initialsText = initials(name);

    document.querySelectorAll(".profile-name").forEach(el => {
        if(el.closest(".top-profile") || el.closest(".profile")){
            el.textContent = name;
        }
    });

    document.querySelectorAll(".profile-role").forEach(el => {
        el.textContent = role;
    });

    document.querySelectorAll(".avatar").forEach(el => {
        el.textContent = initialsText;
    });

    document.getElementById("profileAvatarLarge").textContent = initialsText;
    document.getElementById("profileDisplayName").textContent = name;
    document.getElementById("profileDisplayRole").textContent = role + " • OJT360";

    profileModal.classList.remove("show");
    showToast("Profile updated successfully.");

    console.log({
        name,
        role,
        email,
        contact,
        department
    });
});

function showToast(message){
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.classList.add("show");

    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(() => {
        toast.classList.remove("show");
    }, 2500);
}


/* ================================================================
   OJT360 AI ASSISTANT
================================================================ */

const aiLauncher = document.getElementById("aiLauncher");
const aiPanel = document.getElementById("aiPanel");
const aiClose = document.getElementById("aiClose");
const aiBody = document.getElementById("aiBody");
const aiForm = document.getElementById("aiForm");
const aiInput = document.getElementById("aiInput");

function openAssistant(){
    aiPanel.classList.add("open");
    setTimeout(() => aiInput.focus(), 180);
}

function closeAssistant(){
    aiPanel.classList.remove("open");
}

function addAssistantMessage(message, isUser = false){
    const row = document.createElement("div");
    row.className = "ai-row" + (isUser ? " user" : "");

    if(!isUser){
        const avatar = document.createElement("div");
        avatar.className = "ai-mini-avatar";
        avatar.textContent = "✦";
        row.appendChild(avatar);
    }

    const bubble = document.createElement("div");
    bubble.className = "ai-bubble";
    bubble.textContent = message;

    const time = document.createElement("span");
    time.className = "ai-time";
    time.textContent = new Date().toLocaleTimeString([], {
        hour:"numeric",
        minute:"2-digit"
    });

    bubble.appendChild(time);
    row.appendChild(bubble);
    aiBody.appendChild(row);
    aiBody.scrollTop = aiBody.scrollHeight;
}

function getAssistantReply(question){
    const q = question.toLowerCase();

    if(q.includes("journal")){
        return "For the OJT journal, keep your daily activities, dates, hours completed, learning reflections, and required signatures or approvals. Follow the current OJT manual used by your program.";
    }

    if(q.includes("qr") || q.includes("attendance")){
        return "For QR attendance, the intern should scan the assigned attendance QR using a phone while connected to the required network or service. The system should record the time and associate it with the correct OJT placement.";
    }

    if(q.includes("assign") || q.includes("company") || q.includes("placement")){
        return "Open Overview, go to Company Management, open the company, then assign the selected intern to that company. The intern's placement should show the assigned company and status.";
    }

    if(q.includes("evaluation")){
        return "Evaluations can be prepared from the Evaluations page. Select the intern, review the required criteria, add feedback, and save or submit the evaluation according to your workflow.";
    }

    if(q.includes("intern")){
        return "The Interns page is focused on student placement and OJT progress. You can review assigned companies, completed hours, status, and student information there.";
    }

    if(q.includes("report")){
        return "Reports & Analytics can be used to review internship progress, hours, placement information, and administration-ready summaries.";
    }

    if(q.includes("help") || q.includes("support")){
        return "You can open Help & Support from the sidebar to submit an issue or request assistance from the OJT coordination team.";
    }

    return "I can help with OJT journals, QR attendance, evaluations, student placement, companies, reports, and OJT360 navigation. Try asking about one of those topics.";
}

aiLauncher.addEventListener("click", () => {
    if(aiPanel.classList.contains("open")){
        closeAssistant();
    }else{
        openAssistant();
    }
});

aiClose.addEventListener("click", closeAssistant);

document.querySelectorAll(".ai-suggestion").forEach(button => {
    button.addEventListener("click", () => {
        const question = button.dataset.question;
        addAssistantMessage(question, true);

        setTimeout(() => {
            addAssistantMessage(getAssistantReply(question));
        }, 280);
    });
});

aiForm.addEventListener("submit", e => {
    e.preventDefault();

    const question = aiInput.value.trim();
    if(!question) return;

    addAssistantMessage(question, true);
    aiInput.value = "";

    setTimeout(() => {
        addAssistantMessage(getAssistantReply(question));
    }, 280);
});

/* ================================================================
   MODALS
================================================================ */

function closeModal(id){
    document.getElementById(id).classList.remove("show");
}

document.querySelectorAll("[data-close]").forEach(button => {
    button.addEventListener("click", () => closeModal(button.dataset.close));
});

document.querySelectorAll(".modal-backdrop").forEach(backdrop => {
    backdrop.addEventListener("click", e => {
        if(e.target === backdrop){
            backdrop.classList.remove("show");
        }
    });
});

document.addEventListener("keydown", e => {
    if(e.key === "Escape"){
        document.querySelectorAll(".modal-backdrop.show").forEach(modal => {
            modal.classList.remove("show");
        });
        closeSidebar();
    }
});

/* ================================================================
   INITIAL RENDER
================================================================ */

renderCompanies();
renderAssignments();
renderInterns();
renderEvaluations();
updateOverviewStats();
</script>
<script src="notification.js"></script>
</body>
</html>