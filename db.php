<?php
/*
|--------------------------------------------------------------------------
| OJT360 Database Connection & Global Helpers
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$db   = 'ojt360';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Helper function to fetch company details safely mapped to your registration schema
function getCompanyProfile($pdo, $company_id = 1) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $c = $stmt->fetch();
    
    if (!$c) {
        return [
            'id' => 1,
            'name' => 'Company Portal',
            'representative' => 'Administrator',
            'role' => 'Company Representative',
            'initials' => 'CP',
            'email' => '',
            'username' => ''
        ];
    }

    $repFullName = trim(($c['rep_first_name'] ?? '') . ' ' . ($c['rep_last_name'] ?? ''));
    $firstInitial = !empty($c['rep_first_name']) ? substr($c['rep_first_name'], 0, 1) : 'C';
    $lastInitial = !empty($c['rep_last_name']) ? substr($c['rep_last_name'], 0, 1) : 'P';
    $initials = strtoupper($firstInitial . $lastInitial);

    return [
        'id' => $c['id'],
        'name' => $c['company_name'] ?? 'Company Portal',
        'representative' => !empty($repFullName) ? $repFullName : 'Administrator',
        'role' => $c['rep_position'] ?? 'Company Representative',
        'initials' => $initials,
        'email' => $c['representative_email'] ?? '',
        'username' => $c['company_username'] ?? ''
    ];
}
?>