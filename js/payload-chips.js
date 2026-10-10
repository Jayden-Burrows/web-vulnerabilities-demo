// Tap-to-fill payload buttons for the SQL injection for the login pags
// Certain characters on a keyboard is difficult and will often autocorrect, so once
// a user has opened "Hint 3" on the SQL injection panel, the login pages will show buttons that fill in the working payloads.

(function () {
    const KEY = 'demo.sqli.answerSeen';

    function safeGet() {
        try {
            return localStorage.getItem(KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function safeSet() {
        try {
            localStorage.setItem(KEY, '1');
        } catch (e) {
            // issue storing answerSeen in localStorage, so 
            // just allow the payload buttons to stay hidden;
        }
    }

    // Landing page: remember that the answer hint was opened.
    const answer = document.querySelector('details[data-unlock="sqli-answer"]');
    if (answer) {
        answer.addEventListener('toggle', function () {
            if (answer.open) {
                safeSet();
            }
        });
    }

    // Login pages: reveal the chips and wire them to the username field.
    const chips = document.getElementById('payload-chips');
    const username = document.getElementById('uname');
    if (chips && username && safeGet()) {
        chips.hidden = false;
        chips.addEventListener('click', function (event) {
            let button = event.target.closest('button[data-fill]');
            if (!button) {
                return;
            }
            username.value = button.getAttribute('data-fill');
            username.focus();
        });
    }
}());