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
| IMPORTANT:
| This uses ONLY the columns that exist in your students table:
|
| id
| first_name
| middle_name
| last_name
| course
| mobile_number
| school_name
| complete_address
| student_id
| institutional_email
| password_hash
| created_at
|
| Since your students table has NO company_id, we do not filter
| students by company_id here.
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
|
| We check whether the attendance table exists before querying it.
| This prevents the dashboard from crashing if the table structure
| is different or does not exist.
|--------------------------------------------------------------------------
*/

$todayLog = [];

try {

    $checkAttendance = $pdo->query("
        SHOW TABLES LIKE 'attendance'
    ");

    if ($checkAttendance->fetchColumn()) {

        /*
         * We only use the attendance query if the expected columns
         * exist.
         */
        $columns = [];

        $columnQuery = $pdo->query("
            SHOW COLUMNS FROM attendance
        ");

        while ($column = $columnQuery->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $column['Field'];
        }

        /*
         * Required columns for this attendance display.
         */
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

    /*
     * Do not crash the dashboard because of attendance.
     */
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


/*
|--------------------------------------------------------------------------
| FIRST NAME
|--------------------------------------------------------------------------
*/
$firstName = trim($representative);

if ($firstName === '') {
    $firstName = $companyName;
}


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/
$pageKey = 'dashboard';
$pageTitle = 'Dashboard';
$eyebrow = 'COMPANY OVERVIEW';

require __DIR__ . '/includes/layout_top.php';
?>


<!-- =========================================================
     PAGE HEADER
     ========================================================= -->

<div class="page-head">

    <div>

        <div class="eyebrow">
            COMPANY OVERVIEW
        </div>

        <h1 class="title">

            <?= e($greet) ?>,
            <?= e($firstName) ?>

            <span class="dotc">.</span>

        </h1>

        <p class="sub">

            <?= e(date('l, F j, Y')) ?>

            &middot;

            View student information and monitor your
            company's OJT activities.

        </p>

    </div>


    <a class="btn dark" href="assigned_students.php">

        <i class="fa-solid fa-user-group"></i>

        View Students

    </a>

</div>



<!-- =========================================================
     COMPANY HERO
     ========================================================= -->

<section class="hero">

    <div>

        <span class="tag">

            <?= e($companyName) ?>

        </span>


        <h2>

            OJT supervision at a glance

        </h2>


        <p>

            View registered students and monitor
            your OJT activities in one place.

        </p>

    </div>


    <div class="row-actions">

        <a class="btn light" href="assigned_students.php">

            <i class="fa-solid fa-user-group"></i>

            View Students

        </a>


        <a class="btn ghost" href="reports.php">

            <i class="fa-solid fa-chart-line"></i>

            View Reports

        </a>

    </div>

</section>



<!-- =========================================================
     STATISTICS
     ========================================================= -->

<section class="stats" aria-label="Key metrics">


    <!-- TOTAL STUDENTS -->

    <div class="stat">

        <div class="ic">

            <i class="fa-solid fa-user-group"></i>

        </div>


        <b>

            <?= $totalStudents ?>

        </b>


        <span>

            Registered students

        </span>

    </div>



    <!-- SCHOOL -->

    <div class="stat">

        <div class="ic warn">

            <i class="fa-solid fa-school"></i>

        </div>


        <b>

            LNU

        </b>


        <span>

            Student source

        </span>

    </div>



    <!-- COURSE -->

    <div class="stat">

        <div class="ic ok">

            <i class="fa-solid fa-graduation-cap"></i>

        </div>


        <b>

            <?php

            /*
             * Get the number of different courses.
             */

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


        <span>

            Courses

        </span>

    </div>



    <!-- TODAY'S ATTENDANCE -->

    <div class="stat">

        <div class="ic">

            <i class="fa-solid fa-clock"></i>

        </div>


        <b>

            <?= count($todayLog) ?>

        </b>


        <span>

            Checked in today

        </span>

    </div>

</section>



<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<div class="grid-2">



    <!-- =====================================================
         STUDENTS
         ===================================================== -->

    <section class="card">

        <div class="card-head">

            <div>

                <h2>

                    Students

                </h2>


                <p>

                    Registered student information

                </p>

            </div>


            <a
                class="btn ghost sm"
                href="assigned_students.php"
            >

                View all

            </a>

        </div>



        <?php if (!$interns): ?>

            <div class="empty">

                <i class="fa-solid fa-user-group"></i>


                <b>

                    No students found

                </b>


                There are currently no registered
                students in the database.

            </div>


        <?php else: ?>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Student ID
                            </th>

                            <th>
                                Course
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($interns as $s): ?>


                        <tr>


                            <!-- STUDENT -->

                            <td>

                                <div class="who">


                                    <span class="avatar sm">

                                        <?= e(
                                            initials(
                                                $s['first_name'],
                                                $s['last_name']
                                            )
                                        ) ?>

                                    </span>


                                    <div>

                                        <b>

                                            <?= e(
                                                trim(
                                                    $s['first_name']
                                                    . ' '
                                                    . $s['last_name']
                                                )
                                            ) ?>

                                        </b>


                                        <small>

                                            <?= e(
                                                $s['institutional_email']
                                            ) ?>

                                        </small>

                                    </div>

                                </div>

                            </td>



                            <!-- STUDENT ID -->

                            <td>

                                <?= e(
                                    $s['student_id']
                                ) ?>

                            </td>



                            <!-- COURSE -->

                            <td>

                                <span class="pill ok">

                                    <?= e(
                                        $s['course']
                                    ) ?>

                                </span>

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

                <h2>

                    Today's attendance

                </h2>


                <p>

                    <?= e(
                        date('F j, Y')
                    ) ?>

                </p>

            </div>


            <a
                class="btn ghost sm"
                href="dynamic.php"
            >

                Manage

            </a>

        </div>



        <?php if (!$todayLog): ?>


            <div class="empty">


                <i class="fa-regular fa-clock"></i>


                <b>

                    No check-ins yet today

                </b>


                Attendance records will appear here
                when students check in.


            </div>


        <?php else: ?>


            <?php foreach ($todayLog as $log): ?>


                <div
                    class="who"
                    style="
                        padding:9px 0;
                        border-top:1px solid var(--line)
                    "
                >


                    <span class="avatar sm">

                        <?= e(
                            initials(
                                $log['first_name'],
                                $log['last_name']
                            )
                        ) ?>

                    </span>


                    <div>


                        <b>

                            <?= e(
                                $log['first_name']
                                . ' '
                                . $log['last_name']
                            ) ?>

                        </b>


                        <small>


                            <?php if (!empty($log['time_in'])): ?>

                                In
                                <?= e(
                                    date(
                                        'g:i A',
                                        strtotime(
                                            $log['time_in']
                                        )
                                    )
                                ) ?>

                            <?php endif; ?>


                            <?php if (!empty($log['time_out'])): ?>

                                &middot;

                                Out
                                <?= e(
                                    date(
                                        'g:i A',
                                        strtotime(
                                            $log['time_out']
                                        )
                                    )
                                ) ?>

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



<?php

require __DIR__ . '/includes/layout_bottom.php';

?>