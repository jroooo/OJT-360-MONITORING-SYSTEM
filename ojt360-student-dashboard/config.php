<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'ojt360';
const DB_USER = 'root';
const DB_PASS = '';

function openai_config(): array {
    static $config = null;
    if ($config !== null) return $config;
    $config = [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model' => getenv('OPENAI_MODEL') ?: 'gpt-6-luna',
        'web_search' => filter_var(getenv('OPENAI_WEB_SEARCH') ?: 'true', FILTER_VALIDATE_BOOLEAN),
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
        ensure_runtime_tables($pdo);
    }
    return $pdo;
}

function ensure_runtime_tables(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS assistant_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        role ENUM('user','assistant') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX(user_id, created_at)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS journal_compilations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL UNIQUE,
        title VARCHAR(180) NOT NULL DEFAULT 'OJT Weekly Journal Compilation',
        content LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
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
    ) ENGINE=InnoDB");
    try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER address"); } catch (Throwable $e) { /* already exists */ }
    $done = true;
}
