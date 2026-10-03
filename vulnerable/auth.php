<?php
function require_login(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: /vulnerable/login.php');
        exit;
    }
}