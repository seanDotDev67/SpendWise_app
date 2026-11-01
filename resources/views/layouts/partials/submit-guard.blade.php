{{-- Prevents duplicate requests: once a form is submitted every button is locked and the clicked one shows a spinner. --}}
<style>
    body.sw-busy button, body.sw-busy input[type="submit"], body.sw-busy input[type="button"], body.sw-busy a.btn { pointer-events: none; }
    .sw-btn-loading { opacity: .75; cursor: progress !important; }
    .sw-btn-loading::before { content: ""; display: inline-block; width: 1em; height: 1em; margin-right: .5em; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; vertical-align: -.15em; animation: sw-btn-spin .7s linear infinite; }
    @keyframes sw-btn-spin { to { transform: rotate(360deg); } }
</style>
<script>
    (function () {
        var busy = false, lastBtn = null, lastAt = 0, timer = null;

        var release = function () {
            busy = false;
            document.body.classList.remove('sw-busy');
            document.querySelectorAll('.sw-btn-loading').forEach(function (b) { b.classList.remove('sw-btn-loading'); });
            document.querySelectorAll('[data-sw-locked]').forEach(function (b) { b.disabled = false; b.removeAttribute('data-sw-locked'); });
        };

        var lock = function (submitter) {
            if (busy) return;
            busy = true;
            document.body.classList.add('sw-busy');
            var btn = submitter || (Date.now() - lastAt < 1500 ? lastBtn : null);
            if (btn) btn.classList.add('sw-btn-loading');
            // Disable after the browser has built the form data, so button values are still submitted.
            setTimeout(function () {
                document.querySelectorAll('button:not([disabled]), input[type="submit"]:not([disabled])').forEach(function (b) {
                    b.disabled = true; b.setAttribute('data-sw-locked', '');
                });
            }, 0);
            // Safety net for responses that don't navigate away (e.g. file downloads).
            clearTimeout(timer);
            timer = setTimeout(release, 20000);
        };

        document.addEventListener('click', function (e) {
            var b = e.target.closest && e.target.closest('button, input[type="submit"]');
            if (!b) return;
            if (busy) { e.preventDefault(); e.stopImmediatePropagation(); return; }
            lastBtn = b; lastAt = Date.now();
        }, true);

        document.addEventListener('submit', function (e) {
            if (busy) { e.preventDefault(); e.stopImmediatePropagation(); return; }
            var submitter = e.submitter || null;
            // Handlers that cancel the submit (AJAX forms) manage their own state.
            setTimeout(function () { if (!e.defaultPrevented) lock(submitter); }, 0);
        }, true);

        // Hidden forms submitted from script (categories, budgets, goals) don't fire a submit event.
        var nativeSubmit = HTMLFormElement.prototype.submit;
        HTMLFormElement.prototype.submit = function () {
            if (busy) return;
            lock(null);
            return nativeSubmit.apply(this, arguments);
        };

        window.addEventListener('pageshow', release);
    })();
</script>
