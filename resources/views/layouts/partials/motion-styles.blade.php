{{-- Shared entrance, chart-reveal, hover and tooltip styles (see motion.blade.php for the script). --}}
<script>
    (function () {
        var root = document.documentElement;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        root.classList.add('sw-js');
        // If the motion script never runs, reveal everything instead of leaving content hidden.
        setTimeout(function () { if (!root.hasAttribute('data-sw-ready')) root.classList.remove('sw-js'); }, 2500);
    })();
</script>
<style>
    @property --sw-sweep { syntax: '<percentage>'; inherits: true; initial-value: 100%; }

    :root { --sw-ease: cubic-bezier(.22, .68, .25, 1); --sw-hl-a: 0%; --sw-hl-b: 0%; }

    /* ---- Headings (CSS-only, always end visible) ---- */
    @keyframes sw-soft-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .sw-page-content :is(.sw-eyebrow, .report-eyebrow) { animation: sw-soft-in .55s var(--sw-ease) both; }
    .sw-page-content :is(h1, .sw-page-title) { animation: sw-soft-in .6s var(--sw-ease) .06s both; }
    .sw-page-content :is(.sw-page-heading, .report-heading, .sw-page-title) ~ p,
    .sw-page-content :is(h1, .sw-page-title) + p,
    .sw-page-content :is(.sw-primary-btn, .report-date-form) { animation: sw-soft-in .6s var(--sw-ease) .14s both; }

    /* ---- Panels: hidden only after JS has flagged the page, revealed on scroll ---- */
    :is(.sw-card, .sw-card-beige, .sw-stat, .sw-summary-card, .sw-panel, .report-card, .report-stat):not(.sw-entrance) {
        transition: opacity .6s ease var(--sw-d, 0ms), transform .6s var(--sw-ease) var(--sw-d, 0ms), box-shadow .3s ease;
    }
    html.sw-js :is(.sw-card, .sw-card-beige, .sw-stat, .sw-summary-card, .sw-panel, .report-card, .report-stat):not(.sw-entrance):not(.sw-in) {
        opacity: 0;
        transform: translateY(16px);
    }
    :is(.sw-card, .sw-card-beige, .sw-stat, .sw-summary-card, .sw-panel, .report-card, .report-stat):not(.sw-entrance).sw-done {
        transition: transform .3s var(--sw-ease), box-shadow .3s ease;
    }
    :is(.sw-summary-card, .sw-panel, .report-card, .report-stat).sw-done:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 26px rgba(30, 50, 40, .12);
    }

    /* ---- Rows (JS adds the class once on load, then removes it) ---- */
    .sw-row-in { animation: sw-soft-in .45s var(--sw-ease) var(--sw-d, 0ms) both; }

    /* ---- Bars ---- */
    .report-bar, .report-cf-bar, .sw-habit-bar, .report-top-fill, .sw-habit-fill {
        transition: transform .9s var(--sw-ease) var(--sw-d, 0ms), filter .25s ease, opacity .25s ease;
    }
    html.sw-js :is(.report-bar, .report-cf-bar, .sw-habit-bar):not(.sw-in) { transform: scaleY(0); transform-origin: bottom center; }
    html.sw-js .report-cf-bar.neg:not(.sw-in) { transform-origin: top center; }
    html.sw-js :is(.report-top-fill, .sw-habit-fill):not(.sw-in) { transform: scaleX(0); transform-origin: left center; }
    .report-top-fill, .sw-habit-fill { transform-origin: left center; }
    .report-bar.sw-done, .report-cf-bar.sw-done, .sw-habit-bar.sw-done, .report-top-fill.sw-done, .sw-habit-fill.sw-done {
        transition: transform .25s var(--sw-ease), filter .25s ease, opacity .25s ease;
    }
    .report-month-bars, .report-cf-col, .report-top-item, .sw-habit-bar-col, .sw-progress-item { transition: opacity .25s ease, transform .3s var(--sw-ease); }
    .report-month-bars:hover .report-bar,
    .report-cf-col:hover .report-cf-bar,
    .sw-habit-bar-col:hover .sw-habit-bar { filter: brightness(1.08) saturate(1.1); }
    .report-bar:hover, .report-cf-bar:hover { transform: scaleX(1.18); }
    .report-bars:hover .report-month-bars:not(:hover),
    .report-cf:hover .report-cf-col:not(:hover),
    .sw-habit-bars:hover .sw-habit-bar-col:not(:hover) { opacity: .6; }
    .report-top-item:hover, .sw-progress-item:hover { transform: translateX(3px); }
    .report-top-item:hover .report-top-fill { filter: brightness(1.1); }
    .report-bars, .report-cf, .report-top-list { cursor: default; }

    /* ---- Donuts ---- */
    /* A card-coloured cover sweeps away to reveal the ring clockwise. */
    .report-donut::before {
        position: absolute; inset: -1px; z-index: 0; border-radius: 50%; content: ""; pointer-events: none;
        background: var(--sw-card, #fff);
        -webkit-mask: conic-gradient(transparent 0 var(--sw-sweep), #000 var(--sw-sweep) 100%);
        mask: conic-gradient(transparent 0 var(--sw-sweep), #000 var(--sw-sweep) 100%);
        transition: --sw-sweep 1.1s var(--sw-ease) .1s;
    }
    html.sw-js .report-donut:not(.sw-in) { --sw-sweep: 0%; }
    .report-donut-hole { z-index: 1; background: var(--sw-card, #fafaf8); }
    .report-donut, .sw-donut { transition: transform .4s var(--sw-ease); }
    .report-donut:hover, .sw-donut:hover { transform: scale(1.03); }
    .sw-donut-hl::after {
        position: absolute; inset: 0; z-index: 0; border-radius: 50%; content: ""; pointer-events: none;
        background: conic-gradient(rgba(250, 250, 248, .6) 0 var(--sw-hl-a), transparent var(--sw-hl-a) var(--sw-hl-b), rgba(250, 250, 248, .6) var(--sw-hl-b) 100%);
    }
    [data-hl] { transition: transform .2s var(--sw-ease), font-weight .2s; }
    [data-hl].sw-hl { font-weight: 700; transform: translateX(3px); }

    /* ---- Tooltip ---- */
    .sw-tip {
        position: fixed; z-index: 2000; top: 0; left: 0; max-width: 240px; padding: 9px 11px;
        border-radius: 9px; background: #1f2f27; color: #f4f1e8; font-size: 11px; line-height: 1.4;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .22); pointer-events: none;
        opacity: 0; transform: translateY(4px); transition: opacity .14s ease, transform .14s ease;
    }
    .sw-tip.on { opacity: 1; transform: none; }
    .sw-tip-title { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-weight: 700; }
    .sw-tip-swatch { width: 8px; height: 8px; flex: 0 0 8px; border-radius: 50%; }
    .sw-tip-row { display: flex; justify-content: space-between; gap: 14px; opacity: .8; }
    .sw-tip-row b { font-weight: 700; }
    .sw-tip-row.focus { opacity: 1; font-weight: 700; }

    @media (prefers-reduced-motion: reduce) {
        .sw-row-in { animation: none; }
        .sw-tip, .report-donut::before { transition: none; }
        [data-hl], .report-month-bars, .report-cf-col, .report-top-item, .sw-progress-item { transition: none; }
    }
</style>
