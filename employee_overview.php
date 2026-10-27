<?php
// Standalone URL for the employee dashboard's Overview page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "overview";
require __DIR__ . "/dashboard(employee).php";
