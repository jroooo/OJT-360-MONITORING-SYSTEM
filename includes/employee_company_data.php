<?php
/* Extracted company data queries and view preparation. */
$companiesFlash = $_SESSION["employee_companies_flash"] ?? null;

unset($_SESSION["employee_companies_flash"]);

$placementCompanyRecords = [];

$placementCompanyPageError = "";

try {

    $placementCompaniesQuery = $conn->query("

        SELECT

            id,

            company_name,

            rep_first_name,

            rep_middle_name,

            rep_last_name,

            rep_position,

            contact_number,

            representative_email,

            created_at

        FROM companies

        ORDER BY company_name ASC, id ASC

    ");

} catch (mysqli_sql_exception $exception) {

    error_log("Unable to load registered placement companies: " . $exception->getMessage());

    $placementCompaniesQuery = false;

}

if ($placementCompaniesQuery === false) {

    $placementCompanyPageError = "Unable to load registered companies right now. Please try again later.";

} else {

    while ($placementCompanyRecord = $placementCompaniesQuery->fetch_assoc()) {

        $placementCompanyRecords[] = $placementCompanyRecord;

    }

    $placementCompaniesQuery->free();

}

$companiesCsrf = htmlspecialchars($_SESSION["employee_companies_csrf"], ENT_QUOTES, "UTF-8");

 

if (!isset($_SESSION["employee_settings_csrf"])) {

    $_SESSION["employee_settings_csrf"] = bin2hex(random_bytes(32));

}

$settingsCsrf = htmlspecialchars($_SESSION["employee_settings_csrf"], ENT_QUOTES, "UTF-8");

$settingsFlash = $_SESSION["employee_settings_flash"] ?? null;

unset($_SESSION["employee_settings_flash"]);
