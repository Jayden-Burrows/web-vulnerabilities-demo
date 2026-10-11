<!DOCTYPE html>
<html lang="en-us">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Web Vulnerability Demo</title>
    <link rel="stylesheet" href="/style.css">

</head>

<body class="theme-vuln">
    <header class="home-bar">
        <a class="brand" href="/">Live Demo</a>
        <nav>
            <a href="vulnerabilities.php">Vulnerabilities</a>
            <a href="vulnerable/index.php">Vulnerable site</a>
            <a href="safe/index.php">Secure site</a>
        </nav>
    </header>
    <main class="home">
        <section class="hero">
            <p class="hero-kicker">Hands-on web security</p>
            <h1>Exploit it. Then see how it's patched.</h1>
            <p class="hero-lead">Two versions of the same small social app. Exploit real vulnerabilities in the first,
                then
                try the same attacks on the second and watch each one fail. All data is fake and resets for every
                visitor.</p>
            <div class="hero-terminal" aria-hidden="true">
                <span class="term-line term-attack">injecting malicious payload...</b></span>
                <span class="term-line term-result-bad">&rarr; vulnerable: logged in as the first user</span>
                <span class="term-line term-result-good">&rarr; secure: Invalid username or password.</span>
            </div>

        </section>

        <section>
            <div class="section-head">
                <h2>What you'll explore</h2>
                <p>Four of the most common web security flaws, each demonstrated twice: broken, then fixed.</p>
            </div>
            <div class="topics">
                <a class="topic-card" href="vulnerabilities.php#sql-injection">
                    <span class="topic-num">01</span>
                    <h3>SQL injection</h3>
                    <p>Input is treated as part of the database query, so it can change what the query does.</p>
                    <span class="topic-meta">OWASP A05:2025 &middot; CWE-89</span>
                    <span class="topic-link">How it works &rarr;</span>
                </a>
                <a class="topic-card" href="vulnerabilities.php#xss">
                    <span class="topic-num">02</span>
                    <h3>Cross-site scripting</h3>
                    <p>Untrusted text is shown as live HTML, so an attacker's script runs in other people's browsers.
                    </p>
                    <span class="topic-meta">OWASP A05:2025 &middot; CWE-79</span>
                    <span class="topic-link">How it works &rarr;</span>
                </a>
                <a class="topic-card" href="vulnerabilities.php#idor">
                    <span class="topic-num">03</span>
                    <h3>IDOR</h3>
                    <p>The app trusts an ID in the request and never checks who owns the thing it points to.</p>
                    <span class="topic-meta">OWASP A01:2025 &middot; CWE-639</span>
                    <span class="topic-link">How it works &rarr;</span>
                </a>
                <a class="topic-card" href="vulnerabilities.php#csrf">
                    <span class="topic-num">04</span>
                    <h3>CSRF</h3>
                    <p>Another site tricks a logged-in browser into sending a request the user never intended.</p>
                    <span class="topic-meta">OWASP A01:2025 &middot; CWE-352</span>
                    <span class="topic-link">How it works &rarr;</span>
                </a>
            </div>
        </section>

        <div class="section-head">
            <h2>Start here</h2>
        </div>
        <div class="card-row">
            <a class="card vuln" href="vulnerable/index.php">
                <strong>Vulnerable site</strong>
                Deliberately contains SQL injection, Cross-Site Scripting and IDOR flaws, with progressive hints.
            </a>
            <a class="card safe" href="safe/index.php">
                <strong>Secure site</strong>
                The same features with each flaw fixed. Try the same attacks.
            </a>
        </div>

        <p class="desktop-note"><small>Best experienced on a laptop or desktop.</small></p>
        <p><small>Demo logins: <code>guest</code> / <code>password</code></small></p>
    </main>
    <script src="/js/ui-motion.js"></script>
</body>

</html>