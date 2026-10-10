<?php
require __DIR__ . '/auth.php';
start_secure_session();

// Logging out requires POSTing a CSRF token to prevent stored CSRF attacks
// ex: clicking <a href="logout.php"> on another site won't log a user out from this site
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /safe/index.php');
    exit;
}
if (!csrf_valid()) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /safe/login.php');
exit;
