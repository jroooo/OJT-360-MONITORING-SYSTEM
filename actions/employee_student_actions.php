<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   HANDLE STUDENT ACTIONS & ASSIGNMENT

========================================================= */

 

if (

    $_SERVER["REQUEST_METHOD"] === "POST" &&

    ($_POST["student_action"] ?? "") === "assign_company"

) {

    $studentDbId = filter_var(

        $_POST["student_db_id"] ?? null,

        FILTER_VALIDATE_INT,

        ["options" => ["min_range" => 1]]

    );

 

    $companyValue = $_POST["company_name"] ?? "";

    $companyName = is_string($companyValue) ? trim($companyValue) : "";

 

    if ($studentDbId === false || $studentDbId === null || $companyName === "") {

        $_SESSION["employee_interns_flash"] = [

            "type" => "error",

            "message" => "Please select a valid student and company."

        ];

    } else {

        try {

            // Confirm the selected student exists.

            $studentStmt = $conn->prepare(

                "SELECT id FROM students WHERE id = ? LIMIT 1"

            );

            $studentStmt->bind_param("i", $studentDbId);

            $studentStmt->execute();

            $studentExists = $studentStmt->get_result()->num_rows === 1;

            $studentStmt->close();

 

            // Confirm the company exists and use its canonical name.

            $companyStmt = $conn->prepare(

                "SELECT company_name FROM company WHERE company_name = ? LIMIT 1"

            );

            $companyStmt->bind_param("s", $companyName);

            $companyStmt->execute();

            $companyRecord = $companyStmt->get_result()->fetch_assoc();

            $companyStmt->close();

 

            if (!$studentExists) {

                $_SESSION["employee_interns_flash"] = [

                    "type" => "error",

                    "message" => "The selected student could not be found."

                ];

            } elseif (!$companyRecord) {

                $_SESSION["employee_interns_flash"] = [

                    "type" => "error",

                    "message" => "The selected company could not be found."

                ];

            } else {

                // Update the latest internship for this student, if present.

                $latestStmt = $conn->prepare(

                    "SELECT id FROM internships

                     WHERE student_pk = ?

                     ORDER BY id DESC

                     LIMIT 1"

                );

                $latestStmt->bind_param("i", $studentDbId);

                $latestStmt->execute();

                $latestInternship = $latestStmt->get_result()->fetch_assoc();

                $latestStmt->close();

 

                if ($latestInternship) {

                    $internshipId = (int) $latestInternship["id"];

                    $saveStmt = $conn->prepare(

                        "UPDATE internships

                         SET company_name = ?

                         WHERE id = ?"

                    );

                    $saveStmt->bind_param(

                        "si",

                        $companyRecord["company_name"],

                        $internshipId

                    );

                } else {

                    // The schema has student_pk and company_name, not student_id/company_id.

                    $saveStmt = $conn->prepare(

                        "INSERT INTO internships (student_pk, company_name)

                         VALUES (?, ?)"

                    );

                    $saveStmt->bind_param(

                        "is",

                        $studentDbId,

                        $companyRecord["company_name"]

                    );

                }

 

                $saveStmt->execute();

                $saveStmt->close();

 

                $_SESSION["employee_interns_flash"] = [

                    "type" => "success",

                    "message" => "Student successfully assigned to company."

                ];

            }

        } catch (mysqli_sql_exception $exception) {

            error_log(

                "Unable to assign company to student: " . $exception->getMessage()

            );

            $_SESSION["employee_interns_flash"] = [

                "type" => "error",

                "message" => "Unable to save the company assignment. Please try again."

            ];

        }

    }

 

    header("Location: dashboard(employee).php?section=companies", true, 303);

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

 

    header("Location: dashboard(employee).php?section=interns", true, 303);

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

 

    header("Location: dashboard(employee).php?section=interns", true, 303);

    exit;

}

 

