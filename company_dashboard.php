<?php
require_once __DIR__ . '/includes/bootstrap.php';

$company = require_company();
$pdo = db();

/*
|--------------------------------------------------------------------------
| COMPANY INFORMATION
|--------------------------------------------------------------------------
*/
$cid = (int)($company['id'] ?? 0);

$companyName = $company['name'] ?? 'Company';
$representative = $company['row']['contact_person']
    ?? ($company['first'] ?? ($company['representative'] ?? ''));

/*
|--------------------------------------------------------------------------
| GET STUDENTS
|--------------------------------------------------------------------------
*/
$st = $pdo->prepare("
    SELECT
        id,
        first_name,
        middle_name,
        last_name,
        course,
        mobile_number,
        school_name,
        complete_address,
        student_id,
        institutional_email,
        created_at
    FROM students
    ORDER BY last_name ASC, first_name ASC
    LIMIT 6
");

$st->execute();
$interns = $st->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOTAL STUDENTS
|--------------------------------------------------------------------------
*/
$st = $pdo->query("
    SELECT COUNT(*) 
    FROM students
");

$totalStudents = (int)$st->fetchColumn();

/*
|--------------------------------------------------------------------------
| TODAY'S DATE
|--------------------------------------------------------------------------
*/
$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| ATTENDANCE
|--------------------------------------------------------------------------
*/
$todayLog = [];

try {

    $checkAttendance = $pdo->query("
        SHOW TABLES LIKE 'attendance'
    ");

    if ($checkAttendance->fetchColumn()) {

        $columns = [];
        $columnQuery = $pdo->query("
            SHOW COLUMNS FROM attendance
        ");

        while ($column = $columnQuery->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $column['Field'];
        }

        $requiredAttendanceColumns = [
            'student_id',
            'date',
            'time_in',
            'time_out'
        ];

        $hasAttendanceColumns = true;

        foreach ($requiredAttendanceColumns as $column) {
            if (!in_array($column, $columns, true)) {
                $hasAttendanceColumns = false;
                break;
            }
        }

        if ($hasAttendanceColumns) {

            $st = $pdo->prepare("
                SELECT
                    a.time_in,
                    a.time_out,
                    s.first_name,
                    s.last_name
                FROM attendance a
                INNER JOIN students s
                    ON s.id = a.student_id
                WHERE a.date = ?
                ORDER BY a.time_in DESC
                LIMIT 6
            ");

            $st->execute([$today]);

            $todayLog = $st->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $todayLog = [];
}

/*
|--------------------------------------------------------------------------
| GREETING
|--------------------------------------------------------------------------
*/
$hour = (int)date('G');

if ($hour < 12) {
    $greet = 'Good morning';
} elseif ($hour < 18) {
    $greet = 'Good afternoon';
} else {
    $greet = 'Good evening';
}

$firstName = trim($representative);
if ($firstName === '') {
    $firstName = $companyName;
}

$pageKey = 'dashboard';
$pageTitle = 'Dashboard';
$eyebrow = 'COMPANY OVERVIEW';

require __DIR__ . '/includes/layout_top.php';
?>

<style>
/* ============================================================
   COMPANY DASHBOARD - DUAL THEME SUPPORT
   ============================================================ */

.dashboard-page {
    width: 100%;
    min-height: calc(100vh - 80px);
    padding: 24px;
    font-family: "Segoe UI", Inter, "Helvetica Neue", Arial, sans-serif;
}

.dashboard-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}

.dash-header-left {
    min-width: 0;
}

.dash-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--qr-brand, #2563eb);
    margin-bottom: 8px;
    line-height: 1.4;
}

.dash-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--qr-brand, #2563eb);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
}

.dash-title {
    margin: 0;
    font-size: clamp(30px, 3.4vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.04em;
    font-weight: 800;
    color: var(--qr-text, #0f172a);
}

.dash-description {
    margin: 10px 0 0;
    max-width: 720px;
    color: var(--qr-text-muted, #64748b);
    font-size: 14px;
    line-height: 1.65;
    font-weight: 400;
}

/* Dark mode overrides for consistency with dynamic_qr.php */
html[data-theme="dark"] .dash-title,
.dark .dash-title {
    color: #ffffff !important;
}
</style>

<div class="dashboard-page">
    <div class="dashboard-container">

        <!-- =========================================================
             PAGE HEADER (Uniform with Dynamic QR Code)
             ========================================================= -->
        <div class="dash-header">
            <div class="dash-header-left">
                <div class="dash-eyebrow">
                    <?= e($eyebrow) ?>
                </div>
                <h1 class="dash-title">
                    <?= e($greet) ?>, <?= e($firstName) ?><span class="dotc">.</span>
                </h1>
                <p class="dash-description">
                    <?= e(date('l, F j, Y')) ?> &middot; View student information and monitor your company's OJT activities.
                </p>
            </div>
        </div>

        <!-- =========================================================
             COMPANY HERO
             ========================================================= -->
        <section class="hero" style="margin-bottom: 24px;">
            <div>
                <span class="tag">
                    <?= e($companyName) ?>
                </span>
                <h2>
                    OJT supervision at a glance
                </h2>
                <p>
                    View registered students and monitor your OJT activities in one place.
                </p>
            </div>
            <div class="row-actions">
                <a class="btn light" href="assigned_students.php">
                    <i class="fa-solid fa-user-group"></i>
                    View Students
                </a>
            </div>
        </section>

        <!-- =========================================================
             STATISTICS
             ========================================================= -->
        <section class="stats" aria-label="Key metrics" style="margin-bottom: 24px;">
            <div class="stat">
                <div class="ic">
                    <i class="fa-solid fa-user-group"></i>
                </div>
                <b><?= $totalStudents ?></b>
                <span>Registered students</span>
            </div>

            <div class="stat">
                <div class="ic warn">
                    <i class="fa-solid fa-school"></i>
                </div>
                <b>LNU</b>
                <span>Student source</span>
            </div>

            <div class="stat">
                <div class="ic ok">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <b>
                    <?php
                    try {
                        $courseQuery = $pdo->query("
                            SELECT COUNT(DISTINCT course)
                            FROM students
                            WHERE course IS NOT NULL
                            AND course <> ''
                        ");
                        $courseCount = (int)$courseQuery->fetchColumn();
                    } catch (PDOException $e) {
                        $courseCount = 0;
                    }
                    echo $courseCount;
                    ?>
                </b>
                <span>Courses</span>
            </div>

            <div class="stat">
                <div class="ic">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <b><?= count($todayLog) ?></b>
                <span>Checked in today</span>
            </div>
        </section>

        <!-- =========================================================
             MAIN CONTENT GRID
             ========================================================= -->
        <div class="grid-2">

            <!-- =====================================================
                 STUDENTS
                 ===================================================== -->
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Students</h2>
                        <p>Registered student information</p>
                    </div>
                    <a class="btn ghost sm" href="assigned_students.php">
                        View all
                    </a>
                </div>

                <?php if (!$interns): ?>
                    <div class="empty">
                        <i class="fa-solid fa-user-group"></i>
                        <b>No students found</b>
                        There are currently no registered students in the database.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Course</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interns as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="who">
                                                <span class="avatar sm">
                                                    <?= e(initials($s['first_name'], $s['last_name'])) ?>
                                                </span>
                                                <div>
                                                    <b><?= e(trim($s['first_name'] . ' ' . $s['last_name'])) ?></b>
                                                    <small><?= e($s['institutional_email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($s['student_id']) ?></td>
                                        <td>
                                            <span class="pill ok"><?= e($s['course']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- =====================================================
                 TODAY'S ATTENDANCE
                 ===================================================== -->
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2>Today's attendance</h2>
                        <p><?= e(date('F j, Y')) ?></p>
                    </div>
                    <a class="btn ghost sm" href="dynamic_qr.php">
                        Manage
                    </a>
                </div>

                <?php if (!$todayLog): ?>
                    <div class="empty">
                        <i class="fa-regular fa-clock"></i>
                        <b>No check-ins yet today</b>
                        Attendance records will appear here when students check in.
                    </div>
                <?php else: ?>
                    <?php foreach ($todayLog as $log): ?>
                        <div class="who" style="padding: 9px 0; border-top: 1px solid var(--line);">
                            <span class="avatar sm">
                                <?= e(initials($log['first_name'], $log['last_name'])) ?>
                            </span>
                            <div>
                                <b><?= e($log['first_name'] . ' ' . $log['last_name']) ?></b>
                                <small>
                                    <?php if (!empty($log['time_in'])): ?>
                                        In <?= e(date('g:i A', strtotime($log['time_in']))) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($log['time_out'])): ?>
                                        &middot; Out <?= e(date('g:i A', strtotime($log['time_out']))) ?>
                                    <?php else: ?>
                                        &middot; still on duty
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        </div>
    </div>
</div>

<?php
require __DIR__ . '/includes/layout_bottom.php';
?>