<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Cross-Site Scripting | Live Demo</title>
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
            <div class="tags"><span class="tag">OWASP A05:2025 Injection</span><span class="tag">CWE-79</span></div>
            <h1>Cross-Site Scripting (XSS)</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-40892</span>
                    <span class="cve-badge">CVSS v3.1: 8.9 - High</span>
                    <span class="cve-meta">Disclosed December 18, 2025</span>
                </div>
                <p>Security researchers at Nozomi Networks disclosed a stored XSS flaw in the reporting feature of
                    Guardian and CMC, industrial network monitoring platforms. A user with report-building
                    permissions could define a report containing a JavaScript payload, or trick a victim into
                    importing a malicious report template. When another user opened or imported that report, the
                    script ran in their browser session, letting the attacker act as that user: modifying data,
                    disrupting the application, or reading information the victim could see.</p>
            </div>

            <h2>Description</h2>
            <p>XSS happens when an application prints untrusted text into a page as HTML instead of as plain text.
                If that text contains a script tag or an event handler, the browser runs it exactly as if the site
                itself had written it. In <em>stored</em> XSS, the payload is saved by the server, so it runs again
                for every visitor who views it.</p>

            <h2>Risk</h2>
            <p>A script running in another user's browser, under the real site's origin, can read anything that page
                can read, submit forms as that user, or simply deface the page. Stored XSS is especially damaging
                because it requires no ongoing action from the attacker once the payload is saved; it simply waits
                for the next visitor.</p>

            <h2>Prevention</h2>
            <p>Escape output for the context it's rendered in. For HTML, that means converting characters like
                <code>&lt;</code>, <code>&gt;</code> and <code>&quot;</code> into their HTML entities before they're
                printed, so the browser displays them as text rather than parsing them as markup. A
                Content-Security-Policy header is useful as a second layer of defense, but escaping output is the
                fix that actually closes the hole.
            </p>

            <h2>In this project</h2>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/posts/index.php</figcaption>
                    <pre><code>&lt;?= $viewedPost["loc"] ?&gt;
...
&lt;?= $viewedPost["msg"] ?&gt;</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/posts/index.php</figcaption>
                    <pre><code>&lt;?= e($viewedPost['loc']) ?&gt;
...
&lt;?= e($viewedPost['msg']) ?&gt;</code></pre>
                </figure>
            </div>
            <p>The vulnerable version prints a post's location and message straight into the page. The secure
                version passes the same values through <code>e()</code>, a small wrapper around
                <code>htmlspecialchars()</code> defined in <code>safe/helpers.php</code>, before they're printed, so
                user input is treated as text instead of as html.
            </p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/index.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/index.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://community.owasp.org/attacks/xss/">OWASP: Cross Site Scripting (XSS)</a></li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-40892">CVE-2025-40892</a></li>
                <li><a href="https://security.nozominetworks.com/NN-2025:13-01">Nozomi Networks advisory
                        (NN-2025:13-01)</a>
                </li>
            </ul>
        </section>
    </main>
</body>

</html>