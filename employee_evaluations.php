<?php
// Standalone URL for the employee dashboard's Evaluations page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "evaluations";
require __DIR__ . "/dashboard(employee).php";
