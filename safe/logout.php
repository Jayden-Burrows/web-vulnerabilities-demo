<?php
require __DIR__ . '/auth.php';
start_secure_session();
$_SESSION = [];
session_destroy();
header('Location: /safe/login.php');
exit;