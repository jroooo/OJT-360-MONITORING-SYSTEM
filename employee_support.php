<?php
// Standalone URL for the employee dashboard's Support page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "support";
require __DIR__ . "/dashboard(employee).php";
