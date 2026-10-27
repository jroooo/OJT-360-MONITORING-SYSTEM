OJT-360 Employee Dashboard — staged refactor

WHAT CHANGED
- dashboard(employee).php remains the entry point. Authentication, shared orchestration, and markup wrapper remain there; request handlers and data-fetching blocks are loaded from includes/actions files.
- Visible employee dashboard page sections are now PHP fragments in pages/employee/.
- The Companies building-icon CSS override is in assets/css/employee-dashboard-overrides.css.
- The large employee-dashboard JavaScript behavior is in assets/js/employee-dashboard.js. PHP-generated data declarations remain inline in the main page so the existing data flow is preserved.
- Company, student assignment, settings, evaluation, and messages API handlers are in actions/. Student, progress, evaluation, and company data-fetch blocks are in includes/.
- dashboard(employee).php now reuses config/database.php for the existing mysqli connection.

IMPORTANT
- This is a conservative first refactor. POST/API handlers and many database operations remain in the main controller to avoid changing their behavior without a runtime test database.
- The archive intentionally excludes .git history and local-only config files such as config/gemini.local.php. Keep your original local config files in your working copy; do not overwrite them from this archive.
- No live database tests were run. Test in a backup/local copy of XAMPP before replacing your working files.

INSTALL
1. Back up the existing OJT-360-MONITORING-SYSTEM folder and database.
2. Extract the contents of this archive into a separate test folder under C:\xampp\htdocs\ (do not overwrite your current project first).
3. Keep any local config files that were excluded from the archive and restore them only where needed.
4. Start Apache and MySQL, then open the test dashboard at its localhost URL.
5. Test Overview, Interns, Evaluations, Messages, Companies, Support, Settings, modals, forms, and chatbot.
6. If everything works, merge the changes into your working folder.
