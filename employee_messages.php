<?php
// Standalone URL for the employee dashboard's Messages page.
// Authentication, database access, shared layout, and existing behavior remain in dashboard(employee).php.
$_GET["section"] = "messages";
require __DIR__ . "/dashboard(employee).php";
