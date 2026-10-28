<?php
// Standalone URL for the employee dashboard's Settings page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "settings";
require __DIR__ . "/dashboard(employee).php";
