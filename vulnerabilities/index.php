<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Live Demo</title>
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
        <section class="learn-intro">
            <h1>Vulnerabilities explained</h1>
            <p>Each page below covers one flaw: a real-world CVE it has caused, what the vulnerability actually is,
                the risk it carries, how to prevent it, and the vulnerable and remediated code from this project side
                by side.</p>
        </section>

        <div class="vuln-hub-grid">
            <a class="vuln-hub-card" href="sql-injection.php">
                <h3>SQL Injection</h3>
                <p>OWASP A05:2025 &middot; CWE-89</p>
                <p>CVE-2026-2584 -; critical SQLi in network firmware, CVSS 9.3.</p>
            </a>
            <a class="vuln-hub-card" href="xss.php">
                <h3>Cross-Site Scripting</h3>
                <p>OWASP A05:2025 &middot; CWE-79</p>
                <p>CVE-2025-40892 -; stored XSS in industrial monitoring software, CVSS 7.1.</p>
            </a>
            <a class="vuln-hub-card" href="idor.php">
                <h3>Insecure Direct Object Reference</h3>
                <p>OWASP A01:2025 &middot; CWE-639</p>
                <p>CVE-2025-43803 -; IDOR in a Liferay Portal widget, CVSS 6.9.</p>
            </a>
            <a class="vuln-hub-card" href="csrf.php">
                <h3>Cross-Site Request Forgery</h3>
                <p>OWASP A01:2025 &middot; CWE-352</p>
                <p>CVE-2025-22552 -; CSRF in a WordPress plugin, CVSS 7.1.</p>
            </a>
            <a class="vuln-hub-card" href="file-upload.php">
                <h3>Unrestricted File Upload</h3>
                <p>OWASP A08:2025 &middot; CWE-434</p>
                <p>CVE-2025-43946 -; unrestricted upload leading to RCE, CVSS 9.8.</p>
            </a>
            <a class="vuln-hub-card" href="ssrf.php">
                <h3>Server-Side Request Forgery</h3>
                <p>OWASP A01:2025 &middot; CWE-918</p>
                <p>CVE-2023-25162 -; Nextcloud SSRF reaching cloud metadata, CVSS 5.3.</p>
            </a>
            <a class="vuln-hub-card" href="xxe.php">
                <h3>XML External Entity (XXE)</h3>
                <p>OWASP A05:2025 &middot; CWE-611</p>
                <p>CVE-2025-66370 -; file exfiltration via an XML import, CVSS 5.0.</p>
            </a>
            <a class="vuln-hub-card" href="broken-access-control.php">
                <h3>Broken Function-Level Authorization</h3>
                <p>OWASP A01:2025 &middot; CWE-862</p>
                <p>CVE-2025-4064 -; an admin page reachable with no session check.</p>
            </a>
        </div>

        <section>
            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/Top10/2025/">OWASP Top 10:2025</a></li>
                <li><a href="https://cwe.mitre.org/">CWE: Common Weakness Enumeration</a></li>
                <li><a href="https://www.cve.org/">CVE Program</a></li>
            </ul>
            <p><small>Only practice these techniques on systems you own or have written permission to test.</small>
            </p>
        </section>
    </main>
</body>

</html>