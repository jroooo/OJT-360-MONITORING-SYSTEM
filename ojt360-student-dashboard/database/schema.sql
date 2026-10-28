-- OJT360 Student Dashboard v18 ΓÇö student-only database
-- Import into the database named in config.php (default: ojt360).
-- The app also creates any missing table automatically on first load.
-- Table `students` is your registration/login table; every other table points to students.id
-- through the column `student_pk` (named this way so it is never confused with students.student_id,
-- which is the 7-digit school ID number).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  id INT(11) NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50) DEFAULT NULL,
  last_name VARCHAR(50) NOT NULL,
  course VARCHAR(100) NOT NULL,
  mobile_number VARCHAR(20) NOT NULL,
  school_name VARCHAR(150) NOT NULL,
  complete_address TEXT NOT NULL,
  profile_image VARCHAR(255) DEFAULT NULL,
  student_id VARCHAR(50) NOT NULL,
  institutional_email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY institutional_email (institutional_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_codes (
  id INT(11) NOT NULL AUTO_INCREMENT,
  qr_value VARCHAR(120) NOT NULL,
  company_name VARCHAR(150) NOT NULL,
  company_address VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY qr_value (qr_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS internships (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  company_address VARCHAR(255) DEFAULT NULL,
  required_hours DECIMAL(8,2) NOT NULL DEFAULT 480,
  completed_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('pending','active','completed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_pk (student_pk),
  CONSTRAINT fk_internships_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  qr_id INT(11) DEFAULT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  time_in DATETIME NOT NULL,
  time_out DATETIME DEFAULT NULL,
  duration_minutes INT(10) UNSIGNED DEFAULT NULL,
  break_seconds INT(10) UNSIGNED NOT NULL DEFAULT 0,
  break_started_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_time (student_pk,time_in),
  CONSTRAINT fk_attendance_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance_events (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  attendance_id INT(11) NOT NULL,
  student_pk INT(11) NOT NULL,
  event_type ENUM('time_in','break_start','break_end','time_out') NOT NULL,
  event_time DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_event (student_pk,event_time),
  KEY attendance_id (attendance_id),
  CONSTRAINT fk_events_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
  CONSTRAINT fk_events_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_logs (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  log_date DATE NOT NULL,
  title VARCHAR(180) NOT NULL DEFAULT '',
  description TEXT DEFAULT NULL,
  hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  evidence_image VARCHAR(255) DEFAULT NULL,
  time_in TIME DEFAULT NULL,
  time_out TIME DEFAULT NULL,
  break_minutes INT(10) UNSIGNED NOT NULL DEFAULT 0,
  attendance_id INT(11) DEFAULT NULL,
  is_auto TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_date (student_pk,log_date),
  CONSTRAINT fk_logs_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journals (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  week_start DATE NOT NULL,
  title VARCHAR(180) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  status ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'draft',
  orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
  page_size ENUM('A4','Letter') NOT NULL DEFAULT 'A4',
  margin_preset VARCHAR(10) NOT NULL DEFAULT 'normal',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY student_week (student_pk,week_start),
  CONSTRAINT fk_journals_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_compilations (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL DEFAULT 'OJT Weekly Journal Compilation',
  content LONGTEXT NOT NULL,
  orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
  page_size ENUM('A4','Letter') NOT NULL DEFAULT 'A4',
  margin_preset VARCHAR(10) NOT NULL DEFAULT 'normal',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY student_pk (student_pk),
  CONSTRAINT fk_compilations_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_submissions (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  adviser_id INT(11) DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  content LONGTEXT NOT NULL,
  orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
  page_size ENUM('A4','Letter') NOT NULL DEFAULT 'A4',
  margin_preset VARCHAR(10) NOT NULL DEFAULT 'normal',
  status ENUM('pending','reviewed','returned') NOT NULL DEFAULT 'pending',
  adviser_remarks TEXT DEFAULT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY student_submitted (student_pk,submitted_at),
  KEY adviser_status (adviser_id,status),
  CONSTRAINT fk_submissions_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  icon VARCHAR(10) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_read (student_pk,is_read),
  CONSTRAINT fk_notifications_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  category VARCHAR(50) DEFAULT 'General',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  action_text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_created (student_pk,created_at),
  CONSTRAINT fk_activity_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assistant_messages (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_created (student_pk,created_at),
  CONSTRAINT fk_assistant_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_requests (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_pk (student_pk),
  CONSTRAINT fk_support_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_settings (
  student_pk INT(11) NOT NULL,
  shift_start TIME NOT NULL DEFAULT '08:00:00',
  grace_minutes SMALLINT(5) UNSIGNED NOT NULL DEFAULT 15,
  daily_target DECIMAL(4,2) NOT NULL DEFAULT 8.00,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (student_pk),
  CONSTRAINT fk_settings_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reference data: the QR code(s) students scan to Time In / Time Out

INSERT INTO qr_codes (qr_value,company_name,company_address,is_active)
SELECT 'OJT360-COMPANY-001','OJT360 Demo Company','Tacloban City, Leyte',1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM qr_codes WHERE qr_value='OJT360-COMPANY-001');

-- Demo student (Student ID 2026001, password: the same demo password as before)

INSERT INTO students (first_name,middle_name,last_name,course,mobile_number,school_name,complete_address,student_id,institutional_email,password_hash)
SELECT 'James',NULL,'Demo','BS Information Technology','09171234567','OJT360 Demo School','Leyte, Philippines','2026001','james@example.com','$2y$12$pe7k.R.pTKtebGEF1xUj0elUQXALg5d6x3QqZIw3C2PCjNQlCZ./6'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id='2026001');

INSERT INTO internships (student_pk,company_name,company_address,required_hours,completed_hours,start_date,end_date,status)
SELECT s.id,'OJT360 Demo Company','Tacloban City, Leyte',480,0,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY),'active'
FROM students s WHERE s.student_id='2026001' AND NOT EXISTS (SELECT 1 FROM internships i WHERE i.student_pk=s.id);

INSERT INTO announcements (title,message,category,is_active)
SELECT 'Weekly Journal Reminder','Please finish your weekly journal before the end of your assigned week.','Reminder',1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title='Weekly Journal Reminder');

INSERT INTO notifications (student_pk,title,message,icon)
SELECT s.id,'Welcome to OJT360','Your student dashboard is ready. Time in with your company QR to start tracking your OJT hours.','Γ£ª'
FROM students s WHERE s.student_id='2026001' AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.student_pk=s.id AND n.title='Welcome to OJT360');
