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

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["company_action"] ?? "") !== ""
) {
    $companyAction = $_POST["company_action"];
    $companyCsrf = $_POST["companies_csrf"] ?? "";
    $companyFlash = ["type" => "error", "message" => "Invalid company action."];

    if (
        !is_string($companyCsrf) ||
        !hash_equals($_SESSION["employee_companies_csrf"], $companyCsrf)
    ) {
        $companyFlash["message"] = "Your security token expired. Reload the page and try again.";
    } elseif ($companyAction === "create") {
        $readCompanyField = static function (string $key): string {
            $value = $_POST[$key] ?? "";
            return is_string($value) ? trim($value) : "";
        };

        $companyName = $readCompanyField("company_name");
        $companyCode = $readCompanyField("company_code");
        $contactPerson = $readCompanyField("contact_person");
        $contactNumber = $readCompanyField("contact_number");
        $companyEmail = $readCompanyField("email");
        $companyAddress = $readCompanyField("address");
        $companyBuilding = $readCompanyField("building");
        $companyDepartment = $readCompanyField("department");
        $companyPosition = $readCompanyField("position");
        $companyStatus = $readCompanyField("status");

        $validCompany = $companyName !== "" &&
            strlen($companyName) <= 150 &&
            strlen($companyCode) <= 50 &&
            $contactPerson !== "" &&
            strlen($contactPerson) <= 100 &&
            $contactNumber !== "" &&
            strlen($contactNumber) <= 20 &&
            filter_var($companyEmail, FILTER_VALIDATE_EMAIL) &&
            strlen($companyEmail) <= 100 &&
            $companyAddress !== "" &&
            strlen($companyAddress) <= 255 &&
            strlen($companyBuilding) <= 100 &&
            strlen($companyDepartment) <= 100 &&
            strlen($companyPosition) <= 100 &&
            in_array($companyStatus, ["active", "inactive"], true);

        if (!$validCompany) {
            $companyFlash["message"] = "Enter valid company details and select an available status.";
        } else {
            $companyCodeValue = $companyCode === "" ? null : $companyCode;
            try {
                $insertCompany = $conn->prepare(
                    "INSERT INTO company (
                        company_name,
                        company_code,
                        contact_person,
                        contact_number,
                        email,
                        address,
                        building,
                        department,
                        position,
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
            } catch (mysqli_sql_exception $exception) {
                $insertCompany = false;
            }

            if ($insertCompany === false) {
                error_log("Unable to prepare company insert: " . ($conn->error ?: "database prepare failed"));
                $companyFlash["message"] = "Unable to add the company right now. Please try again.";
            } else {
                $insertCompany->bind_param(
                    "ssssssssss",
                    $companyName,
                    $companyCodeValue,
                    $contactPerson,
                    $contactNumber,
                    $companyEmail,
                    $companyAddress,
                    $companyBuilding,
                    $companyDepartment,
                    $companyPosition,
                    $companyStatus
                );

                try {
                    $companyInserted = $insertCompany->execute();
                } catch (mysqli_sql_exception $exception) {
                    $companyInserted = false;
                }

                if ($companyInserted) {
                    $companyFlash = ["type" => "success", "message" => "Company added successfully."];
                } elseif ($insertCompany->errno === 1062) {
                    $companyFlash["message"] = "That company code is already in use.";
                } else {
                    error_log("Unable to insert company: " . ($insertCompany->error ?: "database insert failed"));
                    $companyFlash["message"] = "Unable to add the company right now. Please try again.";
                }

                $insertCompany->close();
            }
        }
    } elseif ($companyAction === "set_status") {
        $companyDatabaseId = filter_var(
            $_POST["company_database_id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );
        $requestedCompanyStatus = $_POST["status"] ?? "";

        if (
            $companyDatabaseId === false ||
            !is_string($requestedCompanyStatus) ||
            !in_array($requestedCompanyStatus, ["active", "inactive"], true)
        ) {
            $companyFlash["message"] = "The selected company or status is invalid.";
        } else {
            try {
                $updateCompanyStatus = $conn->prepare(
                    "UPDATE company SET status = ? WHERE id = ?"
                );
            } catch (mysqli_sql_exception $exception) {
                $updateCompanyStatus = false;
            }

            if ($updateCompanyStatus === false) {
                error_log("Unable to prepare company status update: " . ($conn->error ?: "database prepare failed"));
                $companyFlash["message"] = "Unable to update the company status right now. Please try again.";
            } else {
                $updateCompanyStatus->bind_param("si", $requestedCompanyStatus, $companyDatabaseId);

                try {
                    $companyUpdated = $updateCompanyStatus->execute();
                } catch (mysqli_sql_exception $exception) {
                    $companyUpdated = false;
                }

                if ($companyUpdated) {
                    $companyFlash = [
                        "type" => "success",
                        "message" => $requestedCompanyStatus === "active"
                            ? "Company activated successfully."
                            : "Company marked inactive successfully."
                    ];
                } else {
                    error_log("Unable to update company status: " . ($updateCompanyStatus->error ?: "database update failed"));
                    $companyFlash["message"] = "Unable to update the company status right now. Please try again.";
                }

                $updateCompanyStatus->close();
            }
        }
    }

    $_SESSION["employee_companies_flash"] = $companyFlash;
    header("Location: Employee-Dashboard", true, 303);
    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["student_action"] ?? "") === "delete"
) {
    $csrfToken = $_POST["students_csrf"] ?? "";
    $studentDatabaseId = filter_var(
        $_POST["student_database_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION["employee_students_csrf"], $csrfToken)
    ) {
        $_SESSION["employee_interns_flash"] = [
            "type" => "error",
            "message" => "Your security token expired. Reload the page and try again."
        ];
    } elseif ($studentDatabaseId === false) {
        $_SESSION["employee_interns_flash"] = [
            "type" => "error",
            "message" => "The selected student record is invalid."
        ];
    } else {
        try {
            $deleteStudent = $conn->prepare("DELETE FROM students WHERE id = ?");
        } catch (mysqli_sql_exception $exception) {
            $deleteStudent = false;
        }

        if ($deleteStudent === false) {
            error_log("Unable to prepare student deletion: " . ($conn->error ?: "database prepare failed"));
            $_SESSION["employee_interns_flash"] = [
                "type" => "error",
                "message" => "Unable to remove the student right now. Please try again."
            ];
        } else {
            $deleteStudent->bind_param("i", $studentDatabaseId);

            try {
                $studentDeleted = $deleteStudent->execute();
            } catch (mysqli_sql_exception $exception) {
                $studentDeleted = false;
            }

            if ($studentDeleted && $deleteStudent->affected_rows === 1) {
                $_SESSION["employee_interns_flash"] = [
                    "type" => "success",
                    "message" => "Student record and account removed successfully."
                ];
            } elseif ($studentDeleted) {
                $_SESSION["employee_interns_flash"] = [
                    "type" => "error",
                    "message" => "The student record no longer exists."
                ];
            } else {
                error_log("Unable to delete student record: " . ($deleteStudent->error ?: "database delete failed"));
                $_SESSION["employee_interns_flash"] = [
                    "type" => "error",
                    "message" => "Unable to remove the student right now. Please try again."
                ];
            }

            $deleteStudent->close();
        }
    }

    header("Location: Employee-Dashboard?section=interns", true, 303);
    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["student_action"] ?? "") === "create"
) {
    $csrfToken = $_POST["students_csrf"] ?? "";
    $readStudentField = static function (string $key): string {
        $value = $_POST[$key] ?? "";
        return is_string($value) ? trim($value) : "";
    };
    $firstName = $readStudentField("first_name");
    $middleName = $readStudentField("middle_name");
    $lastName = $readStudentField("last_name");
    $course = $readStudentField("course");
    $mobileNumber = $readStudentField("mobile_number");
    $schoolName = "Leyte Normal University";
    $completeAddress = $readStudentField("complete_address");
    $studentId = $readStudentField("student_id");
    $institutionalEmail = $readStudentField("institutional_email");
    $studentPassword = $_POST["student_password"] ?? "";

    $validCsrf = is_string($csrfToken) &&
        hash_equals($_SESSION["employee_students_csrf"], $csrfToken);
    $validFields = is_string($studentPassword) &&
        $firstName !== "" &&
        strlen($firstName) <= 50 &&
        strlen($middleName) <= 50 &&
        $lastName !== "" &&
        strlen($lastName) <= 50 &&
        $course !== "" &&
        strlen($course) <= 100 &&
        $mobileNumber !== "" &&
        strlen($mobileNumber) <= 20 &&
        $schoolName !== "" &&
        strlen($schoolName) <= 150 &&
        $completeAddress !== "" &&
        $studentId !== "" &&
        strlen($studentId) <= 50 &&
        filter_var($institutionalEmail, FILTER_VALIDATE_EMAIL) &&
        strlen($institutionalEmail) <= 100 &&
        strlen($studentPassword) >= 8;

    if (!$validCsrf) {
        $_SESSION["employee_interns_flash"] = [
            "type" => "error",
            "message" => "Your security token expired. Reload the page and try again."
        ];
    } elseif (!$validFields) {
        $_SESSION["employee_interns_flash"] = [
            "type" => "error",
            "message" => "Enter all required student details, a valid email, and a password of at least 8 characters."
        ];
    } else {
        try {
            $duplicateCheck = $conn->prepare(
                "SELECT id
                FROM students
                WHERE student_id = ? OR institutional_email = ?
                LIMIT 1"
            );
        } catch (mysqli_sql_exception $exception) {
            $duplicateCheck = false;
        }

        if ($duplicateCheck === false) {
            error_log("Unable to prepare student duplicate check: " . ($conn->error ?: "database prepare failed"));
            $_SESSION["employee_interns_flash"] = [
                "type" => "error",
                "message" => "Unable to add the student right now. Please try again."
            ];
        } else {
            $duplicateCheck->bind_param("ss", $studentId, $institutionalEmail);
            try {
                $duplicateCheckSucceeded = $duplicateCheck->execute();
                $duplicateRecord = $duplicateCheckSucceeded
                    ? $duplicateCheck->get_result()->fetch_assoc()
                    : null;
            } catch (mysqli_sql_exception $exception) {
                $duplicateCheckSucceeded = false;
                $duplicateRecord = null;
            }

            $duplicateCheck->close();

            if (!$duplicateCheckSucceeded) {
                error_log("Unable to check for duplicate student account: " . ($conn->error ?: "database query failed"));
                $_SESSION["employee_interns_flash"] = [
                    "type" => "error",
                    "message" => "Unable to add the student right now. Please try again."
                ];
            } elseif ($duplicateRecord) {
                $_SESSION["employee_interns_flash"] = [
                    "type" => "error",
                    "message" => "That student ID or institutional email is already registered."
                ];
            } else {
                $studentPasswordHash = password_hash($studentPassword, PASSWORD_DEFAULT);
                try {
                    $insertStudent = $conn->prepare(
                        "INSERT INTO students (
                            first_name,
                            middle_name,
                            last_name,
                            course,
                            mobile_number,
                            school_name,
                            complete_address,
                            student_id,
                            institutional_email,
                            password_hash
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                } catch (mysqli_sql_exception $exception) {
                    $insertStudent = false;
                }

                if ($studentPasswordHash === false || $insertStudent === false) {
                    error_log("Unable to prepare student registration: " . ($conn->error ?: "database prepare failed"));
                    $_SESSION["employee_interns_flash"] = [
                        "type" => "error",
                        "message" => "Unable to add the student right now. Please try again."
                    ];
                } else {
                    $insertStudent->bind_param(
                        "ssssssssss",
                        $firstName,
                        $middleName,
                        $lastName,
                        $course,
                        $mobileNumber,
                        $schoolName,
                        $completeAddress,
                        $studentId,
                        $institutionalEmail,
                        $studentPasswordHash
                    );

                    try {
                        $studentInserted = $insertStudent->execute();
                    } catch (mysqli_sql_exception $exception) {
                        $studentInserted = false;
                    }

                    if ($studentInserted) {
                        $_SESSION["employee_interns_flash"] = [
                            "type" => "success",
                            "message" => "Student account added successfully."
                        ];
                    } elseif ($insertStudent->errno === 1062) {
                        $_SESSION["employee_interns_flash"] = [
                            "type" => "error",
                            "message" => "That student ID or institutional email is already registered."
                        ];
                    } else {
                        error_log("Unable to insert student account: " . ($insertStudent->error ?: "database insert failed"));
                        $_SESSION["employee_interns_flash"] = [
                            "type" => "error",
                            "message" => "Unable to add the student right now. Please try again."
                        ];
                    }

                    $insertStudent->close();
                }
            }
        }
    }

    header("Location: Employee-Dashboard?section=interns", true, 303);
    exit;
}

/* =========================================================
   EMPLOYEE SETTINGS ACTIONS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["settings_action"] ?? "") === "change_password"
) {
    $csrfToken = $_POST["settings_csrf"] ?? "";

    if (
        !is_string($csrfToken) ||
        !isset($_SESSION["employee_settings_csrf"]) ||
        !hash_equals($_SESSION["employee_settings_csrf"], $csrfToken)
    ) {
        $_SESSION["employee_settings_flash"] = [
            "type" => "error",
            "message" => "Your security token expired. Reload the page and try again."
        ];
    } else {
        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        if (
            !is_string($currentPassword) ||
            !is_string($newPassword) ||
            !is_string($confirmPassword) ||
            $currentPassword === "" ||
            strlen($newPassword) < 8 ||
            !hash_equals($newPassword, $confirmPassword)
        ) {
            $_SESSION["employee_settings_flash"] = [
                "type" => "error",
                "message" => "Enter your current password, choose a new password of at least 8 characters, and confirm it."
            ];
        } else {
            $passwordStmt = $conn->prepare(
                "SELECT password_hash
                FROM employees
                WHERE employee_id = ?
                LIMIT 1"
            );

            if ($passwordStmt === false) {
                error_log("Unable to prepare employee password lookup: " . $conn->error);
                $_SESSION["employee_settings_flash"] = [
                    "type" => "error",
                    "message" => "Unable to change your password right now. Please try again."
                ];
            } else {
                $passwordStmt->bind_param("s", $_SESSION["employee_id"]);

                if (!$passwordStmt->execute()) {
                    error_log("Unable to load employee password: " . $passwordStmt->error);
                    $_SESSION["employee_settings_flash"] = [
                        "type" => "error",
                        "message" => "Unable to change your password right now. Please try again."
                    ];
                } else {
                    $passwordRecord = $passwordStmt->get_result()->fetch_assoc();

                    if (
                        !$passwordRecord ||
                        !password_verify($currentPassword, $passwordRecord["password_hash"])
                    ) {
                        $_SESSION["employee_settings_flash"] = [
                            "type" => "error",
                            "message" => "Your current password is incorrect."
                        ];
                    } else {
                        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                        if ($newPasswordHash === false) {
                            $_SESSION["employee_settings_flash"] = [
                                "type" => "error",
                                "message" => "Unable to change your password right now. Please try again."
                            ];
                        } else {
                            $updatePasswordStmt = $conn->prepare(
                                "UPDATE employees
                                SET password_hash = ?
                                WHERE employee_id = ?"
                            );

                            if ($updatePasswordStmt === false) {
                                error_log("Unable to prepare employee password update: " . $conn->error);
                                $_SESSION["employee_settings_flash"] = [
                                    "type" => "error",
                                    "message" => "Unable to change your password right now. Please try again."
                                ];
                            } else {
                                $updatePasswordStmt->bind_param(
                                    "ss",
                                    $newPasswordHash,
                                    $_SESSION["employee_id"]
                                );

                                if ($updatePasswordStmt->execute()) {
                                    $_SESSION["employee_settings_flash"] = [
                                        "type" => "success",
                                        "message" => "Your password was changed successfully."
                                    ];
                                } else {
                                    error_log("Unable to update employee password: " . $updatePasswordStmt->error);
                                    $_SESSION["employee_settings_flash"] = [
                                        "type" => "error",
                                        "message" => "Unable to change your password right now. Please try again."
                                    ];
                                }

                                $updatePasswordStmt->close();
                            }
                        }
                    }
                }

                $passwordStmt->close();
            }
        }
    }

    $conn->close();
    header("Location: Employee%20Dashboard?section=settings", true, 303);
    exit;
}

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
    header("Location: Employee-Dashboard?section=evaluations", true, 303);
    exit;
}

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

$studentProgressByStudentId = [];
$studentProgressPageError = "";
try {
    $studentProgressQuery = $conn->query("
        SELECT
            student.student_id,
            portal_user.id AS portal_user_id,
            company.name AS company_name,
            attendance_total.completed_minutes
        FROM students AS student
        LEFT JOIN users AS portal_user
            ON portal_user.student_no = student.student_id
            AND portal_user.role = 'student'
        LEFT JOIN internships AS internship
            ON internship.user_id = portal_user.id
            AND internship.id = (
                SELECT MAX(latest_internship.id)
                FROM internships AS latest_internship
                WHERE latest_internship.user_id = portal_user.id
            )
        LEFT JOIN companies AS company
            ON company.id = internship.company_id
            LEFT JOIN (
                SELECT student_pk, SUM(duration_minutes) AS completed_minutes
                FROM attendance
                GROUP BY student_pk
            ) AS attendance_total
                ON attendance_total.student_pk = student.id
    ");
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load student attendance progress: " . $exception->getMessage());
    $studentProgressQuery = false;
}

if ($studentProgressQuery === false) {
    $studentProgressPageError = "Company assignments and working hours could not be loaded right now.";
} else {
    while ($studentProgressRecord = $studentProgressQuery->fetch_assoc()) {
        $studentProgressByStudentId[$studentProgressRecord["student_id"]] = [
            "portalLinked" => $studentProgressRecord["portal_user_id"] !== null,
            "company" => $studentProgressRecord["company_name"],
            "completedHours" => $studentProgressRecord["completed_minutes"] !== null
                ? (float)$studentProgressRecord["completed_minutes"] / 60
                : 0.0
        ];
    }
    $studentProgressQuery->free();
}

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

$companyRecords = [];
$companyPageError = "";
try {
    $companiesQuery = $conn->query("
        SELECT
            id,
            company_name,
            company_code,
            contact_person,
            contact_number,
            email,
            address,
            building,
            department,
            position,
            status,
            created_at
        FROM company
        ORDER BY created_at DESC, id DESC
    ");
} catch (mysqli_sql_exception $exception) {
    error_log("Unable to load companies: " . $exception->getMessage());
    $companiesQuery = false;
}

if ($companiesQuery === false) {
    $companyPageError = "Unable to load company records right now. Please try again later.";
} else {
    while ($companyRecord = $companiesQuery->fetch_assoc()) {
        $companyRecords[] = $companyRecord;
    }
    $companiesQuery->free();
}

$companiesFlash = $_SESSION["employee_companies_flash"] ?? null;
unset($_SESSION["employee_companies_flash"]);
$companiesCsrf = htmlspecialchars($_SESSION["employee_companies_csrf"], ENT_QUOTES, "UTF-8");

if (!isset($_SESSION["employee_settings_csrf"])) {
    $_SESSION["employee_settings_csrf"] = bin2hex(random_bytes(32));
}
$settingsCsrf = htmlspecialchars($_SESSION["employee_settings_csrf"], ENT_QUOTES, "UTF-8");
$settingsFlash = $_SESSION["employee_settings_flash"] ?? null;
unset($_SESSION["employee_settings_flash"]);
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
    <aside class="sidebar compact" id="sidebar">
        <div class="brand">
            <div class="brand-mark">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="brand-text">OJT360</div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar" title="Close sidebar">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-link active" data-page="overview" aria-label="Overview" title="Overview">
                <span class="nav-icon">▦</span>
                <span>Overview</span>
            </button>

            <button class="nav-link" data-page="interns" aria-label="Interns" title="Interns">
                <span class="nav-icon">♙</span>
                <span>Interns</span>
            </button>

            <button class="nav-link" data-page="evaluations" aria-label="Evaluations" title="Evaluations">
                <span class="nav-icon">▤</span>
                <span>Evaluations</span>
            </button>

            <button class="nav-link" data-page="messages" aria-label="Messages" title="Messages">
                <span class="nav-icon">➤</span>
                <span>Messages</span>
            </button>

            <button class="nav-link" data-page="reports" aria-label="Reports and Analytics" title="Reports and Analytics">
                <span class="nav-icon">▥</span>
                <span>Reports &amp; Analytics</span>
            </button>

        </nav>

        <div class="sidebar-bottom">
            <button class="nav-link help-link" data-page="support" aria-label="Help and Support" title="Help and Support">
                <span class="nav-icon">?</span>
                <span>Help &amp; Support</span>
            </button>

            <button class="nav-link settings-bottom-link" data-page="settings" aria-label="Settings" title="Settings">
                <span class="nav-icon">⚙</span>
                <span>Settings</span>
            </button>

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

            <!-- =================================================
                 OVERVIEW
            ================================================== -->
            <section class="page active" id="page-overview">

                <div class="hero">
                    <div class="eyebrow">Employee Workspace</div>
                    <h1>Manage companies and registered students.</h1>
                    <p>Review student profiles registered in the OJT360 database and manage partner companies.</p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">A</div>
                        <div>
                            <div class="stat-label">Active Companies</div>
                            <div class="stat-value" id="activeCompanyCount"><?= count(array_filter($companyRecords, static fn(array $companyRecord): bool => $companyRecord["status"] === "active")) ?></div>
                            <div class="stat-sub">Available for assignment</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">A</div>
                        <div>
                            <div class="stat-label">Inactive Companies</div>
                            <div class="stat-value" id="inactiveCompanyCount"><?= count(array_filter($companyRecords, static fn(array $companyRecord): bool => $companyRecord["status"] === "inactive")) ?></div>
                            <div class="stat-sub">Not currently accepting placements</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Registered Students</div>
                            <div class="stat-value"><?= count($studentRecords) ?></div>
                            <div class="stat-sub">From the students database</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">U</div>
                        <div>
                            <div class="stat-label">Schools</div>
                            <div class="stat-value"><?= $studentSchoolCount ?></div>
                            <div class="stat-sub">Unique schools represented</div>
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

                    <?php if ($companiesFlash): ?>
                        <div class="interns-flash <?= ($companiesFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($companiesFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">
                            <?= htmlspecialchars($companiesFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($companyPageError !== ""): ?>
                        <div class="interns-flash error" role="alert">
                            <?= htmlspecialchars($companyPageError, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <div class="toolbar">
                        <input class="search" id="companySearch" placeholder="Search companies...">

                        <div class="filter-group">
                            <button class="filter active" data-company-filter="all">All</button>
                            <button class="filter" data-company-filter="active">Active</button>
                            <button class="filter" data-company-filter="inactive">Inactive</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>
                                    <th>Contact Number</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="companyTable">
                                <?php if ($companyPageError !== ""): ?>
                                    <tr><td colspan="7"><div class="empty">Company records could not be loaded.</div></td></tr>
                                <?php elseif (!$companyRecords): ?>
                                    <tr><td colspan="7"><div class="empty">No companies found.</div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($companyRecords as $companyRecord): ?>
                                        <tr>
                                            <td>
                                                <div class="person">
                                                    <div class="mini-avatar"><?= htmlspecialchars(strtoupper(substr($companyRecord["company_name"], 0, 2)), ENT_QUOTES, "UTF-8") ?></div>
                                                    <div>
                                                        <div class="person-name"><?= htmlspecialchars($companyRecord["company_name"], ENT_QUOTES, "UTF-8") ?></div>
                                                        <div class="person-sub"><?= htmlspecialchars($companyRecord["company_code"] ?? "No company code", ENT_QUOTES, "UTF-8") ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($companyRecord["contact_person"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($companyRecord["email"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($companyRecord["contact_number"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($companyRecord["address"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td>
                                                <span class="badge <?= $companyRecord["status"] === "active" ? "green" : "yellow" ?>">
                                                    <?= $companyRecord["status"] === "active" ? "Active" : "Inactive" ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-row">
                                                    <button class="small-btn company-view-button" type="button" data-company-id="<?= (int)$companyRecord["id"] ?>">View</button>
                                                    <form class="company-status-form" method="post" action="Employee-Dashboard">
                                                        <input type="hidden" name="company_action" value="set_status">
                                                        <input type="hidden" name="companies_csrf" value="<?= $companiesCsrf ?>">
                                                        <input type="hidden" name="company_database_id" value="<?= (int)$companyRecord["id"] ?>">
                                                        <input type="hidden" name="status" value="<?= $companyRecord["status"] === "active" ? "inactive" : "active" ?>">
                                                        <button class="small-btn" type="submit"><?= $companyRecord["status"] === "active" ? "Deactivate" : "Activate" ?></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Student Management</div>
                            <h2 class="section-title">Registered Students</h2>
                            <p class="section-sub">Student profiles from the same database used by the Interns and Evaluations pages.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Course</th>
                                    <th>Institutional Email</th>
                                    <th>Mobile Number</th>
                                    <th>School</th>
                                    <th>Working Hours</th>
                                </tr>
                            </thead>
                            <tbody id="assignmentTable">
                                <?php if ($internPageError !== ""): ?>
                                    <tr><td colspan="7"><div class="empty">Student records could not be loaded.</div></td></tr>
                                <?php elseif (!$studentRecords): ?>
                                    <tr><td colspan="7"><div class="empty">No registered students found.</div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($studentRecords as $studentRecord): ?>
                                        <?php
                                            $studentName = trim(
                                                $studentRecord["first_name"] . " " .
                                                ($studentRecord["middle_name"] !== "" ? $studentRecord["middle_name"] . " " : "") .
                                                $studentRecord["last_name"]
                                            );
                                            $studentInitials = strtoupper(
                                                substr($studentRecord["first_name"], 0, 1) .
                                                substr($studentRecord["last_name"], 0, 1)
                                            );
                                            $studentProgress = $studentProgressByStudentId[$studentRecord["student_id"]] ?? null;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="person">
                                                    <div class="mini-avatar"><?= htmlspecialchars($studentInitials, ENT_QUOTES, "UTF-8") ?></div>
                                                    <div class="person-name"><?= htmlspecialchars($studentName, ENT_QUOTES, "UTF-8") ?></div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($studentRecord["student_id"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["course"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["institutional_email"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["mobile_number"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["school_name"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td>
                                                <?php if ($studentProgressPageError !== ""): ?>
                                                    Unavailable
                                                <?php elseif ($studentProgress === null): ?>
                                                    Unavailable
                                                <?php else: ?>
                                                    <?= number_format($studentProgress["completedHours"], 1) ?> hrs
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
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
                        <h1>Intern Students</h1>
                        <p>Student profiles loaded from the registered students database.</p>
                    </div>

                    <button class="btn btn-primary" id="addInternBtn">+ Add Intern Student</button>
                </div>

                <?php if ($internsFlash): ?>
                    <div class="interns-flash <?= ($internsFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($internsFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">
                        <?= htmlspecialchars($internsFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <?php if ($internPageError !== ""): ?>
                    <div class="interns-flash error" role="alert">
                        <?= htmlspecialchars($internPageError, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon">T</div>
                        <div>
                            <div class="stat-label">Registered Students</div>
                            <div class="stat-value"><?= count($studentRecords) ?></div>
                            <div class="stat-sub">Records in the students table</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">A</div>
                        <div>
                            <div class="stat-label">Courses</div>
                            <div class="stat-value"><?= $studentCourseCount ?></div>
                            <div class="stat-sub">Unique courses represented</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon amber">U</div>
                        <div>
                            <div class="stat-label">Schools</div>
                            <div class="stat-value"><?= $studentSchoolCount ?></div>
                            <div class="stat-sub">Unique schools represented</div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Student Information</div>
                            <h2 class="section-title">Registered Students</h2>
                            <p class="section-sub">Profile and registration details from the students database.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Course</th>
                                    <th>Institutional Email</th>
                                    <th>Mobile Number</th>
                                    <th>School</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($internPageError !== ""): ?>
                                    <tr><td colspan="8"><div class="empty">Student records could not be loaded.</div></td></tr>
                                <?php elseif (!$studentRecords): ?>
                                    <tr><td colspan="8"><div class="empty">No registered students found.</div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($studentRecords as $studentRecord): ?>
                                        <?php
                                            $studentName = trim(
                                                $studentRecord["first_name"] . " " .
                                                ($studentRecord["middle_name"] !== "" ? $studentRecord["middle_name"] . " " : "") .
                                                $studentRecord["last_name"]
                                            );
                                            $studentInitials = strtoupper(
                                                substr($studentRecord["first_name"], 0, 1) .
                                                substr($studentRecord["last_name"], 0, 1)
                                            );
                                            $registeredAt = strtotime($studentRecord["created_at"]);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="person">
                                                    <div class="mini-avatar"><?= htmlspecialchars($studentInitials, ENT_QUOTES, "UTF-8") ?></div>
                                                    <div class="person-name"><?= htmlspecialchars($studentName, ENT_QUOTES, "UTF-8") ?></div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($studentRecord["student_id"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["course"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["institutional_email"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["mobile_number"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= htmlspecialchars($studentRecord["school_name"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td><?= $registeredAt === false ? "—" : date("M j, Y", $registeredAt) ?></td>
                                            <td>
                                                <form class="remove-student-form" method="post" action="Employee-Dashboard?section=interns" data-student-name="<?= htmlspecialchars($studentName, ENT_QUOTES, "UTF-8") ?>">
                                                    <input type="hidden" name="student_action" value="delete">
                                                    <input type="hidden" name="students_csrf" value="<?= $studentsCsrf ?>">
                                                    <input type="hidden" name="student_database_id" value="<?= (int)$studentRecord["id"] ?>">
                                                    <button class="small-btn danger" type="submit">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
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
                        <p>Review and record performance evaluations for registered students.</p>
                    </div>

                    <button class="btn btn-primary" id="startEvaluationBtn">Start evaluation</button>
                </div>

                <div class="section-card">
                    <?php if ($evaluationFlash && ($evaluationFlash["type"] ?? "") !== "success"): ?>
                        <div class="interns-flash error" role="alert">
                            <?= htmlspecialchars($evaluationFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($evaluationPageError !== ""): ?>
                        <div class="interns-flash error" role="alert">
                            <?= htmlspecialchars($evaluationPageError, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($studentProgressPageError !== ""): ?>
                        <div class="interns-flash error" role="alert">
                            <?= htmlspecialchars($studentProgressPageError, ENT_QUOTES, "UTF-8") ?>
                        </div>
                    <?php endif; ?>

                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Student Roster</div>
                            <h2 class="section-title">Registered students</h2>
                            <p class="section-sub">The latest saved evaluation and feedback are shown for each registered student.</p>
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
                            <p>Rate each performance criterion from 1 to 5, record feedback, then choose whether to save the evaluation as a draft or complete the review.</p>
                        </div>
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
                            <input class="search" id="conversationSearch" style="width:100%" placeholder="Search companies...">
                        </div>

                        <div id="companyConversationList" aria-live="polite"></div>
                    </div>

                    <div class="chat">
                        <div class="chat-head">
                            <div>
                                <div class="chat-name" id="chatName">Select a company</div>
                                <div class="chat-status" id="chatStatus">Company coordination</div>
                            </div>
                            <button class="small-btn">⋮</button>
                        </div>

                        <div class="chat-body" id="chatBody">
                            <div data-empty-chat="true" style="padding:24px;text-align:center;color:#8597a6;font-size:12px;">Choose a company to view its conversation.</div>
                        </div>

                        <div class="chat-input">
                            <button class="small-btn">＋</button>
                            <input id="messageInput" placeholder="Write a message..." disabled>
                            <button class="btn btn-primary" id="sendMessageBtn" type="button" disabled>Send</button>
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
                            <div class="stat-value"><?= $evaluationStats["average"] !== null ? number_format($evaluationStats["average"], 2) . " / 5" : "—" ?></div>
                            <div class="stat-sub"><?= $evaluationStats["count"] ?> evaluations · <?= $evaluationStats["completed"] ?> completed</div>
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

            <!-- =================================================
                 SETTINGS
            ================================================== -->
            <section class="page" id="page-settings">
                <div class="page-heading">
                    <div>
                        <div class="section-kicker">Preferences</div>
                        <h1>Settings<span class="settings-title-dot">.</span></h1>
                        <p>Personalize the portal, set your work schedule, and manage your account.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <section class="section-card settings-card">
                        <div class="settings-card-heading">
                            <div class="section-kicker">Appearance</div>
                            <h2 class="section-title">Theme</h2>
                        </div>

                        <div class="settings-themes" role="group" aria-label="Color theme">
                            <button class="settings-theme-card" type="button" data-settings-theme="light" aria-pressed="false">
                                <span class="settings-theme-preview light"><i></i><i></i><i></i></span>
                                <strong>☼ Light</strong>
                                <small>Bright and clean</small>
                            </button>
                            <button class="settings-theme-card" type="button" data-settings-theme="dark" aria-pressed="false">
                                <span class="settings-theme-preview dark"><i></i><i></i><i></i></span>
                                <strong>☾ Dark</strong>
                                <small>Easy on the eyes</small>
                            </button>
                            <button class="settings-theme-card" type="button" data-settings-theme="system" aria-pressed="false">
                                <span class="settings-theme-preview system"><i></i><i></i><i></i></span>
                                <strong>▣ System</strong>
                                <small>Match my device</small>
                            </button>
                        </div>

                        <div class="settings-preference-row">
                            <span class="settings-preference-icon">☰</span>
                            <span class="settings-preference-copy">
                                <strong>Keep sidebar open</strong>
                                <small>Turn off to use the compact icon-only sidebar on desktop.</small>
                            </span>
                            <label class="settings-switch">
                                <input type="checkbox" id="settingsSidebarToggle">
                                <span></span>
                            </label>
                        </div>
                        <div class="settings-preference-row">
                            <span class="settings-preference-icon">ϟ</span>
                            <span class="settings-preference-copy">
                                <strong>Reduce motion</strong>
                                <small>Calms animations such as transitions and page scrolling.</small>
                            </span>
                            <label class="settings-switch">
                                <input type="checkbox" id="settingsMotionToggle">
                                <span></span>
                            </label>
                        </div>
                    </section>

                    <section class="section-card settings-card">
                        <div class="settings-card-heading">
                            <div class="section-kicker">Work Schedule</div>
                            <h2 class="section-title">Attendance &amp; hours goal</h2>
                        </div>
                        <p class="settings-help-text">Your shift start decides whether a Time In counts as on time on the dashboard.</p>

                        <form id="settingsScheduleForm" class="settings-form">
                            <div class="settings-fields three">
                                <label>Shift starts
                                    <input class="field" type="time" name="shift_start" value="08:00" required>
                                </label>
                                <label>Grace period (min)
                                    <input class="field" type="number" name="grace_minutes" min="0" max="120" value="15" required>
                                </label>
                                <label>Daily target (hours)
                                    <input class="field" type="number" name="daily_target" min="1" max="16" step="0.5" value="8" required>
                                </label>
                            </div>
                            <div class="settings-actions">
                                <span class="settings-local-note">Saved on this device</span>
                                <button class="btn btn-primary" type="submit">Save schedule</button>
                            </div>
                        </form>

                        <hr class="settings-divider">

                        <form id="settingsGoalForm" class="settings-form">
                            <label>Required OJT hours
                                <input class="field" type="number" name="required_hours" min="1" max="2000" step="1" value="480" required>
                                <small class="settings-field-help">Defined by your school. Used for your progress ring and goal bar.</small>
                            </label>
                            <div class="settings-actions">
                                <span class="settings-local-note">Saved on this device</span>
                                <button class="btn btn-primary" type="submit">Update goal</button>
                            </div>
                        </form>
                    </section>

                    <section class="section-card settings-card">
                        <div class="settings-card-heading">
                            <div class="section-kicker">Security</div>
                            <h2 class="section-title">Change password</h2>
                        </div>
                        <form class="settings-form" method="POST" action="Employee%20Dashboard?section=settings">
                            <input type="hidden" name="settings_action" value="change_password">
                            <input type="hidden" name="settings_csrf" value="<?= $settingsCsrf ?>">
                            <label>Current password
                                <input class="field" type="password" name="current_password" autocomplete="current-password" required>
                            </label>
                            <div class="settings-fields two">
                                <label>New password
                                    <input class="field" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
                                </label>
                                <label>Confirm new password
                                    <input class="field" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                </label>
                            </div>
                            <div class="settings-actions">
                                <span class="settings-local-note">Use at least 8 characters</span>
                                <button class="btn btn-primary" type="submit">Update password</button>
                            </div>
                        </form>
                    </section>

                    <section class="section-card settings-card">
                        <div class="settings-card-heading">
                            <div class="section-kicker">Account</div>
                            <h2 class="section-title">Quick links</h2>
                        </div>
                        <div class="settings-quick-links">
                            <button type="button" data-settings-page="interns"><span>♙</span> Intern assignments <b>›</b></button>
                            <button type="button" data-settings-page="evaluations"><span>▤</span> Evaluations <b>›</b></button>
                            <button type="button" data-settings-page="reports"><span>▥</span> Reports &amp; analytics <b>›</b></button>
                            <button type="button" data-settings-page="support"><span>?</span> Help &amp; support <b>›</b></button>
                        </div>
                        <div class="settings-account-summary">
                            <div class="avatar"><?= $displayInitials ?></div>
                            <div><strong><?= $displayName ?></strong><small><?= $displayEmail ?></small></div>
                            <button class="small-btn" type="button" id="settingsProfileButton">Edit profile</button>
                        </div>
                    </section>
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

        <form id="companyForm" method="post" action="Employee-Dashboard">
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
            <h3 class="company-assigned-title">Student placement</h3>
            <div class="company-student-list">
                <div class="company-details-empty">Student-company assignments are not stored in the provided database tables.</div>
            </div>
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

        <form id="internForm" method="post">
            <input type="hidden" name="student_action" value="create">
            <input type="hidden" name="students_csrf" value="<?= $studentsCsrf ?>">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="internFirstName">First name</label>
                        <input class="field" id="internFirstName" name="first_name" maxlength="50" autocomplete="given-name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internMiddleName">Middle name</label>
                        <input class="field" id="internMiddleName" name="middle_name" maxlength="50" autocomplete="additional-name">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internLastName">Last name</label>
                        <input class="field" id="internLastName" name="last_name" maxlength="50" autocomplete="family-name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internStudentId">Student ID</label>
                        <input class="field" id="internStudentId" name="student_id" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internCourse">Course</label>
                        <input class="field" id="internCourse" name="course" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internMobileNumber">Mobile number</label>
                        <input class="field" id="internMobileNumber" name="mobile_number" maxlength="20" autocomplete="tel" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internSchoolName">School name</label>
                        <input class="field" id="internSchoolName" name="school_name" value="Leyte Normal University" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internEmail">Institutional email</label>
                        <input class="field" id="internEmail" type="email" name="institutional_email" maxlength="100" autocomplete="email" required>
                    </div>

                    <div class="form-group full">
                        <label class="form-label" for="internAddress">Complete address</label>
                        <textarea class="field" id="internAddress" name="complete_address" rows="3" required></textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label" for="internPassword">Initial student password</label>
                        <input class="field" id="internPassword" type="password" name="student_password" minlength="8" autocomplete="new-password" required>
                        <small>The password is securely hashed before it is saved.</small>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-light" data-close="internModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Create Student Account</button>
            </div>
        </form>
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
let currentRealtimeChannel = null;
const locallyInsertedMessageIds = new Set();

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
            "status" => $companyRecord["status"]
        ];
    }, $companyRecords),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
) ?>;

let students = <?= json_encode(
    array_map(static function (array $studentRecord): array {
        return [
            "id" => (int)$studentRecord["id"],
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
            "portalLinked" => $studentProgressByStudentId[$studentRecord["student_id"]]["portalLinked"] ?? false,
            "completedHours" => $studentProgressByStudentId[$studentRecord["student_id"]]["completedHours"] ?? null,
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
const topbarPageName = document.getElementById("topbarPageName");
const themeStorageKey = "ojt360-employee-theme-" + CURRENT_EMPLOYEE_ID;
let keepSidebarOpen = false;

function setDashboardTheme(theme){
    const isDark = theme === "dark" ||
        (theme === "system" && window.matchMedia("(prefers-color-scheme: dark)").matches);
    document.body.dataset.theme = isDark ? "dark" : "light";
    themeToggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
    themeToggle.title = isDark ? "Switch to light mode" : "Switch to dark mode";

    document.querySelectorAll("[data-settings-theme]").forEach(button => {
        const selected = button.dataset.settingsTheme === theme;
        button.classList.toggle("selected", selected);
        button.setAttribute("aria-pressed", String(selected));
    });
}

let savedTheme = "light";
try{
    savedTheme = localStorage.getItem(themeStorageKey) ||
        localStorage.getItem("ojt360-employee-theme") ||
        "light";
    if(!["light", "dark", "system"].includes(savedTheme)) savedTheme = "light";
}catch(error){
    console.error("Unable to load the saved dashboard theme.", error);
}
setDashboardTheme(savedTheme);

window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
    if(savedTheme === "system") setDashboardTheme("system");
});

themeToggle.addEventListener("click", () => {
    savedTheme = document.body.dataset.theme === "dark" ? "light" : "dark";
    const nextTheme = savedTheme;
    setDashboardTheme(nextTheme);
    try{
        localStorage.setItem(themeStorageKey, nextTheme);
    }catch(error){
        console.error("Unable to save the dashboard theme preference.", error);
    }
});

document.querySelectorAll("[data-settings-theme]").forEach(button => {
    button.addEventListener("click", () => {
        savedTheme = button.dataset.settingsTheme;
        setDashboardTheme(savedTheme);
        try{
            localStorage.setItem(themeStorageKey, savedTheme);
        }catch(error){
            console.error("Unable to save the dashboard theme preference.", error);
            showToast("Unable to save your theme preference on this device.");
        }
    });
});

function closeSidebar(){
    if(window.innerWidth <= 1000){
        sidebar.classList.remove("open");
    }
    sidebarOverlay.classList.remove("show");
    menuToggle.setAttribute("aria-expanded", "false");
}

function openSidebar(){
    if(window.innerWidth > 1000){
        sidebar.classList.remove("compact");
        keepSidebarOpen = true;
        settingsSidebarToggle.checked = true;
        employeeSettings.keep_sidebar_open = true;
        persistEmployeeSettings();
    } else {
        sidebar.classList.add("open");
        sidebarOverlay.classList.add("show");
        menuToggle.setAttribute("aria-expanded", "true");
    }
}

function showPage(pageName){
    const pageLabels = {
        overview: "Employee",
        interns: "Interns",
        evaluations: "Evaluations",
        messages: "Messages",
        reports: "Reports",
        support: "Help & Support",
        settings: "Settings"
    };

    pages.forEach(page => {
        page.classList.toggle("active", page.id === "page-" + pageName);
    });

    navLinks.forEach(link => {
        link.classList.toggle("active", link.dataset.page === pageName);
    });

    topbarPageName.dataset.currentPage = pageLabels[pageName] || "Employee";
    closeSidebar();
    window.scrollTo({top:0,behavior:"smooth"});
}

const requestedSection = new URLSearchParams(window.location.search).get("section");
if(["settings", "interns", "evaluations"].includes(requestedSection)){
    showPage(requestedSection);
}

navLinks.forEach(link => {
    link.addEventListener("click", () => showPage(link.dataset.page));
});

menuToggle.addEventListener("click", () => {
    if(window.innerWidth > 1000){
        keepSidebarOpen = sidebar.classList.contains("compact");
        sidebar.classList.toggle("compact", !keepSidebarOpen);
        settingsSidebarToggle.checked = keepSidebarOpen;
        employeeSettings.keep_sidebar_open = keepSidebarOpen;
        persistEmployeeSettings();
        return;
    }

    if(sidebar.classList.contains("open")) closeSidebar();
    else openSidebar();
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

    if(COMPANY_DATA_ERROR){
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty">Company records could not be loaded.</div></td></tr>`;
        return;
    }

    let filtered = companies.filter(company => {
        const matchesSearch =
            company.name.toLowerCase().includes(search) ||
            company.contact.toLowerCase().includes(search) ||
            company.email.toLowerCase().includes(search) ||
            company.phone.toLowerCase().includes(search) ||
            company.location.toLowerCase().includes(search) ||
            company.code.toLowerCase().includes(search);

        const matchesFilter =
            companyFilter === "all" ||
            company.status === companyFilter;

        return matchesSearch && matchesFilter;
    });

    if(!filtered.length){
        tbody.innerHTML = `
            <tr>
                <td colspan="7">
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
                        <div class="person-sub">${escapeHtml(company.code || "No company code")}</div>
                    </div>
                </div>
            </td>

            <td>${escapeHtml(company.contact)}</td>
            <td>${escapeHtml(company.email)}</td>
            <td>${escapeHtml(company.phone)}</td>
            <td>${escapeHtml(company.location)}</td>

            <td>
                <span class="badge ${company.status === "active" ? "green" : "yellow"}">
                    ${company.status === "active" ? "Active" : "Inactive"}
                </span>
            </td>

            <td>
                <div class="action-row">
                    <button class="small-btn company-view-button" type="button" data-company-id="${company.id}">View</button>
                    <form class="company-status-form" method="post" action="Employee-Dashboard">
                        <input type="hidden" name="company_action" value="set_status">
                        <input type="hidden" name="companies_csrf" value="${COMPANIES_CSRF}">
                        <input type="hidden" name="company_database_id" value="${company.id}">
                        <input type="hidden" name="status" value="${company.status === "active" ? "inactive" : "active"}">
                        <button class="small-btn" type="submit">${company.status === "active" ? "Archive" : "Restore"}</button>
                    </form>
                </div>
            </td>
        </tr>
    `).join("");

    tbody.querySelectorAll(".company-view-button").forEach(button => {
        button.addEventListener("click", () => viewCompany(Number(button.dataset.companyId)));
    });

    tbody.querySelectorAll(".company-status-form").forEach(form => {
        form.addEventListener("submit", event => {
            const company = companies.find(item => item.id === Number(form.elements.company_database_id.value));
            if(company && company.status === "active"){
                event.preventDefault();
                openActionConfirmation({
                    title: "Archive company",
                    message: "This company will be marked inactive and remain in your records. You can restore it later.",
                    actionLabel: "Archive company",
                    summaryLabel: "Company",
                    summaryValue: company.name,
                    onConfirm: () => form.submit()
                });
            }
        });
    });
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

    document.getElementById("companyDetailsName").textContent = company.name;
    document.getElementById("companyDetailsContact").textContent = company.contact || "Not provided";
    document.getElementById("companyDetailsEmail").textContent = company.email || "Not provided";
    document.getElementById("companyDetailsPhone").textContent = company.phone || "No contact number";
    document.getElementById("companyDetailsCode").textContent = company.code || "Not provided";
    document.getElementById("companyDetailsBuilding").textContent = company.building || "Not provided";
    document.getElementById("companyDetailsDepartment").textContent =
        [company.department, company.position].filter(Boolean).join(" / ") || "Not provided";
    document.getElementById("companyDetailsLocation").textContent = company.location || "Not provided";
    document.getElementById("companyDetailsCreated").textContent = company.createdAt
        ? new Date(company.createdAt).toLocaleDateString()
        : "Not provided";
    document.getElementById("companyDetailsStatus").innerHTML =
        `<span class="badge ${company.status === "active" ? "green" : "yellow"}">` +
        `${company.status === "active" ? "Active" : "Inactive"}</span>`;

    document.getElementById("companyDetailsModal").classList.add("show");
}

function updateOverviewStats(){
    const active = companies.filter(c => c.status === "active").length;
    const inactive = companies.filter(c => c.status === "inactive").length;

    document.getElementById("activeCompanyCount").textContent = active;
    document.getElementById("inactiveCompanyCount").textContent = inactive;
}

/* ================================================================
   OVERVIEW - ASSIGNMENTS
================================================================ */

function renderAssignments(){
    const tbody = document.getElementById("assignmentTable");

    if(STUDENT_DATA_ERROR){
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty">Student records could not be loaded.</div></td></tr>`;
        return;
    }

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
            <td>${escapeHtml(student.email)}</td>
            <td>${escapeHtml(student.mobileNumber)}</td>
            <td>${escapeHtml(student.schoolName)}</td>
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
    renderCompanies();
    renderEvaluations();
    updateOverviewStats();
});

/* ================================================================
   INTERN STUDENT RECORDS ARE RENDERED FROM MYSQL
================================================================ */

/* ================================================================
   ADD COMPANY
================================================================ */

document.getElementById("addCompanyBtn").addEventListener("click", () => {
    document.getElementById("companyModal").classList.add("show");
});

/* ================================================================
   ADD INTERN
================================================================ */

document.getElementById("addInternBtn").addEventListener("click", () => {
    document.getElementById("internModal").classList.add("show");
});

document.querySelectorAll(".remove-student-form").forEach(form => {
    form.addEventListener("submit", event => {
        event.preventDefault();
        const studentName = form.dataset.studentName || "this student";
        openActionConfirmation({
            title: "Remove intern",
            message: "This action permanently deletes the intern and their account from the database. It cannot be undone.",
            actionLabel: "Remove intern",
            summaryLabel: "Intern",
            summaryValue: studentName,
            danger: true,
            onConfirm: () => form.submit()
        });
    });
});

/* ================================================================
   EVALUATIONS
================================================================ */

const evaluationCriteria = [
    ["attendance_punctuality", "Attendance & punctuality"],
    ["quality_of_work", "Quality of work"],
    ["productivity", "Productivity"],
    ["communication", "Communication"],
    ["teamwork", "Teamwork"],
    ["initiative", "Initiative"],
    ["professionalism", "Professionalism"],
    ["adaptability", "Adaptability"],
    ["problem_solving", "Problem solving"],
    ["technical_skills", "Technical skills"]
];

document.getElementById("evaluationCriteria").innerHTML = evaluationCriteria.map(([key, label]) => `
    <div class="evaluation-criterion">
        <span class="evaluation-criterion-name">${escapeHtml(label)}</span>
        <div class="evaluation-rating-options" role="radiogroup" aria-label="${escapeHtml(label)} rating">
            ${[1, 2, 3, 4, 5].map(rating => `
                <label class="evaluation-rating-option" for="evaluationScore-${key}-${rating}">
                    <input id="evaluationScore-${key}-${rating}" type="radio" name="scores[${key}]" value="${rating}" required>
                    <span>${rating}</span>
                </label>
            `).join("")}
        </div>
    </div>
`).join("");

function openEvaluationModal(studentId = "", draft = null){
    if(EVALUATION_DATA_ERROR){
        showToast("Evaluation records are unavailable. Check that the intern_evaluations table exists and try again.");
        return;
    }
    if(!students.length){
        showToast("Add a registered student before recording an evaluation.");
        return;
    }

    const form = document.querySelector("#evaluationModal form");
    const studentSelect = document.getElementById("evaluationStudent");
    form.reset();
    document.getElementById("evaluationRecordId").value = draft ? String(draft.id) : "";
    studentSelect.innerHTML = `<option value="">Select a student</option>` + students.map(student =>
        `<option value="${student.id}">${escapeHtml(student.name)} (${escapeHtml(student.studentId)})</option>`
    ).join("");
    studentSelect.value = studentId ? String(studentId) : "";
    if(draft){
        document.getElementById("evaluationDate").value = draft.evaluationDate;
        document.getElementById("evaluationPerformance").value = draft.overallPerformance;
        document.getElementById("evaluationStatus").value = draft.status;
        document.getElementById("evaluationStrengths").value = draft.strengths;
        document.getElementById("evaluationImprovements").value = draft.areasForImprovement;
        document.getElementById("evaluationComments").value = draft.comments;
        document.getElementById("evaluationRecommendation").value = draft.recommendation;
        evaluationCriteria.forEach(([key]) => {
            const camelCaseKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
            const rating = document.querySelector(
                `input[name="scores[${key}]"][value="${draft[camelCaseKey]}"]`
            );
            if(rating) rating.checked = true;
        });
        document.querySelector("#evaluationModal .modal-head h2").textContent = "Continue draft evaluation";
        document.querySelector("#evaluationModal .modal-foot .btn-primary").textContent = "Save changes";
    } else {
        document.querySelector("#evaluationModal .modal-head h2").textContent = "Record intern evaluation";
        document.querySelector("#evaluationModal .modal-foot .btn-primary").textContent = "Save evaluation";
    }
    document.getElementById("evaluationModal").classList.add("show");
}

function viewStudentEvaluation(studentId){
    const student = students.find(item => item.id === Number(studentId));
    const evaluation = evaluationsByStudentId[studentId];
    if(!student || !evaluation) return;

    document.getElementById("studentEvaluationName").textContent = student.name;
    document.getElementById("studentEvaluationIdentity").textContent =
        `Student ID: ${student.studentId} · ${student.email}`;
    document.getElementById("studentEvaluationProgram").textContent = student.program || "Not provided";
    document.getElementById("studentEvaluationDate").textContent = new Date(
        evaluation.evaluationDate + "T00:00:00"
    ).toLocaleDateString(undefined, {year: "numeric", month: "long", day: "numeric"});
    document.getElementById("studentEvaluationPerformance").textContent =
        evaluation.overallPerformance || "Not provided";
    document.getElementById("studentEvaluationRating").textContent =
        evaluation.overallRating === null ? "Not provided" : `${Number(evaluation.overallRating).toFixed(2)} / 5`;
    document.getElementById("studentEvaluationStatus").textContent = evaluation.status;

    document.getElementById("studentEvaluationScores").innerHTML = evaluationCriteria.map(([key, label]) => {
        const camelCaseKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
        return `<tr><td>${escapeHtml(label)}</td><td><strong>${escapeHtml(evaluation[camelCaseKey])} / 5</strong></td></tr>`;
    }).join("");

    const feedbackFields = [
        ["studentEvaluationStrengths", evaluation.strengths],
        ["studentEvaluationImprovements", evaluation.areasForImprovement],
        ["studentEvaluationComments", evaluation.comments],
        ["studentEvaluationRecommendation", evaluation.recommendation]
    ];
    feedbackFields.forEach(([elementId, value]) => {
        document.getElementById(elementId).textContent = value || "No feedback provided.";
    });

    document.getElementById("studentEvaluationModal").classList.add("show");
}

function renderEvaluations(){
    const roster = document.getElementById("evaluationRoster");

    if(STUDENT_DATA_ERROR){
        roster.innerHTML = `<div class="empty">Student records could not be loaded.</div>`;
        return;
    }

    if(EVALUATION_DATA_ERROR){
        roster.innerHTML = `<div class="empty">Evaluation records could not be loaded. Check the intern_evaluations table and try again.</div>`;
        return;
    }

    if(!students.length){
        roster.innerHTML = `<div class="empty">No registered students found.</div>`;
        return;
    }

    roster.innerHTML = students.map(student => {
        const evaluation = evaluationsByStudentId[student.id];
        const hasEvaluation = Boolean(evaluation);

        return `
            <div class="eval-row">
                <div class="person">
                    <div class="mini-avatar">${initials(student.name)}</div>
                    <div>
                        <div class="person-name">${escapeHtml(student.name)}</div>
                        <div class="person-sub">${escapeHtml(student.studentId)}</div>
                    </div>
                </div>

                <div class="week">${escapeHtml(student.program)}</div>
                <div class="evaluation-placement evaluation-company">
                    <span>Company</span>
                    <strong>${escapeHtml(STUDENT_PROGRESS_DATA_ERROR ? "Unavailable" : !student.portalLinked ? "Student account not linked" : student.company || "Not assigned")}</strong>
                </div>
                <div class="evaluation-placement evaluation-hours">
                    <span>Working hours</span>
                    <strong>${escapeHtml(STUDENT_PROGRESS_DATA_ERROR || student.completedHours === null ? "Unavailable" : `${Number(student.completedHours).toFixed(1)} hrs`)}</strong>
                </div>
                <div class="week">${hasEvaluation ? `${Number(evaluation.overallRating).toFixed(2)} / 5 · ${escapeHtml(evaluation.evaluationDate)}` : "No evaluation yet"}</div>
                <span class="badge ${hasEvaluation && evaluation.status === "Completed" ? "green" : "yellow"}">${hasEvaluation ? escapeHtml(evaluation.status) : "Not evaluated"}</span>
                <div class="evaluation-actions">
                    ${hasEvaluation ? `<button class="small-btn evaluation-view-button" type="button" data-student-id="${student.id}">View evaluation</button>` : ""}
                    ${hasEvaluation && evaluation.status === "Draft" ? `<button class="small-btn evaluation-continue-button" type="button" data-student-id="${student.id}">Continue draft</button>` : ""}
                    <button class="small-btn evaluation-action-button" type="button" data-student-id="${student.id}">${hasEvaluation ? "New evaluation" : "Evaluate"}</button>
                </div>
            </div>
        `;
    }).join("");
}

document.getElementById("evalSearch").addEventListener("input", function(){
    const query = this.value.toLowerCase();

    document.querySelectorAll("#evaluationRoster .eval-row").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(query) ? "grid" : "none";
    });
});

document.getElementById("evaluationRoster").addEventListener("click", event => {
    const target = event.target;
    if(!(target instanceof Element)) return;

    const viewButton = target.closest(".evaluation-view-button");
    if(viewButton){
        viewStudentEvaluation(viewButton.dataset.studentId);
        return;
    }

    const continueButton = target.closest(".evaluation-continue-button");
    if(continueButton){
        const studentId = continueButton.dataset.studentId;
        openEvaluationModal(studentId, evaluationsByStudentId[studentId]);
        return;
    }

    const evaluationButton = target.closest(".evaluation-action-button");
    if(evaluationButton){
        openEvaluationModal(evaluationButton.dataset.studentId);
    }
});

document.getElementById("startEvaluationBtn").addEventListener("click", () => openEvaluationModal());

/* ================================================================
   MESSAGES - SUPABASE
================================================================ */

let conversationElements = [];
const conversationList = document.getElementById("companyConversationList");
const conversationSearch = document.getElementById("conversationSearch");
const chatInput = document.getElementById("messageInput");
const sendMessageButton = document.getElementById("sendMessageBtn");

function renderCompanyConversations(){
    if(!conversationList) return;

    if(COMPANY_DATA_ERROR){
        conversationList.innerHTML = `<div class="empty">Company records could not be loaded.</div>`;
        conversationElements = [];
        return;
    }

    if(!companies.length){
        conversationList.innerHTML = `<div class="empty">No companies are registered yet.</div>`;
        conversationElements = [];
        return;
    }

    const searchTerm = conversationSearch.value.trim().toLowerCase();
    const filteredCompanies = companies.filter(company =>
        company.name.toLowerCase().includes(searchTerm) ||
        company.code.toLowerCase().includes(searchTerm) ||
        company.contact.toLowerCase().includes(searchTerm)
    );

    if(!filteredCompanies.length){
        conversationList.innerHTML = `<div class="empty">No matching companies found.</div>`;
        conversationElements = [];
        return;
    }

    conversationList.innerHTML = filteredCompanies.map(company => `
        <button class="conversation${currentConversation && currentConversation.receiverId === company.name ? " active" : ""}"
            type="button"
            data-chat="${escapeHtml(company.name)}"
            data-receiver-type="company"
            data-receiver-id="${escapeHtml(company.name)}">
            <span class="conversation-top">
                <span class="conversation-name">${escapeHtml(company.name)}</span>
                <span class="badge ${company.status === "active" ? "green" : "yellow"}">${company.status === "active" ? "Active" : "Inactive"}</span>
            </span>
            <span class="conversation-msg">${escapeHtml(company.contact)} · ${escapeHtml(company.email)}</span>
        </button>
    `).join("");

    conversationElements = Array.from(conversationList.querySelectorAll(".conversation"));
    conversationElements.forEach(conversation => {
        conversation.addEventListener("click", async () => {
            conversationElements.forEach(item => item.classList.remove("active"));
            conversation.classList.add("active");

            const info = getConversationInfo(conversation);
            currentConversation = info;
            document.getElementById("chatName").textContent = info.name;
            const selectedCompany = companies.find(company => company.name === info.receiverId);
            const chatStatus = document.getElementById("chatStatus");
            chatStatus.textContent = selectedCompany && selectedCompany.status === "active"
                ? "Active company"
                : "Inactive company";
            chatStatus.dataset.companyStatus = selectedCompany ? selectedCompany.status : "";
            chatInput.disabled = false;
            sendMessageButton.disabled = false;
            chatInput.focus();
            await loadMessages(info);
        });
    });
}

conversationSearch.addEventListener("input", renderCompanyConversations);

function getActiveConversation(){
    return conversationList.querySelector(".conversation.active");
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
        <div data-empty-chat="true" style="padding:24px;text-align:center;color:#8597a6;font-size:12px;">
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

async function sendMessage(){
    const input = document.getElementById("messageInput");
    const value = input.value.trim();
    const conversationInfo = currentConversation;

    if(!value) return;

    if(!supabaseReady || !supabaseClient){
        showToast("Configure Supabase first.");
        return;
    }

    if(!conversationInfo || !conversationInfo.receiverId){
        showToast("Please select a company conversation first.");
        return;
    }

    const sendButton = sendMessageButton;
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

/* Start realtime listening before loading the selected company conversation. */
if(supabaseReady){
    subscribeToMessages();
}

renderCompanyConversations();

if(!COMPANY_DATA_ERROR && companies.length){
    const initialCompany = companies.find(company => company.status === "active") || companies[0];
    const initialConversation = conversationElements.find(
        conversation => conversation.dataset.receiverId === initialCompany.name
    );

    if(initialConversation){
        initialConversation.click();
    }
}

document.getElementById("newMessageBtn").addEventListener("click", () => {
    conversationSearch.value = "";
    renderCompanyConversations();
    const firstConversation = conversationElements[0];

    if(firstConversation){
        firstConversation.click();
    } else {
        showToast("No company is available for messaging.");
    }
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
    openActionConfirmation({
        title: "Sign out",
        message: "You will be signed out of your employee account and returned to the login page.",
        actionLabel: "Sign out",
        summaryLabel: "Account",
        summaryValue: <?= json_encode($displayName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        onConfirm: () => { window.location.href = "logout.php"; }
    });
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

const settingsKey = "ojt360-employee-settings-" + CURRENT_EMPLOYEE_ID;
const settingsScheduleForm = document.getElementById("settingsScheduleForm");
const settingsGoalForm = document.getElementById("settingsGoalForm");
const settingsSidebarToggle = document.getElementById("settingsSidebarToggle");
const settingsMotionToggle = document.getElementById("settingsMotionToggle");
let employeeSettings = {
    shift_start: "08:00",
    grace_minutes: "15",
    daily_target: "8",
    required_hours: "480",
    keep_sidebar_open: true,
    reduce_motion: false
};

try{
    const savedSettings = JSON.parse(localStorage.getItem(settingsKey) || "{}");
    if(savedSettings && typeof savedSettings === "object" && !Array.isArray(savedSettings)){
        employeeSettings = {...employeeSettings, ...savedSettings};
    }
}catch(error){
    console.error("Unable to load employee settings.", error);
}

function persistEmployeeSettings(){
    try{
        localStorage.setItem(settingsKey, JSON.stringify(employeeSettings));
        return true;
    }catch(error){
        console.error("Unable to save employee settings.", error);
        showToast("Unable to save your settings on this device.");
        return false;
    }
}

Object.entries(employeeSettings).forEach(([name, value]) => {
    const field = settingsScheduleForm.elements.namedItem(name) ||
        settingsGoalForm.elements.namedItem(name);
    if(field && typeof value !== "boolean") field.value = value;
});

settingsSidebarToggle.checked = employeeSettings.keep_sidebar_open === true;
keepSidebarOpen = settingsSidebarToggle.checked;
sidebar.classList.toggle("compact", !keepSidebarOpen);
settingsMotionToggle.checked = employeeSettings.reduce_motion === true;
document.documentElement.classList.toggle("reduce-motion", settingsMotionToggle.checked);

settingsSidebarToggle.addEventListener("change", () => {
    employeeSettings.keep_sidebar_open = settingsSidebarToggle.checked;
    keepSidebarOpen = settingsSidebarToggle.checked;
    sidebar.classList.toggle("compact", !keepSidebarOpen);
    persistEmployeeSettings();
});

settingsMotionToggle.addEventListener("change", () => {
    employeeSettings.reduce_motion = settingsMotionToggle.checked;
    document.documentElement.classList.toggle("reduce-motion", settingsMotionToggle.checked);
    persistEmployeeSettings();
});

settingsScheduleForm.addEventListener("submit", event => {
    event.preventDefault();
    const formData = new FormData(settingsScheduleForm);
    employeeSettings.shift_start = formData.get("shift_start");
    employeeSettings.grace_minutes = formData.get("grace_minutes");
    employeeSettings.daily_target = formData.get("daily_target");
    if(persistEmployeeSettings()) showToast("Work schedule saved on this device.");
});

settingsGoalForm.addEventListener("submit", event => {
    event.preventDefault();
    employeeSettings.required_hours = new FormData(settingsGoalForm).get("required_hours");
    if(persistEmployeeSettings()) showToast("OJT hours goal saved on this device.");
});

document.querySelectorAll("[data-settings-page]").forEach(button => {
    button.addEventListener("click", () => showPage(button.dataset.settingsPage));
});

document.getElementById("settingsProfileButton").addEventListener("click", openProfile);

const settingsPasswordFields = document.querySelector(
    '#page-settings form[method="POST"]'
);

settingsPasswordFields.addEventListener("submit", event => {
    const newPassword = settingsPasswordFields.elements.namedItem("new_password").value;
    const confirmPassword = settingsPasswordFields.elements.namedItem("confirm_password").value;

    if(newPassword !== confirmPassword){
        event.preventDefault();
        showToast("The new password and confirmation do not match.");
    }
});

const settingsFlash = <?= json_encode(
    $settingsFlash,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
) ?>;

if(settingsFlash && typeof settingsFlash.message === "string"){
    showToast(settingsFlash.message);
}

/* ================================================================
   MODALS
================================================================ */

let pendingActionConfirmation = null;

function openActionConfirmation({title, message, actionLabel, summaryLabel, summaryValue, danger = false, onConfirm}){
    document.getElementById("actionConfirmTitle").textContent = title;
    document.getElementById("actionConfirmMessage").textContent = message;
    document.getElementById("actionConfirmLabel").textContent = summaryLabel;
    document.getElementById("actionConfirmValue").textContent = summaryValue;
    const actionButton = document.getElementById("actionConfirmButton");
    actionButton.textContent = actionLabel;
    actionButton.className = danger ? "btn btn-danger" : "btn btn-primary";
    pendingActionConfirmation = onConfirm;
    document.getElementById("actionConfirmModal").classList.add("show");
    actionButton.focus();
}

document.getElementById("actionConfirmButton").addEventListener("click", () => {
    const confirmAction = pendingActionConfirmation;
    pendingActionConfirmation = null;
    closeModal("actionConfirmModal");
    if(confirmAction) confirmAction();
});

function closeModal(id){
    document.getElementById(id).classList.remove("show");
    if(id === "actionConfirmModal") pendingActionConfirmation = null;
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
renderEvaluations();
updateOverviewStats();
if(EVALUATION_SUCCESS_MESSAGE){
    showToast(EVALUATION_SUCCESS_MESSAGE);
}
</script>
<script src="notification.js"></script>
<script src="chatbot.js"></script>
</body>
</html>