<!DOCTYPE html>
<html lang="en-us">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Web Vulnerability Demo</title>
    <link rel="stylesheet" href="/style.css">

</head>

<body>
    <main>
        <h1>Web Vulnerability Demo</h1>
        <p>On this website, there are two versions of the same basic social media site. Exploit the vulnerabilities in
            the
            first one, and then see how the second one stops you.
            All data is fake and resets for every visitor.</p>

        <a class="card vuln" href="vulnerable/index.php">
            <strong>Vulnerable site</strong>
            Deliberately contains vulnerabilities related to SQL injection, Cross-Site Scripting and IDOR, with hints.
        </a>
        <a class="card safe" href="safe/index.php">
            <strong>Secure site</strong>
            The same features with each flaw fixed. Try the same attacks.
        </a>

        <p><small>Demo logins: <code>guest</code> / <code>password</code></small></p>
    </main>
</body>

</html>