<?php
require_once __DIR__ . '/session.php';
start_vuln_session();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Live Demo - Vulnerable Site</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="theme-vuln">

    <header class="app-header">
        <a class="logo" href="/">Live Demo</a>
        <nav class="app-header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="login" href="/vulnerable/logout.php">Logout</a>
            <?php else: ?>
                <a class="login" href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="corp-body">
        <h1 class="corp-statement">Getting First-Hand Experience Learning How Hackers Exploit Security Flaws</h1>
        <p class="corp-paragraph">
            The purpose of this website is to give users a first-hand experience exploiting security vulnerabilities in
            application code, so they can better understand how attackers may try to attack their websites. The specific
            vulnerabilities in this website relate to <a
                href="https://jayden-burrows.me/nmc/4020/module-1/project-one/sql-injections.html">SQL Injections</a>,
            <a href="https://jayden-burrows.me/nmc/4020/module-1/project-one/idor.html">IDOR attacks</a>, <a
                href="https://jayden-burrows.me/nmc/4020/module-1/project-one/cross-site-scripting.html">Cross-Site
                Scripting</a>. The links provided should provide a short overview of these attacks and give you some
            ideas of how to approach this page.
        </p>
        <p class="corp-paragraph">
            If you are struggling with getting started, try checking some of the sections below for hints and
            walkthroughs.
        </p>
        <button class="accordion">Getting Started</button>
        <div class="panel">
            <p>Everything on this site uses fake data. To log in without attacking anything, use a demo account:
                <code>guest</code> / <code>password</code>.
                For each topic below, the hints give progressively more information about how to find and approach the
                vulnerabilities in the site.
            </p>
            <p>On a phone? Your keyboard may swap <code>--</code> for a long dash or straighten quotes into curly
                ones. The attack fields on this site convert them back automatically, and once you've opened the SQL
                injection answer, the login page shows tap-to-fill buttons for the payloads.</p>
            <p>When you're done, check out <code>safe/</code> to compare how the secure site behaves.</p>
            <p>This website can't give you access to the browser URL bar or developer tools, so use the
                <code>Attacker tools</code> button. This button gives you access to an URL bar that you can modify and
                to a request sender.
            </p>
        </div>

        <button class="accordion">SQL Injections</button>
        <div class="panel">
            <p><strong>Goal:</strong> log in as <strong>a</strong> user without knowing their password.</p>
            <details class="hint">
                <summary>Hint 1: Look at what the server tells you</summary>
                <p>Try filling out the form and submitting it. The page shows you the query the server just ran and how
                    your input affected it. Consider what would happen if your input contained a single
                    quote (<code>'</code>)?</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Break out of the query</summary>
                <p>The query looks like
                    <code>SELECT * FROM users WHERE username = '...' AND pass = '...'</code>. If you close the
                    first quote yourself, you can add your own SQL after it. In SQL, <code>--</code> starts a comment,
                    so everything after it is ignored. What part of the query would you most like the database to
                    ignore?
                </p>
            </details>
            <details class="hint" data-unlock="sqli-answer">
                <summary>Hint 3: The answer</summary>
                <p>Enter <code>' OR 1=1 --</code> as the username with any password. The query
                    <code>SELECT * FROM users WHERE username = ' OR 1=1--' AND pass = '...'</code> always evaluates to
                    <code>TRUE</code> , so
                    the database returns every user, and the app logs you in as the first one. To become a specific
                    user, you can comment out the password check instead: <code>bob_demo' --</code>
                </p>
            </details>
            <details class="hint">
                <summary>My payload didn't work</summary>
                <p>A very common payload is <code>' OR '1'='1</code>, and on its own it is not enough here. The query
                    becomes <code>... WHERE username = '' OR '1'='1' AND pass = '...'</code>. SQL evaluates
                    <code>AND</code> before <code>OR</code>, so this really means "the username is empty, <em>or</em>
                    (<code>1=1</code> <em>and</em> the password matches)". The password check is still alive. Ending
                    your input with <code>--</code> comments it out, which is why the payload in Hint 3 works.
                </p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>The website builds a SQL query by passing your input directly into it without validating it first. As
                    a
                    consequence, the database can't distinguish user input from code that should be executed. To prevent
                    this, developers often use prepared statements which makes sure the database treats the user input
                    as strictly literal values. See the secure version for more details.</p>
            </details>
        </div>

        <button class="accordion">IDOR Attacks</button>
        <div class="panel">
            <p>An IDOR (Insecure Direct Object Reference) happens when a page lets users access resources by providing a
                valid ID for that resource without
                checking that the resource belongs to that user.
            <p><strong>Goal:</strong> Read, and then change, a draft that belongs to someone else.</p>
            <details class="hint">
                <summary>Hint 1: Pay attention to the URLs</summary>
                <p>Log in, open <a href="posts/profile.php?tab=drafts">Drafts</a>, and click one of your own drafts.
                    Consider how the page makes the request for your draft's information.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Look for the missing post IDs</summary>
                <p>Post and draft IDs here are plain numbers that count up across <strong>every</strong> user's content,
                    not per account. Your draft might be <code>draft_id=3</code>, so there could be
                    <code>draft_id=4</code>, <code>5</code> or <code>6</code>. You can try iteratively searching for
                    other users' drafts by changing the draft_id in the url, or you can find the missing post IDs on the
                    posts page.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Log in as <code>alice_demo</code> and open <code>posts/profile.php?draft_id=6</code>. This should
                    open Bob's
                    private draft. Editing it and pressing Save changes Bob's post when you navigate back to it. The
                    delete request works the same
                    way.</p>
            </details>
        </div>

        <button class="accordion">Cross-Site Scripting</button>
        <div class="panel">
            <p><strong>Goal:</strong> get your own JavaScript to run in another user's browser.</p>
            <details class="hint">
                <summary>Hint 1: Where does your text end up?</summary>
                <p>Consider where something you type is later shown to other people. Try the
                    <strong>+</strong> button, create a new post with a message and a location, then open the post.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 2: Text or HTML?</summary>
                <p>Consider how your input is treated by the website. Try a message like
                    <code>&lt;b&gt;hello&lt;/b&gt;</code> and see whether it comes out bold. If it does, the browser is
                    treating your input as page markup. What else could markup do besides bold?
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Load a script as a message and post it. For instance:
                    <code>&lt;img src=x onerror="alert('XSS')"&gt;</code>. The image fails to load, the
                    <code>onerror</code> code runs, and it will run for <em>everyone</em> who opens the post. That's
                    what makes it <em>stored</em> XSS. The location field is vulnerable too.
                </p>
            </details>
            <details class="hint">
                <summary>How could the attack exploit this?</summary>
                <p>Change the payload to <code>alert(document.cookie)</code>. Notice that the
                    <code>auth_token</code> cookie is readable because it wasn't marked <code>HttpOnly</code>, and look
                    at
                    what's inside it. Then compare with the secure version.
                </p>
            </details>
        </div>

        <button class="accordion">CSRF (Cross-Site Request Forgery)</button>
        <div class="panel">
            <p>Your browser attaches this site's cookies to requests automatically, even when another site triggers
                the request. If a page only checks for a valid cookie, it can't tell your real click from a request
                some other website arranged on your behalf.</p>
            <p><strong>Goal:</strong> see a website you've never interacted with change your account anyway.</p>
            <details class="hint">
                <summary>Hint 1: Log out without clicking Logout</summary>
                <p>Sign in here, then open <a href="/tools/evil-coupons.html">this page</a> (pretend it's a site you
                    found through an ad) in a new tab. Read what it claims to offer before you click anything.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: What ran before you clicked anything</summary>
                <p>Look at the "What loaded the moment this page opened" box. Something was sent to this site the
                    instant the tab opened &mdash; check your saved posts afterward.</p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>The "Claim My Coupon" button is a plain link to <code>/vulnerable/logout.php</code>, a state change
                    accepted on GET with no token, so clicking it logs you out. The page also fires a background
                    request to the save-post endpoint on load, using the session cookie your browser already sends
                    automatically. Neither request needed to read anything back from this site to do its damage.</p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>Nothing here checks for a secret the attacker's page couldn't also have supplied &mdash; only that
                    some valid session cookie came along. Logout accepts GET instead of requiring a state-changing
                    POST, and the save endpoint doesn't ask for anything beyond the cookie. See the secure version for
                    how a per-session token closes both of those.</p>
            </details>
        </div>

        <button class="accordion">Unrestricted File Upload</button>
        <div class="panel">
            <p>The account page's avatar uploader checks a file's declared type, not its actual content, and keeps
                the name you gave it.</p>
            <p><strong>Goal:</strong> get a file you control hosted at a URL on this site's own domain.</p>
            <details class="hint">
                <summary>Hint 1: Start the upload</summary>
                <p>Sign in, then open <a href="account.php">Account</a>. The avatar uploader only looks at the
                    <code>Content-Type</code> your browser sends for the file &mdash; that's just a label you can set
                    to anything.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 2: Pick a name and a type</summary>
                <p>Most file-picker dialogs won't let you type a fake type, but tools like curl, Postman, or your
                    browser's dev tools can send a multipart request with any filename and any declared
                    <code>Content-Type</code> you want, regardless of what's actually inside the file.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Send a request where the file's declared type is <code>image/jpeg</code> (or png/gif/webp) but the
                    filename is something like <code>avatar.php</code> and the content is plain text or HTML. It's
                    accepted and stored under that exact name. Visit the URL the page gives back.</p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>Both the type check and the filename came entirely from the upload request &mdash; nothing the
                    uploader doesn't already control. See <a href="/vulnerabilities/file-upload.php">the write-up</a>
                    for what a page hosted this way can do, and for how far a file like this could go.</p>
            </details>
        </div>

        <button class="accordion">SSRF (Server-Side Request Forgery)</button>
        <div class="panel">
            <p>The account page can also set your avatar by fetching a URL you give it &mdash; from the server, not
                your browser.</p>
            <p><strong>Goal:</strong> get the server to read something it wouldn't hand to you directly.</p>
            <details class="hint">
                <summary>Hint 1: Who's actually making the request?</summary>
                <p>On <a href="account.php">Account</a>, use "Import from a URL" instead of the file upload. The
                    fetch happens server-side, using whatever that server is allowed to reach.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Not every URL means "a website"</summary>
                <p><code>file_get_contents()</code>, which this feature calls directly, understands more than
                    <code>http://</code> and <code>https://</code>. A URL scheme can point at the local filesystem
                    instead.
                </p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Enter <code>file:///path/to/vulnerable/internal-notice.txt</code> (use the real path on your
                    server) as the avatar URL. The page reports how many bytes it fetched &mdash; a local file, read
                    because you asked the server to, not because you could reach it yourself.</p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>Nothing restricts which schemes or destinations this fetch will follow. See
                    <a href="/vulnerabilities/ssrf.php">the write-up</a> for what a real deployment exposes this way,
                    and how the fix restricts both the scheme and the destination IP.
                </p>
            </details>
        </div>

        <button class="accordion">XXE (XML External Entity Injection)</button>
        <div class="panel">
            <p>The account page can also import your posts from an XML file you upload.</p>
            <p><strong>Goal:</strong> get the server to read a local file into one of your own posts.</p>
            <details class="hint">
                <summary>Hint 1: XML can define its own shorthand</summary>
                <p>An XML file can declare custom "entities" in an optional <code>&lt;!DOCTYPE&gt;</code> block at
                    the top &mdash; little find-and-replace rules the parser applies wherever that entity is used in
                    the document.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Entities can point outside the document</summary>
                <p>A <code>SYSTEM</code> entity can point at a file path instead of just plain text. If the parser
                    resolves it, that file's content takes the entity's place.</p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Build an XML file like this (swap in a real path on your server), then import it on
                    <a href="account.php">Account</a>:
                </p>
                <pre><code>&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE posts [ &lt;!ENTITY xxe SYSTEM "file:///path/to/vulnerable/internal-notice.txt"&gt; ]&gt;
&lt;posts&gt;&lt;post&gt;&lt;msg&gt;&amp;xxe;&lt;/msg&gt;&lt;loc&gt;test&lt;/loc&gt;&lt;/post&gt;&lt;/posts&gt;</code></pre>
                <p>Export your posts again afterward &mdash; the file's content is now sitting in a draft.</p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>The import parses your file with a flag that tells it to resolve entities, including the external
                    one you declared. See <a href="/vulnerabilities/xxe.php">the write-up</a> for the one-line fix.
                </p>
            </details>
        </div>

        <button class="accordion">Broken Access Control</button>
        <div class="panel">
            <p>There's an admin panel on this site. Its link only shows up for admins &mdash; but is that the only
                thing protecting it?</p>
            <p><strong>Goal:</strong> see the admin panel without being an admin.</p>
            <details class="hint">
                <summary>Hint 1: Where would an admin page live?</summary>
                <p>Sign in as any account (<code>guest</code> / <code>password</code> works) and look at the nav bar
                    on your feed. See an admin link? Probably not &mdash; try guessing the URL anyway.</p>
            </details>
            <details class="hint">
                <summary>Hint 2: Common paths</summary>
                <p><code>/vulnerable/admin.php</code> is a reasonable guess for an admin panel on a site structured
                    like this one.</p>
            </details>
            <details class="hint">
                <summary>Hint 3: The answer</summary>
                <p>Go directly to <a href="admin.php">admin.php</a> while signed in as any user. It loads &mdash; full
                    user list and every draft in the system &mdash; with no admin check at all.</p>
            </details>
            <details class="hint">
                <summary>Why this was possible</summary>
                <p>The page only confirms you're logged in, not that you're an admin. Hiding the link in the nav
                    changed what you could see, not what you could reach. See
                    <a href="/vulnerabilities/broken-access-control.php">the write-up</a> for the fix.
                </p>
            </details>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="posts/index.php"><i class="fa-solid fa-house"></i></a>
        <button type="button" id="create-btn"><i class="fa-solid fa-plus"></i></button>
        <a href="posts/profile.php"><i class="fa-solid fa-user"></i></a>
    </nav>

    <script src="https://kit.fontawesome.com/1cb5b7a573.js" crossorigin="anonymous"></script>
    <script src="/js/straight-input.js"></script>
    <script src="/js/ui-motion.js"></script>
    <script src="/js/payload-chips.js"></script>
    <script>
        let accordions = document.querySelectorAll(".accordion");

        document.querySelectorAll(".panel details").forEach(d => {
            d.addEventListener("toggle", () => {
                const panel = d.closest(".panel");
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        });

        accordions.forEach(accordion => {
            accordion.addEventListener("click", function () {
                this.classList.toggle("panel-open");
                let panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        });
    </script>
    <?php include __DIR__ . '/../tools/attacker-tools.php'; ?>
</body>

</html>