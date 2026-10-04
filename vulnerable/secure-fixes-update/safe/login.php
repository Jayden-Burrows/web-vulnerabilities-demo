<?php
require __DIR__ . '/db.php';
start_secure_session();

$pdo = get_db();

$loginError = '';
$shownQuery = '';

// Handle login submission.
// SAFE VERSION:
//  1. CSRF token: the form must come from THIS site (stops "login CSRF").
//  2. The SQL text is fixed and has only a ? placeholder for the username.
//  3. The password is checked with password_verify() against a salted bcrypt hash.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = str_param($_POST, 'uname');
    $psw = str_param($_POST, 'psw');

    $shownQuery = 'SELECT id, username, pass FROM users WHERE username = ?';

    if (!csrf_valid()) {
        $loginError = 'Your session expired. Please try again.';
    } else {
        try {
            $user = authenticate($pdo, $uname, $psw);

            if ($user) {
                session_regenerate_id(true);   // new session ID on login (prevents session fixation)
                unset($_SESSION['csrf_token']);  // and a fresh CSRF token for the new session
                $_SESSION['user_id'] = $user['id'];
                header('Location: /safe/posts/index.php');
                exit;
            }

            // Same message whether the username or the password was wrong.
            $loginError = 'Invalid username or password.';
        } catch (PDOException $e) {
            // Log the real error server-side; show the visitor nothing useful.
            error_log('Login query failed: ' . $e->getMessage());
            $loginError = 'Something went wrong. Please try again.';
        }
    }
}

// Already logged in from an earlier request: skip the form.
if (isset($_SESSION['user_id'])) {
    header('Location: /safe/posts/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login — Secure Corp</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/material-design-iconic-font@2.2.0/dist/css/material-design-iconic-font.min.css">
</head>

<body>

    <div class="login-page">
        <div class="login-side-content">
            <a href="index.php" class="login-side-link">Live Demo</a>
            <p class="login-side-text">This is the secure version of the login form. It uses a prepared
                statement, bcrypt-hashed passwords, a CSRF token — try the same SQL injection you used on the vulnerable site and watch it fail.</p>
        </div>

        <div class="login-panel">
            <div class="login-panel-inner">

                <h1 class="login-title">Welcome back</h1>
                <p class="login-subtitle">Enter your details to continue</p>

                <?php if ($loginError): ?>
                    <p class="login-error"><?= e($loginError) ?></p>
                <?php endif; ?>

                <form method="post" action="login.php" class="login-form">
                    <?= csrf_field() ?>
                    <label for="uname">Username</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" placeholder="Enter Username" name="uname" id="uname" required>
                    </div>

                    <label for="psw">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" placeholder="Enter Password" name="psw" id="psw" required>
                        <button type="button" class="toggle-password" onclick="showPassword()"
                            aria-label="Show password">
                            <i class="fa-solid fa-eye" id="toggle-password-icon"></i>
                        </button>
                    </div>

                    <button type="submit">Sign in</button>
                </form>

                <?php if ($shownQuery && $loginError): ?>
                    <div class="sql-debug">
                        <p class="sql-debug-label">Query the server ran (your input is bound separately, never pasted in). The password is then checked with password_verify():</p>
                        <pre class="sql-debug-query"><?= e($shownQuery) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script>
        const passInput = document.getElementById('psw');
        const toggleIcon = document.getElementById('toggle-password-icon');

        function showPassword() {
            const showing = passInput.type === 'text';
            passInput.type = showing ? 'password' : 'text';
            toggleIcon.classList.toggle('zmdi-eye', showing);
            toggleIcon.classList.toggle('zmdi-eye-off', !showing);
        }
    </script>
</body>

</html>