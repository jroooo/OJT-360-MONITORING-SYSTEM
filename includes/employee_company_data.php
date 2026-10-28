<?php
/* Extracted company data queries and view preparation. */
/* =========================================================

   FETCH COMPANY RECORDS

========================================================= */

 

$companyRecords = [];

$companyPageError = "";

try {

    $companiesQuery = $conn->query("

        SELECT

            company.id,

            company.company_name,

            company.company_code,

            company.contact_person,

            company.contact_number,

            company.email,

            company.address,

            company.building,

            company.department,

            company.position,

            company.status,

            company.created_at,

            registered.id AS registered_company_id

        FROM company

        LEFT JOIN companies AS registered

            ON registered.company_name = company.company_name

        ORDER BY company.created_at DESC, company.id DESC

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

