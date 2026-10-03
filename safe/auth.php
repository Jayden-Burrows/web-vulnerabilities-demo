<?php
require_once __DIR__ . '/helpers.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

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
