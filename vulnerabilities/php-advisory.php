<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PHP Security Notice | Live Demo</title>
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
            <div class="tags"><span class="tag">Security notice</span><span class="tag">PHP runtime</span><span
                    class="tag">Updated October 21, 2026</span></div>
            <h1>Critical PHP vulnerabilities (CVE-2026-109618 and CVE-2026-109619)</h1>

            <h2>What happened</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2026-109618</span>
                    <span class="cve-badge">CVSS v4.0: 9.2 - Critical</span>
                    <span class="cve-meta">Disclosed October 21, 2026</span>
                </div>
                <p>Memory corruption when PHP handles an out-of-memory error, which can lead to unauthenticated remote
                    code execution.</p>
            </div>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2026-109619</span>
                    <span class="cve-badge">CVSS v4.0: 9.2 - Critical</span>
                    <span class="cve-meta">Disclosed October 21, 2026</span>
                </div>
                <p>A use-after-free caused by stale state left over between requests handled by the same PHP worker. It
                    can lead to unauthenticated remote code execution and memory disclosure.</p>
            </div>
            <p>The PHP project shipped fixes in <strong>PHP 8.5.12, 8.4.27, 8.3.36 and 8.2.35</strong>. PHP 8.1 and
                earlier are end of life and are not being patched. PHP powers a large share of the web, including
                WordPress, so a flaw in the runtime itself reaches far beyond any one application.</p>

            <h2>Does this affect this demo?</h2>
            <p>The hosted demo runs on WordPress Playground, which executes PHP inside your own browser using
                WebAssembly. There is no server of mine running PHP that these flaws could be used against, and all
                demo data is fake and resets for every visitor.</p>
            <p>The exposure is for anyone who <strong>self-hosts</strong> this project with Docker or
                <code>php -S</code>. This project deliberately contains vulnerabilities, so it should never be exposed
                to the internet. An unpatched PHP runtime would stack a critical flaw on top of them.</p>

            <h2>If you run it yourself</h2>
            <ul>
                <li>Update to PHP 8.5.12, 8.4.27, 8.3.36 or 8.2.35 or later. Don't stay on 8.1 or earlier.</li>
                <li>With Docker, rebuild so the base image is refreshed:
                    <code>docker build --pull --no-cache -t web-vulnerabilities-demo .</code></li>
                <li>Run the demo only on <code>localhost</code> or an isolated network, never on a public host.</li>
            </ul>

            <h2>Why this belongs on a vulnerability site</h2>
            <p>Not every flaw is in the code you wrote. Keeping runtimes, frameworks and dependencies patched is part
                of defending an application, and a safe application on an unpatched runtime is still at risk. This
                project's CI runs <code>composer audit</code> for the same reason, though it covers Composer
                packages and not the PHP runtime itself.</p>

            <h2>References</h2>
            <ul>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2026-109618">CVE-2026-109618 (CVE.org)</a></li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2026-109619">CVE-2026-109619 (CVE.org)</a></li>
                <li><a href="https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp">PHP advisory for
                        CVE-2026-109619 (GHSA-6wmf-4rv5-p3rp)</a></li>
                <li><a href="https://www.helpnetsecurity.com/2026/10/21/php2shell-php-vulnerabilities-patched-cve-2026-109618-cve-2026-109619/">Help
                        Net Security: PHP2Shell coverage</a></li>
            </ul>
        </section>
    </main>
    <script src="/js/ui-motion.js"></script>
</body>

</html>
