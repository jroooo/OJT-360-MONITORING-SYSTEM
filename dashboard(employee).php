

<?php

session_start();

$section = $_GET["section"] ?? "overview";

 

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

 

require_once __DIR__ . "/config/database.php";

 

if (!isset($_SESSION["employee_students_csrf"])) {

    $_SESSION["employee_students_csrf"] = bin2hex(random_bytes(32));

}

 

if (!isset($_SESSION["employee_companies_csrf"])) {

    $_SESSION["employee_companies_csrf"] = bin2hex(random_bytes(32));

}

 

if (!isset($_SESSION["employee_evaluations_csrf"])) {

    $_SESSION["employee_evaluations_csrf"] = bin2hex(random_bytes(32));

}

 

if (!isset($_SESSION["employee_chatbot_csrf"])) {

    $_SESSION["employee_chatbot_csrf"] = bin2hex(random_bytes(32));

}

 

require __DIR__ . "/actions/employee_company_actions.php";

require __DIR__ . "/actions/employee_student_actions.php";

require __DIR__ . "/actions/employee_settings_actions.php";

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

 

require __DIR__ . "/actions/employee_evaluation_actions.php";

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

 

require __DIR__ . "/actions/employee_messages_api.php";

require __DIR__ . "/includes/employee_student_data.php";

require __DIR__ . "/includes/employee_progress_data.php";

require __DIR__ . "/includes/employee_evaluation_data.php";

require __DIR__ . "/includes/employee_company_data.php";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employee Dashboard</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet" href="assets/css/employee-dashboard-overrides.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">



</head>

 

<body>

<div class="app">

 

    <!-- =========================================================

         SIDEBAR

    ========================================================== -->

    <aside class="sidebar compact" id="sidebar">

        <div class="brand">

            <div class="brand-mark">

                <span></span><span></span><span></span><span></span>

            </div>

            <div class="brand-text">OJT360</div>

            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar" title="Close sidebar">&times;</button>

        </div>

 

        <nav class="sidebar-nav">

            <a class="nav-link active" data-page="overview" href="employee_overview.php" aria-label="Overview" title="Overview">

                <span class="nav-icon">▦</span>

                <span>Overview</span>

            </a>

 

            <a class="nav-link" data-page="interns" href="employee_interns.php" aria-label="Interns" title="Interns">

                <span class="nav-icon">♙</span>

                <span>Interns</span>

            </a>

 

            <a class="nav-link" data-page="evaluations" href="employee_evaluations.php" aria-label="Evaluations" title="Evaluations">

                <span class="nav-icon">▤</span>

                <span>Evaluations</span>

            </a>

 

            <a class="nav-link" data-page="messages" href="employee_messages.php" aria-label="Messages" title="Messages">

                <span class="nav-icon">➤</span>

                <span>Messages</span>

            </a>

 

            <a class="nav-link" data-page="companies" href="employee_companies.php" aria-label="Companies" title="Companies">

                <span class="nav-icon company-building-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"/>
                        <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/>
                        <path d="M15 10h3a2 2 0 0 1 2 2v9"/>
                        <path d="M8 7h1M11 7h1M8 11h1M11 11h1M8 15h1M11 15h1"/>
                        <path d="M9 21v-3h3v3M17 14h1M17 17h1"/>
                    </svg>
                </span>

                <span>Companies</span>

            </a>

 

        </nav>

 

        <div class="sidebar-bottom">

            <a class="nav-link help-link" data-page="support" href="employee_support.php" aria-label="Help and Support" title="Help and Support">

                <span class="nav-icon">?</span>

                <span>Help &amp; Support</span>

            </a>

 

            <a class="nav-link settings-bottom-link" data-page="settings" href="employee_settings.php" aria-label="Settings" title="Settings">

                <span class="nav-icon">⚙</span>

                <span>Settings</span>

            </a>

 

            <button class="nav-link" id="signOutBtn" aria-label="Sign out" title="Sign out">

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

                <div class="brand-text" id="topbarPageName" data-current-page="Employee">OJT360</div>

            </div>

 

            <div class="sync">

                <span class="sync-dot"></span>

                <span>Online</span>

            </div>

 

            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode">

                <span id="themeToggleIcon" aria-hidden="true">

                    <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <circle cx="12" cy="12" r="4"></circle>

                        <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path>

                    </svg>

                    <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" aria-hidden="true">

                        <path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"></path>

                    </svg>

                </span>

            </button>

 

            <div class="notification-wrapper">

                <button class="icon-button" id="notificationBtn" type="button" title="Notifications" aria-label="Notifications" aria-controls="notificationDropdown" aria-expanded="false">

                    <span class="notification-badge" id="notificationBadge" aria-live="polite" hidden>0</span>

                </button>

                <section class="notification-panel" id="notificationDropdown" aria-label="Notifications" hidden>

                    <div class="notification-panel-header">

                        <strong>Notifications</strong>

                        <button class="notification-mark-read" id="markAllReadBtn" type="button">Mark all read</button>

                    </div>

                    <div class="notification-list" id="notificationList">

                        <p class="notification-empty">You're all caught up.</p>

                    </div>

                </section>

            </div>

 

            <button class="top-profile profile-trigger" id="topProfileBtn" title="Open profile" aria-label="Open profile for <?= $displayName ?>">

                <div class="avatar"><?= $displayInitials ?></div>

            </button>

        </header>

 

        <div class="content">

 

            <?php require __DIR__ . "/pages/employee/overview.php"; ?>

<?php require __DIR__ . "/pages/employee/interns.php"; ?>

<?php require __DIR__ . "/pages/employee/evaluations.php"; ?>

<?php require __DIR__ . "/pages/employee/messages.php"; ?>

<?php require __DIR__ . "/pages/employee/companies.php"; ?>

<?php require __DIR__ . "/pages/employee/support.php"; ?>

<?php require __DIR__ . "/pages/employee/settings.php"; ?>
<?php require __DIR__ . "/pages/employee/profile.php"; ?>

<!-- =============================================================

     OJT360 AI ASSISTANT

============================================================== -->

<button class="ai-launcher" id="aiLauncher" aria-label="Open OJT360 Assistant" aria-expanded="false" title="OJT360 Assistant">

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

 

    <div class="ai-body" id="aiBody" role="log" aria-live="polite" aria-relevant="additions text">

        <div class="ai-row">

            <div class="ai-mini-avatar">✦</div>

            <div class="ai-bubble">

                Hi! I can help with OJT guidelines, attendance, journals, evaluations, and student placement. What do you need?

            </div>

        </div>

 

        <div class="ai-suggestions">

            <button class="ai-suggestion" data-question="Where can I review registered interns?">Find interns</button>

            <button class="ai-suggestion" data-question="How do I record and review a student evaluation?">Evaluation guide</button>

            <button class="ai-suggestion" data-question="What can I do from the Messages page?">Using Messages</button>

        </div>

    </div>

 

    <form class="ai-foot" id="aiForm">

        <input class="ai-input" id="aiInput" autocomplete="off" placeholder="Ask OJT360...">

        <button class="ai-send" type="submit" aria-label="Send message">↑</button>

    </form>

</section>

 

<div class="toast" id="toast"></div>

 

<!-- =============================================================

     FORMAL ACTION CONFIRMATION

============================================================== -->

<div class="modal-backdrop" id="actionConfirmModal">

    <section class="modal action-confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="actionConfirmTitle" aria-describedby="actionConfirmMessage">

        <div class="action-confirm-head">

            <div class="action-confirm-icon" aria-hidden="true">!</div>

            <div>

                <div class="assignment-kicker">Please confirm</div>

                <h2 id="actionConfirmTitle"></h2>

            </div>

        </div>

        <div class="action-confirm-body">

            <p id="actionConfirmMessage"></p>

            <div class="fm-summary">

                <div class="fm-row">

                    <span id="actionConfirmLabel"></span>

                    <strong id="actionConfirmValue"></strong>

                </div>

            </div>

        </div>

        <div class="modal-foot">

            <button type="button" class="btn btn-light" data-close="actionConfirmModal">Cancel</button>

            <button type="button" class="btn btn-primary" id="actionConfirmButton"></button>

        </div>

    </section>

</div>

 

<!-- =============================================================

     ADD COMPANY MODAL

============================================================== -->

<div class="modal-backdrop" id="companyModal">

    <div class="modal">

        <div class="modal-head">

            <h2>Add Company</h2>

            <button class="close" data-close="companyModal">&times;</button>

        </div>

 

        <form id="companyForm" method="post" action="dashboard(employee).php">

            <input type="hidden" name="company_action" value="create">

            <input type="hidden" name="companies_csrf" value="<?= $companiesCsrf ?>">

            <div class="modal-body">

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label" for="companyName">Company name</label>

                        <input class="field" id="companyName" name="company_name" maxlength="150" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyCode">Company code</label>

                        <input class="field" id="companyCode" name="company_code" maxlength="50">

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyContactPerson">Contact person</label>

                        <input class="field" id="companyContactPerson" name="contact_person" maxlength="100" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyContactNumber">Contact number</label>

                        <input class="field" id="companyContactNumber" name="contact_number" maxlength="20" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyEmail">Contact email</label>

                        <input class="field" id="companyEmail" type="email" name="email" maxlength="100" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyStatus">Status</label>

                        <select class="field" id="companyStatus" name="status">

                            <option value="active">Active</option>

                            <option value="inactive">Inactive</option>

                        </select>

                    </div>

 

                    <div class="form-group full">

                        <label class="form-label" for="companyAddress">Address</label>

                        <input class="field" id="companyAddress" name="address" maxlength="255" required>

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyBuilding">Building</label>

                        <input class="field" id="companyBuilding" name="building" maxlength="100">

                    </div>

 

                    <div class="form-group">

                        <label class="form-label" for="companyDepartment">Department</label>

                        <input class="field" id="companyDepartment" name="department" maxlength="100">

                    </div>

 

                    <div class="form-group full">

                        <label class="form-label" for="companyPosition">Position</label>

                        <input class="field" id="companyPosition" name="position" maxlength="100">

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

                    <label>Company Code</label>

                    <strong id="companyDetailsCode"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Status</label>

                    <span id="companyDetailsStatus"></span>

                </div>

                <div class="company-detail-item">

                    <label>Contact Person</label>

                    <strong id="companyDetailsContact"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Contact Information</label>

                    <strong id="companyDetailsEmail"></strong>

                    <div class="company-detail-secondary" id="companyDetailsPhone"></div>

                </div>

                <div class="company-detail-item">

                    <label>Building</label>

                    <strong id="companyDetailsBuilding"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Department / Position</label>

                    <strong id="companyDetailsDepartment"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Address</label>

                    <strong id="companyDetailsLocation"></strong>

                </div>

            </div>

 

            <div class="company-detail-item">

                <label>Registered</label>

                <strong id="companyDetailsCreated"></strong>

            </div>

        </div>

    </section>

</div>

 

<!-- =============================================================

     STUDENT ADVISER RECORD MODAL (HOURS, ABSENCES & JOURNALS)

============================================================== -->

<div class="modal-backdrop" id="studentAdviserRecordModal">

    <div class="modal evaluation-modal" style="max-width: 800px;">

        <div class="modal-head">

            <h2>Student Adviser Record</h2>

            <button class="close" type="button" data-close="studentAdviserRecordModal">&times;</button>

        </div>

 

        <div class="modal-body" style="padding: 20px;">

            <div class="student-evaluation-summary" style="margin-bottom: 20px;">

                <div class="company-detail-item">

                    <label>Student Name</label>

                    <strong id="advStudentName"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Student ID</label>

                    <strong id="advStudentId"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Assigned Company</label>

                    <strong id="advCompany"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Total Hours Logged</label>

                    <strong id="advCompletedHours" style="color: #1688ba; font-size: 16px;"></strong>

                </div>

            </div>

 

            <h3 style="font-size: 14px; margin-bottom: 10px;">Absence Log &amp; Reasons</h3>

            <div class="table-wrap" style="margin-bottom: 24px;">

                <table>

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Reason for Absence</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody id="advAbsenceTable">

                        <!-- Populated by JS -->

                    </tbody>

                </table>

            </div>

 

            <h3 style="font-size: 14px; margin-bottom: 10px;">Weekly Journals</h3>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Week</th>

                            <th>Journal Entry</th>

                            <th>Status</th>

                            <th>Feedback</th>

                        </tr>

                    </thead>

                    <tbody id="advJournalTable">

                        <!-- Populated by JS -->

                    </tbody>

                </table>

            </div>

        </div>

 

        <div class="modal-foot">

            <button class="btn btn-light" type="button" data-close="studentAdviserRecordModal">Close</button>

        </div>

    </div>

</div>

 

<!-- =============================================================

     INTERN EVALUATION MODAL

============================================================== -->

<div class="modal-backdrop" id="evaluationModal">

    <div class="modal evaluation-modal">

        <div class="modal-head">

            <h2>Record intern evaluation</h2>

            <button class="close" type="button" data-close="evaluationModal" aria-label="Close">&times;</button>

        </div>

 

        <form method="post">

            <input type="hidden" name="evaluation_action" value="save">

            <input type="hidden" name="evaluations_csrf" value="<?= $evaluationsCsrf ?>">

            <input type="hidden" id="evaluationRecordId" name="evaluation_id" value="">

            <div class="modal-body">

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label" for="evaluationStudent">Student</label>

                        <select class="field" id="evaluationStudent" name="student_db_id" required></select>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationDate">Evaluation date</label>

                        <input class="field" id="evaluationDate" type="date" name="evaluation_date" value="<?= htmlspecialchars(date("Y-m-d"), ENT_QUOTES, "UTF-8") ?>" required>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationPerformance">Overall performance</label>

                        <select class="field" id="evaluationPerformance" name="overall_performance" required>

                            <option value="">Select performance</option>

                            <option>Outstanding</option>

                            <option>Very Good</option>

                            <option>Satisfactory</option>

                            <option>Needs Improvement</option>

                            <option>Unsatisfactory</option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationStatus">Review status</label>

                        <select class="field" id="evaluationStatus" name="status" required>

                            <option value="Draft">Save as draft (continue later)</option>

                            <option value="Completed">Mark as completed (finalize review)</option>

                        </select>

                    </div>

                    <div class="form-group full">

                        <span class="form-label">Performance criteria (1 = lowest, 5 = highest)</span>

                        <div class="evaluation-criteria-list" id="evaluationCriteria"></div>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationStrengths">Strengths</label>

                        <textarea class="field" id="evaluationStrengths" name="strengths" rows="3" maxlength="5000"></textarea>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationImprovements">Areas for improvement</label>

                        <textarea class="field" id="evaluationImprovements" name="areas_for_improvement" rows="3" maxlength="5000"></textarea>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationComments">Comments</label>

                        <textarea class="field" id="evaluationComments" name="comments" rows="3" maxlength="5000"></textarea>

                    </div>

                    <div class="form-group">

                        <label class="form-label" for="evaluationRecommendation">Recommendation</label>

                        <input class="field" id="evaluationRecommendation" name="recommendation" maxlength="100">

                    </div>

                </div>

            </div>

 

            <div class="modal-foot">

                <button type="button" class="btn btn-light" data-close="evaluationModal">Cancel</button>

                <button class="btn btn-primary" type="submit">Save evaluation</button>

            </div>

        </form>

    </div>

</div>

 

<!-- =============================================================

     STUDENT EVALUATION DETAILS

============================================================== -->

<div class="modal-backdrop" id="studentEvaluationModal">

    <section class="modal student-evaluation-modal" aria-labelledby="studentEvaluationName">

        <header class="student-evaluation-head">

            <div>

                <div class="assignment-kicker">Intern Performance Record</div>

                <h2 id="studentEvaluationName"></h2>

                <p id="studentEvaluationIdentity"></p>

            </div>

            <button class="close" type="button" data-close="studentEvaluationModal" aria-label="Close evaluation">&times;</button>

        </header>

 

        <div class="student-evaluation-body">

            <div class="student-evaluation-summary">

                <div class="company-detail-item">

                    <label>Program</label>

                    <strong id="studentEvaluationProgram"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Evaluation date</label>

                    <strong id="studentEvaluationDate"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Overall performance</label>

                    <strong id="studentEvaluationPerformance"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Overall rating</label>

                    <strong id="studentEvaluationRating"></strong>

                </div>

                <div class="company-detail-item">

                    <label>Review status</label>

                    <strong id="studentEvaluationStatus"></strong>

                </div>

            </div>

 

            <section class="student-evaluation-section" aria-labelledby="evaluationCriteriaHeading">

                <h3 id="evaluationCriteriaHeading">Performance criteria</h3>

                <div class="table-wrap">

                    <table class="student-evaluation-table">

                        <thead>

                            <tr><th>Criterion</th><th>Rating</th></tr>

                        </thead>

                        <tbody id="studentEvaluationScores"></tbody>

                    </table>

                </div>

            </section>

 

            <section class="student-evaluation-section">

                <h3>Written feedback</h3>

                <div class="student-evaluation-feedback-grid">

                    <div class="evaluation-feedback-item"><h4>Strengths</h4><p id="studentEvaluationStrengths"></p></div>

                    <div class="evaluation-feedback-item"><h4>Areas for improvement</h4><p id="studentEvaluationImprovements"></p></div>

                    <div class="evaluation-feedback-item"><h4>Evaluator comments</h4><p id="studentEvaluationComments"></p></div>

                    <div class="evaluation-feedback-item"><h4>Recommendation</h4><p id="studentEvaluationRecommendation"></p></div>

                </div>

            </section>

        </div>

 

        <footer class="modal-foot">

            <button class="btn btn-light" type="button" data-close="studentEvaluationModal">Close</button>

        </footer>

    </section>

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

 

        <form id="assignmentForm" method="post" action="dashboard(employee).php">

            <input type="hidden" name="student_action" value="assign_company">

            <input type="hidden" id="assignmentStudentDbId" name="student_db_id" value="">

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

                    <select class="field" id="assignmentCompany" name="company_name" required></select>

                </div>

            </div>

 

            <div class="modal-foot">

                <button type="button" class="btn btn-light" data-close="assignmentModal">Cancel</button>

                <button class="btn btn-primary" id="saveAssignmentBtn" type="submit">Assign Student</button>

            </div>

        </form>

    </div>

</div>

 

<script>

const CURRENT_EMPLOYEE_ID = <?= json_encode($employeeId) ?>;

const CURRENT_EMPLOYEE_TYPE = "employee";

const STUDENT_DATA_ERROR = <?= json_encode($internPageError !== "") ?>;

const STUDENT_PROGRESS_DATA_ERROR = <?= json_encode($studentProgressPageError !== "") ?>;

const EVALUATION_DATA_ERROR = <?= json_encode($evaluationPageError !== "") ?>;

const CHATBOT_CSRF_TOKEN = <?= json_encode(

    $_SESSION["employee_chatbot_csrf"],

    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

) ?>;

const EVALUATION_SUCCESS_MESSAGE = <?= json_encode(

    ($evaluationFlash["type"] ?? "") === "success" ? ($evaluationFlash["message"] ?? "") : "",

    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

) ?>;

 

let currentConversation = null;

 

/* ================================================================

   COMPANY AND STUDENT RECORDS LOADED FROM MYSQL

================================================================ */

 

const COMPANY_DATA_ERROR = <?= json_encode($companyPageError !== "") ?>;

const COMPANIES_CSRF = <?= json_encode($_SESSION["employee_companies_csrf"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

 

let companies = <?= json_encode(

    array_map(static function (array $companyRecord): array {

        return [

            "id" => (int)$companyRecord["id"],

            "name" => $companyRecord["company_name"],

            "code" => $companyRecord["company_code"] ?? "",

            "contact" => $companyRecord["contact_person"],

            "phone" => $companyRecord["contact_number"],

            "email" => $companyRecord["email"],

            "location" => $companyRecord["address"],

            "building" => $companyRecord["building"] ?? "",

            "department" => $companyRecord["department"] ?? "",

            "position" => $companyRecord["position"] ?? "",

            "createdAt" => $companyRecord["created_at"],

            "status" => $companyRecord["status"],

            "registeredCompanyId" => $companyRecord["registered_company_id"] !== null ? (int)$companyRecord["registered_company_id"] : null

        ];

    }, $companyRecords),

    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

) ?>;

 

let students = <?= json_encode(

    array_map(static function (array $studentRecord) use ($studentProgressByStudentId, $studentAbsencesByStudentId, $studentJournalsByStudentId): array {

        $stId = $studentRecord["id"];

        return [

            "id" => (int)$stId,

            "name" => trim(

                $studentRecord["first_name"] . " " .

                ($studentRecord["middle_name"] !== "" ? $studentRecord["middle_name"] . " " : "") .

                $studentRecord["last_name"]

            ),

            "studentId" => $studentRecord["student_id"],

            "program" => $studentRecord["course"],

            "email" => $studentRecord["institutional_email"],

            "mobileNumber" => $studentRecord["mobile_number"],

            "schoolName" => $studentRecord["school_name"],

            "createdAt" => $studentRecord["created_at"],

            "company" => $studentProgressByStudentId[$studentRecord["student_id"]]["company"] ?? null,

            "completedHours" => $studentProgressByStudentId[$studentRecord["student_id"]]["completedHours"] ?? 0,

            "absences" => $studentAbsencesByStudentId[$stId] ?? [],

            "journals" => $studentJournalsByStudentId[$stId] ?? [],

            "status" => !empty($studentProgressByStudentId[$studentRecord["student_id"]]["company"])

                ? "Assigned"

                : "Unassigned"

        ];

    }, $studentRecords),

    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

) ?>;

 

let evaluationsByStudentId = <?= json_encode(

    array_map(static function (array $evaluationRecord): array {

        return [

            "id" => (int)$evaluationRecord["id"],

            "evaluationDate" => $evaluationRecord["evaluation_date"],

            "attendancePunctuality" => (int)$evaluationRecord["attendance_punctuality"],

            "qualityOfWork" => (int)$evaluationRecord["quality_of_work"],

            "productivity" => (int)$evaluationRecord["productivity"],

            "communication" => (int)$evaluationRecord["communication"],

            "teamwork" => (int)$evaluationRecord["teamwork"],

            "initiative" => (int)$evaluationRecord["initiative"],

            "professionalism" => (int)$evaluationRecord["professionalism"],

            "adaptability" => (int)$evaluationRecord["adaptability"],

            "problemSolving" => (int)$evaluationRecord["problem_solving"],

            "technicalSkills" => (int)$evaluationRecord["technical_skills"],

            "overallRating" => $evaluationRecord["overall_rating"] !== null

                ? (float)$evaluationRecord["overall_rating"]

                : null,

            "overallPerformance" => $evaluationRecord["overall_performance"] ?? "",

            "strengths" => $evaluationRecord["strengths"] ?? "",

            "areasForImprovement" => $evaluationRecord["areas_for_improvement"] ?? "",

            "comments" => $evaluationRecord["comments"] ?? "",

            "recommendation" => $evaluationRecord["recommendation"] ?? "",

            "status" => $evaluationRecord["status"]

        ];

    }, $evaluationRecordsByStudentId),

    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

) ?>;

 

/* ================================================================

   HELPERS

================================================================ */

const MESSAGE_API_URL = <?= json_encode($_SERVER["PHP_SELF"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

</script>
<script>window.OJT360_INITIAL_SECTION = <?= json_encode($section, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/employee-dashboard.js"></script>

<script src="notification.js"></script>

<script src="chatbot.js"></script>

</body>

</html>

