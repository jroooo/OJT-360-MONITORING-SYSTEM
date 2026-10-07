# OJT360 Student Dashboard

PHP + MySQL student-side OJT management dashboard based on the supplied prototype. This update preserves the existing dashboard flow while adding the requested professional UI enhancements and student productivity features.

## Updated features
- Student dashboard and OJT progress
- Attendance time-in/time-out with company QR validation
- Attendance history + CSV export
- Weekly journal editor, drafts, timeline, and edit buttons
- Final journal compilation containing all weekly entries
- Editable final compilation before printing
- Browser print and **Save as PDF** workflow
- Notifications + mark all as read
- Student profile editing + profile picture upload
- Help & Support ticket storage
- My Tasks page for company-created/assigned tasks
- Dashboard task visibility for unfinished tasks
- Settings page with Light/Dark Mode and compact sidebar preference
- Collapsible desktop sidebar with hamburger control
- Responsive mobile sidebar
- OJT360 Assistant connected to the OpenAI Responses API
- Assistant conversation history stored per student
- Assistant can answer open-ended questions, use private OJT context, help with journals/tasks/attendance, and optionally use web search
- Logout confirmation

## Requirements
- XAMPP (Apache + MySQL)
- PHP 8+
- MySQL/MariaDB
- PHP cURL extension for the AI API
- PHP Fileinfo extension for profile-picture validation
- Modern browser

## Installation
1. Replace the old `ojt360-student-dashboard` folder with this updated folder.
2. Start Apache and MySQL in XAMPP.
3. Keep using the existing `ojt360` database. The application automatically creates the new runtime tables and adds the profile-image column if needed.
4. If starting from a fresh database, import `database/schema.sql`.
5. Open `http://localhost/OJT-360-MONITORING-SYSTEM/ojt360-student-dashboard/`.

## OpenAI Assistant
The Assistant uses the OpenAI Responses API from the PHP server. The browser never receives the API key.

1. Copy `config.local.php.example` to `config.local.php`.
2. Add your server-side OpenAI API key.
3. The default model is `gpt-6-luna`; you can change it in `config.local.php` or the `OPENAI_MODEL` environment variable.
4. Keep `config.local.php` private. It is ignored by Git.
5. The optional web-search tool can be disabled with `web_search => false`.

The API key is not included in this ZIP.

## Journal workflow
- Write or edit an individual weekly entry.
- Save it as a draft or submit it.
- Review all entries in the timeline and Drafts panel.
- Open **Final Compilation** to combine the weekly entries.
- Edit the final compilation freely before saving.
- **Print** opens the browser print dialog.
- **Save as PDF** opens the same dialog with a reminder to choose “Save as PDF”.

## Tasks
Company-created tasks can be inserted into the `tasks` table with either the student's `user_id` or the student's assigned `company_id`. The student dashboard displays unfinished tasks, while `tasks.php` lets the student mark an assigned task complete or reopen it.

## Profile pictures
Images are stored in `uploads/profile/`. Accepted types are JPG, PNG, and WEBP, with a 3 MB maximum. For production, move uploads outside the public web root or use object storage.

## Demo
Student ID: `2026001`
Password: `demo123`

Demo QR: `OJT360-COMPANY-001`
