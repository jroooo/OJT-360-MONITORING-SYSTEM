<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   FETCH STUDENT ATTENDANCE, ABSENCES & JOURNALS

========================================================= */

 

$studentProgressByStudentId = [];

$studentAbsencesByStudentId = [];

$studentJournalsByStudentId = [];

$studentAttendanceByStudentId = [];

$studentProgressPageError = "";

 

try {

    $studentProgressQuery = $conn->query("

        SELECT

            student.student_id,

            student.id AS student_db_id,

            registered_company.company_name AS company_name,

            internship.required_hours AS required_hours,

            attendance_total.completed_minutes

        FROM students AS student

        LEFT JOIN internships AS internship

            ON internship.student_pk = student.id

            AND internship.id = (

                SELECT MAX(latest_internship.id)

                FROM internships AS latest_internship

                WHERE latest_internship.student_pk = student.id

            )
        LEFT JOIN companies AS registered_company
            ON registered_company.company_name = internship.company_name

        LEFT JOIN (

            SELECT
                a.student_pk,
                SUM(
                    CASE
                        WHEN a.time_out IS NULL THEN 0
                        ELSE FLOOR(GREATEST(
                            0,
                            TIMESTAMPDIFF(
                                SECOND,
                                a.time_in,
                                a.time_out
                            ) - COALESCE(a.break_seconds, 0)
                        ) / 60)
                    END
                ) AS completed_minutes

            FROM attendance AS a

            GROUP BY a.student_pk

        ) AS attendance_total

            ON attendance_total.student_pk = student.id

    ");

 

    if ($studentProgressQuery) {

        while ($studentProgressRecord = $studentProgressQuery->fetch_assoc()) {

            $studentProgressByStudentId[$studentProgressRecord["student_id"]] = [

                "company" => $studentProgressRecord["company_name"],

                "completedHours" => $studentProgressRecord["completed_minutes"] !== null

                    ? (float)$studentProgressRecord["completed_minutes"] / 60

                    : 0.0,

                "requiredHours" => $studentProgressRecord["required_hours"] !== null

                    ? (float)$studentProgressRecord["required_hours"]

                    : null

            ];

        }

        $studentProgressQuery->free();

    }

 

    $absencesQuery = $conn->query("

        SELECT a.student_pk, a.absence_date, a.reason, a.status 

        FROM absences a

        ORDER BY a.absence_date DESC

    ");

    if ($absencesQuery) {

        while ($abs = $absencesQuery->fetch_assoc()) {

            $studentAbsencesByStudentId[$abs['student_pk']][] = $abs;

        }

        $absencesQuery->free();

    }

 

    $journalsQuery = $conn->query("

        SELECT j.id, j.student_pk, j.week_start, j.title, j.status
        FROM journals j
        ORDER BY j.week_start DESC

    ");

    if ($journalsQuery) {

        while ($jnl = $journalsQuery->fetch_assoc()) {

            $studentJournalsByStudentId[$jnl['student_pk']][] = $jnl;

        }

        $journalsQuery->free();

    }

    $attendanceQuery = $conn->query("

        SELECT
            student_pk,
            company_name,
            a.time_in AS actual_time_in,
            time_out,
            CASE
                WHEN time_out IS NULL THEN NULL
                ELSE FLOOR(GREATEST(
                    0,
                    TIMESTAMPDIFF(
                        SECOND,
                        a.time_in,
                        a.time_out
                    ) - COALESCE(break_seconds, 0)
                ) / 60)
            END AS logged_minutes

        FROM attendance AS a

        ORDER BY time_in DESC, id DESC

    ");

    if ($attendanceQuery) {

        while ($attendance = $attendanceQuery->fetch_assoc()) {

            $studentAttendanceByStudentId[$attendance["student_pk"]][] = $attendance;

        }

        $attendanceQuery->free();

    }

 

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load student details: " . $exception->getMessage());

    $studentProgressPageError = "Company assignments and working hours could not be loaded right now.";

}

 
