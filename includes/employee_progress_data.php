<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   FETCH STUDENT ATTENDANCE, ABSENCES & JOURNALS

========================================================= */

 

$studentProgressByStudentId = [];

$studentAbsencesByStudentId = [];

$studentJournalsByStudentId = [];

$studentProgressPageError = "";

 

try {

    $studentProgressQuery = $conn->query("

        SELECT

            student.student_id,

            student.id AS student_db_id,

            internship.company_name AS company_name,

            attendance_total.completed_minutes

        FROM students AS student

        LEFT JOIN internships AS internship

            ON internship.student_pk = student.id

            AND internship.id = (

                SELECT MAX(latest_internship.id)

                FROM internships AS latest_internship

                WHERE latest_internship.student_pk = student.id

            )

        LEFT JOIN (

            SELECT student_pk, SUM(duration_minutes) AS completed_minutes

            FROM attendance

            GROUP BY student_pk

        ) AS attendance_total

            ON attendance_total.student_pk = student.id

    ");

 

    if ($studentProgressQuery) {

        while ($studentProgressRecord = $studentProgressQuery->fetch_assoc()) {

            $studentProgressByStudentId[$studentProgressRecord["student_id"]] = [

                "company" => $studentProgressRecord["company_name"],

                "completedHours" => $studentProgressRecord["completed_minutes"] !== null

                    ? (float)$studentProgressRecord["completed_minutes"] / 60

                    : 0.0

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

        SELECT j.student_pk, j.week_number, j.start_date, j.end_date, j.journal_entry, j.status, j.feedback

        FROM weekly_journals j

        ORDER BY j.week_number DESC

    ");

    if ($journalsQuery) {

        while ($jnl = $journalsQuery->fetch_assoc()) {

            $studentJournalsByStudentId[$jnl['student_pk']][] = $jnl;

        }

        $journalsQuery->free();

    }

 

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load student details: " . $exception->getMessage());

    $studentProgressPageError = "Company assignments and working hours could not be loaded right now.";

}

 

