<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>XML External Entity Injection | Live Demo</title>
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
            <div class="tags"><span class="tag">OWASP A05:2021 Security Misconfiguration</span><span
                    class="tag">CWE-611</span></div>
            <h1>XML External Entity (XXE) Injection</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-66370</span>
                    <span class="cve-badge cvss-medium">CVSS v3.1: 5.0 - Medium</span>
                    <span class="cve-meta">Published November 28, 2025</span>
                </div>
                <p>Kivitendo, an open-source Enterprise Resource Planning system, accepted electronic invoices in the
                    ZUGFeRD format, which
                    embeds structured XML inside a PDF, and parsed that XML without restricting external entities.
                    By uploading an invoice containing a crafted entity declaration, an attacker could read and
                    exfiltrate files from the server's filesystem.</p>
            </div>

            <h2>Description</h2>
            <p>XML lets a document define its own custom shorthand, called an <em>entity</em>, in an optional
                <code>&lt;!DOCTYPE&gt;</code> block at the top of the file. A <code>SYSTEM</code> entity can point at
                an external resource and some parser configurations
                will fetch that resource and substitute its content wherever the entity is referenced which can be
                anywhere in
                the document. If you point the XML External Entity (XXE) at a server-local file, then the file's content
                will load into whatever
                field that entity was used in.
            </p>

            <h2>Risk</h2>
            <p>Any feature that parses XML a visitor supplies such as a document import, an invoice, or a SVG upload is
                a potential target. A successful attack can read arbitrary
                files that the server process can access. Furthermore, the same entity mechanism can also be pointed at
                internal
                URLs or abused to exhaust memory with deeply nested self-referential
                entities (a classic Billion Laughs denial-of-service attack).</p>

            <h2>Prevention</h2>
            <p>The most robust method of preventing XXE injection is to never let external entities resolve. In PHP,
                that often includes rejecting any uploaded XML that contains a <code>&lt;!DOCTYPE&gt;</code> declaration
                before it's ever parsed. Furthermore, it includes being aware of exactly what your code does as several
                innocuous settings in PHP (e.g. <code>DOMDocument</code>'s <code>substituteEntities</code> and the
                <code>LIBXML_NOENT</code> flag in <code>simplexml_load_string</code>) can end up substituting and
                resolving external entitities.
            </p>

            <h2>In this project</h2>
            <p>The account page's "import posts from XML" feature is vulnerable to XXE injections.</p>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/account.php</figcaption>
                    <pre><code>$dom = new DOMDocument();
$dom->substituteEntities = true;
$dom->loadXML($xmlContent);
$xml = simplexml_import_dom($dom);
foreach ($xml->post as $postNode) {
    $msg = (string) $postNode->msg;
    // &amp;xxe; in msg has already been resolved by here
}</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/account.php</figcaption>
                    <pre><code>if (stripos($xmlContent, '&lt;!DOCTYPE') !== false) {
    $message = 'That XML file is not allowed (DOCTYPE declarations are rejected).';
} else {
    $xml = simplexml_load_string($xmlContent);
    // no DOCTYPE reaches here -- nothing to resolve
}</code></pre>
                </figure>
            </div>
            <p>The vulnerable version sets <code>substituteEntities = true</code> so that entities declared in the
                uploaded file's own <code>DOCTYPE</code> get substituted - including a <code>SYSTEM</code> entity
                pointing at a local file - and the substituted value lands straight in a new draft post's message,
                where the attacker can read it back by viewing their own drafts. That property reads as an
                unremarkable "expand the entities this document defines" setting, which is what makes this an easy
                mistake to make. The secure version sidesteps the question entirely by rejecting any file containing a
                <code>DOCTYPE</code> before parsing starts, so no parser setting is left to get wrong.
            </p>

            <h2>Try it</h2>
            <p>Sign in to the vulnerable site, open <a href="/vulnerable/account.php">Account</a>, and import an XML
                file shaped like this (swap in a real path on your server):</p>
            <pre
                class="learn-table-wrap"><code>&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE posts [ &lt;!ENTITY xxe SYSTEM "file:///path/to/vulnerable/internal-notice.txt"&gt; ]&gt;
&lt;posts&gt;&lt;post&gt;&lt;msg&gt;&amp;xxe;&lt;/msg&gt;&lt;loc&gt;test&lt;/loc&gt;&lt;/post&gt;&lt;/posts&gt;</code></pre>
            <p>Then export your posts again - the file's content is now sitting in your drafts. On the secure site,
                the same file is rejected before it's even parsed.</p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/account.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/account.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://owasp.org/www-community/vulnerabilities/XML_External_Entity_(XXE)_Processing">OWASP:
                        XML External Entity (XXE) Processing</a>
                </li>
                <li><a href="https://cwe.mitre.org/data/definitions/611.html">CWE-611: Improper Restriction of XML
                        External Entity Reference</a>
                </li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-66370">CVE-2025-66370</a></li>
            </ul>
        </section>
    </main>
</body>

</html>