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
    <title>Live Demo - Secure Site</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body>

    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <nav class="app-header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="post" action="logout.php" class="logout-form"><?= csrf_field() ?><button type="submit"
                        class="login">Logout</button></form>
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
            compare what happens. The <code>Attacker tools</code> button gives you access to the same editable URL bar
            and request sender as on the vulnerable site. The sections below explain what changed in the code.
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
                whether the record belonged to the logged-in user, so changing <code>?draft_id=3</code> to
                <code>6</code> showed (and could edit or delete) someone else's content.
            </p>
            <p><strong>After:</strong> every read, edit, publish and delete of a draft includes
                <code>AND author_id = ?</code> with the <em>session's</em> user ID, and a record that isn't yours
                gets the same "not found" as one that doesn't exist.
            </p>
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
                also marked <code>HttpOnly</code>, so scripts can't read it even if one slipped through.
            </p>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="posts/index.php"><i class="fa-solid fa-house"></i></a>
        <button type="button" id="create-btn"><i class="fa-solid fa-plus"></i></button>
        <a href="posts/profile.php"><i class="fa-solid fa-user"></i></a>
    </nav>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script src="/js/straight-input.js"></script>
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