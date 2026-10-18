
<?php
/*
|--------------------------------------------------------------------------
| OJT360 Company Portal - shared bootstrap
|--------------------------------------------------------------------------
| Config, secure session, single PDO connection, auth guard, CSRF, helpers.
| DB settings: defaults (XAMPP) < config/config.local.php < environment vars
| (OJT_DB_HOST, OJT_DB_NAME, OJT_DB_USER, OJT_DB_PASS, OJT_TIMEZONE).
*/
if (defined('OJT_BOOTSTRAP')) {
    return;
}
define('OJT_BOOTSTRAP', true);

$OJT_CFG = [
    'db_host' => 'localhost',
    'db_name' => 'ojt360',
    'db_user' => 'root',
    'db_pass' => '',
    'timezone' => 'Asia/Manila',
    'session_idle_seconds' => 7200,
];

$ojtLocal = __DIR__ . '/../config/config.local.php';
if (is_file($ojtLocal)) {
    $o = require $ojtLocal;
    if (is_array($o)) {
        $OJT_CFG = array_merge($OJT_CFG, $o);
    }
}

foreach ([
    'db_host' => 'OJT_DB_HOST',
    'db_name' => 'OJT_DB_NAME',
    'db_user' => 'OJT_DB_USER',
    'db_pass' => 'OJT_DB_PASS',
    'timezone' => 'OJT_TIMEZONE',
] as $k => $env) {
    $v = getenv($env);
    if ($v !== false && $v !== '') {
        $OJT_CFG[$k] = $v;
    }
}

date_default_timezone_set($OJT_CFG['timezone']);

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/* ------------------------------- Session ------------------------------- */

function ojt_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('OJT360_COMPANY');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/* ------------------------------ Database ------------------------------- */

function db(): PDO
{
    global $OJT_CFG;
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . $OJT_CFG['db_host']
                . ';dbname=' . $OJT_CFG['db_name']
                . ';charset=utf8mb4',
            $OJT_CFG['db_user'],
            $OJT_CFG['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $ex) {
        error_log('OJT360 DB connection failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('The service is temporarily unavailable. Please try again later.');
    }

    return $pdo;
}

/* ------------------------------- Helpers ------------------------------- */

function e($v): string
{
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $msg): void
{
    ojt_start_session();
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_get(): array
{
    ojt_start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    ojt_start_session();

    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . e(csrf_token()) . '">';
}

/**
 * Validates CSRF tokens from regular forms or AJAX requests.
 * Existing callers using csrf_valid() continue to work.
 * Callers may optionally pass a token explicitly.
 */
function csrf_valid(?string $submittedToken = null): bool
{
    ojt_start_session();

    if ($submittedToken === null) {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $formToken = $_POST['_csrf'] ?? '';

        if (is_string($headerToken) && $headerToken !== '') {
            $submittedToken = $headerToken;
        } else {
            $submittedToken = is_string($formToken) ? $formToken : '';
        }
    }

    return is_string($submittedToken)
        && isset($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && $submittedToken !== ''
        && hash_equals($_SESSION['csrf'], $submittedToken);
}

/** Call at the top of every POST handler on protected pages. */
function require_csrf(string $backTo): void
{
    if (!csrf_valid()) {
        flash_set(
            'error',
            'Your session expired or the request was invalid. Please try again.'
        );
        redirect($backTo);
    }
}

function initials(
    string $first,
    string $last = '',
    string $fallback = 'C'
): string {
    $a = mb_substr(trim($first), 0, 1);
    $b = mb_substr(trim($last), 0, 1);
    $s = mb_strtoupper($a . $b);

    return $s !== ''
        ? $s
        : mb_strtoupper(mb_substr($fallback, 0, 1));
}

function validate_password_policy(string $pw): ?string
{
    if (strlen($pw) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (strlen($pw) > 72) {
        return 'Password must be 72 characters or fewer.';
    }

    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) {
        return 'Password must contain at least one letter and one number.';
    }

    return null;
}

function profile_from_row(array $c): array
{
    $first = trim($c['rep_first_name'] ?? '');
    $last = trim($c['rep_last_name'] ?? '');
    $rep = trim($first . ' ' . $last);
    $name = $c['company_name'] ?? 'Company';

    return [
        'id' => (int) $c['id'],
        'name' => $name,
        'first' => $first,
        'last' => $last,
        'representative' => $rep !== '' ? $rep : 'Company Representative',
        'role' => ($c['rep_position'] ?? '') !== ''
            ? $c['rep_position']
            : 'Representative',
        'initials' => initials($first, $last, $name),
        'email' => $c['representative_email'] ?? '',
        'username' => $c['company_username'] ?? '',
        'row' => $c,
    ];
}

/** Returns the authenticated company profile or redirects to login. */
function require_company(): array
{
    global $OJT_CFG;

    ojt_start_session();

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $cid = $_SESSION['company_id'] ?? null;
    $last = $_SESSION['last_activity'] ?? 0;

    if (!$cid || (time() - (int) $last) > (int) $OJT_CFG['session_idle_seconds']) {
        $_SESSION = [];

        if ($cid) {
            flash_set('error', 'Your session has expired. Please sign in again.');
        }

        redirect('login.php');
    }

    $st = db()->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
    $st->execute([(int) $cid]);
    $row = $st->fetch();

    if (!$row) {
        $_SESSION = [];
        redirect('login.php');
    }

    $_SESSION['last_activity'] = time();

    return profile_from_row($row);
}

/** Aggregate metrics for one company. */
function company_stats(int $cid): array
{
    static $cache = [];

    if (isset($cache[$cid])) {
        return $cache[$cid];
    }

    $pdo = db();

    $st = $pdo->prepare(
        'SELECT COUNT(*) n,
                COALESCE(SUM(completed_hours), 0) done,
                COALESCE(SUM(required_hours), 0) req
         FROM students
         WHERE company_id = ?'
    );
    $st->execute([$cid]);
    $r = $st->fetch();

    $st = $pdo->prepare(
        "SELECT COUNT(*)
         FROM students s
         WHERE s.company_id = ?
           AND NOT EXISTS (
               SELECT 1
               FROM evaluations e
               WHERE e.student_id = s.id
                 AND e.company_id = s.company_id
                 AND e.status = 'Completed'
           )"
    );
    $st->execute([$cid]);
    $pending = (int) $st->fetchColumn();

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM attendance WHERE company_id = ? AND date = ?'
    );
    $st->execute([$cid, date('Y-m-d')]);
    $today = (int) $st->fetchColumn();

    $done = (int) $r['done'];
    $req = (int) $r['req'];
    $total = (int) $r['n'];

    return $cache[$cid] = [
        'interns' => $total,
        'pending' => $pending,
        'completed' => $total - $pending,
        'hours_done' => $done,
        'hours_req' => $req,
        'progress' => $req > 0
            ? min(100, (int) round($done / $req * 100))
            : 0,
        'today' => $today,
    ];
}

function pct(int $done, int $req): int
{
    return $req > 0
        ? max(0, min(100, (int) round($done / $req * 100)))
        : 0;
}

function name_ok(string $s, int $max = 50): bool
{
    return $s !== ''
        && mb_strlen($s) <= $max
        && preg_match(
            "/^[\\p{L}\\p{M}][\\p{L}\\p{M}\\s.'\\-]*$/u",
            $s
        ) === 1;
}
