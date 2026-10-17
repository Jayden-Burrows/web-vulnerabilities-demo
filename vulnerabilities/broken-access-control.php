<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Broken Access Control | Live Demo</title>
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
                    class="tag">CWE-862</span>
            </div>
            <h1>Broken Function Level Authorization</h1>
            <p>This is a different shape of access-control failure than <a href="/vulnerabilities/idor.php">the IDOR
                    page</a>.
                IDOR is about reaching <em>someone else's</em> record through an object you don't own. This one is
                about reaching an entire <em>feature</em> that shouldn't be reachable by
                your account at all.</p>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-4064</span>
                    <span class="cve-badge cvss-medium">CVSS v3.1: 5.3 - Medium</span>
                    <span class="cve-meta">Published April 29, 2025</span>
                </div>
                <p>An admin page, <code>/admin/viewenquiry.php</code>, in ScriptAndTools' Online Traveling System
                    was reachable by simply requesting the URL. The application assumed authentication
                    was handled somewhere else and relied on the admin path being obscure
                    rather than actually protected.</p>
            </div>

            <h2>Description</h2>
            <p>Function-level (as opposed to object-level) broken access control happens when an application checks
                who can use a feature inconsistently or not at all. For instance, consider
                a check that exists in the user interface such as an admin link only renders for admins, but the page or
                endpoint behind that link does no authorization check, trusting that nobody will request a URL they
                weren't shown a link to.
            </p>

            <h2>Risk</h2>
            <p>Once an attacker identifies or guesses the URL (which often isn't hard, <code>/admin.php</code>,
                <code>/admin/</code>, and similar are common conventions), there is nothing left to bypass. Unlike an
                IDOR, which usually exposes one record at a time, a missing function-level check on an admin panel
                can expose every user's data, or every administrative action, in one request.
            </p>

            <h2>Prevention</h2>
            <p>Authorize on the server, for every request to the protected feature, using a role or permission
                stored in a place the client can't edit. Put the check in one
                shared function used everywhere the feature is reachable, rather than re-implementing it per page,
                and fail with an explicit 403 rather than quietly rendering a blank or redirected page, so "doesn't
                exist" and "exists but you can't see it" stay distinguishable in your own testing.</p>

            <h2>In this project</h2>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/admin.php</figcaption>
                    <pre><code>require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

// Only checks that someone is logged in but not if they're an admin
require_login();</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/admin.php</figcaption>
                    <pre><code>require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

require_login();
require_admin();</code></pre>
                </figure>
            </div>
            <p>The vulnerable page only confirms <em>someone</em> is signed in; any of the seeded accounts, including
                <code>guest</code>, can open it directly. The admin link in its own nav is hidden from non-admins in
                the UI, but that's cosmetic - the page itself enforces nothing, so knowing or guessing the URL is
                enough. The secure version adds <code>require_admin()</code> (<code>safe/auth.php</code>), which
                checks the signed-in user's <code>is_admin</code> flag and returns a 403 if it isn't set, regardless
                of whether the visitor ever saw a link to the page.
            </p>

            <h2>Try it</h2>
            <p>Sign in to the vulnerable site as any account, including the seeded <code>guest</code> /
                <code>password</code>, and go directly to <a href="/vulnerable/admin.php">/vulnerable/admin.php</a> -
                no admin link needs to be visible for this to work. On the secure site, the same account gets a 403;
                only <code>admin_demo</code> / <code>password</code> can get in.
            </p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/admin.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/admin.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/Top10/2025/A01_2025-Broken_Access_Control/">OWASP: A01:2025 Broken Access
                        Control</a>
                </li>
                <li><a href="https://cwe.mitre.org/data/definitions/862.html">CWE-862: Missing Authorization</a></li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-4064">CVE-2025-4064</a></li>
            </ul>
        </section>
    </main>
</body>

</html>