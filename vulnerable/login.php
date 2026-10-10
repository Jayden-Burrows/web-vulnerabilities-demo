<?php
require_once __DIR__ . '/session.php';
start_vuln_session();

require __DIR__ . '/db.php';

$pdo = get_db();

$loginError = '';
$sqlError = '';
$shownQuery = '';
$loginResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_POST['uname'] ?? '';
    $psw = $_POST['psw'] ?? '';
    $hashed_psw = hash('sha256', $psw);

    $query = "SELECT * FROM users WHERE username = '$uname' AND pass = '$hashed_psw'";
    $shownQuery = $query;

    try {
        $result = $pdo->query($query);
        $matches = $result->fetchAll(PDO::FETCH_ASSOC);

        if (count($matches) > 0) {
            $user = $matches[0];
            $loginResult = ['matchCount' => count($matches), 'user' => $user];
            $_SESSION['user_id'] = $user['id'];

            setcookie(
                'auth_token',
                $uname . "_" . $psw,
                [
                    'expires' => time() + 3600,
                    'path' => '/',
                ]
            );
        } else {
            $loginError = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        $sqlError = $e->getMessage();
    }
}

if (isset($_SESSION['user_id']) && !$loginResult) {
    header('Location: /vulnerable/posts/index.php');
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
            <p class="login-side-text">This login form is intentionally vulnerable to SQL injection; give it a
                try!</p>
        </div>

        <div class="login-panel">
            <div class="login-panel-inner">

                <h1 class="login-title">Welcome back</h1>
                <p class="login-subtitle">Enter your details to continue</p>

                <?php if ($loginError): ?>
                    <p class="login-error"><?= htmlspecialchars($loginError) ?></p>
                <?php endif; ?>

                <form method="post" action="login.php" class="login-form">
                    <label for="uname">Username</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" placeholder="Enter Username" name="uname" id="uname" required data-straight>
                    </div>

                    <label for="psw">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" placeholder="Enter Password" name="psw" id="psw" required data-straight>
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

                <?php if ($shownQuery && ($sqlError || $loginError)): ?>
                    <div class="sql-debug">
                        <p class="sql-debug-label">Query the server just ran:</p>
                        <pre class="sql-debug-query"><?= htmlspecialchars($shownQuery) ?></pre>

                        <?php if ($sqlError): ?>
                            <p class="sql-debug-error">SQL error: <?= htmlspecialchars($sqlError) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php if ($loginResult): ?>
        <div id="login-alert" class="modal" style="display:block;">
            <div class="modal-content animate">
                <div class="container">
                    <p class="sql-debug-label">Query the server just ran:</p>
                    <pre class="sql-debug-query"><?= htmlspecialchars($shownQuery) ?></pre>
                    <p class="sql-debug-success">
                        Matched <?= (int) $loginResult['matchCount'] ?> row(s).
                        Logged in as the first one returned:
                        <strong><?= htmlspecialchars($loginResult['user']['username']) ?></strong>
                        (id #<?= htmlspecialchars($loginResult['user']['id']) ?>).
                    </p>
                    <button type="button" onclick="closeLoginAlert()">Close</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script src="/js/straight-input.js"></script>
    <script src="/js/payload-chips.js"></script>
    <script>
        const passInput = document.getElementById('psw');
        const toggleIcon = document.getElementById('toggle-password-icon');

        function showPassword() {
            const showing = passInput.type === 'text';
            passInput.type = showing ? 'password' : 'text';
            toggleIcon.classList.toggle('fa-eye', showing);
            toggleIcon.classList.toggle('fa-eye-slash', !showing);
        }

        function closeLoginAlert() {
            window.location.href = '/vulnerable/posts/index.php';
        }
    </script>
    <?php include __DIR__ . '/../tools/attacker-tools.php'; ?>

</body>

</html>