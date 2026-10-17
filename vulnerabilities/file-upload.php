<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Unrestricted File Upload | Live Demo</title>
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
            <div class="tags"><span class="tag">OWASP A08:2025 Software and Data Integrity Failures</span><span
                    class="tag">CWE-434</span></div>
            <h1>Unrestricted File Upload</h1>

            <h2>Real-world example</h2>
            <div class="cve-box">
                <div class="cve-box-head">
                    <span class="cve-id">CVE-2025-43946</span>
                    <span class="cve-badge">CVSS v3.1: 9.8 - Critical</span>
                    <span class="cve-meta">Published April 22, 2025</span>
                </div>
                <p>TCPWave DDI, a network and DNS management platform, shipped a file upload feature that, combined
                    with a path traversal flaw, let an attacker control both the content and the destination of an
                    uploaded file. This was considered as a critical vulnerability as remote attackers could upload
                    files to execute their malicious code with the privileges of the application.</p>
            </div>

            <h2>Description</h2>
            <p>An unrestricted file upload happens when a server accepts a file from a visitor and later serves or
                executes it without properly checking what it actually is. This can be particularly dangerous when the
                upload
                lands somewhere the web server will execute as code (usually inside the web root), and the application
                only
                verifies attributes that the uploader controls (e.g. claimed MIME type, extension, and file name) but
                not that file's real content.</p>

            <h2>Risk</h2>
            <p>If an attacker is able to get a server-script onto the server and have it successfully run, then the
                script can do serious damage even outside the application's data. For instance, a script running
                as the web server can often read other files that the server can read, make outbound network requests,
                and sometimes even run commands on the underlying machine supporting the web server. As a result,
                unrestricted file uploads are often labeled as more critical vulnerabilities on the CVSS scale.
            </p>

            <h2>Prevention</h2>
            <ul>
                <li><strong>Check the file's actual bytes rather than what it claims.</strong> A browser-supplied
                    <code>Content-Type</code> header and a file's extension are both just labels that the upload can
                    manipulate and may not reflect the actual file contents.
                </li>
                <li><strong>Never trust the uploaded filename.</strong> Generate the stored filename (and its
                    extension) on the server from the type you verified. </li>
                <li><strong>Keep uploads out of execution paths.</strong> Store them outside the web root, or
                    somewhere the server is configured not to execute scripts from, and serve them back through a
                    small script that sets the response headers itself.</li>
                <li><strong>Cap the upload size server-side</strong>, and don't rely on client-side checks for anything
                    that matters.
                </li>
            </ul>

            <h3>Checking the file type is not a complete fix</h3>
            <p>Even once a file is confirmed to be a real image, letting the public upload images at all carries its
                own, smaller risks worth knowing about: image-parsing libraries have had their own serious
                vulnerabilities (ImageMagick's 2016 "ImageTragick" flaws let a crafted image file trigger remote code
                execution in the library decoding it, independent of any upload-validation bug); SVG files are
                themselves XML and can embed <code>&lt;script&gt;</code>, so accepting <code>image/svg+xml</code>
                uploads without sanitizing them is close to accepting raw HTML (this project's allow list
                deliberately excludes SVG for that reason); and photos frequently carry EXIF metadata such as GPS
                coordinates which is important to strip before a photo is shown back to other users. The secure
                version's
                re-encode step (below) also helps here, since decoding and re-saving a photo through GD discards
                EXIF data and most embedded-payload tricks along with it instead of just confirming the upload
                "looks like" a valid image and serving the original bytes back unchanged.</p>

            <h2>In this project</h2>
            <p>The account page's avatar uploader is the vulnerable feature: both sites let a signed-in user replace
                their avatar with an uploaded file.</p>
            <div class="code-compare">
                <figure class="is-vuln">
                    <figcaption>vulnerable/account.php</figcaption>
                    <pre><code>$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
// ...
if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowedTypes, true)) {
    $fileName = basename($file['name']);
    move_uploaded_file($file['tmp_name'], $avatarDir . $fileName);
}</code></pre>
                </figure>
                <figure class="is-safe">
                    <figcaption>safe/account.php</figcaption>
                    <pre><code>$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
if (!isset($allowedMimeTypes[$mimeType])) {
    return false;
}
$image = imagecreatefrompng($tmpPath); // or jpeg/gif/webp
$fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
imagepng($image, $avatarDir . $fileName); // re-encoded, not copied</code></pre>
                </figure>
            </div>
            <p>The vulnerable version checks <code>$file['type']</code>, which is just the <code>Content-Type</code>
                the uploader's own request declared. However, this can be set to anything, regardless of what the file
                actually contains. Then, the vulnerable version keeps the uploader's own filename, so a file named
                <code>avatar.php</code> stays named <code>avatar.php</code>. The secure version reads the file's
                actual magic bytes with PHP's <code>fileinfo</code> extension, decodes the verified image through
                GD, writes a brand new file from the decoded pixels, discarding anything else the original
                bytes contained, and generates a random filename and an extension taken from the verified type.
            </p>

            <h2>Try it: a stored page on the site's own origin</h2>
            <p>Sign in to the vulnerable site and open <a href="/vulnerable/account.php">Account</a>. Upload any
                file as long as its <em>declared</em> type is <code>image/jpeg</code>, <code>image/png</code>,
                <code>image/gif</code>, or <code>image/webp</code> - the actual content can be anything, including an
                HTML or PHP file, since nothing checks it. Name it something like <code>avatar.php</code>, then visit
                the URL the page gives you back.
            </p>
            <p>Because that file now lives under <code>/images/uploads/avatars/</code> on the <strong>same
                    domain</strong> as the real site, it can do things an attacker's own separate domain can't as
                easily: it can link directly to <code>/style.css</code> and anything in <code>/js/</code> to
                visually match the real site, and a forged request it sends (the same technique as
                <a href="/vulnerabilities/csrf.php">the CSRF page</a>) isn't a <em>cross</em>-site request at all -
                it's same-origin, so none of the cross-site cookie protections a browser applies even come into
                play. A convincing, same-origin fake login page or "logout" link hidden in an uploaded page is a
                realistic next step from here; try building one and see what it can reach.
            </p>
            <p>A file like this could go further than a static page: PHP code placed here runs with the same
                permissions as the rest of the application, which is how file upload bugs turn into full server
                compromise in real incidents like the CVE above. A shell code example won't be provided on this site as
                that could constitute a real attack. Moreover, an example likely wouldn't be able to do much within
                this context as WordPress Playground (where this site is hosted online) restricts applications to a
                limited set of commands (PHP and WP-CLI).
                However, it's interesting to note that the gap between "my file renders as a page" and "my file runs
                arbitrary
                server-side code" is technically nothing as PHP doesn't distinguish between the two once a
                <code>.php</code> file is reachable and request-triggered.
            </p>

            <div class="try-links">
                <a class="try-vuln" href="/vulnerable/account.php">Try it on the vulnerable site</a>
                <a class="try-safe" href="/safe/account.php">Try it on the secure site</a>
            </div>

            <h2>References</h2>
            <ul>
                <li><a href="https://cwe.mitre.org/data/definitions/434.html">CWE-434: Unrestricted Upload of File with
                        Dangerous Type</a>
                </li>
                <li><a href="https://www.cve.org/CVERecord?id=CVE-2025-43946">CVE-2025-43946</a></li>
                <li><a href="https://www.incibe.es/en/incibe-cert/early-warning/vulnerabilities/cve-2025-43946">INCIBE
                        advisory</a>
                </li>
            </ul>
        </section>
    </main>
</body>

</html>