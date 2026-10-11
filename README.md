# web-vulnerabilities-demo
This project uses PHP and SQLite to provide a sandbox environment to learn how hackers exploit website vulnerabilities through first-hand experience.

The website is themed after a simple social media app where users can draft, create, and save posts.

- On the `vulnerable/` path, vulnerabilities related to SQL Injections, Cross Site Scripting, and IDOR are purposefully embedded, and users are encouraged to try to exploit them to gain unauthorized access to website resources. Progressive hints are included on the landing page.

- On the `safe/` path, these vulnerabilities are patched, and users can test how the website now prevents those attacks. The landing page contains details about how each flaw is fixed (see also "What the safe version changes" below).

All data is fake. Each SQLite database is created and seeded on first request.

## Running it online
Try checking out the website [here](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Jayden-Burrows/web-vulnerabilities-demo/refs/heads/main/playground/blueprint.json). The site runs through WordPress Playground which can be memory intensive, so opening the website on a laptop or desktop is ideal.

## Running it locally

Requires PHP 8.1+ with `pdo_sqlite`. Clone this repo, and from this folder, run:

```bash
php -S localhost:8000
```

Open <http://localhost:8000/>. Demo logins (both sites): `guest` / `password`, `alice_demo` / `password`, `bob_demo` / `12345678`. You can reset the data by stopping the server and deleting `vulnerable/data/` and `safe/data/`.

Dependencies (`ramsey/uuid`) are committed in `vendor/`. If you change `composer.json`, run `composer install` and commit `vendor/` again.

## What the safe version changes

| Area | Vulnerable | Safe |
|---|---|---|
| Login (SQLi) | Input pasted into the SQL string | Prepared statement, `?` placeholders |
| Login (other) | Plain-text `auth_token` cookie, raw SQL errors shown | No such cookie, generic error, `HttpOnly` + `SameSite` session cookie, session ID regenerated |
| Passwords | Unsalted SHA-256 | bcrypt via `password_hash()` / `password_verify()`; same error for bad user or password; dummy hash equalizes timing |
| CSRF | No tokens | Per-session token in a hidden field or `X-CSRF-Token` header, checked with `hash_equals()`; logout is POST-only |
| Output (XSS) | `<?= $viewedPost['msg'] ?>` and friends | Every value goes through `e()` (`htmlspecialchars` with `ENT_QUOTES`) |
| Post IDs (IDOR) | Sequential integers | UUIDs (defense in depth only) |
| Authorization (IDOR) | No ownership checks | `author_id = ?` in every draft read/update/publish/delete; saving requires a published post |
| Errors | `echo` / uncaught exceptions | JSON `{"success": false, "error": "..."}` with a proper HTTP status; details go to `error_log` |

## Trying the attacks on a phone

Phone keyboards can quietly break payloads: iOS Smart Punctuation turns `--` into a long dash and straight quotes into curly ones, so a correct SQL injection or XSS payload fails for the wrong reason. Two things handle this:

- `js/straight-input.js` runs on the attack-surface fields of both sites (login username, post text and location, the attacker-tools boxes). It turns off autocorrect and converts curly quotes, long dashes and ellipses back to ASCII as you type. It is a keyboard-compatibility aid for the demo, not a security control, and it runs on the `safe/` site too so both sites receive the same input.
- `js/payload-chips.js` shows tap-to-fill payload buttons on the login pages once the visitor has opened "Hint 3: The answer" on the SQL injection panel. Until then they stay hidden so they don't give the answer away.

A common payload, `' OR '1'='1`, does not log you in on its own: `AND` is evaluated before `OR`, so the password check still applies. The landing page has a hint explaining this ("My payload didn't work").

## Tests

The suite in `tests/` starts its own PHP server, resets `safe/data/` and `vulnerable/data/`, and checks that each exploit works on the vulnerable site and fails on the secure one (SQL injection, IDOR, stored XSS, CSRF, session isolation, and that a leftover database from an older schema gets rebuilt instead of breaking logins). It needs PHP with `pdo_sqlite` and Python 3 only:

```bash
python3 -m unittest discover -s tests -v
```

GitHub Actions (`.github/workflows/ci.yml`) runs these tests on every push, plus `composer audit` and a Semgrep scan of `safe/`.

### AI Disclosure
I initially came up with an overview of the website including the vulnerabilities to showcase, how to showcase them, and what the pages of each website should look like. From there, I leveraged AI to generate a base framework for me to further develop. While I expanded on this framework, I additionally used AI to assist me with styling, debugging code, and adding new features (such as the Attacker Tools).
