# OJT360 Student Dashboard ΓÇö v18

PHP + MySQL/MariaDB (XAMPP). Student-only: one `students` table, no company/employee tables.
See `INTEGRATION-NOTES.txt` for the full list of changes.

1. Copy the folder to `htdocs/ojt360` (or your web root).
2. Database `ojt360`:
   - existing database -> run `database/reset-student-tables.sql` once (keeps your `students` rows)
   - new database -> import `database/schema.sql`
3. Edit `config.php` if your MySQL user/password differ.
4. Use the application root's `login.php` and `register.php` for student sign-in and registration. The dashboard's own login and registration URLs redirect to those shared pages.
5. Optional AI assistant: copy `config.local.php.example` to `config.local.php` and add a Gemini API key. The assistant uses Gemini; an OpenAI API key will not work.
