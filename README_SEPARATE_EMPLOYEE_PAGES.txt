OJT-360 Employee Dashboard — Separate Page Entry Points

This package is based on the uploaded project. The original dashboard implementation remains in
"dashboard(employee).php". The existing page fragments remain under pages/employee/.

New standalone employee page URLs:
- employee_overview.php
- employee_interns.php
- employee_evaluations.php
- employee_messages.php
- employee_companies.php
- employee_support.php
- employee_settings.php

The sidebar links now navigate to these PHP entry points. Each entry point selects the appropriate
existing dashboard section, preserving shared authentication, database initialization, layout,
forms, modals, and scripts. This is a safe first step toward separating routes; the shared dashboard
shell and page fragments are still included together to avoid breaking cross-page JavaScript and modals.

Install: back up your project, extract/copy these files into your test project folder, then open
http://localhost/<your-project-folder>/login.php. Keep your local config files and credentials.
Test all sections and form submissions in XAMPP before using this in your main project.
