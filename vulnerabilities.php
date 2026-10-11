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
            <a href="vulnerabilities.php">Vulnerabilities</a>
            <a href="vulnerable/index.php">Vulnerable site</a>
            <a href="safe/index.php">Secure site</a>
        </nav>
    </header>

    <main class="learn">
        <section class="learn-intro">
            <h1>Vulnerabilities explained</h1>
            <p>Each section below explains one flaw, shows how an attacker uses it, and points to the exact place this
                project demonstrates it on the vulnerable site and fixes it on the secure site. Categories follow the
                OWASP Top 10:2025, and CWE numbers identify the underlying weakness.</p>
            <ul class="learn-toc">
                <li><a href="#sql-injection">SQL injection</a></li>
                <li><a href="#xss">Cross-site scripting</a></li>
                <li><a href="#idor">IDOR</a></li>
                <li><a href="#csrf">CSRF</a></li>
                <li><a href="#also">Also demonstrated</a></li>
            </ul>
        </section>

        <section class="vuln-section" id="sql-injection">
            <div class="tags"><span class="tag">OWASP A05:2025 Injection</span><span class="tag">CWE-89</span></div>
            <h2>SQL injection</h2>
            <p>SQL injection happens when an application builds a database query by pasting user input into the query
                text. The database cannot tell which part was written by the developer and which part was typed by the
                user, so the input can change what the query does.</p>

            <h3>How an attacker uses it</h3>
            <p>In a login form, the attacker types a quote to end the username string, adds a condition that is always
                true, and uses <code>--</code> to comment out the rest of the query, including the password check. The
                query then matches users without needing a valid password.</p>

            <h3>In this project</h3>
            <div class="compare">
                <div class="is-vuln">
                    <h4>Vulnerable site</h4>
                    <p><code>vulnerable/login.php</code> concatenates the username and the password hash into the SQL
                        string and runs it directly. The login page shows the query the server ran with your input
                        highlighted, so you can see your text become part of the code.</p>
                </div>
                <div class="is-safe">
                    <h4>Secure site</h4>
                    <p><code>safe/db.php</code> looks the user up with a prepared statement
                        (<code>WHERE username = ?</code>) and checks the password in PHP with
                        <code>password_verify</code>. The same payload is just a username that does not exist.</p>
                </div>
            </div>

            <h3>Takeaway</h3>
            <p>Keep code and data separate. Parameterized queries make user input a value, never part of the SQL.</p>
            <div class="try-links">
                <a class="try-vuln" href="vulnerable/login.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="safe/login.php">Try it on the secure site</a>
            </div>
        </section>

        <section class="vuln-section" id="xss">
            <div class="tags"><span class="tag">OWASP A05:2025 Injection</span><span class="tag">CWE-79</span></div>
            <h2>Cross-site scripting (XSS)</h2>
            <p>XSS happens when an application outputs untrusted text as HTML. If that text contains script, the
                browser runs it as if the site itself had sent it. In stored XSS the malicious text is saved, so it runs
                for everyone who views it.</p>

            <h3>How an attacker uses it</h3>
            <p>The attacker creates a post whose message is an HTML tag with an event handler, such as an image that
                fails to load and runs script when it does. Anyone who opens the post runs that script in their own
                session, where it can read page data or act as them.</p>

            <h3>In this project</h3>
            <div class="compare">
                <div class="is-vuln">
                    <h4>Vulnerable site</h4>
                    <p>When a post is viewed, its message and location are printed into the page without escaping, so
                        the tag is parsed as HTML and the handler runs.</p>
                </div>
                <div class="is-safe">
                    <h4>Secure site</h4>
                    <p>Every value goes through the <code>e()</code> helper in <code>safe/helpers.php</code>, which
                        wraps <code>htmlspecialchars</code>. The same message appears as visible text instead of
                        running.</p>
                </div>
            </div>

            <h3>Takeaway</h3>
            <p>Escape output for the context it lands in. A Content-Security-Policy header is a useful second layer,
                but escaping is the real fix.</p>
            <div class="try-links">
                <a class="try-vuln" href="vulnerable/index.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="safe/index.php">Try it on the secure site</a>
            </div>
        </section>

        <section class="vuln-section" id="idor">
            <div class="tags"><span class="tag">OWASP A01:2025 Broken Access Control</span><span class="tag">CWE-639</span></div>
            <h2>Insecure direct object reference (IDOR)</h2>
            <p>An IDOR is an access control failure. The application takes an identifier from the request, such as a
                number in the URL, and loads that record without checking that the signed-in user is allowed to see
                it.</p>

            <h3>How an attacker uses it</h3>
            <p>The attacker logs in as themselves, then changes the ID in the address bar to another number. If the
                server only checks that someone is logged in, it returns another user's private data.</p>

            <h3>In this project</h3>
            <div class="compare">
                <div class="is-vuln">
                    <h4>Vulnerable site</h4>
                    <p>The draft page <code>posts/profile.php?draft_id=N</code> loads draft number N for any signed-in
                        user, including drafts that belong to someone else.</p>
                </div>
                <div class="is-safe">
                    <h4>Secure site</h4>
                    <p>Drafts and posts use unguessable UUIDs, and every read, update, publish and delete query also
                        includes <code>author_id = ?</code>. The ownership check is the actual fix; UUIDs only make
                        guessing harder.</p>
                </div>
            </div>

            <h3>Takeaway</h3>
            <p>Authorize every request on the server, for every object, instead of relying on hidden or hard-to-guess
                IDs.</p>
            <div class="try-links">
                <a class="try-vuln" href="vulnerable/index.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="safe/index.php">Try it on the secure site</a>
            </div>
        </section>

        <section class="vuln-section" id="csrf">
            <div class="tags"><span class="tag">OWASP A01:2025 Broken Access Control</span><span class="tag">CWE-352</span></div>
            <h2>Cross-site request forgery (CSRF)</h2>
            <p>Browsers attach a site's cookies to every request to that site, even when the request was triggered by a
                different website. If an application trusts the cookie alone, a malicious page can make a signed-in
                visitor's browser perform actions they never chose.</p>

            <h3>How an attacker uses it</h3>
            <p>The attacker hosts a page with a hidden form or image that sends a request to the target application, then
                gets a signed-in user to open it. The request arrives with the user's real session cookie.</p>

            <h3>In this project</h3>
            <p><a href="tools/evil-coupons.html">A fake "coupon" page</a> stands in for an attacker's site: a link
                styled as a discount button is really a link to <code>vulnerable/logout.php</code>, and a background
                script fires a forged "save this post" request the moment the page opens. Sign in to the vulnerable
                site first, then open it in a new tab, to see both happen without clicking a thing beyond that one
                tempting button.</p>
            <div class="compare">
                <div class="is-vuln">
                    <h4>Vulnerable site</h4>
                    <p>State-changing requests, including logout, are accepted on the session cookie alone. Logout
                        even accepts a plain GET link, so loading one is enough.</p>
                </div>
                <div class="is-safe">
                    <h4>Secure site</h4>
                    <p>Forms carry a per-session token, JavaScript requests send it in an
                        <code>X-CSRF-Token</code> header, and the server compares it with
                        <code>hash_equals</code>. Logout is POST-only, and the session cookie is
                        <code>HttpOnly</code> with <code>SameSite=Lax</code>. The automated tests check that requests
                        without the token are rejected.</p>
                </div>
            </div>

            <h3>Takeaway</h3>
            <p>Require something an attacker's page cannot supply, such as a secret token, for every action that changes
                state.</p>
            <div class="try-links">
                <a class="try-vuln" href="tools/evil-coupons.html">Try the forged-request demo</a>
                <a class="try-safe" href="safe/login.php">Then see the same demo rejected on the secure site</a>
            </div>
        </section>

        <section class="vuln-section" id="also">
            <h2>Also demonstrated</h2>
            <p>These weaknesses sit alongside the four above and are fixed on the secure site as well.</p>
            <div class="learn-table-wrap">
                <table class="learn-table">
                    <thead>
                        <tr>
                            <th>Topic</th>
                            <th>Vulnerable site</th>
                            <th>Secure site</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Password storage</td>
                            <td>Unsalted SHA-256 hashes</td>
                            <td>bcrypt with <code>password_hash</code> and <code>password_verify</code></td>
                        </tr>
                        <tr>
                            <td>Session cookie</td>
                            <td>Plain <code>auth_token</code> cookie</td>
                            <td>PHP session cookie, <code>HttpOnly</code> and <code>SameSite=Lax</code>, ID regenerated at login</td>
                        </tr>
                        <tr>
                            <td>Error messages</td>
                            <td>Raw SQL errors shown to the user</td>
                            <td>Generic messages, details kept in the server log</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/Top10/2025/">OWASP Top 10:2025</a></li>
                <li><a href="https://cwe.mitre.org/">CWE: Common Weakness Enumeration</a></li>
            </ul>
            <p><small>Only practice these techniques on systems you own or have written permission to test.</small></p>
        </section>
    </main>
</body>

</html>
