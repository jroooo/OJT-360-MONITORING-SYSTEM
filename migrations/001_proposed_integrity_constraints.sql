-- PROPOSED ONLY - NOT APPLIED. Review, back up first (phpMyAdmin > Export), then run manually.
-- Purpose: let the database itself enforce what the Company Portal code already checks, preventing
-- duplicates under concurrent requests. Purely additive: no drops, no data changes.
--
-- STEP 1 - make sure no duplicates exist (each query should return 0 rows):
--   SELECT company_username, COUNT(*) FROM companies GROUP BY company_username HAVING COUNT(*) > 1;
--   SELECT representative_email, COUNT(*) FROM companies GROUP BY representative_email HAVING COUNT(*) > 1;
--   SELECT student_id_no, COUNT(*) FROM students GROUP BY student_id_no HAVING COUNT(*) > 1;
--   SELECT student_id, date, COUNT(*) FROM attendance GROUP BY student_id, date HAVING COUNT(*) > 1;
--   SELECT student_id, company_id, COUNT(*) FROM evaluations GROUP BY student_id, company_id HAVING COUNT(*) > 1;
--
-- STEP 2 - apply:
ALTER TABLE `companies`   ADD UNIQUE KEY `uq_company_username` (`company_username`),
                          ADD UNIQUE KEY `uq_company_rep_email` (`representative_email`);
ALTER TABLE `students`    ADD UNIQUE KEY `uq_student_id_no` (`student_id_no`);
ALTER TABLE `attendance`  ADD UNIQUE KEY `uq_attendance_student_day` (`student_id`, `date`);
ALTER TABLE `evaluations` ADD UNIQUE KEY `uq_eval_student_company` (`student_id`, `company_id`);
--
-- ROLLBACK:
--   ALTER TABLE companies DROP INDEX uq_company_username, DROP INDEX uq_company_rep_email;
--   ALTER TABLE students DROP INDEX uq_student_id_no;
--   ALTER TABLE attendance DROP INDEX uq_attendance_student_day;
--   ALTER TABLE evaluations DROP INDEX uq_eval_student_company;
