# Web Vulnerability Demo

Two versions of the same small social app, for teaching SQL injection, XSS and IDOR:

- `vulnerable/` has all three flaws on purpose, plus progressive hints on its landing page.
- `safe/` is the same app with each flaw fixed (see "What the safe version changes").

All data is fake. Each SQLite database is created and seeded on first request.

## Run it locally

Requires PHP 8.1+ with `pdo_sqlite`. From this folder:

```bash
php -S localhost:8000
```

Open <http://localhost:8000/>. Demo logins: `alice_demo` / `password`, `bob_demo` / `12345678`.

To reset the data, stop the server and delete `vulnerable/data/` and `safe/data/`.

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

## Hosting on WordPress Playground

Playground runs PHP and SQLite in the visitor's browser (WebAssembly), so there is no server and no database credentials to leak, and every visitor gets a private, resettable copy. A Blueprint with `"wp": false` boots plain PHP without WordPress.

1. Put this folder in a **public** GitHub repo, with `vendor/` committed and `vulnerable/data/` + `safe/data/` **not** committed (see `.gitignore`). Playground can't run Composer, and a committed `.sqlite` file would stop the app from re-seeding.
2. In `playground/blueprint.json`, replace the two `REPLACE_WITH_...` parts of the repo URL (and `ref` if your branch isn't `main`).
3. Open:
   `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/<you>/<repo>/main/playground/blueprint.json`
4. To embed it on a page, use that URL in an `<iframe>`.

The Blueprint copies the repo into `/wordpress` using the `git:directory` resource, so there is no zip to build or host.
