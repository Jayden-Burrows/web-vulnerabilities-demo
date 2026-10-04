<?php
require __DIR__ . '/auth.php';
start_secure_session();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?= csrf_meta() ?>
    <title>Secure Corp — Live Demo</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <header class="app-header">
        <a class="logo" href="index.php">Live Demo</a>
        <nav class="app-header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="post" action="logout.php" class="logout-form"><?= csrf_field() ?><button type="submit" class="login">Logout</button></form>
            <?php else: ?>
                <a class="login" href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="corp-body">
        <h1 class="corp-statement">The Secure Version: Same App, Fixed Code</h1>
        <p class="corp-paragraph">
            This is the same site as the vulnerable version, with each flaw fixed. Try the exact attacks that worked
            on the vulnerable site (the same SQL injection, the same script in a post, the same ID tampering) and
            compare what happens. The <strong>Attacker tools</strong> button (bottom right) gives you the same editable address bar and request sender as on the vulnerable site. The sections below explain what changed in the code.
        </p>

        <button class="accordion">SQL Injection &rarr; Prepared Statements</button>
        <div class="panel">
            <p><strong>Before:</strong> the login query was built by pasting the username and password hash into the
                SQL string, so input like <code>' OR 1=1 --</code> became part of the query itself.</p>
            <p><strong>After:</strong> the query text is fixed and uses <code>?</code> placeholders; the values are
                bound separately with <code>$stmt-&gt;execute([$uname, $hashed_psw])</code>. The database treats them
                purely as data, so a quote character is just a quote character.</p>
            <p>Other changes: the failed-login page no longer shows raw SQL errors, the session ID is regenerated on
                login, and the old cookie that stored the username and password in plain text is gone.</p>
        </div>

        <button class="accordion">IDOR &rarr; Ownership Checks + UUIDs</button>
        <div class="panel">
            <p><strong>Before:</strong> posts and drafts were looked up by a sequential number, and nothing checked
                whether the record belonged to the logged-in user, so changing <code>?draft_id=4</code> to
                <code>5</code> showed (and could edit or delete) someone else's content.</p>
            <p><strong>After (the real fix):</strong> every read, edit, publish and delete of a draft includes
                <code>AND author_id = ?</code> with the <em>session's</em> user ID, and a record that isn't yours
                gets the same "not found" as one that doesn't exist.</p>
            <p><strong>After (defense in depth):</strong> post IDs are random UUIDs instead of 1, 2, 3&hellip;, so IDs
                can't be guessed by counting. UUIDs alone are not authorization, which is why the checks above
                still exist.</p>
        </div>

        <button class="accordion">Cross-Site Scripting &rarr; Output Escaping</button>
        <div class="panel">
            <p><strong>Before:</strong> post messages, locations and names were printed straight into the page, so
                a post containing <code>&lt;img src=x onerror=...&gt;</code> ran as code in every viewer's browser.</p>
            <p><strong>After:</strong> every value that comes from the database or the request goes through
                <code>htmlspecialchars()</code> (wrapped in a small <code>e()</code> helper) before it is printed,
                which turns <code>&lt;</code> into <code>&amp;lt;</code> so it shows up as text. The session cookie is
                also marked <code>HttpOnly</code>, so scripts can't read it even if one slipped through.</p>
        </div>

        <button class="accordion">Password Storage &rarr; bcrypt</button>
        <div class="panel">
            <p><strong>Before:</strong> passwords were stored as a plain SHA-256 hash. SHA-256 is fast and unsalted, so
                two users with the same password get the same hash, and an attacker who steals the database can test
                billions of guesses per second.</p>
            <p><strong>After:</strong> <code>password_hash()</code> creates a salted bcrypt hash: a random salt per
                password and a deliberately slow cost factor. Login looks the user up by username only, then checks the
                password in PHP with <code>password_verify()</code>, so the password never goes anywhere near SQL.</p>
            <p>Also: wrong username and wrong password give the same message, a dummy hash is checked for unknown
                usernames so timing doesn't reveal which accounts exist, and hashes are upgraded automatically if the
                cost factor is ever raised. The vulnerable site still uses unsalted SHA-256 so you can compare.</p>
        </div>

        <button class="accordion">CSRF &rarr; Anti-CSRF Tokens</button>
        <div class="panel">
            <p><strong>Before:</strong> a request that changes data only needed your session cookie, which the browser
                attaches automatically, so a malicious page could make your browser submit a form or call an endpoint
                as you.</p>
            <p><strong>After:</strong> every state-changing request must also carry a random, per-session token (a
                hidden form field, or an <code>X-CSRF-Token</code> header for <code>fetch()</code>), checked with
                <code>hash_equals()</code>. Another site can ride along on your cookie but can't read the token. The
                session cookie is also <code>SameSite=Lax</code>, and logout is now a POST form instead of a link.</p>
            <p>Try it: in Attacker tools &rarr; <em>Send request</em>, untick &ldquo;Include my CSRF token&rdquo; and
                send the request. You get a 403 before the server even looks at the post ID.</p>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="posts/index.php"><i class="fa-solid fa-house"></i></a>
        <button type="button" id="create-btn"><i class="fa-solid fa-plus"></i></button>
        <a href="posts/profile.php"><i class="fa-solid fa-user"></i></a>
    </nav>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script>
        document.querySelectorAll(".accordion").forEach(accordion => {
            accordion.addEventListener("click", function () {
                this.classList.toggle("panel-open");
                const panel = this.nextElementSibling;
                panel.style.maxHeight = panel.style.maxHeight ? null : panel.scrollHeight + "px";
            });
        });
    </script>
    <script src="posts/js/create-post-modal.js"></script>
    <?php include __DIR__ . '/../tools/attacker-tools.php'; ?>
</body>

</html>
