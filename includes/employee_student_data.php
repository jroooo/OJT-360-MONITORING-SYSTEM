<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   FETCH STUDENT RECORDS

========================================================= */

 

$studentRecords = [];

$internPageError = "";

try {

    $studentsQuery = $conn->query("

        SELECT

            id,

            first_name,

            middle_name,

            last_name,

            course,

            mobile_number,

            school_name,

            student_id,

            institutional_email,

            created_at

        FROM students

        ORDER BY created_at DESC, id DESC

    ");

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load registered students: " . $exception->getMessage());

    $studentsQuery = false;

}

 

if ($studentsQuery === false) {

    $internPageError = "Unable to load student records right now. Please try again later.";

} else {

    while ($studentRecord = $studentsQuery->fetch_assoc()) {

        $studentRecords[] = $studentRecord;

    }

    $studentsQuery->free();

}

 

$studentCourseCount = count(array_unique(array_column($studentRecords, "course")));

$studentSchoolCount = count(array_unique(array_column($studentRecords, "school_name")));

$internsFlash = $_SESSION["employee_interns_flash"] ?? null;

unset($_SESSION["employee_interns_flash"]);

$studentsCsrf = htmlspecialchars($_SESSION["employee_students_csrf"], ENT_QUOTES, "UTF-8");

 

