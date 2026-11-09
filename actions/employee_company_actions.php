<?php
/* Extracted from dashboard(employee).php; runs in the dashboard request scope. */
/* =========================================================

   HANDLE COMPANY ACTIONS

========================================================= */

 

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

    header("Location: dashboard(employee).php?section=companies", true, 303);

    exit;

}

 

