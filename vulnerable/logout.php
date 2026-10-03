<?php
session_start();
session_destroy();
header('Location: /vulnerable/login.php');
exit;