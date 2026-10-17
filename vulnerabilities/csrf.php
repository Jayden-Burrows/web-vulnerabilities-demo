<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Cross-Site Request Forgery | Live Demo</title>
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
            <div class="tags"><span class="tag">OWASP A01:2025 Broken Access Control</span><span
                    class="tag">CWE-352</span>
            </div>
            <h1>Cross-Site Request Forgery (CSRF)</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-22552</span>
                    <span class="cve-badge">CVSS v3.1: 7.1 - High</span>
                    <span class="cve-meta">Published January 7, 2025</span>
                </div>
                <p>A CSRF vulnerability in the "Affiliate Disclosure Statement" WordPress plugin
                    (through version 0.3) was reported through Patchstack, an open-source security platform for
                    vulnerability intelligence and virtual patching. The plugin performed a state-changing action
                    without verifying that the request genuinely came from the site's own forms, so an attacker could
                    craft a page that, when
                    visited by a logged-in administrator, submitted the forged request on their behalf. Patchstack
                    rated it high severity as the request required no authentication of its own and only low complexity to
                    pull off.</p>
            </div>

            <h2>Description</h2>
            <p>Browsers attach a site's cookies to requests automatically, even when a different site triggered the
                request. If an application only checks for a valid session cookie and nothing else, it can't tell
                the difference between a click the user actually made and a request another website arranged on
                their behalf.</p>

            <h2>Risk</h2>
            <p>Any state-changing action protected only by a cookie is forgeable. Attackers can conduct serious attacks
                by
                simply tricking users into loading a page or clicking a link while already signed in elsewhere.</p>

            <h2>Prevention</h2>
            <p>Requiring a per-session token for every state-changing request helps prevent attackers from forging
                requests as they would need to include this token. Using constant-time comparisons to compare session
                tokens also hellps to prevent attackers from using timing to guess tokens.
                Restricting state changes to POST (never GET) and setting the session cookie's <code>SameSite</code>
                attribute can add further defense.</p>

            <h2>In this project</h2>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/logout.php</figcaption>
                    <pre><code>session_destroy();
header('Location: /vulnerable/login.php');</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/logout.php</figcaption>
                    <pre><code>if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /safe/index.php');
    exit;
}
if (!csrf_valid()) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}</code></pre>
                </figure>
            </div>
            <p>The vulnerable logout runs on any GET request with no further checks, so a plain link logs the visitor
                out.
                The secure version only accepts POST and requires <code>csrf_valid()</code> to confirm a matching
                per-session token before it does anything,
                which an attacker's page has no way to supply.</p>

            <p>See it happen: <a href="/tools/evil-coupons.html">a fake "coupon" page</a> that forges exactly this
                request against both sites.</p>

            <div class="try-links">
                <a class="try-vuln" href="/tools/evil-coupons.html">Try the forged-request demo</a>
                <a class="try-safe" href="/safe/login.php">Then see it rejected on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/www-community/attacks/csrf">OWASP: Cross-Site Request Forgery (CSRF)</a>
                </li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-22552">CVE-2025-22552</a></li>
                <li><a href="https://cwe.mitre.org/data/definitions/352.html">CWE-352: Cross-Site Request Forgery</a>
                </li>
            </ul>
        </section>
    </main>
</body>

</html>