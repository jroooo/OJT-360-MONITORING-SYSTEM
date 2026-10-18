# OJT360 Company Portal - setup (XAMPP)

1. Back up first: keep your original ZIP and `ojt360.sql`. In phpMyAdmin you can also Export the `ojt360` DB.
2. Copy the `OJT-360-MONITORING-SYSTEM` folder to `C:\xampp\htdocs\`. Start Apache and MySQL in XAMPP.
3. Database: your existing `ojt360` database is used as-is. No schema change is required for this build.
   (Optional, later: `migrations/001_proposed_integrity_constraints.sql` - read the header first.)
4. Optional config: copy `config/config.example.php` to `config/config.local.php` to change DB host/user/password.
   Defaults are XAMPP's (`root`, empty password). Environment variables OJT_DB_HOST/NAME/USER/PASS also work.
5. Syntax check (recommended): in a terminal, `C:\xampp\php\php.exe -l file.php` for each file, or run:
   `for %f in (*.php includes\*.php) do C:\xampp\php\php.exe -l %f`
6. Open http://localhost/OJT-360-MONITORING-SYSTEM/ -> goes to login.
7. The seeded TechNova account has a placeholder password hash and cannot log in. Register a new company
   at register.php (Company tab) and sign in with the company email you entered.

## Test checklist
- Register: valid data -> redirected to login with success message; row appears in `companies`; `password_hash` starts with `$2y$`.
- Register: empty field / bad email / weak password / mismatched passwords / duplicate email -> clear error, nothing saved.
- Login: wrong password and unknown user show the same message; 5 failures in 5 min are temporarily blocked.
- Login: success -> company_dashboard.php with your real name, initials and company name everywhere.
- Logout (sidebar -> confirm) -> login page; pressing Back or opening company_dashboard.php directly redirects to login.
- Visit every sidebar link logged out -> all redirect to login.
- Add Intern -> appears in roster, dashboard counts change; same Student ID again -> rejected.
- Edit intern hours -> progress bars update. Evaluate intern -> pending count drops, reports update.
- Attendance: Time in / Time out; a second Time in the same day is rejected.
- Reports: totals match; Download CSV opens in Excel.
- Theme toggle persists after reload and across pages; sidebar collapse persists; on a phone-width window the sidebar opens
  with the menu button, closes via overlay or Esc.
- Two companies: register a second company; confirm it never sees the first company's interns.
