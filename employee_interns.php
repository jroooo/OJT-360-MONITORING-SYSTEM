<?php
// Standalone URL for the employee dashboard's Interns page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "interns";
require __DIR__ . "/dashboard(employee).php";
