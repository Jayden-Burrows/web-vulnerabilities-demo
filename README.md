# Live Demo

A deliberately vulnerable PHP/SQLite web application built as a hands-on sandbox for
learning how common web vulnerabilities are exploited — and how they are remediated.

Themed as a simple social media app where users draft, create, and save posts.

- **`vulnerable/`** — SQL Injection, Cross-Site Scripting (XSS), IDOR, and CSRF are
  intentionally embedded. Users are encouraged to exploit them to gain unauthorized
  access. Progressive hints are provided on the landing page.
- **`safe/`** — The same application with each vulnerability remediated. Users can
  verify that the exploits no longer succeed. The landing page documents how each
  flaw was fixed (see "What the safe version changes").
- **`vulnerabilities/`** — Formal write-ups for each demonstrated vulnerability:
  - `vulnerabilities/sql-injection.php`
  - `vulnerabilities/xss.php`
  - `vulnerabilities/idor.php`
  - `vulnerabilities/csrf.php`

## What each write-up contains

Each finding is documented in a structured, assessment-style format:

- OWASP Top 10 (2025) category mapping
- CWE ID
- Real-world example with CVE reference and CVSS 4.0 severity score
- Description — root cause and mechanism
- Risk — business and technical impact
- Prevention — recommended controls and defense-in-depth
- Vulnerable vs. remediated code snippets for this application
- References (OWASP, NVD, vendor advisories)

The vulnerable and remediated implementations are maintained side by side so that the effectiveness of each control can be directly verified.

## Running it online

Try the live demo [here](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/Jayden-Burrows/web-vulnerabilities-demo/refs/heads/main/playground/blueprint.json).
The site runs through WordPress Playground, which can be memory intensive — a laptop
or desktop is recommended.

## Running it with Docker

Build and run the included `Dockerfile`:

```bash
docker build -t web-vulnerabilities-demo .
docker run -p 8080:80 web-vulnerabilities-demo
```

Or use Docker Compose to safely persist data files:

```bash
docker compose up --build
```

Then open <http://localhost:8080/>. `.github/workflows/ci.yml` builds and tests this
image on every push.

## Running it locally

Requires PHP 8.1+ with `pdo_sqlite`. Clone this repo, and from this folder, run:

```bash
php -S localhost:8080
```

Open <http://localhost:8080/>. Demo logins (both sites): `guest` / `password` and
`alice_demo` / `password`. Reset the data by stopping the server and deleting
`vulnerable/data/` and `safe/data/`.

Dependencies (`ramsey/uuid`) are committed in `vendor/`. If you change
`composer.json`, run `composer install` and commit `vendor/` again.

## What the safe version changes

| Area | Vulnerable | Safe |
|---|---|---|
| Login (SQLi) | Input pasted into the SQL string | Prepared statement, `?` placeholders |
| Login (other) | Plain-text `auth_token` cookie, raw SQL errors shown | generic error messages, `HttpOnly` + `SameSite` session cookie, session ID regenerated |
| Passwords | Unsalted SHA-256 | bcrypt via `password_hash()` / `password_verify()`; same error for invalid user or password; use dummy hash to prevent attackers from guessing usernames or passwords based on timing |
| CSRF | No tokens | Per-session token in a hidden field or `X-CSRF-Token` header, checked with `hash_equals()`; logout is POST-only |
| Output (XSS) | `<?= $viewedPost['msg'] ?>` and friends | Output encoding, every value goes through `e()` (a wrapper function for `htmlspecialchars` with `ENT_QUOTES`) |
| Post IDs (IDOR) | Sequential integers | UUIDs to prevent guessing valid IDs |
| Authorization (IDOR) | No ownership checks | `author_id = ?` for reading or modifying data |
| Errors | `echo` / uncaught exceptions | JSON `{"success": false, "error": "..."}` with proper HTTP status; details go to `error_log` |

## Verification / Tests

The test suite in `tests/` starts its own PHP server, resets `safe/data/` and
`vulnerable/data/`, and verifies that each exploit **succeeds on the vulnerable site
and fails on the secure site** — SQL injection, IDOR, stored XSS, CSRF, session
isolation, and schema migration of stale databases. Requires PHP with `pdo_sqlite`
and Python 3:

```bash
python3 -m unittest discover -s tests -v
```

GitHub Actions (`.github/workflows/ci.yml`) runs these tests on every push, plus
`composer audit` and a Semgrep scan of `safe/`.

## Trying the attacks on a phone

Phone keyboards can quietly break payloads: iOS Smart Punctuation turns `--` into a
long dash and straight quotes into curly ones, so a correct SQL injection or XSS
payload fails for the wrong reason. Two things handle this:

- `js/straight-input.js` runs on the attack-surface fields of both sites (login
  username, post text and location, the attacker-tools boxes). It turns off
  autocorrect and converts curly quotes, long dashes and ellipses back to ASCII as
  you type. It is a keyboard-compatibility aid for the demo, not a security control,
  and it runs on the `safe/` site too so both sites receive the same input.
- `js/payload-chips.js` shows tap-to-fill payload buttons on the login pages once the
  visitor has opened "Hint 3: The answer" on the SQL injection panel. Until then they
  stay hidden so they don't give the answer away.

## AI Disclosure

I initially came up with an overview of the website including the vulnerabilities to
showcase, how to showcase them, and what the pages of each website should look like.
From there, I leveraged AI to generate a base framework for me to further develop.
While I expanded on this framework, I additionally used AI to assist me with styling and adding/debugging more technical features