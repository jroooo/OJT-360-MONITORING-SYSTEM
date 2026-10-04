CREATE DATABASE IF NOT EXISTS ojt360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ojt360;

CREATE TABLE IF NOT EXISTS students (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 first_name VARCHAR(80) NOT NULL,
 middle_name VARCHAR(80) DEFAULT '',
 last_name VARCHAR(80) NOT NULL,
 course VARCHAR(120) NOT NULL,
 mobile_number VARCHAR(40) NOT NULL,
 school_name VARCHAR(180) NOT NULL,
 complete_address VARCHAR(255) NOT NULL,
 student_id VARCHAR(20) NOT NULL UNIQUE,
 institutional_email VARCHAR(160) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 employee_id VARCHAR(50) NOT NULL UNIQUE,
 first_name VARCHAR(80) NOT NULL,
 middle_name VARCHAR(80) DEFAULT '',
 last_name VARCHAR(80) NOT NULL,
 contact_number VARCHAR(40) NOT NULL,
 email VARCHAR(160) NOT NULL UNIQUE,
 department VARCHAR(120) NOT NULL,
 position VARCHAR(120) NOT NULL,
 username VARCHAR(160) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(160) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 student_no VARCHAR(50) NOT NULL UNIQUE,
 course VARCHAR(120) NOT NULL DEFAULT 'BS Information Technology',
 section VARCHAR(80) NOT NULL DEFAULT 'AI23',
 phone VARCHAR(40) DEFAULT '',
 address VARCHAR(255) DEFAULT '',
 profile_image VARCHAR(255) DEFAULT NULL,
 role ENUM('student','adviser','admin') NOT NULL DEFAULT 'student',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS companies (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 company_name VARCHAR(160) DEFAULT NULL,
 rep_first_name VARCHAR(80) DEFAULT '',
 rep_middle_name VARCHAR(80) DEFAULT '',
 rep_last_name VARCHAR(80) DEFAULT '',
 rep_position VARCHAR(120) DEFAULT '',
 contact_number VARCHAR(40) DEFAULT '',
 representative_email VARCHAR(160) DEFAULT '',
 company_username VARCHAR(160) DEFAULT NULL UNIQUE,
 password_hash VARCHAR(255) DEFAULT NULL,
 address VARCHAR(255) DEFAULT '',
 contact_email VARCHAR(160) DEFAULT '',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS company_qr_codes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id INT UNSIGNED NOT NULL,
 qr_value VARCHAR(255) NOT NULL UNIQUE,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS internships (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 company_id INT UNSIGNED NOT NULL,
 required_hours DECIMAL(8,2) NOT NULL DEFAULT 480,
 completed_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
 start_date DATE DEFAULT NULL,
 end_date DATE DEFAULT NULL,
 status ENUM('pending','active','completed') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 company_id INT UNSIGNED NOT NULL,
 time_in DATETIME NOT NULL,
 time_out DATETIME DEFAULT NULL,
 duration_minutes INT UNSIGNED DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
 INDEX(user_id,time_in)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS journals (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 week_start DATE NOT NULL,
 title VARCHAR(180) NOT NULL,
 content TEXT NOT NULL,
 status ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'draft',
 orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY user_week(user_id,week_start),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 icon VARCHAR(10) DEFAULT '•',
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(user_id,is_read)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 category VARCHAR(50) DEFAULT 'General',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 action_text VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS journal_compilations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL UNIQUE,
 title VARCHAR(180) NOT NULL DEFAULT 'OJT Weekly Journal Compilation',
 content LONGTEXT NOT NULL,
 orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
 page_size ENUM('A4','Letter') NOT NULL DEFAULT 'A4',
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 company_id INT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT DEFAULT NULL,
 due_date DATE DEFAULT NULL,
 status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(user_id,status,due_date),
 INDEX(company_id,status),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS task_updates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 status ENUM('pending','in_progress','completed') NOT NULL,
 comment TEXT DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY task_user(task_id,user_id),
 FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS company_notifications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_id INT UNSIGNED NOT NULL,
 task_id INT UNSIGNED NULL,
 user_id INT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(company_id,is_read),
 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
 FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assistant_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 role ENUM('user','assistant') NOT NULL,
 message TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS support_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 subject VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO companies (id,name,company_name,address,contact_email)
SELECT 1,'OJT360 Demo Company','OJT360 Demo Company','Tacloban City, Leyte','demo@ojt360.local'
WHERE NOT EXISTS (SELECT 1 FROM companies WHERE id=1);

INSERT INTO company_qr_codes (company_id,qr_value,is_active)
SELECT 1,'OJT360-COMPANY-001',1
WHERE NOT EXISTS (SELECT 1 FROM company_qr_codes WHERE qr_value='OJT360-COMPANY-001');

INSERT INTO users (full_name,email,password_hash,student_no,course,section,phone,address,role)
SELECT 'James Demo','james@example.com', '$2y$12$pe7k.R.pTKtebGEF1xUj0elUQXALg5d6x3QqZIw3C2PCjNQlCZ./6', '2026001','BS Information Technology','AI23','09171234567','Leyte, Philippines','student'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='james@example.com');

INSERT INTO students (first_name,middle_name,last_name,course,mobile_number,school_name,complete_address,student_id,institutional_email,password_hash)
SELECT 'James','','Demo','BS Information Technology','09171234567','OJT360 Demo School','Leyte, Philippines','2026001','james@example.com', '$2y$12$pe7k.R.pTKtebGEF1xUj0elUQXALg5d6x3QqZIw3C2PCjNQlCZ./6'
WHERE NOT EXISTS (SELECT 1 FROM students WHERE student_id='2026001');

INSERT INTO internships (user_id,company_id,required_hours,completed_hours,start_date,end_date,status)
SELECT u.id,1,480,124.5,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 60 DAY),'active'
FROM users u
WHERE u.email='james@example.com'
AND NOT EXISTS (SELECT 1 FROM internships i WHERE i.user_id=u.id);

INSERT INTO tasks (user_id,company_id,title,description,due_date,status)
SELECT u.id,1,'Prepare weekly OJT progress update','Review your attendance and journal records and prepare a short progress update for your company supervisor.',DATE_ADD(CURDATE(),INTERVAL 3 DAY),'pending'
FROM users u WHERE u.email='james@example.com' AND NOT EXISTS (SELECT 1 FROM tasks t WHERE t.user_id=u.id AND t.title='Prepare weekly OJT progress update');

INSERT INTO tasks (user_id,company_id,title,description,due_date,status)
SELECT u.id,1,'Complete weekly journal','Finish and submit the journal entry for your current OJT week.',DATE_ADD(CURDATE(),INTERVAL 5 DAY),'in_progress'
FROM users u WHERE u.email='james@example.com' AND NOT EXISTS (SELECT 1 FROM tasks t WHERE t.user_id=u.id AND t.title='Complete weekly journal');

INSERT INTO announcements (title,message,category,is_active)
SELECT 'Weekly Journal Reminder','Please submit your weekly journal before the end of your assigned week.','Reminder',1
WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title='Weekly Journal Reminder');

INSERT INTO notifications (user_id,title,message,icon)
SELECT u.id,'Welcome to OJT360','Your student dashboard is ready. Complete your profile and start tracking your OJT attendance.','✦'
FROM users u WHERE u.email='james@example.com'
AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.user_id=u.id AND n.title='Welcome to OJT360');
