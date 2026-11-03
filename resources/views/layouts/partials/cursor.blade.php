{{-- Custom cursor: a dot that tracks the pointer with a trailing ring. Mouse/trackpad only; skipped for reduced motion. --}}
<style>
    html.sw-cursor-on, html.sw-cursor-on * { cursor: none !important; }
    html.sw-cursor-on :is(input, textarea, [contenteditable]) { cursor: text !important; }

    .sw-cursor-dot, .sw-cursor-ring {
        position: fixed; top: 0; left: 0; z-index: 9999; pointer-events: none;
        border-radius: 50%; opacity: 0; mix-blend-mode: difference; will-change: transform;
        transition: opacity .2s ease;
    }
    .sw-cursor-dot { width: 6px; height: 6px; margin: -3px 0 0 -3px; background: #fff; transition: opacity .2s ease, scale .15s ease; }
    .sw-cursor-ring {
        width: 34px; height: 34px; margin: -17px 0 0 -17px; border: 1.5px solid #fff;
        transition: opacity .2s ease, width .25s var(--sw-ease, ease), height .25s var(--sw-ease, ease), margin .25s var(--sw-ease, ease), background-color .25s ease;
    }
    html.sw-cursor-on .sw-cursor-dot, html.sw-cursor-on .sw-cursor-ring { opacity: 1; }
    html.sw-cursor-on.sw-cursor-hover .sw-cursor-ring { width: 54px; height: 54px; margin: -27px 0 0 -27px; background-color: rgba(255, 255, 255, .15); }
    html.sw-cursor-on.sw-cursor-down .sw-cursor-dot { scale: .6; }
    html.sw-cursor-on.sw-cursor-down .sw-cursor-ring { scale: .85; }
</style>
<script>
    (function () {
        var root = document.documentElement;
        var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.dataset.reducedMotion === 'true';
        if (!fine || reduce) return;

        var HOVER_TARGETS = 'a, button, [role="button"], input, select, textarea, label, summary, [data-hl]';
        var dot = document.createElement('div');
        var ring = document.createElement('div');
        dot.className = 'sw-cursor-dot';
        ring.className = 'sw-cursor-ring';
        document.body.append(dot, ring);

        var x = 0, y = 0, rx = 0, ry = 0, raf = null;

        function frame() {
            rx += (x - rx) * 0.2;
            ry += (y - ry) * 0.2;
            dot.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
            ring.style.transform = 'translate3d(' + rx + 'px,' + ry + 'px,0)';
            raf = (Math.abs(x - rx) > 0.1 || Math.abs(y - ry) > 0.1) ? requestAnimationFrame(frame) : null;
        }

        document.addEventListener('pointermove', function (e) {
            if (e.pointerType !== 'mouse') return;
            if (!root.classList.contains('sw-cursor-on')) {
                rx = e.clientX;
                ry = e.clientY;
                root.classList.add('sw-cursor-on');
            }
            x = e.clientX;
            y = e.clientY;
            if (!raf) raf = requestAnimationFrame(frame);
        }, { passive: true });

        document.addEventListener('pointerover', function (e) {
            var hit = e.target instanceof Element && e.target.closest(HOVER_TARGETS);
            root.classList.toggle('sw-cursor-hover', !!hit);
        });

        document.addEventListener('pointerdown', function () { root.classList.add('sw-cursor-down'); });
        document.addEventListener('pointerup', function () { root.classList.remove('sw-cursor-down'); });

        // Leaving the window: hide the custom cursor so the native one returns.
        document.addEventListener('mouseout', function (e) {
            if (!e.relatedTarget) root.classList.remove('sw-cursor-on', 'sw-cursor-hover', 'sw-cursor-down');
        });
    })();
</script>
