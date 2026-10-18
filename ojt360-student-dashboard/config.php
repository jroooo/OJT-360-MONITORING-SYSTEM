<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'ojt360';
const DB_USER = 'root';
const DB_PASS = '';

/* One clock for PHP and MySQL so Time In / Time Out / Break math never drifts.
   Change both values together if you are not in the Philippines (UTC+8). */
const APP_TIMEZONE = 'Asia/Manila';
const APP_TZ_OFFSET = '+08:00';
date_default_timezone_set(APP_TIMEZONE);

const SCHEMA_VERSION = 18;

function assistant_config(): array {
    static $config = null;
    if ($config !== null) return $config;
    $config = [
        'api_key' => getenv('GEMINI_API_KEY') ?: '',
        'model' => getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash',
        'web_search' => filter_var(getenv('GEMINI_WEB_SEARCH') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    ];
    $local = __DIR__ . '/config.local.php';
    if (is_file($local)) {
        $localConfig = require $local;
        if (is_array($localConfig)) $config = array_merge($config, $localConfig);
    }
    return $config;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        try { $pdo->exec("SET time_zone = '" . APP_TZ_OFFSET . "'"); } catch (Throwable $e) { /* keep server default */ }
        ensure_runtime_tables($pdo);
    }
    return $pdo;
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

/* Every table the student dashboard uses. Mirrors database/schema.sql. */
function schema_statements(): array {
    return [
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS qr_codes (
  id INT(11) NOT NULL AUTO_INCREMENT,
  qr_value VARCHAR(120) NOT NULL,
  company_name VARCHAR(150) NOT NULL,
  company_address VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY qr_value (qr_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS tasks (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  source VARCHAR(12) NOT NULL DEFAULT 'company',
  company_name VARCHAR(150) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_status (student_pk,status,due_date),
  CONSTRAINT fk_tasks_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS task_updates (
  id INT(11) NOT NULL AUTO_INCREMENT,
  task_id INT(11) NOT NULL,
  student_pk INT(11) NOT NULL,
  status ENUM('pending','in_progress','completed') NOT NULL,
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY task_student (task_id,student_pk),
  CONSTRAINT fk_taskupd_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_taskupd_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS announcements (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  category VARCHAR(50) DEFAULT 'General',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  action_text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_created (student_pk,created_at),
  CONSTRAINT fk_activity_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS assistant_messages (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  student_pk INT(11) NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY student_created (student_pk,created_at),
  CONSTRAINT fk_assistant_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
<<<'SQL'
CREATE TABLE IF NOT EXISTS student_settings (
  student_pk INT(11) NOT NULL,
  shift_start TIME NOT NULL DEFAULT '08:00:00',
  grace_minutes SMALLINT(5) UNSIGNED NOT NULL DEFAULT 15,
  daily_target DECIMAL(4,2) NOT NULL DEFAULT 8.00,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (student_pk),
  CONSTRAINT fk_settings_student FOREIGN KEY (student_pk) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
    ];
}

/* Creates any missing table. Safe to run on every request; it only does real work once per login session. */
function ensure_runtime_tables(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    if (($_SESSION['ojt_schema'] ?? 0) === SCHEMA_VERSION) return;

    // A database from the old layout (users table, user_id / company_id columns) must be reset first.
    $legacy = false;
    foreach (['attendance', 'daily_logs', 'journals', 'notifications'] as $table) {
        $exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $exists->execute([$table]);
        if ((int)$exists->fetchColumn() > 0 && column_exists($pdo, $table, 'user_id')) { $legacy = true; break; }
    }
    if ($legacy) {
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><body style="font-family:Arial;max-width:640px;margin:60px auto;line-height:1.6"><h2>Database needs the one-time reset</h2><p>This version of OJT360 uses a student-only database (a single <code>students</code> table and <code>student_pk</code> columns). Your database still has the old layout.</p><p>In phpMyAdmin, open the <b>' . DB_NAME . '</b> database, go to the <b>SQL</b> tab, run <code>database/reset-student-tables.sql</code>, then reload this page. Your registered students in the <code>students</code> table are kept.</p></body>');
    }

    foreach (schema_statements() as $sql) $pdo->exec($sql);
    $pdo->exec(<<<'SQL'
INSERT INTO qr_codes (qr_value,company_name,company_address,is_active)
SELECT 'OJT360-COMPANY-001','OJT360 Demo Company','Tacloban City, Leyte',1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM qr_codes WHERE qr_value='OJT360-COMPANY-001')
SQL);

    foreach (['daily_logs', 'journal', 'profile'] as $folder) {
        $dir = __DIR__ . '/uploads/' . $folder;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
    }
    $_SESSION['ojt_schema'] = SCHEMA_VERSION;
}
