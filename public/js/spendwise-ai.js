/* Shared SpendWise AI chat helpers: safe Markdown rendering and smooth NDJSON streaming. */
(function () {
    'use strict';

    function appendInline(parent, text) {
        // Supports **bold**, __bold__, *italic*, _italic_ and `code`; everything else stays plain text.
        var pattern = /(\*\*([^*\n]+?)\*\*|__([^_\n]+?)__|`([^`\n]+?)`|(?<![\w*])\*([^*\s][^*\n]*?)\*(?![\w*])|(?<![\w])_([^_\s][^_\n]*?)_(?![\w]))/g;
        var last = 0;
        var match;
        while ((match = pattern.exec(text)) !== null) {
            if (match.index > last) parent.appendChild(document.createTextNode(text.slice(last, match.index)));
            var node;
            if (match[2] !== undefined || match[3] !== undefined) {
                node = document.createElement('strong');
                node.textContent = match[2] !== undefined ? match[2] : match[3];
            } else if (match[4] !== undefined) {
                node = document.createElement('code');
                node.textContent = match[4];
            } else {
                node = document.createElement('em');
                node.textContent = match[5] !== undefined ? match[5] : match[6];
            }
            parent.appendChild(node);
            last = pattern.lastIndex;
        }
        if (last < text.length) parent.appendChild(document.createTextNode(text.slice(last)));
    }

    // Builds DOM nodes (never innerHTML), so model output cannot inject markup.
    function renderMarkdown(target, text) {
        var fragment = document.createDocumentFragment();
        var paragraph = null;
        var list = null;

        text.replace(/\r\n?/g, '\n').split('\n').forEach(function (rawLine) {
            var line = rawLine.replace(/\s+$/, '');
            if (line.trim() === '') { paragraph = null; list = null; return; }

            var bullet = line.match(/^\s*[-*•]\s+(.*)$/);
            var numbered = line.match(/^\s*\d+[.)]\s+(.*)$/);
            var heading = line.match(/^\s*#{1,6}\s+(.*)$/);

            if (bullet || numbered) {
                var tag = bullet ? 'ul' : 'ol';
                if (!list || list.tagName.toLowerCase() !== tag) {
                    list = document.createElement(tag);
                    fragment.appendChild(list);
                }
                paragraph = null;
                var item = document.createElement('li');
                appendInline(item, (bullet || numbered)[1]);
                list.appendChild(item);
            } else if (heading) {
                paragraph = null;
                list = null;
                var block = document.createElement('p');
                var strong = document.createElement('strong');
                appendInline(strong, heading[1]);
                block.appendChild(strong);
                fragment.appendChild(block);
            } else {
                list = null;
                if (!paragraph) {
                    paragraph = document.createElement('p');
                    fragment.appendChild(paragraph);
                } else {
                    paragraph.appendChild(document.createElement('br'));
                }
                appendInline(paragraph, line);
            }
        });

        target.classList.add('md');
        target.replaceChildren(fragment);
    }

    /**
     * POSTs a question and reads the NDJSON stream (token / done / error events).
     * Text is revealed at a steady, backlog-adaptive pace instead of in network bursts.
     *
     * options: { url, csrfToken, payload, onFirstText(), onText(shownSoFar), onScroll() }
     * Resolves with the final validated answer; rejects with an Error whose message is user-safe.
     */
    async function streamChat(options) {
        var pending = '';
        var shown = '';
        var started = false;
        var streamEnded = false;
        var drainResolve = null;
        var lastFrame = 0;
        var rafId = null;

        function pump(now) {
            rafId = null;
            var elapsed = Math.min(now - (lastFrame || now), 100);
            lastFrame = now;
            if (pending.length > 0) {
                var charsPerSecond = Math.max(40, pending.length * 2.5);
                var count = Math.max(1, Math.round(charsPerSecond * elapsed / 1000));
                shown += pending.slice(0, count);
                pending = pending.slice(count);
                options.onText(shown);
            }
            if (pending.length > 0 || !streamEnded) {
                rafId = requestAnimationFrame(pump);
            } else if (drainResolve) {
                drainResolve();
            }
        }

        function startPump() {
            if (rafId === null) {
                lastFrame = 0;
                rafId = requestAnimationFrame(pump);
            }
        }

        try {
            var response = await fetch(options.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/x-ndjson',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': options.csrfToken
                },
                body: JSON.stringify(options.payload)
            });

            if (!response.ok || !response.body) {
                var failure = {};
                try { failure = await response.json(); } catch (e) { /* not JSON */ }
                var fallback = response.status === 422
                    ? 'Please check your message and try again.'
                    : response.status === 429
                        ? 'You are sending messages too quickly. Please wait a moment and try again.'
                        : 'The assistant could not answer right now. Please try again.';
                throw new Error(failure.message && response.status !== 422 ? failure.message : fallback);
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';
            var finished = false;
            var finalAnswer = '';
            var finalVisuals = [];
            var finalInsight = null;

            var handle = function (line) {
                if (!line.trim()) return;
                var event;
                try { event = JSON.parse(line); } catch (e) { return; }

                if (event.type === 'token') {
                    if (!started) { started = true; options.onFirstText(); }
                    pending += event.text;
                    startPump();
                } else if (event.type === 'done') {
                    finished = true;
                    finalAnswer = event.answer;
                    finalVisuals = event.visuals;
                    finalInsight = event.insight || null;
                } else if (event.type === 'error') {
                    throw new Error(event.message);
                }
            };

            while (true) {
                var chunk = await reader.read();
                if (chunk.done) break;
                buffer += decoder.decode(chunk.value, { stream: true });
                var lines = buffer.split('\n');
                buffer = lines.pop();
                lines.forEach(handle);
            }
            handle(buffer);

            if (!finished) {
                throw new Error('The assistant response was interrupted. Please try again.');
            }

            streamEnded = true;
            if (started && pending.length > 0) {
                await new Promise(function (resolve) { drainResolve = resolve; startPump(); });
            }
            if (!started) options.onFirstText();
            options.onText(finalAnswer);
            if (options.onInsight && finalInsight) options.onInsight(finalInsight);
            else if (options.onVisuals) options.onVisuals(finalVisuals || []);

            return finalAnswer;
        } catch (error) {
            streamEnded = true;
            pending = '';
            if (rafId !== null) cancelAnimationFrame(rafId);
            throw error;
        }
    }

    var SVG_NS = 'http://www.w3.org/2000/svg';
    var PALETTE = ['#2F704F', '#D9A441', '#D9534F', '#4A7FB5', '#8E6BB3', '#5FAFA3', '#A8B1A9'];

    function money(value) {
        return '₱' + Number(value).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }

    function compact(value) {
        var abs = Math.abs(value);
        if (abs >= 1e6) return '₱' + +(value / 1e6).toFixed(1) + 'M';
        if (abs >= 1e3) return '₱' + +(value / 1e3).toFixed(1) + 'k';
        return '₱' + Math.round(value);
    }

    function svgEl(name, attrs, text) {
        var node = document.createElementNS(SVG_NS, name);
        Object.keys(attrs || {}).forEach(function (key) { node.setAttribute(key, attrs[key]); });
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function shorten(label, max) {
        label = String(label);
        return label.length > max ? label.slice(0, max - 1) + '…' : label;
    }

    function niceMax(value) {
        if (value <= 0) return 1;
        var pow = Math.pow(10, Math.floor(Math.log10(value)));
        var n = value / pow;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow;
    }

    function addLegend(card, items) {
        var legend = document.createElement('div');
        legend.className = 'swv-legend';
        items.forEach(function (item) {
            var entry = document.createElement('span');
            var swatch = document.createElement('i');
            swatch.style.background = item.color;
            entry.appendChild(swatch);
            entry.appendChild(document.createTextNode(item.label));
            legend.appendChild(entry);
        });
        card.appendChild(legend);
    }

    function drawAxes(svg, width, height, pad, max) {
        for (var i = 0; i <= 4; i++) {
            var y = pad.top + (height - pad.top - pad.bottom) * (1 - i / 4);
            svg.appendChild(svgEl('line', { x1: pad.left, x2: width - pad.right, y1: y, y2: y, class: 'swv-grid' }));
            svg.appendChild(svgEl('text', { x: pad.left - 5, y: y + 3, 'text-anchor': 'end', class: 'swv-axis' }, compact(max * i / 4)));
        }
    }

    function drawBars(card, visual) {
        var width = 320, height = 190, pad = { top: 10, right: 8, bottom: 34, left: 44 };
        var svg = svgEl('svg', { viewBox: '0 0 ' + width + ' ' + height, role: 'img', 'aria-label': visual.title });
        var all = [];
        visual.series.forEach(function (s) { all = all.concat(s.values); });
        var max = niceMax(Math.max.apply(null, all.concat([0])));
        drawAxes(svg, width, height, pad, max);

        var plotW = width - pad.left - pad.right;
        var plotH = height - pad.top - pad.bottom;
        var groupW = plotW / visual.labels.length;
        var barW = Math.min(26, (groupW * 0.7) / visual.series.length);

        visual.labels.forEach(function (label, i) {
            var start = pad.left + groupW * i + (groupW - barW * visual.series.length) / 2;
            visual.series.forEach(function (series, s) {
                var value = series.values[i] || 0;
                var h = Math.max(value > 0 ? 2 : 0, (Math.max(0, value) / max) * plotH);
                var color = (series.colors && series.colors[i]) || series.color || PALETTE[s];
                var rect = svgEl('rect', {
                    x: start + barW * s, width: barW - 2, rx: 3, fill: color, class: 'swv-bar',
                    y: pad.top + plotH, height: 0
                });
                rect.appendChild(svgEl('title', {}, label + (visual.series.length > 1 ? ' · ' + series.name : '') + ': ' + money(value)));
                svg.appendChild(rect);
                requestAnimationFrame(function () {
                    rect.setAttribute('y', pad.top + plotH - h);
                    rect.setAttribute('height', h);
                });
            });
            svg.appendChild(svgEl('text', { x: pad.left + groupW * (i + 0.5), y: height - 18, 'text-anchor': 'middle', class: 'swv-axis' }, shorten(label, 9)));
        });
        card.appendChild(svg);

        if (visual.series.length > 1) {
            addLegend(card, visual.series.map(function (s, i) { return { label: s.name, color: s.color || PALETTE[i] }; }));
        }
    }

    function drawLine(card, visual) {
        var width = 320, height = 190, pad = { top: 10, right: 10, bottom: 34, left: 44 };
        var svg = svgEl('svg', { viewBox: '0 0 ' + width + ' ' + height, role: 'img', 'aria-label': visual.title });
        var all = [];
        visual.series.forEach(function (s) { all = all.concat(s.values); });
        var max = niceMax(Math.max.apply(null, all.concat([0])));
        drawAxes(svg, width, height, pad, max);

        var plotW = width - pad.left - pad.right;
        var plotH = height - pad.top - pad.bottom;
        var count = visual.labels.length;
        var xAt = function (i) { return pad.left + (count === 1 ? plotW / 2 : (plotW * i) / (count - 1)); };
        var yAt = function (v) { return pad.top + plotH - (Math.max(0, v) / max) * plotH; };
        var step = Math.ceil(count / 6);

        visual.labels.forEach(function (label, i) {
            if (i % step === 0 || i === count - 1) {
                svg.appendChild(svgEl('text', { x: xAt(i), y: height - 18, 'text-anchor': 'middle', class: 'swv-axis' }, shorten(label, 7)));
            }
        });

        visual.series.forEach(function (series, s) {
            var color = series.color || PALETTE[s];
            var points = series.values.map(function (v, i) { return xAt(i) + ',' + yAt(v); });
            var line = svgEl('polyline', { points: points.join(' '), fill: 'none', stroke: color, 'stroke-width': 2.2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round', class: 'swv-line' });
            svg.appendChild(line);
            series.values.forEach(function (v, i) {
                var dot = svgEl('circle', { cx: xAt(i), cy: yAt(v), r: 3, fill: color });
                dot.appendChild(svgEl('title', {}, visual.labels[i] + ' · ' + series.name + ': ' + money(v)));
                svg.appendChild(dot);
            });
        });
        card.appendChild(svg);
        addLegend(card, visual.series.map(function (s, i) { return { label: s.name, color: s.color || PALETTE[i] }; }));
    }

    function drawDonut(card, visual) {
        var values = visual.series[0].values;
        var total = values.reduce(function (a, b) { return a + b; }, 0);
        if (total <= 0) return false;

        var size = 140, radius = 52, stroke = 22, c = 2 * Math.PI * radius;
        var wrap = document.createElement('div');
        wrap.className = 'swv-donut';
        var svg = svgEl('svg', { viewBox: '0 0 ' + size + ' ' + size, role: 'img', 'aria-label': visual.title });
        svg.appendChild(svgEl('circle', { cx: 70, cy: 70, r: radius, fill: 'none', stroke: 'rgba(0,0,0,.06)', 'stroke-width': stroke }));

        var offset = 0;
        values.forEach(function (value, i) {
            var length = (value / total) * c;
            var arc = svgEl('circle', {
                cx: 70, cy: 70, r: radius, fill: 'none', stroke: PALETTE[i % PALETTE.length], 'stroke-width': stroke,
                'stroke-dasharray': length + ' ' + (c - length), 'stroke-dashoffset': -offset,
                transform: 'rotate(-90 70 70)', class: 'swv-arc'
            });
            arc.appendChild(svgEl('title', {}, visual.labels[i] + ': ' + money(value) + ' (' + Math.round((value / total) * 100) + '%)'));
            svg.appendChild(arc);
            offset += length;
        });
        svg.appendChild(svgEl('text', { x: 70, y: 67, 'text-anchor': 'middle', class: 'swv-axis' }, 'Total'));
        svg.appendChild(svgEl('text', { x: 70, y: 82, 'text-anchor': 'middle', class: 'swv-total' }, compact(total)));
        wrap.appendChild(svg);

        var list = document.createElement('ul');
        list.className = 'swv-list';
        values.forEach(function (value, i) {
            var item = document.createElement('li');
            var swatch = document.createElement('i');
            swatch.style.background = PALETTE[i % PALETTE.length];
            var name = document.createElement('span');
            name.textContent = shorten(visual.labels[i], 14);
            var pct = document.createElement('b');
            pct.textContent = Math.round((value / total) * 100) + '%';
            item.append(swatch, name, pct);
            list.appendChild(item);
        });
        wrap.appendChild(list);
        card.appendChild(wrap);
        return true;
    }

    function injectVisualStyles() {
        if (document.getElementById('swv-styles')) return;
        var style = document.createElement('style');
        style.id = 'swv-styles';
        style.textContent = [
            '.swv-card{align-self:flex-start;width:min(100%,360px);box-sizing:border-box;margin-top:2px;padding:10px 12px;border:1px solid #DDD5BF;border-radius:14px;background:#fff;color:#2b3a31}',
            '.swv-title{margin:0 0 6px;font-size:12.5px;font-weight:700}',
            '.swv-card svg{display:block;width:100%;height:auto;overflow:visible}',
            '.swv-grid{stroke:rgba(0,0,0,.08);stroke-width:1}',
            '.swv-axis{fill:#6b7a70;font-size:9px}',
            '.swv-total{fill:#2b3a31;font-size:13px;font-weight:700}',
            '.swv-bar{transition:y .6s ease,height .6s ease}',
            '.swv-line{stroke-dasharray:1200;stroke-dashoffset:1200;animation:swv-draw 1s ease forwards}',
            '.swv-arc{animation:swv-fade .8s ease both}',
            '@keyframes swv-draw{to{stroke-dashoffset:0}}',
            '@keyframes swv-fade{from{opacity:0}to{opacity:1}}',
            '.swv-legend{display:flex;flex-wrap:wrap;gap:4px 12px;margin-top:6px;font-size:11px;color:#55645a}',
            '.swv-legend i,.swv-list i{display:inline-block;width:9px;height:9px;margin-right:5px;border-radius:3px}',
            '.swv-donut{display:flex;align-items:center;gap:12px}',
            '.swv-donut svg{flex:0 0 120px;width:120px}',
            '.swv-list{flex:1;min-width:0;margin:0;padding:0;list-style:none;font-size:11.5px}',
            '.swv-list li{display:flex;align-items:center;gap:2px;padding:2px 0}',
            '.swv-list span{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}',
            '.swv-badge{margin-left:6px;padding:1px 7px;border-radius:99px;background:#F6E7C1;color:#8a6410;font-size:10px;font-style:normal;font-weight:700}',
            '.swv-note{margin:6px 0 0;font-size:10.5px;line-height:1.4;color:#6b7a70}',
            '.swv-progress{display:grid;gap:9px}',
            '.swv-prow>div:first-child{display:flex;justify-content:space-between;gap:8px;font-size:11.5px}',
            '.swv-track{height:8px;margin-top:4px;border-radius:99px;background:#EEEADD;overflow:hidden}',
            '.swv-track i{display:block;height:100%;border-radius:99px;transition:width .7s ease}',
            '.swv-summary{font-size:12px;font-weight:600;background:#F7F3E7;border-left:4px solid #A8B1A9}',
            '.swv-summary.swv-positive{border-left-color:#2F704F;background:#EAF3EC}',
            '.swv-summary.swv-warning{border-left-color:#D9A441;background:#FBF3DF}',
            '.swv-empty{font-size:12px;background:#F7F3E7;border-style:dashed}',
            '.swv-points{margin:0;padding-left:16px;font-size:12px;line-height:1.5}',
            '.swv-recs{background:#EAF3EC;border-color:#BFD8C6}',
            '.swv-insights .swv-title,.swv-recs .swv-title{margin-bottom:3px}',
            '@media (prefers-reduced-motion:reduce){.swv-bar{transition:none}.swv-line,.swv-arc{animation:none;stroke-dashoffset:0}}'
        ].join('');
        document.head.appendChild(style);
    }

    function drawProgress(card, visual) {
        var list = document.createElement('div');
        list.className = 'swv-progress';
        var values = visual.series[0].values;
        visual.labels.forEach(function (label, i) {
            var value = values[i] || 0;
            var row = document.createElement('div');
            row.className = 'swv-prow';
            var head = document.createElement('div');
            var name = document.createElement('span');
            name.textContent = shorten(label, 28);
            var pct = document.createElement('b');
            pct.textContent = Math.round(value) + '%';
            head.append(name, pct);
            var track = document.createElement('div');
            track.className = 'swv-track';
            track.setAttribute('role', 'progressbar');
            track.setAttribute('aria-valuemin', '0');
            track.setAttribute('aria-valuemax', '100');
            track.setAttribute('aria-valuenow', String(Math.min(100, Math.round(value))));
            track.setAttribute('aria-label', label);
            var fill = document.createElement('i');
            fill.style.width = '0%';
            fill.style.background = value > 100 ? '#D9534F' : PALETTE[i % PALETTE.length];
            track.appendChild(fill);
            row.append(head, track);
            list.appendChild(row);
            requestAnimationFrame(function () { fill.style.width = Math.min(100, Math.max(0, value)) + '%'; });
        });
        card.appendChild(list);
        return true;
    }

    // Accepts the server's chart format ({type, datasets[{label, data}]}) and maps it to the internal drawing model.
    function normalizeVisual(visual) {
        if (!visual || !Array.isArray(visual.labels) || !visual.labels.length) return null;
        var sets = Array.isArray(visual.datasets) ? visual.datasets : visual.series;
        if (!Array.isArray(sets) || !sets.length) return null;
        var series = sets.map(function (set) {
            var values = set.data || set.values;
            if (!Array.isArray(values)) return null;
            return { name: set.label || set.name || '', values: values.map(Number), color: set.color, colors: set.colors };
        });
        if (series.indexOf(null) !== -1) return null;
        var kinds = { donut: 'doughnut', bars: 'comparison' };
        return {
            type: kinds[visual.type] || visual.type,
            title: visual.title || '',
            labels: visual.labels.map(String),
            series: series,
            kind: visual.kind,
            note: visual.note
        };
    }

    function buildChartCard(raw) {
        var visual = normalizeVisual(raw);
        if (!visual) return null;

        var card = document.createElement('div');
        card.className = 'swv-card';
        var title = document.createElement('p');
        title.className = 'swv-title';
        title.textContent = visual.title;
        if (visual.kind === 'simulation') {
            var badge = document.createElement('em');
            badge.className = 'swv-badge';
            badge.textContent = 'Simulated';
            title.appendChild(badge);
        }
        card.appendChild(title);

        var drawn = true;
        if (visual.type === 'doughnut') drawn = drawDonut(card, visual);
        else if (visual.type === 'line') drawLine(card, visual);
        else if (visual.type === 'progress') drawProgress(card, visual);
        else if (visual.type === 'bar' || visual.type === 'comparison') drawBars(card, visual);
        else drawn = false;
        if (drawn === false) return null;

        if (visual.note) {
            var note = document.createElement('p');
            note.className = 'swv-note';
            note.textContent = visual.note;
            card.appendChild(note);
        }
        return card;
    }

    function buildListCard(heading, items, className) {
        if (!Array.isArray(items) || !items.length) return null;
        var card = document.createElement('div');
        card.className = 'swv-card swv-info ' + className;
        var title = document.createElement('p');
        title.className = 'swv-title';
        title.textContent = heading;
        var list = document.createElement('ul');
        list.className = 'swv-points';
        items.forEach(function (text) {
            var item = document.createElement('li');
            item.textContent = text;
            list.appendChild(item);
        });
        card.append(title, list);
        return card;
    }

    // Renders chart cards (built by the server from verified data) after the given element.
    function renderVisuals(after, visuals) {
        if (!Array.isArray(visuals) || !visuals.length || !after || !after.parentNode) return;
        injectVisualStyles();

        var anchor = after;
        visuals.forEach(function (visual) {
            var card = buildChartCard(visual);
            if (!card) return;
            anchor.parentNode.insertBefore(card, anchor.nextSibling);
            anchor = card;
        });
    }

    // Renders the validated structured response: summary, charts, insight and recommendation cards.
    function renderInsight(after, insight) {
        if (!insight || !after || !after.parentNode) return;
        injectVisualStyles();

        var cards = [];
        if (insight.insufficient_data) {
            var empty = document.createElement('div');
            empty.className = 'swv-card swv-empty';
            empty.textContent = (insight.summary || 'Not enough financial data yet.') + ' '
                + ((insight.recommendations || [])[0] || '');
            cards.push(empty);
        } else {
            if (insight.summary) {
                var summary = document.createElement('div');
                summary.className = 'swv-card swv-summary swv-' + (insight.severity || 'informational');
                summary.textContent = insight.summary;
                cards.push(summary);
            }
            (insight.charts || []).forEach(function (chart) { cards.push(buildChartCard(chart)); });
            cards.push(buildListCard('Insights', insight.insights, 'swv-insights'));
            cards.push(buildListCard('Recommendations', insight.recommendations, 'swv-recs'));
        }

        if (insight.unverified_figures) {
            var warning = document.createElement('p');
            warning.className = 'swv-note';
            warning.textContent = 'Some figures in the explanation above could not be matched to your records. The charts and cards use verified data only.';
            cards.push(warning);
        }

        var anchor = after;
        cards.filter(Boolean).forEach(function (card) {
            anchor.parentNode.insertBefore(card, anchor.nextSibling);
            anchor = card;
        });
    }

    window.SpendWiseAI = { renderMarkdown: renderMarkdown, streamChat: streamChat, renderVisuals: renderVisuals, renderInsight: renderInsight };
})();
