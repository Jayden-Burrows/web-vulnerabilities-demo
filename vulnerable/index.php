<?php
require_once __DIR__ . '/session.php';
start_vuln_session();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Live Demo - Vulnerable Site</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-vuln">

    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <nav class="app-header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="login" href="/vulnerable/logout.php">Logout</a>
            <?php else: ?>
                <a class="login" href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="corp-body">
        <h1 class="corp-statement">Getting First-Hand Experience Learning How Hackers Exploit Security Flaws</h1>
        <p class="corp-paragraph">
            The purpose of this website is to give users a first-hand experience exploiting security vulnerabilities in
            application code, so they can better understand how attackers may try to attack their websites. The specific
            vulnerabilities in this website relate to <a
                href="https://jayden-burrows.me/nmc/4020/module-1/project-one/sql-injections.html">SQL Injections</a>,
            <a href="https://jayden-burrows.me/nmc/4020/module-1/project-one/idor.html">IDOR attacks</a>, <a
                href="https://jayden-burrows.me/nmc/4020/module-1/project-one/cross-site-scripting.html">Cross-Site
                Scripting</a>. The links provided should provide a short overview of these attacks and give you some
            ideas of how to approach this page.
        </p>
        <p class="corp-paragraph">
            If you are struggling with getting started, try checking some of the sections below for hints and
            walkthroughs.
        </p>
        <button class="accordion">Getting Started</button>
        <div class="panel">
            <p>Everything on this site uses fake data. To log in without attacking anything, use a demo account:
                <code>guest</code> / <code>password</code>.
                For each topic below, the hints give progressively more information about how to find and approach the
                vulnerabilities in the site.
            </p>
            <p>On a phone? Your keyboard may swap <code>--</code> for a long dash or straighten quotes into curly
                ones. The attack fields on this site convert them back automatically, and once you've opened the SQL
                injection answer, the login page shows tap-to-fill buttons for the payloads.</p>
            <p>When you're done, check out <code>safe/</code> to compare how the secure site behaves.</p>
            <p>This website can't give you access to the browser URL bar or developer tools, so use the
                <code>Attacker tools</code> button. This button gives you access to an URL bar that you can modify and
                to a request sender.
            </p>
        </div>

        <button class="accordion">SQL Injections</button>
        <div class="panel">
            <p><strong>Goal:</strong> log in as <strong>a</strong> user without knowing their password.</p>
            <details class="hint">
                <summary>Hint 1: Look at what the server tells you</summary>
                <p>Try filling out the form and submitting it. The page shows you the query the server just ran and how
                    your input affected it. Consider what would happen if your input contained a single
                    quote (<code>'</code>)?</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Break out of the query</summary>
                <p>The query looks like
                    <code>SELECT * FROM users WHERE username = '...' AND pass = '...'</code>. If you close the
                    first quote yourself, you can add your own SQL after it. In SQL, <code>--</code> starts a comment,
                    so everything after it is ignored. What part of the query would you most like the database to
                    ignore?
                </p>
            </details>
            <details class="hint" data-unlock="sqli-answer">
                <summary>Hint 3: The answer</summary>
                <p>Enter <code>' OR 1=1 --</code> as the username with any password. The query
                    <code>SELECT * FROM users WHERE username = ' OR 1=1--' AND pass = '...'</code> always evaluates to
                    <code>TRUE</code> , so
                    the database returns every user, and the app logs you in as the first one. To become a specific
                    user, you can comment out the password check instead: <code>bob_demo' --</code>
                </p>
            </details>
            <details class="hint">
                <summary>My payload didn't work</summary>
                <p>A very common payload is <code>' OR '1'='1</code>, and on its own it is not enough here. The query
                    becomes <code>... WHERE username = '' OR '1'='1' AND pass = '...'</code>. SQL evaluates
                    <code>AND</code> before <code>OR</code>, so this really means "the username is empty, <em>or</em>
                    (<code>1=1</code> <em>and</em> the password matches)". The password check is still alive. Ending
                    your input with <code>--</code> comments it out, which is why the payload in Hint 3 works.
                </p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>The website builds a SQL query by passing your input directly into it without validating it first. As
                    a
                    consequence, the database can't distinguish user input from code that should be executed. To prevent
                    this, developers often use prepared statements which makes sure the database treats the user input
                    as strictly literal values. See the secure version for more details.</p>
            </details>
        </div>

        <button class="accordion">IDOR Attacks</button>
        <div class="panel">
            <p>An IDOR (Insecure Direct Object Reference) happens when a page lets users access resources by providing a
                valid ID for that resource without
                checking that the resource belongs to that user.
            <p><strong>Goal:</strong> Read, and then change, a draft that belongs to someone else.</p>
            <details class="hint">
                <summary>Hint 1: Pay attention to the URLs</summary>
                <p>Log in, open <a href="posts/profile.php?tab=drafts">Drafts</a>, and click one of your own drafts.
                    Consider how the page makes the request for your draft's information.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Look for the missing post IDs</summary>
                <p>Post and draft IDs here are plain numbers that count up across <strong>every</strong> user's content,
                    not per account. Your draft might be <code>draft_id=3</code>, so there could be
                    <code>draft_id=4</code>, <code>5</code> or <code>6</code>. You can try iteratively searching for
                    other users' drafts by changing the draft_id in the url, or you can find the missing post IDs on the
                    posts page.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Log in as <code>alice_demo</code> and open <code>posts/profile.php?draft_id=6</code>. This should
                    open Bob's
                    private draft. Editing it and pressing Save changes Bob's post when you navigate back to it. The
                    delete request works the same
                    way.</p>
            </details>
        </div>

        <button class="accordion">Cross-Site Scripting</button>
        <div class="panel">
            <p><strong>Goal:</strong> get your own JavaScript to run in another user's browser.</p>
            <details class="hint">
                <summary>Hint 1: Where does your text end up?</summary>
                <p>Consider where something you type is later shown to other people. Try the
                    <strong>+</strong> button, create a new post with a message and a location, then open the post.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 2: Text or HTML?</summary>
                <p>Consider how your input is treated by the website. Try a message like
                    <code>&lt;b&gt;hello&lt;/b&gt;</code> and see whether it comes out bold. If it does, the browser is
                    treating your input as page markup. What else could markup do besides bold?
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Load a script as a message and post it. For instance:
                    <code>&lt;img src=x onerror="alert('XSS')"&gt;</code>. The image fails to load, the
                    <code>onerror</code> code runs, and it will run for <em>everyone</em> who opens the post. That's
                    what makes it <em>stored</em> XSS. The location field is vulnerable too.
                </p>
            </details>
            <details class="hint">
                <summary>How could the attack exploit this?</summary>
                <p>Change the payload to <code>alert(document.cookie)</code>. Notice that the
                    <code>auth_token</code> cookie is readable because it wasn't marked <code>HttpOnly</code>, and look
                    at
                    what's inside it. Then compare with the secure version.
                </p>
            </details>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="posts/index.php"><i class="fa-solid fa-house"></i></a>
        <button type="button" id="create-btn"><i class="fa-solid fa-plus"></i></button>
        <a href="posts/profile.php"><i class="fa-solid fa-user"></i></a>
    </nav>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script src="/js/straight-input.js"></script>
    <script src="/js/ui-motion.js"></script>
    <script src="/js/payload-chips.js"></script>
    <script>
        let accordions = document.querySelectorAll(".accordion");

        document.querySelectorAll(".panel details").forEach(d => {
            d.addEventListener("toggle", () => {
                const panel = d.closest(".panel");
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        });

        accordions.forEach(accordion => {
            accordion.addEventListener("click", function () {
                this.classList.toggle("panel-open");
                let panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        });
    </script>
    <?php include __DIR__ . '/../tools/attacker-tools.php'; ?>
</body>

</html>