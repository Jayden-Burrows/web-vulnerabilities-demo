<?php
require_once __DIR__ . '/session.php';

function require_login(): void
{
    start_vuln_session();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /vulnerable/login.php');
        exit;
    }
}