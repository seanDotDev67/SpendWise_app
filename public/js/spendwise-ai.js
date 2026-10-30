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

            return finalAnswer;
        } catch (error) {
            streamEnded = true;
            pending = '';
            if (rafId !== null) cancelAnimationFrame(rafId);
            throw error;
        }
    }

    window.SpendWiseAI = { renderMarkdown: renderMarkdown, streamChat: streamChat };
})();
