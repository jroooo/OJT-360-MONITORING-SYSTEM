-- OJT360 Student Dashboard v18 ΓÇö one-time reset of the OLD student-dashboard tables.
-- Run this ONCE in phpMyAdmin (SQL tab) on your existing `ojt360` database, then reload the site.
--
-- KEPT   : `students`  (your registered students and their passwords)
-- DROPPED: `users` (merged into `students`) and the old per-student tables, which are recreated
--          automatically with the new `student_pk` column. Old attendance/log/journal rows are lost.
-- UNTOUCHED: employees, companies, company_qr_codes, company_notifications (other dashboards).

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS task_updates;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS assistant_messages;
DROP TABLE IF EXISTS support_requests;
DROP TABLE IF EXISTS student_settings;
DROP TABLE IF EXISTS journal_submissions;
DROP TABLE IF EXISTS journal_compilations;
DROP TABLE IF EXISTS journals;
DROP TABLE IF EXISTS daily_logs;
DROP TABLE IF EXISTS attendance_events;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS internships;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;
