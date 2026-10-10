<?php
require_once __DIR__ . '/session.php';
start_vuln_session();
session_destroy();
header('Location: /vulnerable/login.php');
exit;