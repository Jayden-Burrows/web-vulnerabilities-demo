<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>SQL Injection | Live Demo</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-home">
    <header class="home-bar">
        <a class="brand" href="/">Live Demo</a>
        <nav>
            <a href="/vulnerabilities/">Vulnerabilities</a>
            <a href="/vulnerable/index.php">Vulnerable site</a>
            <a href="/safe/index.php">Secure site</a>
        </nav>
    </header>

    <main class="learn">
        <p class="vuln-breadcrumb"><a href="/vulnerabilities/">&larr; All vulnerabilities</a></p>

        <section class="vuln-section">
            <div class="tags"><span class="tag">OWASP A05:2025 Injection</span><span class="tag">CWE-89</span></div>
            <h1>SQL Injection</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2026-2584</span>
                    <span class="cve-badge">CVSS v4.0: 9.3 - Critical</span>
                    <span class="cve-meta">Published March 2, 2026</span>
                </div>
                <p>Spain's National Cybersecurity Institute (INCIBE) coordinated the disclosure of a SQL injection
                    vulnerability in the CSIP firmware made by Ciser System SL, a telecommunications vendor. An
                    unauthenticated attacker could send crafted input through the firmware's web login page and reach
                    the database behind it directly, exposing the device's configuration. INCIBE rated it low attack
                    complexity and reachable over the network with no credentials needed, which is why a straight
                    SQL-string injection flaw in a login form scored as high as it did.</p>
            </div>

            <h2>Description</h2>
            <p>SQL injection happens when an application builds a database query by pasting user input directly into
                the query text. The database engine cannot tell which part of the string was written by the
                developer and which part was typed by the attacker, so attacker input can change what the query
                does entirely.</p>

            <h2>Risk</h2>
            <p>Any place that lets user input reach a SQL query is a potential target, which is part of why this
                class of bug is so common. A successful attack can expose or modify anything the database account can
                reach: user credentials, private records, configuration data, or in some cases the rest of the
                database server. Because the technique is well documented and the tools to find it are widely
                available, SQL injection remains cheap for attackers to find and expensive for victims when it
                succeeds.</p>

            <h2>Prevention</h2>
            <p>Input validation using an allow list of safe characters or a deny list of potentially dangerous ones
                can help, but it tends to need constant upkeep to close every loophole attackers find. The reliable
                fix is a parameterized query (a prepared statement): the query's structure is sent to the database
                separately from the user's values, so the database engine always treats user input as a literal
                value.</p>

            <h2>In this project</h2>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/login.php</figcaption>
                    <pre><code>$query = "SELECT * FROM users WHERE username = '$uname' AND pass = '$hashed_psw'";</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/db.php - authenticate()</figcaption>
                    <pre><code>$stmt = $pdo->prepare('SELECT id, username, pass FROM users WHERE username = ?');
$stmt->execute([$username]);</code></pre>
                </figure>
            </div>
            <p>The vulnerable version compares the inputted username and hashed password within the query string, so
                a username like <code>' OR 1=1 --</code> changes the query's logic. The secure version sends
                <code>?</code> as a placeholder and supplies <code>$username</code> as a bound parameter, so the
                database always treats it as a literal value to compare and never as SQL to execute. Note that the secure version
                also compares the password in PHP using password_verify(). 
            </p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/login.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/login.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://community.owasp.org/attacks/SQL_Injection">OWASP: SQL Injection</a></li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2026-2584">CVE-2026-2584</a></li>
                <li><a
                        href="https://www.incibe.es/incibe-cert/alerta-temprana/avisos/inyeccion-sql-en-el-firmware-de-ciser-system-sl">INCIBE
                        advisory (Spanish)</a>
                </li>
            </ul>
        </section>
    </main>
</body>

</html>