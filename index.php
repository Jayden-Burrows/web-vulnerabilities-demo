<!DOCTYPE html>
<html lang="en-us">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Web Vulnerability Demo</title>
    <link rel="stylesheet" href="/style.css">

</head>

<body class="theme-home">
    <main class="home">
        <section class="hero">
            <p class="hero-kicker">Hands-on web security</p>
            <h1>Exploit vulnerabilties, and then learn how to fix them.</h1>
            <p class="hero-lead">Two versions of the same small social app. Exploit real vulnerabilities in the first, then
                try the same attacks on the second and watch each one fail. All data is fake and resets for every visitor.</p>
            <div class="hero-terminal" aria-hidden="true">
                <span class="term-line term-attack">injecting malicious code </b></span>
                <span class="term-line term-result-bad">&rarr; vulnerable: logged in as the first user</span>
                <span class="term-line term-result-good">&rarr; secure: Invalid username or password.</span>
            </div>
            <ul class="owasp-chips">
                <li>SQL injection <small>CWE-89</small></li>
                <li>Cross-site scripting <small>CWE-79</small></li>
                <li>IDOR <small>CWE-639</small></li>
                <li>CSRF <small>CWE-352</small></li>
            </ul>
        </section>

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