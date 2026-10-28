<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
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

    header("Location: dashboard(employee).php?section=settings", true, 303);

    exit;

}

 

