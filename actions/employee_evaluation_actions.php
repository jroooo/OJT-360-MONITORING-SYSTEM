<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   SAVE EVALUATION ACTION

========================================================= */

 

if (

    $_SERVER["REQUEST_METHOD"] === "POST" &&

    ($_POST["evaluation_action"] ?? "") === "save"

) {

    $evaluationFlash = ["type" => "error", "message" => "Unable to save the evaluation."];

    $evaluationCsrf = $_POST["evaluations_csrf"] ?? "";

    $evaluationStudentValue = $_POST["student_db_id"] ?? null;

    $evaluationStudentId = is_string($evaluationStudentValue) || is_int($evaluationStudentValue)

        ? filter_var(

            $evaluationStudentValue,

            FILTER_VALIDATE_INT,

            ["options" => ["min_range" => 1]]

        )

        : false;

    $evaluationRecordValue = $_POST["evaluation_id"] ?? "";

    $evaluationRecordId = $evaluationRecordValue === ""

        ? null

        : (

            is_string($evaluationRecordValue) || is_int($evaluationRecordValue)

                ? filter_var(

                    $evaluationRecordValue,

                    FILTER_VALIDATE_INT,

                    ["options" => ["min_range" => 1]]

                )

                : false

        );

    $evaluationDate = $_POST["evaluation_date"] ?? "";

    $overallPerformance = $_POST["overall_performance"] ?? "";

    $evaluationStatus = $_POST["status"] ?? "";

    $recommendation = $_POST["recommendation"] ?? "";

    $strengths = $_POST["strengths"] ?? "";

    $areasForImprovement = $_POST["areas_for_improvement"] ?? "";

    $comments = $_POST["comments"] ?? "";

    $postedScores = $_POST["scores"] ?? [];

    $scoreFields = [

        "attendance_punctuality",

        "quality_of_work",

        "productivity",

        "communication",

        "teamwork",

        "initiative",

        "professionalism",

        "adaptability",

        "problem_solving",

        "technical_skills"

    ];

    $performanceOptions = [

        "Outstanding",

        "Very Good",

        "Satisfactory",

        "Needs Improvement",

        "Unsatisfactory"

    ];

    $evaluationRatings = [];

 

    if (

        !is_string($evaluationCsrf) ||

        !hash_equals($_SESSION["employee_evaluations_csrf"], $evaluationCsrf)

    ) {

        $evaluationFlash["message"] = "Your security token expired. Reload the page and try again.";

    } elseif (

        $evaluationStudentId === false ||

        $evaluationRecordId === false ||

        !is_string($evaluationDate) ||

        !is_string($overallPerformance) ||

        !in_array($overallPerformance, $performanceOptions, true) ||

        !is_string($evaluationStatus) ||

        !in_array($evaluationStatus, ["Draft", "Completed"], true) ||

        !is_string($recommendation) ||

        strlen($recommendation) > 100 ||

        !is_string($strengths) ||

        strlen($strengths) > 5000 ||

        !is_string($areasForImprovement) ||

        strlen($areasForImprovement) > 5000 ||

        !is_string($comments) ||

        strlen($comments) > 5000 ||

        !is_array($postedScores)

    ) {

        $evaluationFlash["message"] = "Enter valid evaluation details and select a rating from 1 to 5 for every criterion.";

    } else {

        $parsedEvaluationDate = DateTime::createFromFormat("!Y-m-d", $evaluationDate);

        $dateErrors = DateTime::getLastErrors();

        $validEvaluationDate = $parsedEvaluationDate !== false &&

            $parsedEvaluationDate->format("Y-m-d") === $evaluationDate &&

            ($dateErrors === false || ($dateErrors["warning_count"] === 0 && $dateErrors["error_count"] === 0));

 

        foreach ($scoreFields as $scoreField) {

            $scoreValue = $postedScores[$scoreField] ?? null;

            $rating = is_string($scoreValue) || is_int($scoreValue)

                ? filter_var($scoreValue, FILTER_VALIDATE_INT)

                : false;

            if ($rating === false || $rating < 1 || $rating > 5) {

                $evaluationRatings = [];

                break;

            }

            $evaluationRatings[$scoreField] = $rating;

        }

 

        if (!$validEvaluationDate || count($evaluationRatings) !== count($scoreFields)) {

            $evaluationFlash["message"] = "Enter a valid evaluation date and select a rating from 1 to 5 for every criterion.";

        } else {

            try {

                $studentExistsStmt = $conn->prepare("SELECT id FROM students WHERE id = ? LIMIT 1");

            } catch (mysqli_sql_exception $exception) {

                $studentExistsStmt = false;

            }

 

            if ($studentExistsStmt === false) {

                error_log("Unable to prepare evaluation student check: " . ($conn->error ?: "database prepare failed"));

                $evaluationFlash["message"] = "Unable to save the evaluation right now. Please try again.";

            } else {

                $studentExistsStmt->bind_param("i", $evaluationStudentId);

                try {

                    $studentCheckSucceeded = $studentExistsStmt->execute();

                    $studentExists = $studentCheckSucceeded && $studentExistsStmt->get_result()->num_rows === 1;

                } catch (mysqli_sql_exception $exception) {

                    $studentCheckSucceeded = false;

                    $studentExists = false;

                }

                $studentExistsStmt->close();

 

                if (!$studentCheckSucceeded) {

                    error_log("Unable to verify evaluation student: " . ($conn->error ?: "database query failed"));

                    $evaluationFlash["message"] = "Unable to save the evaluation right now. Please try again.";

                } elseif (!$studentExists) {

                    $evaluationFlash["message"] = "The selected student record could not be found.";

                } else {

                    $attendancePunctuality = $evaluationRatings["attendance_punctuality"];

                    $qualityOfWork = $evaluationRatings["quality_of_work"];

                    $productivity = $evaluationRatings["productivity"];

                    $communication = $evaluationRatings["communication"];

                    $teamwork = $evaluationRatings["teamwork"];

                    $initiative = $evaluationRatings["initiative"];

                    $professionalism = $evaluationRatings["professionalism"];

                    $adaptability = $evaluationRatings["adaptability"];

                    $problemSolving = $evaluationRatings["problem_solving"];

                    $technicalSkills = $evaluationRatings["technical_skills"];

                    $overallRating = round(array_sum($evaluationRatings) / count($evaluationRatings), 2);

                    $evaluatorId = (int)$employee["id"];

 

                    if ($evaluationRecordId !== null) {

                        try {

                            $draftCheck = $conn->prepare(

                                "SELECT id

                                FROM intern_evaluations

                                WHERE id = ? AND student_db_id = ? AND status = 'Draft'

                                LIMIT 1"

                            );

                        } catch (mysqli_sql_exception $exception) {

                            $draftCheck = false;

                        }

 

                        if ($draftCheck === false) {

                            error_log("Unable to prepare evaluation draft check: " . ($conn->error ?: "database prepare failed"));

                            $evaluationFlash["message"] = "Unable to update the draft right now. Please try again.";

                        } else {

                            $draftCheck->bind_param("ii", $evaluationRecordId, $evaluationStudentId);

                            try {

                                $draftCheckSucceeded = $draftCheck->execute();

                                $draftExists = $draftCheckSucceeded && $draftCheck->get_result()->num_rows === 1;

                            } catch (mysqli_sql_exception $exception) {

                                $draftCheckSucceeded = false;

                                $draftExists = false;

                            }

                            $draftCheck->close();

 

                            if (!$draftCheckSucceeded) {

                                error_log("Unable to verify evaluation draft: " . ($conn->error ?: "database query failed"));

                                $evaluationFlash["message"] = "Unable to update the draft right now. Please try again.";

                            } elseif (!$draftExists) {

                                $evaluationFlash["message"] = "This draft is no longer available to edit. Refresh the page and try again.";

                            } else {

                                try {

                                    $updateEvaluation = $conn->prepare(

                                        "UPDATE intern_evaluations SET

                                            evaluation_date = ?,

                                            attendance_punctuality = ?,

                                            quality_of_work = ?,

                                            productivity = ?,

                                            communication = ?,

                                            teamwork = ?,

                                            initiative = ?,

                                            professionalism = ?,

                                            adaptability = ?,

                                            problem_solving = ?,

                                            technical_skills = ?,

                                            overall_rating = ?,

                                            overall_performance = ?,

                                            strengths = ?,

                                            areas_for_improvement = ?,

                                            comments = ?,

                                            recommendation = ?,

                                            status = ?

                                        WHERE id = ? AND student_db_id = ? AND status = 'Draft'"

                                    );

                                } catch (mysqli_sql_exception $exception) {

                                    $updateEvaluation = false;

                                }

 

                                if ($updateEvaluation === false) {

                                    error_log("Unable to prepare evaluation draft update: " . ($conn->error ?: "database prepare failed"));

                                    $evaluationFlash["message"] = "Unable to update the draft right now. Please try again.";

                                } else {

                                    $updateEvaluation->bind_param(

                                        "siiiiiiiiiidssssssii",

                                        $evaluationDate,

                                        $attendancePunctuality,

                                        $qualityOfWork,

                                        $productivity,

                                        $communication,

                                        $teamwork,

                                        $initiative,

                                        $professionalism,

                                        $adaptability,

                                        $problemSolving,

                                        $technicalSkills,

                                        $overallRating,

                                        $overallPerformance,

                                        $strengths,

                                        $areasForImprovement,

                                        $comments,

                                        $recommendation,

                                        $evaluationStatus,

                                        $evaluationRecordId,

                                        $evaluationStudentId

                                    );

                                    try {

                                        $evaluationSaved = $updateEvaluation->execute();

                                    } catch (mysqli_sql_exception $exception) {

                                        $evaluationSaved = false;

                                    }

 

                                    if ($evaluationSaved) {

                                        $evaluationFlash = [

                                            "type" => "success",

                                            "message" => $evaluationStatus === "Completed"

                                                ? "Draft marked as completed successfully."

                                                : "Draft updated successfully."

                                        ];

                                    } else {

                                        error_log("Unable to update evaluation draft: " . ($updateEvaluation->error ?: "database update failed"));

                                        $evaluationFlash["message"] = "Unable to update the draft right now. Please try again.";

                                    }

                                    $updateEvaluation->close();

                                }

                            }

                        }

                    } else {

                        try {

                            $insertEvaluation = $conn->prepare(

                                "INSERT INTO intern_evaluations (

                                    student_db_id,

                                    evaluator_id,

                                    evaluation_date,

                                    attendance_punctuality,

                                    quality_of_work,

                                    productivity,

                                    communication,

                                    teamwork,

                                    initiative,

                                    professionalism,

                                    adaptability,

                                    problem_solving,

                                    technical_skills,

                                    overall_rating,

                                    overall_performance,

                                    strengths,

                                    areas_for_improvement,

                                    comments,

                                    recommendation,

                                    status

                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"

                            );

                        } catch (mysqli_sql_exception $exception) {

                            $insertEvaluation = false;

                        }

 

                        if ($insertEvaluation === false) {

                            error_log("Unable to prepare evaluation insert: " . ($conn->error ?: "database prepare failed"));

                            $evaluationFlash["message"] = "Unable to save the evaluation right now. Please try again.";

                        } else {

                            $insertEvaluation->bind_param(

                                "iisiiiiiiiiiidssssss",

                                $evaluationStudentId,

                                $evaluatorId,

                                $evaluationDate,

                                $attendancePunctuality,

                                $qualityOfWork,

                                $productivity,

                                $communication,

                                $teamwork,

                                $initiative,

                                $professionalism,

                                $adaptability,

                                $problemSolving,

                                $technicalSkills,

                                $overallRating,

                                $overallPerformance,

                                $strengths,

                                $areasForImprovement,

                                $comments,

                                $recommendation,

                                $evaluationStatus

                            );

                            try {

                                $evaluationSaved = $insertEvaluation->execute();

                            } catch (mysqli_sql_exception $exception) {

                                $evaluationSaved = false;

                            }

 

                            if ($evaluationSaved) {

                                $evaluationFlash = [

                                    "type" => "success",

                                    "message" => "Evaluation saved successfully."

                                ];

                            } else {

                                error_log("Unable to insert evaluation: " . ($insertEvaluation->error ?: "database insert failed"));

                                $evaluationFlash["message"] = "Unable to save the evaluation right now. Please try again.";

                            }

                            $insertEvaluation->close();

                        }

                    }

                }

            }

        }

    }

 

    $_SESSION["employee_evaluations_flash"] = $evaluationFlash;

    header("Location: dashboard(employee).php?section=evaluations", true, 303);

    exit;

}

 

