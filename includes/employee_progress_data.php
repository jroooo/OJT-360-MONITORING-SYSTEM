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

 

    $journalColumns = [];
    $journalColumnsQuery = $conn->query("SHOW COLUMNS FROM journal_submissions");
    if ($journalColumnsQuery) {
        while ($column = $journalColumnsQuery->fetch_assoc()) $journalColumns[] = $column['Field'];
        $journalColumnsQuery->free();
    }
    $submissionTypeColumn = in_array('submission_type', $journalColumns, true) ? 'j.submission_type' : "'weekly'";
    $pdfFileColumn = in_array('pdf_file', $journalColumns, true) ? 'j.pdf_file' : 'NULL';
    $journalsQuery = $conn->query("
        SELECT j.id, j.student_pk, j.title, j.content, j.status, j.adviser_remarks, j.submitted_at,
               {$submissionTypeColumn} AS submission_type, {$pdfFileColumn} AS pdf_file
        FROM journal_submissions j
        ORDER BY j.submitted_at DESC
    ");

    if ($journalsQuery) {
        while ($jnl = $journalsQuery->fetch_assoc()) {
            $journalText = trim(preg_replace('/\s+/', ' ', strip_tags((string)$jnl['content'])));
            if ($journalText === '') $journalText = 'Contains formatted content or images.';
            if (mb_strlen($journalText) > 280) $journalText = mb_substr($journalText, 0, 277) . '...';
            $statusLabels = ['pending' => 'Pending', 'reviewed' => 'Reviewed', 'returned' => 'Returned'];
            $submittedAt = strtotime((string)$jnl['submitted_at']);
            $studentJournalsByStudentId[$jnl['student_pk']][] = [
                'id' => (int)$jnl['id'],
                'title' => $jnl['title'],
                'submission_type' => $jnl['submission_type'] === 'final' ? 'Final Compilation' : 'Weekly Journal',
                'submitted_at' => $submittedAt ? date('M j, Y g:i A', $submittedAt) : (string)$jnl['submitted_at'],
                'journal_entry' => $journalText,
                'status' => $statusLabels[$jnl['status']] ?? ucfirst((string)$jnl['status']),
                'feedback' => $jnl['adviser_remarks'],
                'has_pdf' => !empty($jnl['pdf_file'])
            ];
        }
        $journalsQuery->free();
    }

 

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load student details: " . $exception->getMessage());

    $studentProgressPageError = "Company assignments and working hours could not be loaded right now.";

}

 
