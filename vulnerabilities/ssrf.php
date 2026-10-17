<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Server-Side Request Forgery | Live Demo</title>
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
                    class="tag">CWE-918</span>
            </div>
            <h1>Server-Side Request Forgery (SSRF)</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2023-25162</span>
                    <span class="cve-badge cvss-medium">CVSS v3.1: 5.3 - Medium</span>
                    <span class="cve-meta">Published February 13, 2023</span>
                </div>
                <p>Nextcloud Server, a widely used self-hosted file-sharing platform, validated URLs supplied to a
                    fetch feature by checking whether the target resolved to a private IP address. Attackers found
                    that an "enclosed alphanumeric" (e.g. &#9312;) encoding of an IP address slipped past that check
                    while still
                    resolving to the real, blocked address. On a server hosted at AWS, a major cloud provider, the bug
                    allowed
                    attackers to read sensitive metadata.</p>
            </div>

            <h2>Description</h2>
            <p>SSRF happens when a server makes an outbound request using a destination the <em>visitor</em> supplied,
                with no real check on where that request
                is allowed to go. As a result, the server acts as a proxy for the attacker, allowing the attacker to
                reach places they couldn't reach directly.</p>

            <h2>Risk</h2>
            <p>SSRF vulnerabilities allow attackers make unauthorized network requests to target internal systems,
                loopback interfaces, and in cloud
                deployments, a metadata endpoint (famously <code>169.254.169.254</code>) that hands out temporary
                credentials for whatever role the server is running as. SSRF turns features that take in user-supplied
                URLs or parameters into
                maps of, and sometimes keys to, everything the server can otherwise privately reach.</p>

            <h2>Prevention</h2>
            <ul>
                <li><strong>Restrict the scheme.</strong> Allow only <code>http</code> and <code>https</code>; reject
                    <code>file://</code>, <code>gopher://</code>, and anything else outright.
                </li>
                <li><strong>Resolve the hostname and check the IP</strong> A hostname can
                    resolve to a loopback or internal address; validating
                    the string alone (as the Nextcloud bug shows) can be bypassed through
                    encoding tricks.</li>
                <li><strong>Block private, loopback, and link-local ranges</strong> (including the cloud metadata
                    range), and don't follow redirects blindly as a validated URL can still redirect to a blocked one.
                </li>
                <li><strong>Validate what returns.</strong> Verify the response returned by the URL before trusting it.
                </li>
            </ul>

            <h2>In this project</h2>
            <p>The account page's "import avatar from a URL" feature demonstrates a SSRF vulnerability</p>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/account.php</figcaption>
                    <pre><code>$url = $_POST['avatar_url'];
$data = @file_get_contents($url);
// fetches anything without checking scheme or IP 
// including file:// and internal addresses</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/account.php</figcaption>
                    <pre><code>function is_fetchable_url(string $url): bool
{
    $parts = parse_url($url);
    if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
        return false;
    }
    $ip = filter_var($parts['host'], FILTER_VALIDATE_IP)
        ? $parts['host'] : gethostbyname($parts['host']);
    return filter_var($ip, FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}</code></pre>
                </figure>
            </div>
            <p>The vulnerable version calls PHP's <code>file_get_contents()</code> directly on whatever string the
                visitor supplies. <code>file_get_contents()</code> understands the
                <code>file://</code> scheme too, so a value like <code>file:///path/to/a/file</code> reads a local
                file on the server and saves its contents as the user's "avatar." The secure version restricts the
                scheme to <code>http</code>/<code>https</code>, resolves the hostname to an IP, and rejects private,
                loopback, and link-local ranges using PHP's own <code>FILTER_FLAG_NO_PRIV_RANGE</code> /
                <code>FILTER_FLAG_NO_RES_RANGE</code> filters and then checks if the fetched content is actually an image before saving it. This same technique is commonly used in real SSRF fixes.
            </p>

            <h2>Try it</h2>
            <p>Sign in to the vulnerable site, open <a href="/vulnerable/account.php">Account</a>, and import an
                avatar from <code>file:///path/to/vulnerable/internal-notice.txt</code> (use the real path on
                whichever server you're running this on). The page reports how many bytes it fetched - the server
                read a local file because you asked it to, not because you can reach that file directly yourself.
                On the secure site, the same URL is rejected before any request is made.</p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/account.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/account.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/www-community/attacks/Server_Side_Request_Forgery">OWASP: Server-Side
                        Request Forgery</a>
                </li>
                <li><a href="https://cwe.mitre.org/data/definitions/918.html">CWE-918: Server-Side Request Forgery
                        (SSRF)</a>
                </li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2023-25162">CVE-2023-25162</a></li>
            </ul>
        </section>
    </main>
</body>

</html>