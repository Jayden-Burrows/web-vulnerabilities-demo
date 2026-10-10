<?php
require_once __DIR__ . '/helpers.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    } else {
        session_name('SAFESESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Prevents logged out users from accessing protected endpoints
function require_login(bool $asJson = false): void
{
    start_secure_session();
    if (!isset($_SESSION['user_id'])) {
        if ($asJson) {
            json_fail('You must be logged in.', 401);
        }
        header('Location: /safe/login.php');
        exit;
    }
}

// Protect data by attaching a secret token to this user's session. When a request is made, 
// require_csrf() is used in the backend logic to ensure that the csrf_token is attached.
// Otherwise, it won't allow the request to go 
// change any data. A forged request from another site can ride along on the session cookie, 
// but it can't read the CSRF token, and therefore can't include it.
function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Attached with forms that change the state of data in the database
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}


function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    start_secure_session();
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $real = $_SESSION['csrf_token'] ?? '';
    // hash_equals is used to guarantee a constant-time comparison, so attackers
    // can't guess the token based on the timing of when the comparison between the 
    // sent and real token is finished
    return is_string($sent) && $real !== '' && hash_equals($sent, $real);
}

function require_csrf(): void
{
    if (!csrf_valid()) {
        json_fail('Invalid or missing CSRF token. Reload the page and try again.', 403);
    }
}