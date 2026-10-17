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
                    <span class="cve-badge">CVSS v4.0: 7.1 - High</span>
                    <span class="cve-meta">Disclosed December 18, 2025</span>
                </div>
                <p>The product security team of Nozomi networks discovered a vulnerability with the Reports
                    functionality of one of their products. This vulnerability allowed authenticated users with report
                    privileges to define reports containing malicious JavaScript code. Furthermore, even without report
                    privileges, attackers could exploit this vulnerability by manipulating users who did have report
                    privileges to import a malicious report template. Then, when a victim would view or import the
                    report, the malicious code would execute in their browser. Because the code executed from the user's
                    browser, the application would view the attacker's code as legitimate traffic from the victim.
                    Consequently, the application would allow the attacker to perform actions as the victim, such as
                    viewing sensitive infromatio or modifying application data. </p>
            </div>

            <h2>Description</h2>
            <p>Cross-Site Scripting (XSS) occurs when an application prints untrusted text into a page as HTML instead
                of as plain text.
                If that text contains a script tag or an event handler, the browser runs it exactly as if the site
                itself had written it. In <em>stored</em> XSS, the payload is saved by the server, so it runs again for
                every visitor who views it.</p>

            <h2>Risk</h2>
            <p>A script running in another user's browser, under the real site's origin, can read anything that page can
                read, submit forms as that user, or deface the page. Stored XSS can be especially damaging because it
                does not require any ongoing action from the attacker once the payload is saved in the application database. Instead, the attacker can simply wait for a victim to stumble upon a valid page serving their malicious content.</p>

            <h2>Prevention</h2>
            <p>Escape output for the context it's rendered in. For HTML, that means converting characters like
                <code>&lt;</code>, <code>&gt;</code> and <code>&quot;</code> into their HTML entities before they're
                printed, so the browser displays them as text rather than parsing them as markup. A
                Content-Security-Policy header can also be useful as a second layer of defense.
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