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

<body class="theme-safe">

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

        <button class="accordion">CSRF &rarr; Per-Session Tokens</button>
        <div class="panel">
            <p><strong>Before:</strong> logout and the save-post endpoint accepted any request carrying a valid
                session cookie, including one triggered by another site the visitor never meant to interact with.</p>
            <p><strong>After:</strong> every form includes a one-time-per-session token from <code>csrf_field()</code>,
                every JavaScript request sends it in an <code>X-CSRF-Token</code> header, and
                <code>csrf_valid()</code> checks it with <code>hash_equals()</code> before anything changes. Logout
                is POST-only, so a plain link can no longer trigger it at all.
            </p>
            <p>Try <a href="/tools/evil-coupons.html">the same forged-request page</a> against this site: the logout
                link does nothing (GET is rejected), and the forged save request comes back with an "Invalid or
                missing CSRF token" error, because the attacker's page has no way to read or guess your token.</p>
        </div>

        <button class="accordion">Unrestricted File Upload &rarr; Verified Content, Server-Generated Names</button>
        <div class="panel">
            <p><strong>Before:</strong> the avatar uploader checked the browser-supplied <code>Content-Type</code>
                &mdash; a label the uploader controls &mdash; and kept the uploader's own filename, extension
                included.</p>
            <p><strong>After:</strong> <a href="account.php">the account page</a> calls PHP's
                <code>getimagesize()</code>, which reads the file's actual header bytes and fails for anything that
                isn't a real image, then generates a random filename with an extension taken from the type it just
                verified. Nothing from the upload itself ends up in the stored file's name.
            </p>
            <p>Try the same spoofed upload here: declare <code>image/jpeg</code> for a file actually containing HTML
                or PHP, named <code>avatar.php</code>. It's rejected with "That file isn't a recognized image," and
                nothing is written to disk.</p>
        </div>

        <button class="accordion">SSRF &rarr; Scheme and IP-Range Checks</button>
        <div class="panel">
            <p><strong>Before:</strong> importing an avatar from a URL called <code>file_get_contents()</code>
                directly on whatever the visitor typed, including <code>file://</code> paths and internal
                addresses.</p>
            <p><strong>After:</strong> <a href="account.php">the account page</a>'s <code>is_fetchable_url()</code>
                allows only <code>http</code>/<code>https</code>, resolves the hostname, and rejects private,
                loopback, and link-local IP ranges with PHP's built-in <code>FILTER_FLAG_NO_PRIV_RANGE</code> /
                <code>FILTER_FLAG_NO_RES_RANGE</code> filters &mdash; then still checks the response is actually an
                image before saving it.
            </p>
            <p>Try <code>file:///path/to/safe/internal-notice.txt</code> or <code>http://127.0.0.1/style.css</code>
                here: both come back "That URL is not allowed" before any request is made.</p>
        </div>

        <button class="accordion">XXE &rarr; No Entity Substitution, DOCTYPE Rejected</button>
        <div class="panel">
            <p><strong>Before:</strong> importing posts from XML parsed the file with <code>LIBXML_NOENT</code>,
                which substitutes entities declared in the file's own <code>DOCTYPE</code> &mdash; including ones
                that point at a local file.</p>
            <p><strong>After:</strong> <a href="account.php">the account page</a> rejects any uploaded XML containing
                a <code>&lt;!DOCTYPE&gt;</code> declaration outright, and never passes <code>LIBXML_NOENT</code> even
                for files that get past that check.</p>
            <p>Try importing the same crafted XML with a <code>SYSTEM</code> entity here: it's rejected as "not
                allowed (DOCTYPE declarations are rejected)" before parsing even starts. A plain XML file with no
                DOCTYPE imports normally.</p>
        </div>

        <button class="accordion">Broken Access Control &rarr; Server-Side Role Check</button>
        <div class="panel">
            <p><strong>Before:</strong> the admin page only checked that someone was logged in, trusting that a
                hidden nav link was protection enough.</p>
            <p><strong>After:</strong> <a href="admin.php">admin.php</a> calls <code>require_admin()</code>
                (<code>safe/auth.php</code>), which checks the signed-in user's own <code>is_admin</code> flag and
                returns a 403 for anyone else &mdash; regardless of whether they were ever shown a link.</p>
            <p>Sign in as <code>guest</code> / <code>password</code> and try going straight to
                <a href="admin.php">admin.php</a>: 403. Only <code>admin_demo</code> / <code>password</code> can get
                in.
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