{{-- Motion + chart interaction script. Safe to include once per page; listeners are delegated. --}}
<script>
(function () {
    if (window.__swMotion) return;
    window.__swMotion = true;

    var root = document.documentElement;
    var body = document.body;

    /* Tooltip (always on, independent of reduced-motion).
     * Elements describe themselves with data-tip-title / data-tip-rows /
     * data-tip-color, rendered server-side from real report data. */
    var tip = document.createElement('div');
    tip.className = 'sw-tip';
    tip.setAttribute('role', 'tooltip');
    tip.setAttribute('aria-hidden', 'true');
    body.appendChild(tip);

    var tipHost = null, hideTimer = null, lastDonut = null;

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function render(title, color, rows, focusKey) {
        var html = '<div class="sw-tip-title">'
            + (color ? '<i class="sw-tip-swatch" style="background:' + esc(color) + '"></i>' : '')
            + '<span>' + esc(title) + '</span></div>';
        rows.forEach(function (r) {
            var on = focusKey && r.k === focusKey;
            html += '<div class="sw-tip-row' + (on ? ' focus' : '') + '"><span>' + esc(r.l) + '</span><b>' + esc(r.v) + '</b></div>';
        });
        tip.innerHTML = html;
    }

    function place(x, y) {
        var pad = 14, w = tip.offsetWidth, h = tip.offsetHeight;
        var left = x + pad, top = y + pad;
        if (left + w > window.innerWidth - 8) left = x - w - pad;
        if (top + h > window.innerHeight - 8) top = y - h - pad;
        tip.style.left = Math.max(8, left) + 'px';
        tip.style.top = Math.max(8, top) + 'px';
    }

    function show() { clearTimeout(hideTimer); tip.classList.add('on'); }

    function clearHighlight() {
        document.querySelectorAll('.sw-hl').forEach(function (n) { n.classList.remove('sw-hl'); });
        if (lastDonut) {
            lastDonut.classList.remove('sw-donut-hl');
            lastDonut = null;
        }
    }

    function hide() {
        tip.classList.remove('on');
        tipHost = null;
        clearHighlight();
    }

    function parseRows(el) {
        try { return JSON.parse(el.getAttribute('data-tip-rows') || '[]'); } catch (e) { return []; }
    }

    function donutSegments(donut) {
        if (donut.__segs) return donut.__segs;
        var data;
        try { data = JSON.parse(donut.getAttribute('data-donut') || '[]'); } catch (e) { data = []; }
        var total = data.reduce(function (s, d) { return s + (Number(d.v) || 0); }, 0);
        var pos = 0;
        donut.__segs = data.map(function (d) {
            var start = total > 0 ? pos / total * 100 : 0;
            pos += Number(d.v) || 0;
            return { d: d, start: start, end: total > 0 ? pos / total * 100 : 0 };
        });
        return donut.__segs;
    }

    function highlightSegment(donut, index) {
        var seg = donutSegments(donut)[index];
        document.querySelectorAll('.sw-hl').forEach(function (n) { n.classList.remove('sw-hl'); });
        if (!seg) return;
        donut.style.setProperty('--sw-hl-a', seg.start + '%');
        donut.style.setProperty('--sw-hl-b', seg.end + '%');
        donut.classList.add('sw-donut-hl');
        lastDonut = donut;
        var group = donut.getAttribute('data-hl-group');
        if (group) {
            document.querySelectorAll('[data-hl="' + group + ':' + index + '"]').forEach(function (n) { n.classList.add('sw-hl'); });
        }
    }

    function segmentAt(donut, x, y) {
        var r = donut.getBoundingClientRect();
        var dx = x - (r.left + r.width / 2), dy = y - (r.top + r.height / 2);
        var dist = Math.sqrt(dx * dx + dy * dy);
        var hole = donut.querySelector('[class$="donut-hole"]');
        var inner = hole ? hole.getBoundingClientRect().width / 2 : r.width / 4;
        if (dist > r.width / 2 || dist < inner) return -1;
        var pct = ((Math.atan2(dx, -dy) * 180 / Math.PI + 360) % 360) / 360 * 100;
        var segs = donutSegments(donut);
        for (var i = 0; i < segs.length; i++) {
            if (pct >= segs[i].start && pct < segs[i].end) return i;
        }
        return segs.length ? segs.length - 1 : -1;
    }

    function updateFromEvent(e) {
        var t = e.target;
        if (!(t instanceof Element)) return;

        var donut = t.closest('[data-donut]');
        if (donut) {
            var idx = segmentAt(donut, e.clientX, e.clientY);
            var seg = donutSegments(donut)[idx];
            if (!seg) { if (tipHost === donut) hide(); return; }
            tipHost = donut;
            render(seg.d.n, seg.d.c, [
                { l: 'Amount', v: seg.d.a },
                { l: 'Share of total', v: seg.d.p + '%' }
            ]);
            highlightSegment(donut, idx);
            show();
            place(e.clientX, e.clientY);
            return;
        }

        var host = t.closest('[data-tip-title]');
        var legend = t.closest('[data-hl]');
        if (legend && !host) {
            var parts = legend.getAttribute('data-hl').split(':');
            var linked = document.querySelector('[data-hl-group="' + parts[0] + '"]');
            if (linked) highlightSegment(linked, Number(parts[1]));
            return;
        }

        if (!host) { if (tipHost) hide(); return; }
        if (tipHost !== host) clearHighlight();
        tipHost = host;
        if (legend) {
            var p2 = legend.getAttribute('data-hl').split(':');
            var d3 = document.querySelector('[data-hl-group="' + p2[0] + '"]');
            if (d3 && lastDonut !== d3) highlightSegment(d3, Number(p2[1]));
        }
        var focus = null;
        ['income', 'expenses'].forEach(function (k) { if (t.classList.contains(k)) focus = k; });
        render(host.getAttribute('data-tip-title'), host.getAttribute('data-tip-color'), parseRows(host), focus);
        show();
        place(e.clientX, e.clientY);
    }

    document.addEventListener('pointermove', updateFromEvent, { passive: true });
    document.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'touch') {
            updateFromEvent(e);
            hideTimer = setTimeout(hide, 3000);
        }
    }, { passive: true });
    root.addEventListener('mouseleave', hide);
    document.addEventListener('scroll', function () { if (tipHost) hide(); }, { passive: true, capture: true });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });

    /* Entrance motion */
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches || body.dataset.reducedMotion === 'true';
    if (reduce || !('IntersectionObserver' in window)) {
        root.classList.remove('sw-js');
        root.setAttribute('data-sw-ready', '1');
        return;
    }

    var PANELS = '.sw-card:not(.sw-entrance), .sw-card-beige:not(.sw-entrance), .sw-stat:not(.sw-entrance), .sw-summary-card, .sw-panel, .report-card, .report-stat';
    var CHARTS = '.report-bar, .report-cf-bar, .sw-habit-bar, .report-top-fill, .sw-habit-fill, .report-donut';
    var COUNTERS = '.sw-stat-value, .report-stat-value, .sw-summary-amount';
    var ROWS = '.report-table tbody tr, .sw-transaction-row';

    var firstBatch = true;
    setTimeout(function () { firstBatch = false; }, 700);

    // Dashboard cards use their own CSS entrance; spread their start times in document order.
    document.querySelectorAll('.sw-entrance').forEach(function (el, i) {
        el.style.animationDelay = Math.min(i * 70 + 120, 700) + 'ms';
    });

    function finish(el, ms) {
        setTimeout(function () { el.classList.add('sw-done'); el.style.removeProperty('--sw-d'); }, ms);
    }

    function watch(selector, onSetup, onEnter, margin) {
        var items = Array.prototype.slice.call(document.querySelectorAll(selector));
        if (!items.length) return;
        var io = new IntersectionObserver(function (entries) {
            var order = 0;
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                io.unobserve(entry.target);
                onEnter(entry.target, order++);
            });
        }, { threshold: 0.1, rootMargin: margin || '0px 0px -5% 0px' });
        items.forEach(function (el) { if (onSetup) onSetup(el); io.observe(el); });
    }

    // Panels are staggered, with an offset on the first screen so headings land first.
    watch(PANELS, null, function (el, order) {
        var d = (firstBatch ? 160 : 0) + Math.min(order, 8) * 80;
        el.style.setProperty('--sw-d', d + 'ms');
        el.classList.add('sw-in');
        finish(el, d + 750);
    });

    watch(CHARTS, null, function (el, order) {
        var d = 250 + Math.min(order, 14) * 45;
        el.style.setProperty('--sw-d', d + 'ms');
        el.classList.add('sw-in');
        finish(el, d + 1200);
    }, '0px 0px -3% 0px');

    // Rows animate once, then the class is removed so filtering never replays it.
    var rowIndex = 0;
    watch(ROWS, null, function (el) {
        el.style.setProperty('--sw-d', Math.min(rowIndex++, 10) * 40 + 'ms');
        el.classList.add('sw-row-in');
        el.addEventListener('animationend', function () {
            el.classList.remove('sw-row-in');
            el.style.removeProperty('--sw-d');
        }, { once: true });
    });

    // Count-up parses the server-rendered figure and always ends on the exact original text.
    var NUM = /^(\D*?)(\d[\d,]*(?:\.(\d+))?)(\D*)$/;
    watch(COUNTERS, function (el) {
        var m = NUM.exec(el.textContent.trim().replace(/\s+/g, ' '));
        if (!m) return;
        el.__count = {
            text: el.textContent,
            pre: m[1], post: m[4],
            dec: m[3] ? m[3].length : 0,
            target: parseFloat(m[2].replace(/,/g, ''))
        };
        el.textContent = m[1] + (0).toFixed(el.__count.dec) + m[4];
    }, function (el) {
        var c = el.__count;
        if (!c) return;
        var start = null, dur = 900;
        function frame(ts) {
            if (start === null) start = ts;
            var p = Math.min(1, (ts - start) / dur);
            if (p >= 1) { el.textContent = c.text; return; }
            el.textContent = c.pre + (c.target * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US', {
                minimumFractionDigits: c.dec, maximumFractionDigits: c.dec
            }) + c.post;
            requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    });

    root.setAttribute('data-sw-ready', '1');
})();
</script>
