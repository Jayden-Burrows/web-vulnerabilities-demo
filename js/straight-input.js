/**
 * Straight-input helper for the attack-surface fields of this demo.
 *
 * Why it exists: iOS "Smart Punctuation" silently rewrites what people type:
 *   --  becomes  —   (em dash)        '  becomes  ’  (curly quote)
 *   "   becomes  “ ” (curly quotes)   ...  becomes  …
 * SQL injection and XSS payloads depend on the exact ASCII characters, so a
 * correct payload typed on a phone would fail for a reason that has nothing to
 * do with the lesson (and the safe site would look like it "blocked" the attack
 * when it never saw one).
 *
 * Two layers, applied to every element marked with the data-straight attribute:
 *   1. Ask the keyboard not to autocorrect / capitalise / spell-check.
 *   2. Convert curly quotes, dashes and ellipses back to ASCII as they appear,
 *      keeping the caret where the user expects it. Pasted text is covered too.
 *
 * This is a keyboard-compatibility aid for the demo, NOT a security control.
 */
(function () {
    if (window.__straightInputLoaded) {
        return;
    }
    window.__straightInputLoaded = true;

    var REPLACEMENTS = [
        [/[\u2018\u2019\u201A\u201B\u2032]/g, "'"],   // curly single quotes, prime
        [/[\u201C\u201D\u201E\u201F\u2033]/g, '"'],   // curly double quotes
        [/[\u2012\u2013\u2014\u2015]/g, '--'],        // figure/en/em dash, horizontal bar
        [/\u2026/g, '...']                            // ellipsis
    ];

    function straighten(text) {
        for (var i = 0; i < REPLACEMENTS.length; i++) {
            text = text.replace(REPLACEMENTS[i][0], REPLACEMENTS[i][1]);
        }
        return text;
    }

    function attach(element) {
        element.setAttribute('autocorrect', 'off');
        element.setAttribute('autocapitalize', 'off');
        element.setAttribute('autocomplete', 'off');
        element.setAttribute('spellcheck', 'false');

        element.addEventListener('input', function () {
            var value = element.value;
            var fixed = straighten(value);
            if (fixed === value) {
                return;
            }
            // Re-compute the caret from the text before it, since a dash grows to two characters.
            var caret = element.selectionStart;
            var caretFixed = (caret === null) ? fixed.length : straighten(value.slice(0, caret)).length;
            element.value = fixed;
            if (element.setSelectionRange && document.activeElement === element) {
                element.setSelectionRange(caretFixed, caretFixed);
            }
        });
    }

    function init() {
        var fields = document.querySelectorAll('[data-straight]');
        for (var i = 0; i < fields.length; i++) {
            attach(fields[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
