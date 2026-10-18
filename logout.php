```php
<?php
require_once __DIR__ . '/includes/bootstrap.php';

ojt_start_session();

if (is_post() && csrf_valid()) {

    // Clear all session data
    $_SESSION = [];

    // Remove the session cookie
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Destroy the current session
    session_destroy();

    // Start a fresh session for the login page
    ojt_start_session();
    session_regenerate_id(true);

    // Show confirmation on the login page
    flash_set('success', 'You have been signed out.');

    // Redirect directly to login.php
    redirect('login.php');
}

// If logout is accessed directly or CSRF validation fails,
// send the user to the login page
redirect('login.php');
