<?php
/*
 * "Attacker tools" drawer, shared by /vulnerable and /safe.
 *
 * WordPress Playground gives visitors no reliable address bar (and phones have
 * no dev tools), so the URL-tampering and request-replay steps of the IDOR demo
 * would be impossible. This drawer rebuilds both inside the page:
 *   - Address bar: edit the part of the URL after /vulnerable/ (or /safe/) and go.
 *   - Send request: replay a request (e.g. the trash-can POST) with edited values.
 *     On the secure site it also sends the visitor's own CSRF token (can be unticked).
 * It only sends same-origin requests a visitor could already make by hand.
 */
?>
<button type="button" id="tools-toggle" class="tools-toggle" aria-expanded="false" aria-controls="tools-panel">
    <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Attacker tools
</button>

<section id="tools-panel" class="tools-panel" aria-label="Attacker tools" hidden>
    <div class="tools-head">
        <div class="tools-tabs" role="tablist">
            <button type="button" role="tab" id="tab-nav" class="tools-tab active" aria-selected="true"
                aria-controls="tools-nav">Address bar</button>
            <button type="button" role="tab" id="tab-send" class="tools-tab" aria-selected="false"
                aria-controls="tools-send">Send request</button>
        </div>
        <button type="button" id="tools-close" class="tools-close" aria-label="Close attacker tools">&times;</button>
    </div>

    <form id="tools-nav" class="tools-form" role="tabpanel" aria-labelledby="tab-nav" autocomplete="off">
        <label for="tools-path">Edit the URL, then press Go. Try changing an ID.</label>
        <div class="tools-row">
            <span id="tools-base" class="tools-base"></span>
            <input type="text" id="tools-path" data-straight spellcheck="false" autocapitalize="off" autocorrect="off">
            <button type="submit">Go</button>
        </div>
        <p id="tools-msg" class="tools-msg" role="status"></p>
    </form>

    <form id="tools-send" class="tools-form" role="tabpanel" aria-labelledby="tab-send" autocomplete="off" hidden>
        <label for="tools-endpoint">Replay a request. It is sent with your current login.</label>
        <div class="tools-row">
            <select id="tools-method" aria-label="Method">
                <option>POST</option>
                <option>GET</option>
            </select>
            <span id="tools-base2" class="tools-base"></span>
            <input type="text" id="tools-endpoint" data-straight value="posts/logic/process-post.php" spellcheck="false"
                autocapitalize="off" autocorrect="off" aria-label="Endpoint">
        </div>
        <textarea id="tools-body" rows="3" data-straight spellcheck="false"
            aria-label="Request body (JSON)">{"post_id": 1, "action": "delete"}</textarea>
        <label id="tools-csrf-wrap" class="tools-check" hidden>
            <input type="checkbox" id="tools-csrf" checked> Include my CSRF token (X-CSRF-Token header)
        </label>
        <div class="tools-presets">
            <button type="button" class="tools-preset" data-method="POST" data-endpoint="posts/logic/process-post.php"
                data-body='{"post_id": 1, "action": "delete"}'>Delete a post</button>
            <button type="button" class="tools-preset" data-method="POST"
                data-endpoint="posts/logic/process-save-post.php?action=insert" data-body='{"post_id": 1}'>Save a
                post</button>
        </div>
        <button type="submit">Send</button>
        <pre id="tools-out" class="tools-out" aria-live="polite"></pre>
    </form>
</section>

<script>
    (function () {
        // Works under any URL prefix (local, subfolder, Playground's /scope:xxxx/).
        let m = location.pathname.match(/^(.*\/(?:vulnerable|safe)\/)(.*)$/);
        if (!m) { return; }
        let base = m[1];                       // e.g. /scope:abc/vulnerable/
        let currentPage = m[2];                // e.g. posts/profile.php
        let siteName = base.replace(/\/$/, '').split('/').pop();

        let $ = function (id) { return document.getElementById(id); };
        let toggle = $('tools-toggle'), panel = $('tools-panel');
        let navForm = $('tools-nav'), sendForm = $('tools-send');
        let pathInput = $('tools-path'), msg = $('tools-msg'), out = $('tools-out');

        // The secure site puts a per-session CSRF token in <meta name="csrf-token">.
        // An attacker replaying requests from their OWN logged-in account has a valid
        // token, so it is sent by default; untick the box to see the server refuse it.
        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) { $('tools-csrf-wrap').hidden = false; }

        $('tools-base').textContent = '/' + siteName + '/';
        $('tools-base2').textContent = '/' + siteName + '/';
        pathInput.value = currentPage + location.search;

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.hidden = open;
            if (open) { pathInput.value = currentPage + location.search; msg.textContent = ''; }
        }
        toggle.addEventListener('click', function () { setOpen(true); });
        $('tools-close').addEventListener('click', function () { setOpen(false); toggle.focus(); });

        function showTab(name) {
            let isNav = name === 'nav';
            navForm.hidden = !isNav;
            sendForm.hidden = isNav;
            $('tab-nav').classList.toggle('active', isNav);
            $('tab-send').classList.toggle('active', !isNav);
            $('tab-nav').setAttribute('aria-selected', String(isNav));
            $('tab-send').setAttribute('aria-selected', String(!isNav));
        }
        $('tab-nav').addEventListener('click', function () { showTab('nav'); });
        $('tab-send').addEventListener('click', function () { showTab('send'); });

        // Turn what the visitor typed into a path inside this site.
        // Returns null if it points anywhere else.
        function clean(raw) {
            let rel = raw.trim();
            if (/^[a-z][a-z0-9+.-]*:/i.test(rel) || rel.indexOf('//') === 0) { return null; }
            rel = rel.replace(/^(\.\/|\/)+/, '');
            if (rel.charAt(0) === '?') { rel = currentPage + rel; }  // "?draft_id=6" keeps the current page
            return rel;
        }

        navForm.addEventListener('submit', function (e) {
            e.preventDefault();
            let rel = clean(pathInput.value);
            if (rel === null) { msg.textContent = 'Only paths inside this site, like posts/profile.php?draft_id=6'; return; }
            location.assign(base + rel);
        });

        Array.prototype.forEach.call(document.querySelectorAll('.tools-preset'), function (btn) {
            btn.addEventListener('click', function () {
                $('tools-method').value = btn.dataset.method;
                $('tools-endpoint').value = btn.dataset.endpoint;
                $('tools-body').value = btn.dataset.body;
            });
        });

        sendForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            let method = $('tools-method').value;
            let rel = clean($('tools-endpoint').value);
            if (rel === null) { out.textContent = 'Only paths inside this site.'; return; }
            let opts = { method: method, credentials: 'same-origin' };
            if (method !== 'GET') {
                opts.headers = { 'Content-Type': 'application/json' };
                if (csrfMeta && $('tools-csrf').checked) { opts.headers['X-CSRF-Token'] = csrfMeta.content; }
                opts.body = $('tools-body').value;
            }
            out.textContent = 'Sending...';
            try {
                let res = await fetch(base + rel, opts);
                let text = await res.text();
                try { text = JSON.stringify(JSON.parse(text), null, 2); } catch (err) { /* not JSON */ }
                if (text.length > 1200) { text = text.slice(0, 1200) + '\n... (' + (text.length - 1200) + ' more characters)'; }
                out.textContent = 'HTTP ' + res.status + (res.statusText ? ' ' + res.statusText : '') + '\n' + text;
            } catch (err) {
                out.textContent = 'Request failed: ' + err.message;
            }
        });
    })();
</script>