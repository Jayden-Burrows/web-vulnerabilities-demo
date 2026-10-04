<?php
require_once __DIR__ . '/helpers.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,   // JavaScript (and therefore XSS) cannot read the session cookie
        'samesite' => 'Lax',  // not sent on cross-site POSTs
    ]);
    session_start();
}

/**
 * Gate for every page and endpoint that needs a logged-in user.
 * Pages redirect to the login form; JSON endpoints answer 401 instead.
 */
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


/* ---------- CSRF protection (synchronizer token) ----------
 * Every state-changing request must carry a secret token that is tied to the
 * visitor's session. A forged request from another site can ride along on the
 * session cookie, but it cannot READ this token, so it cannot include it.
 * Forms send it in a hidden field; fetch() calls send it in an X-CSRF-Token header.
 */
function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

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
    // hash_equals: constant-time comparison, so the token can't be guessed by timing.
    return is_string($sent) && $real !== '' && hash_equals($real, $sent);
}

/** Gate for JSON endpoints that change data. */
function require_csrf(): void
{
    if (!csrf_valid()) {
        json_fail('Invalid or missing CSRF token. Reload the page and try again.', 403);
    }
}
