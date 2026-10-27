<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   FETCH EVALUATIONS

========================================================= */

 

$evaluationRecordsByStudentId = [];

$evaluationPageError = "";

$evaluationStats = [

    "count" => 0,

    "completed" => 0,

    "average" => null

];

try {

    $evaluationsQuery = $conn->query("

        SELECT

            evaluation.id,

            evaluation.student_db_id,

            evaluation.evaluation_date,

            evaluation.attendance_punctuality,

            evaluation.quality_of_work,

            evaluation.productivity,

            evaluation.communication,

            evaluation.teamwork,

            evaluation.initiative,

            evaluation.professionalism,

            evaluation.adaptability,

            evaluation.problem_solving,

            evaluation.technical_skills,

            evaluation.overall_rating,

            evaluation.overall_performance,

            evaluation.strengths,

            evaluation.areas_for_improvement,

            evaluation.comments,

            evaluation.recommendation,

            evaluation.status

        FROM intern_evaluations AS evaluation

        INNER JOIN (

            SELECT student_db_id, MAX(id) AS latest_id

            FROM intern_evaluations

            GROUP BY student_db_id

        ) AS latest ON latest.latest_id = evaluation.id

        ORDER BY evaluation.evaluation_date DESC, evaluation.id DESC

    ");

    $evaluationStatsQuery = $conn->query("

        SELECT

            COUNT(*) AS evaluation_count,

            SUM(status = 'Completed') AS completed_count,

            AVG(CASE WHEN status = 'Completed' THEN overall_rating END) AS average_rating

        FROM intern_evaluations

    ");

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load evaluations: " . $exception->getMessage());

    $evaluationsQuery = false;

    $evaluationStatsQuery = false;

}

 

if ($evaluationsQuery === false || $evaluationStatsQuery === false) {

    $evaluationPageError = "Unable to load evaluation records right now. Please check the intern_evaluations table and try again.";

} else {

    while ($evaluationRecord = $evaluationsQuery->fetch_assoc()) {

        $evaluationRecordsByStudentId[(int)$evaluationRecord["student_db_id"]] = $evaluationRecord;

    }

    $evaluationsQuery->free();

 

    $evaluationStatsRecord = $evaluationStatsQuery->fetch_assoc();

    $evaluationStats = [

        "count" => (int)($evaluationStatsRecord["evaluation_count"] ?? 0),

        "completed" => (int)($evaluationStatsRecord["completed_count"] ?? 0),

        "average" => $evaluationStatsRecord["average_rating"] !== null

            ? (float)$evaluationStatsRecord["average_rating"]

            : null

    ];

    $evaluationStatsQuery->free();

}

$evaluationFlash = $_SESSION["employee_evaluations_flash"] ?? null;

unset($_SESSION["employee_evaluations_flash"]);

$evaluationsCsrf = htmlspecialchars($_SESSION["employee_evaluations_csrf"], ENT_QUOTES, "UTF-8");

 

