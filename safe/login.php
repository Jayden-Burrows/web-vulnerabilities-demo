<?php
require __DIR__ . '/db.php';
start_secure_session();

// Prevents browsers from showing cached copies of CSRF tokens
// that no longer work
header('Cache-Control: no-store');

$pdo = get_db();

$loginError = '';
$shownQuery = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ensure the username and password provided are plain strings
    $uname = str_param($_POST, 'uname');
    $psw = str_param($_POST, 'psw');

    $shownQuery = 'SELECT id, username, pass FROM users WHERE username = ?';

    if (!csrf_valid()) {
        $loginError = 'Your session expired. Please try again.';
    } else {
        try {
            // check the provided password with the user's using 
            // password_verify which is implemented in PHP instead of directly
            // comparing inside the SQL query.
            $user = authenticate($pdo, $uname, $psw);

            if ($user) {
                session_regenerate_id(true); // new session ID on login (prevents session fixation)
                unset($_SESSION['csrf_token']); // add a fresh CSRF token for the new session
                $_SESSION['user_id'] = $user['id'];
                header('Location: /safe/posts/index.php');
                exit;
            }
            // If login failed, provide an error message
            $loginError = 'Invalid username or password.';
        } catch (PDOException $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $loginError = 'Something went wrong. Please try again.';
        }
    }
}

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
    <title>Login</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>

    <div class="login-page">
        <div class="login-side-content">
            <a href="index.php" class="login-side-link">Live Demo</a>
            <p class="login-side-text">This is the secure version of the login form. It uses a prepared
                statement; try the same SQL injection you used on the vulnerable site and watch it fail.</p>
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
                        <input type="text" placeholder="Enter Username" name="uname" id="uname" required data-straight
                            autocorrect="off" autocapitalize="off" spellcheck="false">
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

                <div class="payload-chips" id="payload-chips" hidden>
                    <p>Typing symbols on a phone is fiddly. Tap to fill in the username:</p>
                    <button type="button" data-fill="' OR 1=1 --">' OR 1=1 --</button>
                    <button type="button" data-fill="bob_demo' --">bob_demo' --</button>
                </div>

                <?php if ($shownQuery && $loginError): ?>
                    <div class="sql-debug">
                        <p class="sql-debug-label">Query the server ran (your input is bound separately, never pasted in):
                        </p>
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
            toggleIcon.classList.toggle('fa-eye', showing);
            toggleIcon.classList.toggle('fa-eye-slash', !showing);
        }
    </script>
</body>

</html>