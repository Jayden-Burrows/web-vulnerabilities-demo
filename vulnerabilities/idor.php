<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Insecure Direct Object Reference | Live Demo</title>
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
                    class="tag">CWE-639</span>
            </div>
            <h1>Insecure Direct Object Reference (IDOR)</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-43803</span>
                    <span class="cve-badge cvss-medium">CVSS v4.0: 6.9 - Medium</span>
                    <span class="cve-meta">Disclosed September 19, 2025</span>
                </div>
                <p>Liferay Inc. disclosed an IDOR vulnerability with the Contacts Center widget in their Liferay Portal.
                    This vulnerability allowed remote attackers to modify the <code>entryId</code> parameter of the
                    Contacts Center widget to view restricted contact information.
                </p>
            </div>

            <h2>Description</h2>
            <p>An IDOR vulnerability occurs when an application doesn't implement proper authorization checks.
                The application takes an identifier straight from a request and uses it to look up a record without
                separately
                checking that the person making the request is allowed to see or change that particular record.</p>

            <h2>Risk</h2>
            <p>When IDOR vulnerabilities are present, an attack can modify the IDs in their requests to view other
                users' potentially
                sensitive data. Furthermore, when IDs are sequential or easy to guess, an attacker can easily automate
                this
                process. Because these requests look completely ordinary to the server, this class of bug is easy to
                miss in testing
                and easy to exploit once found.</p>

            <h2>Prevention</h2>
            <p>Authorize every request against the specific object it asks for, on the server. Using unpredictable
                identifiers like UUIDs makes guessing harder, but it's only a secondary measure for the authorization
                check.</p>

            <h2>In this project</h2>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/posts/logic/profile-logic.php</figcaption>
                    <pre><code>$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND is_posted = 0');
$stmt->execute([$_GET['draft_id']]);
$viewedDraft = $stmt->fetch(PDO::FETCH_ASSOC);</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/posts/logic/profile-logic.php + safe/db.php</figcaption>
                    <pre><code>$viewedDraft = get_owned_post($pdo, $requestedDraftId, $userId, 0);

// in get_owned_post():
$sql = 'SELECT * FROM posts WHERE id = ? AND author_id = ?';</code></pre>
                </figure>
            </div>
            <p>The vulnerable version fetches <em>any</em> draft by ID with no check on who owns it, so changing
                <code>draft_id</code> in the URL opens someone else's draft. The secure version's
                <code>get_owned_post()</code> adds <code>AND author_id = ?</code> to the query, bound to the
                currently logged-in user, so a draft that isn't yours simply doesn't match and nothing is returned.
            </p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/index.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/index.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://cwe.mitre.org/data/definitions/639.html">CWE-639: Authorization Bypass Through
                        User-Controlled Key</a>
                </li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-43803">CVE-2025-43803</a></li>
                <li><a href="https://github.com/advisories/GHSA-8c8v-r5jj-4425">GitHub Security Advisory
                        GHSA-8c8v-r5jj-4425</a>
                </li>
            </ul>
        </section>
    </main>
</body>

</html>