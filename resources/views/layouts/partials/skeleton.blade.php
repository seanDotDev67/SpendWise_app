{{-- Page-loading skeleton: shown while navigating to another page, so users see that something is loading. --}}
<style>
    .sw-skeleton { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1010; display: none; background: var(--sw-bg); padding: calc(var(--sw-u) * 2) calc(var(--sw-u) * 2.5); overflow: hidden; }
    @media (min-width: 992px) { .sw-skeleton { left: var(--sw-side-w); } }
    .sw-skeleton.is-active { display: block; animation: sw-skeleton-in .15s ease-out; }
    .sw-sk { position: relative; overflow: hidden; border-radius: 14px; background: #d9d3bc; }
    .sw-sk::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent); animation: sw-shimmer 1.2s infinite; }
    .sw-sk-title { width: 220px; max-width: 60%; height: 34px; margin-bottom: 22px; }
    .sw-sk-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .sw-sk-card { height: 110px; }
    .sw-sk-block { height: 260px; margin-bottom: 16px; }
    .sw-sk-line { height: 18px; margin-bottom: 12px; }
    @keyframes sw-shimmer { to { transform: translateX(100%); } }
    @keyframes sw-skeleton-in { from { opacity: 0; } to { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .sw-sk::after { animation: none; } }
    body[data-reduced-motion="true"] .sw-sk::after { animation: none; }
</style>

<div class="sw-skeleton" id="swSkeleton" aria-hidden="true">
    <div class="sw-sk sw-sk-title"></div>
    <div class="sw-sk-grid">
        <div class="sw-sk sw-sk-card"></div>
        <div class="sw-sk sw-sk-card"></div>
        <div class="sw-sk sw-sk-card"></div>
    </div>
    <div class="sw-sk sw-sk-block"></div>
    <div class="sw-sk sw-sk-line" style="width: 80%"></div>
    <div class="sw-sk sw-sk-line" style="width: 65%"></div>
    <div class="sw-sk sw-sk-line" style="width: 72%"></div>
</div>

<script>
    (function () {
        var sk = document.getElementById('swSkeleton');
        if (!sk) return;
        var show = function () { sk.classList.add('is-active'); };
        // Hide again when restored from the back/forward cache.
        window.addEventListener('pageshow', function () { sk.classList.remove('is-active'); });

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || a.target === '_blank' || a.hasAttribute('download') || a.origin !== location.origin) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
            if (a.pathname === location.pathname && a.search === location.search) return;
            if (a.hasAttribute('data-bs-toggle') || a.hasAttribute('onclick')) return;
            show();
        });

        document.addEventListener('submit', function (e) {
            var f = e.target;
            if (e.defaultPrevented || !f || f.target === '_blank') return;
            // Defer so handlers that cancel the submit (AJAX forms) can opt out first.
            setTimeout(function () { if (!e.defaultPrevented) show(); }, 0);
        });
    })();
</script>
