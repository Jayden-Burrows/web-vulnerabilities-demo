import hashlib
import html
import http.cookiejar
import json
import os
import pathlib
import re
import shutil
import socket
import sqlite3
import subprocess
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request
import uuid

# Get the root directory of this project
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
# A simple 1x1 PNG stored in byters format
# Generated with imagecreatetruecolor(1,1) + imagepng(), rather than hand-built, so it's a PNG
# GD itself can actually decode -- the safe site's avatar uploader re-encodes through GD, which a
# merely magic-byte-valid-but-malformed PNG would fail.
PNG = bytes.fromhex(
    "89504e470d0a1a0a0000000d4948445200000001000000010802000000907753de000000097048597300000ec4"
    "00000ec401952b0e1b0000000c49444154089963606060000000040001a30a15e30000000049454e44ae426082"
)

XSS = "<img src=x onerror=alert(1)"

# A minimal, harmless PHP file used only to prove the server executes an uploaded .php file -- it
# just echoes a marker string, nothing that touches the filesystem, shell, or network.
POC_PHP = b"<?php echo 'upload-poc-' . (21 + 21); ?>"

# INTERNAL ONLY -- the first line of vulnerable/internal-notice.txt and safe/internal-notice.txt,
# used by the SSRF and XXE tests to confirm a server-local file was actually read.
INTERNAL_MARKER = "INTERNAL ONLY"


# file:// URLs need forward slashes and (on Windows) an extra leading slash before the drive
# letter; pathlib.Path(...).as_uri() builds this correctly on every OS, where a plain
# f'file://{path}' string does not.
def local_file_uri(*parts):
    return pathlib.Path(ROOT, *parts).as_uri()


def xxe_payload(site):
    return (
        f'<?xml version="1.0"?><!DOCTYPE posts [ <!ENTITY xxe SYSTEM "{local_file_uri(site, "internal-notice.txt")}"> ]>'
        f"<posts><post><msg>&xxe;</msg><loc>test</loc></post></posts>"
    ).encode()


server = None
base = ""

UPLOADS_DIR = os.path.join(ROOT, "images", "uploads")
_uploads_snapshot = set()


def _snapshot_uploads():
    found = set()
    for dirpath, _dirnames, filenames in os.walk(UPLOADS_DIR):
        for name in filenames:
            found.add(os.path.relpath(os.path.join(dirpath, name), UPLOADS_DIR))
    return found


def setUpModule():
    global server, base, _uploads_snapshot
    for site in ("safe", "vulnerable"):
        # Remove the data files from safe and vulnerable
        shutil.rmtree(os.path.join(ROOT, site, "data"), ignore_errors=True)
    # images/uploads/ is shared by both sites and already holds the seeded demo photos; only
    # clean up whatever the tests themselves add to it, not what was already there.
    _uploads_snapshot = _snapshot_uploads()
    with socket.socket() as s:
        s.bind(("127.0.0.1", 0))
        port = s.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    server = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port}", "-t", ROOT],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    # Try opening a php server 50 times
    for _ in range(50):
        try:
            urllib.request.urlopen(base + "/", timeout=1)
            return
        except OSError:
            time.sleep(0.1)
    raise RuntimeError("php server did not start")


def tearDownModule():
    server.terminate()
    server.wait()
    for rel in _snapshot_uploads() - _uploads_snapshot:
        os.remove(os.path.join(UPLOADS_DIR, rel))
    for dirpath, dirnames, filenames in os.walk(UPLOADS_DIR, topdown=False):
        if dirpath != UPLOADS_DIR and not dirnames and not filenames:
            os.rmdir(dirpath)


# A class for preventing the url opener from following redirects
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.jar), NoRedirect
        )

    # Make requests to different paths on the website
    def request(self, method, path, data=None, headers=None):
        req = urllib.request.Request(
            base + path, data=data, method=method, headers=headers or {}
        )
        try:
            resp = self.opener.open(req)
        except urllib.error.HTTPError as e:
            resp = e
        body = resp.read().decode("utf-8", "replace")
        return resp.status, resp.headers.get("Location", ""), body

    def get(self, path):
        return self.request("GET", path)

    def post(self, path, fields, headers=None):
        return self.request(
            "POST", path, urllib.parse.urlencode(fields).encode(), headers
        )

    # Post a JSON
    def post_json(self, path, payload, headers=None):
        header_JSON = {"Content-Type": "application/json", **(headers or {})}
        return self.request("POST", path, json.dumps(payload).encode(), header_JSON)

    def post_multipart(self, path, fields, headers=None):
        # Generates a unique string for separating form fields and uploaded files in the multipart/form-data
        boundary = uuid.uuid4().hex
        parts = []
        # post the multiple fields in a form
        for name, value in fields.items():
            parts.append(
                f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode()
            )
        parts.append(
            (
                f'--{boundary}\r\nContent-Disposition: form-data; name="my_files[]"; filename="a.png"\r\n'
                "Content-Type: image/png\r\n\r\n"
            ).encode()
            + PNG
            + b"\r\n"
        )
        parts.append(f"--{boundary}--\r\n".encode())
        header_JSON = {
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            **(headers or {}),
        }
        return self.request("POST", path, b"".join(parts), header_JSON)

    # Post one named file, with a filename and Content-Type the caller controls (both of which a
    # real attacker also controls), alongside any other plain form fields.
    def post_file(
        self,
        path,
        field_name,
        filename,
        content,
        content_type,
        fields=None,
        headers=None,
    ):
        boundary = uuid.uuid4().hex
        parts = []
        for name, value in (fields or {}).items():
            parts.append(
                f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode()
            )
        parts.append(
            (
                f'--{boundary}\r\nContent-Disposition: form-data; name="{field_name}"; '
                f'filename="{filename}"\r\nContent-Type: {content_type}\r\n\r\n'
            ).encode()
            + content
            + b"\r\n"
        )
        parts.append(f"--{boundary}--\r\n".encode())
        header_JSON = {
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            **(headers or {}),
        }
        return self.request("POST", path, b"".join(parts), header_JSON)


# look for the csrf token in the page
def token(page):
    match = re.search(r'name="csrf_token" value="([^"]+)"', page) or re.search(
        r'name="csrf-token" content="([^"]+)"', page
    )
    return match.group(1) if match else ""


# try logging into the vulnerable site
def vuln_login(client, user, password):
    _, _, body = client.post("/vulnerable/login.php", {"uname": user, "psw": password})
    return "sql-debug-success" in body, body


# try logging into the safe site
def safe_login(client, user, password, send_token=True):
    _, _, page = client.get("/safe/login.php")
    fields = {"uname": user, "psw": password}
    if send_token:
        fields["csrf_token"] = token(page)
    status, location, body = client.post("/safe/login.php", fields)
    return status == 302 and location.endswith("/safe/posts/index.php"), body


def db(site, sql, *args):
    # connect to the database in the given site (vulnerable or safe)
    con = sqlite3.connect(os.path.join(ROOT, site, "data", "demo.sqlite"))
    try:
        return con.execute(sql, args).fetchall()
    finally:
        con.close()


class VulnerableSite(unittest.TestCase):
    # Testing Login / SQL Injections
    def test_valid_login(self):
        self.assertTrue(vuln_login(Client(), "guest", "password")[0])

    def test_wrong_password_rejected(self):
        self.assertFalse(vuln_login(Client(), "guest", "wrongpassword")[0])

    def test_sql_injection_bypasses_password(self):
        self.assertTrue(vuln_login(Client(), "' OR 1=1 --", "x")[0])

    def test_sql_injection_targets_specific_user(self):
        ok, body = vuln_login(Client(), "bob_demo' --", "x")
        self.assertTrue(ok)
        self.assertIn("bob_demo", body)

    def test_payload_without_comment_fails_on_precedence(self):
        self.assertFalse(vuln_login(Client(), "' OR '1'='1", "x")[0])

    # testing how curly single quote works versus standard single quote '
    def test_curly_quote_payload_is_not_injection(self):
        self.assertFalse(vuln_login(Client(), "\u2019 OR 1=1 \u2014", "x")[0])

    # Test IDOR
    def test_idor_reads_another_users_draft(self):
        msg, draft_id = db(
            "vulnerable",
            "SELECT msg, id FROM posts WHERE is_posted = 0 AND author_id = "
            "(SELECT id FROM users WHERE username = 'bob_demo') LIMIT 1",
        )[0]
        alice = Client()
        self.assertTrue(vuln_login(alice, "alice_demo", "password")[0])
        _, _, page = alice.get(f"/vulnerable/posts/profile.php?draft_id={draft_id}")
        self.assertIn(msg[:30], html.unescape(page))

    # Test XSS
    def test_csrf_forged_logout_link_logs_out(self):
        c = Client()
        vuln_login(c, "guest", "password")
        c.get("/vulnerable/logout.php")
        status, location, _ = c.get("/vulnerable/posts/index.php")
        self.assertEqual(status, 302)
        self.assertTrue(location.endswith("/vulnerable/login.php"))

    def test_csrf_forged_save_request_succeeds(self):
        c = Client()
        vuln_login(c, "guest", "password")
        status, _, body = c.post_json(
            "/vulnerable/posts/logic/process-save-post.php?action=insert",
            {"post_id": 1},
        )
        self.assertEqual(status, 200)
        self.assertIn('"success":true', body)

    def test_stored_xss_is_rendered_unescaped(self):
        c = Client()
        vuln_login(c, "guest", "password")
        c.post_multipart(
            "/vulnerable/posts/logic/process-post.php",
            {"msg": XSS, "location": "x", "action": "post"},
        )
        (post_id,) = db("vulnerable", "SELECT id FROM posts WHERE msg = ?", XSS)[0]
        page = c.get(f"/vulnerable/posts/index.php?post_id={post_id}")[2]
        self.assertTrue(XSS in page)

    # Test Broken Access Control
    def test_admin_reachable_by_any_logged_in_user(self):
        c = Client()
        vuln_login(c, "guest", "password")
        status, _, body = c.get("/vulnerable/admin.php")
        self.assertEqual(status, 200)
        self.assertIn("admin@example.test", body)

    def test_admin_link_hidden_for_non_admin(self):
        c = Client()
        vuln_login(c, "guest", "password")
        _, _, body = c.get("/vulnerable/posts/index.php")
        self.assertNotIn("admin.php", body)

    # Test File Upload
    def test_avatar_upload_executes_uploaded_php(self):
        c = Client()
        vuln_login(c, "guest", "password")
        status, _, _ = c.post_file(
            "/vulnerable/account.php",
            "avatar",
            "avatar.php",
            POC_PHP,
            "image/jpeg",
            fields={"action": "avatar_file"},
        )
        self.assertEqual(status, 200)
        _, _, served = c.get("/images/uploads/avatars/avatar.php")
        self.assertIn("upload-poc-42", served)
        shutil.rmtree(
            os.path.join(ROOT, "images", "uploads", "avatars"), ignore_errors=True
        )

    # Test SSRF
    def test_url_import_reads_a_local_file(self):
        c = Client()
        vuln_login(c, "guest", "password")
        status, _, body = c.post(
            "/vulnerable/account.php",
            {
                "action": "avatar_url",
                "avatar_url": local_file_uri("vulnerable", "internal-notice.txt"),
            },
        )
        self.assertEqual(status, 200)
        self.assertIn("Fetched", body)
        shutil.rmtree(
            os.path.join(ROOT, "images", "uploads", "avatars"), ignore_errors=True
        )

    # Test XXE
    def test_xml_import_resolves_external_entity(self):
        c = Client()
        vuln_login(c, "guest", "password")
        status, _, _ = c.post_file(
            "/vulnerable/account.php",
            "import",
            "posts.xml",
            xxe_payload("vulnerable"),
            "text/xml",
            fields={"action": "import_xml"},
        )
        self.assertEqual(status, 200)
        _, _, exported = c.get("/vulnerable/account.php?export=1")
        self.assertIn(INTERNAL_MARKER, exported)


class SafeSite(unittest.TestCase):
    # Test login / SQL injection / CSRF
    def test_valid_login(self):
        self.assertTrue(safe_login(Client(), "guest", "password")[0])

    def test_wrong_password_rejected(self):
        self.assertFalse(safe_login(Client(), "guest", "wrong")[0])

    def test_sql_injection_rejected(self):
        for payload in ("' OR 1=1 --", "bob_demo' --"):
            self.assertFalse(safe_login(Client(), payload, "x")[0], payload)

    def test_login_without_csrf_token_rejected(self):
        ok, body = safe_login(Client(), "guest", "password", send_token=False)
        self.assertFalse(ok)
        self.assertIn("session expired", body)

    def test_sessions_are_isolated_between_sites(self):
        c = Client()
        self.assertTrue(vuln_login(c, "guest", "password")[0])
        status, location, _ = c.get("/safe/posts/index.php")
        self.assertEqual(status, 302)
        self.assertTrue(location.endswith("/safe/login.php"))

    def test_login_survives_logout_on_other_site(self):
        c = Client()
        vuln_login(c, "guest", "password")
        _, _, page = c.get("/safe/login.php")
        c.get("/vulnerable/logout.php")
        status, _, _ = c.post(
            "/safe/login.php",
            {"uname": "guest", "psw": "password", "csrf_token": token(page)},
        )
        self.assertEqual(status, 302)

    # Test IDOR
    def test_idor_draft_of_another_user_not_visible(self):
        msg, draft_id = db(
            "safe",
            "SELECT msg, id FROM posts WHERE is_posted = 0 AND author_id = "
            "(SELECT id FROM users WHERE username = 'bob_demo') LIMIT 1",
        )[0]
        alice = Client()
        self.assertTrue(safe_login(alice, "alice_demo", "password")[0])
        _, _, page = alice.get(f"/safe/posts/profile.php?draft_id={draft_id}")
        self.assertNotIn(msg[:30], html.unescape(page))

    def test_cannot_delete_another_users_post(self):
        (post_id,) = db(
            "safe",
            "SELECT id FROM posts WHERE is_posted = 1 AND author_id = "
            "(SELECT id FROM users WHERE username = 'bob_demo') LIMIT 1",
        )[0]
        alice = Client()
        safe_login(alice, "alice_demo", "password")
        t = token(alice.get("/safe/posts/index.php")[2])
        status, _, _ = alice.post_json(
            "/safe/posts/logic/process-post.php",
            {"post_id": post_id, "action": "delete"},
            {"X-CSRF-Token": t},
        )
        self.assertEqual(status, 404)

    def test_state_change_requires_csrf_token(self):
        c = Client()
        safe_login(c, "alice_demo", "password")
        (post_id,) = db("safe", "SELECT id FROM posts WHERE is_posted = 1 LIMIT 1")[0]
        for path, body in (
            (
                "/safe/posts/logic/process-post.php",
                {"post_id": post_id, "action": "delete"},
            ),
            (
                "/safe/posts/logic/process-save-post.php?action=insert",
                {"post_id": post_id},
            ),
        ):
            self.assertEqual(c.post_json(path, body)[0], 403, path)

    def test_save_post_with_token(self):
        c = Client()
        safe_login(c, "alice_demo", "password")
        t = token(c.get("/safe/posts/index.php")[2])
        (post_id,) = db("safe", "SELECT id FROM posts WHERE is_posted = 1 LIMIT 1")[0]
        status, _, _ = c.post_json(
            "/safe/posts/logic/process-save-post.php?action=insert",
            {"post_id": post_id},
            {"X-CSRF-Token": t},
        )
        self.assertEqual(status, 200)

    # Test XSS
    def test_stored_xss_is_escaped(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/posts/create-page.php")[2])
        c.post_multipart(
            "/safe/posts/logic/process-post.php",
            {"msg": XSS, "location": "x", "action": "post", "csrf_token": t},
        )
        (post_id,) = db("safe", "SELECT id FROM posts WHERE msg = ?", XSS)[0]
        page = c.get(f"/safe/posts/index.php?post_id={post_id}")[2]
        self.assertTrue(XSS not in page)
        self.assertTrue(html.escape(XSS, quote=True) in page)

    # Test CSRF
    def test_csrf_forged_logout_link_does_nothing(self):
        c = Client()
        safe_login(c, "guest", "password")
        c.get("/safe/logout.php")
        self.assertEqual(c.get("/safe/posts/index.php")[0], 200)

    def test_csrf_forged_save_request_rejected(self):
        c = Client()
        safe_login(c, "guest", "password")
        status, _, body = c.post_json(
            "/safe/posts/logic/process-save-post.php?action=insert", {"post_id": 1}
        )
        self.assertEqual(status, 403)
        self.assertIn("CSRF", body)

    def test_stale_database_schema_is_rebuilt(self):
        db_path = os.path.join(ROOT, "safe", "data", "demo.sqlite")
        shutil.rmtree(os.path.join(ROOT, "safe", "data"), ignore_errors=True)
        os.makedirs(os.path.dirname(db_path))
        con = sqlite3.connect(db_path)
        con.execute(
            "CREATE TABLE users (id INTEGER PRIMARY KEY, display_name TEXT, username TEXT, email TEXT, "
            "pass TEXT, profile_pic TEXT)"
        )
        con.execute(
            "INSERT INTO users (display_name, username, email, pass, profile_pic) VALUES (?,?,?,?,?)",
            (
                "Guest",
                "guest",
                "guest@example.test",
                hashlib.sha256(b"password").hexdigest(),
                "",
            ),
        )
        con.execute(
            "CREATE TABLE posts (id TEXT PRIMARY KEY, author_id INTEGER, img_url TEXT, msg TEXT, loc TEXT, "
            "post_date TEXT, is_posted INTEGER)"
        )
        con.execute("CREATE TABLE saves (user_id INTEGER, post_id TEXT)")
        con.commit()
        con.close()
        self.assertTrue(safe_login(Client(), "guest", "password")[0])

    def test_logout_needs_post_and_token(self):
        c = Client()
        safe_login(c, "guest", "password")
        c.get("/safe/logout.php")
        self.assertEqual(c.get("/safe/posts/index.php")[0], 200)
        t = token(c.get("/safe/posts/index.php")[2])
        c.post("/safe/logout.php", {"csrf_token": t})
        self.assertEqual(c.get("/safe/posts/index.php")[0], 302)

    # Test Broken Access Control
    def test_admin_blocked_for_non_admin(self):
        c = Client()
        safe_login(c, "guest", "password")
        self.assertEqual(c.get("/safe/admin.php")[0], 403)

    def test_admin_reachable_by_admin(self):
        c = Client()
        safe_login(c, "admin_demo", "password")
        status, _, body = c.get("/safe/admin.php")
        self.assertEqual(status, 200)
        self.assertIn("admin@example.test", body)

    # Test File Upload
    def test_avatar_upload_rejects_the_same_file(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        status, _, body = c.post_file(
            "/safe/account.php",
            "avatar",
            "avatar.php",
            POC_PHP,
            "image/jpeg",
            fields={"action": "avatar_file", "csrf_token": t},
        )
        self.assertEqual(status, 200)
        self.assertNotIn("upload-poc-42", body)
        self.assertFalse(
            os.path.exists(
                os.path.join(ROOT, "images", "uploads", "avatars", "avatar.php")
            )
        )
        shutil.rmtree(
            os.path.join(ROOT, "images", "uploads", "avatars"), ignore_errors=True
        )

    def test_avatar_upload_accepts_a_real_image(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        status, _, body = c.post_file(
            "/safe/account.php",
            "avatar",
            "photo.png",
            PNG,
            "image/png",
            fields={"action": "avatar_file", "csrf_token": t},
        )
        self.assertEqual(status, 200)
        self.assertIn("Avatar updated", body)
        shutil.rmtree(
            os.path.join(ROOT, "images", "uploads", "avatars"), ignore_errors=True
        )

    # Test SSRF
    def test_url_import_rejects_file_scheme(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        status, _, body = c.post(
            "/safe/account.php",
            {
                "action": "avatar_url",
                "avatar_url": local_file_uri("safe", "internal-notice.txt"),
                "csrf_token": t,
            },
        )
        self.assertEqual(status, 200)
        self.assertIn("not allowed", body)

    def test_url_import_rejects_loopback_address(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        status, _, body = c.post(
            "/safe/account.php",
            {
                "action": "avatar_url",
                "avatar_url": "http://127.0.0.1/style.css",
                "csrf_token": t,
            },
        )
        self.assertEqual(status, 200)
        self.assertIn("not allowed", body)

    # Test XXE
    def test_xml_import_rejects_doctype(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        status, _, body = c.post_file(
            "/safe/account.php",
            "import",
            "posts.xml",
            xxe_payload("safe"),
            "text/xml",
            fields={"action": "import_xml", "csrf_token": t},
        )
        self.assertEqual(status, 200)
        self.assertIn("not allowed", body)
        _, _, exported = c.get("/safe/account.php?export=1")
        self.assertNotIn(INTERNAL_MARKER, exported)

    def test_xml_import_accepts_well_formed_posts(self):
        c = Client()
        safe_login(c, "guest", "password")
        t = token(c.get("/safe/account.php")[2])
        good = b'<?xml version="1.0"?><posts><post><msg>Hello</msg><loc>Athens</loc></post></posts>'
        status, _, body = c.post_file(
            "/safe/account.php",
            "import",
            "posts.xml",
            good,
            "text/xml",
            fields={"action": "import_xml", "csrf_token": t},
        )
        self.assertEqual(status, 200)
        self.assertIn("Imported 1 post", body)
        _, _, exported = c.get("/safe/account.php?export=1")
        self.assertIn("Hello", exported)


class Pages(unittest.TestCase):
    # Ensure all pages and resources are loading properly
    def test_public_pages_and_assets_load(self):
        c = Client()
        for path in (
            "/",
            "/vulnerable/index.php",
            "/safe/index.php",
            "/vulnerable/login.php",
            "/safe/login.php",
            "/vulnerabilities/",
            "/vulnerabilities/sql-injection.php",
            "/vulnerabilities/xss.php",
            "/vulnerabilities/idor.php",
            "/vulnerabilities/csrf.php",
            "/vulnerabilities/file-upload.php",
            "/vulnerabilities/ssrf.php",
            "/vulnerabilities/xxe.php",
            "/vulnerabilities/broken-access-control.php",
            "/tools/evil-coupons.html",
            "/fonts/inter-latin-wght-normal.woff2",
            "/style.css",
            "/js/straight-input.js",
            "/js/payload-chips.js",
            "/js/ui-motion.js",
        ):
            self.assertEqual(c.get(path)[0], 200, path)


if __name__ == "__main__":
    # Runs the tests in VulnerableSite, SafeSite, and Pages
    unittest.main()
