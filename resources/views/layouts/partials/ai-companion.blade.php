@php
    $companion = $companion ?? ['enabled' => false];
@endphp
@if ($companion['enabled'])
<div class="swc" id="swAiCompanion"
     data-page="{{ $companion['page'] }}"
     data-stream-url="{{ route('reports.assistant.stream') }}"
     data-reset-url="{{ route('reports.assistant.reset') }}"
     data-alert="{{ $companion['alert']['type'] ?? '' }}"
     data-configured="{{ $companion['configured'] ? '1' : '0' }}">
    <section class="swc-panel" id="swAiPanel" role="dialog" aria-label="SpendWise AI companion" hidden>
        <header class="swc-head">
            <span class="swc-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7l-4.5 3.5V17H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><path d="M9 11h.01M15 11h.01" stroke-width="2.4"/><path d="M9.5 13.8c.7.6 1.5.9 2.5.9s1.8-.3 2.5-.9"/></svg>
            </span>
            <span class="swc-title">
                <strong>SpendWise AI</strong>
                <small>Your finance companion</small>
            </span>
            <button type="button" class="swc-iconbtn" id="swAiNew" title="Start a new chat" aria-label="Start a new chat">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            </button>
            <button type="button" class="swc-iconbtn" id="swAiClose" title="Close" aria-label="Close the AI companion">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </header>

        @unless ($companion['configured'])
            <div class="swc-note">SpendWise AI needs to be configured by the administrator before it can respond.</div>
        @endunless

        <div class="swc-log" id="swAiLog" aria-live="polite">
            @forelse ($companion['history'] as $message)
                @if (in_array($message['role'] ?? null, ['user', 'assistant'], true))
                    <div class="swc-bubble {{ $message['role'] }}">{{ $message['content'] }}</div>
                @endif
            @empty
                <div class="swc-bubble assistant swc-greeting">Hi! I’m your SpendWise AI companion. Need help understanding your finances or using SpendWise?</div>
            @endforelse
        </div>

        <div class="swc-chips" id="swAiChips" @if (! empty($companion['history'])) hidden @endif>
            @if (! empty($companion['alert']))
                <button type="button" class="swc-chip swc-chip-alert" data-question="Can you explain this pattern in my spending: {{ $companion['alert']['title'] }}?">{{ $companion['alert']['title'] }}</button>
            @endif
            <button type="button" class="swc-chip" data-question="How much did I spend this month?">How much did I spend this month?</button>
            <button type="button" class="swc-chip" data-question="How can I reduce my spending?">How can I reduce my spending?</button>
            <button type="button" class="swc-chip" data-question="How do I add a transaction?">How do I add a transaction?</button>
        </div>

        <form class="swc-form" id="swAiForm" autocomplete="off">
            <textarea id="swAiInput" rows="1" maxlength="1000" placeholder="Ask me anything…" aria-label="Message SpendWise AI"></textarea>
            <button type="submit" class="swc-send" aria-label="Send message">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6 3.3 10l12.6 2-12.6 2z"/></svg>
            </button>
        </form>
        <p class="swc-foot">Financial answers use only your recorded totals. It can explain, but never changes your data.</p>
    </section>

    <button type="button" class="swc-fab" id="swAiFab" aria-label="Ask SpendWise AI" aria-expanded="false" aria-controls="swAiPanel">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7l-4.5 3.5V17H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><path d="M9 11h.01M15 11h.01" stroke-width="2.4"/><path d="M9.5 13.6c.7.6 1.5.9 2.5.9s1.8-.3 2.5-.9"/></svg>
        <span class="swc-spark" aria-hidden="true">✦</span>
        <span class="swc-dot" id="swAiDot" hidden></span>
        <span class="swc-tip" role="tooltip">Ask SpendWise AI</span>
    </button>
</div>

<style>
    .swc, .swc * { box-sizing: border-box; }
    .swc { position: fixed; right: 20px; bottom: 20px; z-index: 1040; font-family: 'Inter', sans-serif; font-size: 14px; line-height: 1.45; color: #2F4A3A; }
    .swc [hidden] { display: none !important; }
    .swc button, .swc textarea { font-family: inherit; }

    .swc-fab { position: relative; display: grid; place-items: center; width: 52px; height: 52px; padding: 0; margin-left: auto; border: 2px solid #fffbe8; border-radius: 50%; background: #2F4A3A; color: #fffbe8; box-shadow: 0 6px 18px rgba(47, 74, 58, .28); cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
    .swc-fab:hover { transform: translateY(-2px) scale(1.04); background: #3a5a47; box-shadow: 0 10px 24px rgba(47, 74, 58, .32); }
    .swc-fab:focus-visible, .swc-iconbtn:focus-visible, .swc-send:focus-visible, .swc-chip:focus-visible { outline: 3px solid #B8F0C0; outline-offset: 2px; }
    .swc-spark { position: absolute; top: 7px; right: 8px; font-size: 11px; color: #B8F0C0; line-height: 1; }
    .swc-dot { position: absolute; top: -1px; right: -1px; width: 13px; height: 13px; border: 2px solid #fffbe8; border-radius: 50%; background: #EB6A78; }
    .swc-tip { position: absolute; right: 62px; top: 50%; transform: translate(4px, -50%); padding: 5px 10px; border-radius: 8px; background: #2F4A3A; color: #fffbe8; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .15s ease, transform .15s ease; }
    .swc-fab:hover .swc-tip, .swc-fab:focus-visible .swc-tip { opacity: 1; transform: translate(0, -50%); }
    .swc.open .swc-tip { display: none; }

    .swc-panel { position: absolute; right: 0; bottom: 66px; display: flex; flex-direction: column; width: 372px; height: min(520px, calc(100vh - 110px)); overflow: hidden; border: 1px solid #DDD5BF; border-radius: 18px; background: #FAFAF8; box-shadow: 0 18px 44px rgba(47, 74, 58, .22); animation: swc-pop .18s ease-out; transform-origin: bottom right; }
    @keyframes swc-pop { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: none; } }
    .swc-head { display: flex; align-items: center; gap: 10px; padding: 12px 12px 12px 14px; background: #2F4A3A; color: #fffbe8; }
    .swc-avatar { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 50%; background: rgba(255, 251, 232, .14); }
    .swc-title { flex: 1; min-width: 0; line-height: 1.2; }
    .swc-title strong { display: block; font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 600; }
    .swc-title small { font-size: 11px; opacity: .8; }
    .swc-iconbtn { display: grid; place-items: center; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 8px; background: transparent; color: #fffbe8; cursor: pointer; }
    .swc-iconbtn:hover { background: rgba(255, 251, 232, .16); }
    .swc-note { padding: 8px 14px; background: #FFE3E3; color: #a53e49; font-size: 12px; }

    .swc-log { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 14px; overflow-y: auto; background: #FAFAF8; }
    .swc-bubble { max-width: 90%; padding: 9px 12px; border: 1px solid #DDD5BF; border-radius: 14px; background: #F4F0E4; font-size: 13.5px; overflow-wrap: anywhere; white-space: pre-wrap; }
    .swc-bubble.user { align-self: flex-end; border-color: #2F4A3A; background: #2F4A3A; color: #fff; }
    .swc-bubble.assistant { align-self: flex-start; }
    .swc-bubble.error { align-self: flex-start; border-color: #e47782; background: #fff; color: #a53e49; }
    .swc-bubble.md { white-space: normal; }
    .swc-bubble.md p { margin: 0; }
    .swc-bubble.md p + p, .swc-bubble.md p + ul, .swc-bubble.md p + ol, .swc-bubble.md ul + p, .swc-bubble.md ol + p { margin-top: 8px; }
    .swc-bubble.md ul, .swc-bubble.md ol { margin: 6px 0 0; padding-left: 20px; }
    .swc-bubble.md li + li { margin-top: 3px; }
    .swc-bubble.md code { padding: 1px 5px; border-radius: 5px; background: rgba(47, 112, 79, .12); font-size: .92em; }
    .swc-typing span { display: inline-block; width: 6px; height: 6px; margin-right: 3px; border-radius: 50%; background: #8b9b72; animation: swc-bounce 1s infinite ease-in-out; }
    .swc-typing span:nth-child(2) { animation-delay: .15s; }
    .swc-typing span:nth-child(3) { animation-delay: .3s; }
    @keyframes swc-bounce { 0%, 80%, 100% { transform: translateY(0); opacity: .5; } 40% { transform: translateY(-4px); opacity: 1; } }

    .swc-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 14px 10px; background: #FAFAF8; }
    .swc-chip { padding: 5px 10px; border: 1px solid #DDD5BF; border-radius: 999px; background: #fff; color: #2F4A3A; font-size: 12px; cursor: pointer; transition: background .15s ease; }
    .swc-chip:hover { background: #F4F0E4; }
    .swc-chip-alert { border-color: #D98E96; background: #FFE3E3; }

    .swc-form { display: flex; align-items: flex-end; gap: 8px; padding: 10px 12px 6px; border-top: 1px solid #E7E1CD; background: #fff; }
    .swc-form textarea { flex: 1; max-height: 96px; padding: 8px 10px; resize: none; border: 1px solid #DDD5BF; border-radius: 12px; background: #FAFAF8; color: #2F4A3A; font-size: 14px; line-height: 1.4; }
    .swc-form textarea:focus { outline: 2px solid #8b9b72; border-color: transparent; }
    .swc-send { display: grid; place-items: center; width: 38px; height: 38px; padding: 0; border: 0; border-radius: 12px; background: #2F4A3A; color: #fffbe8; cursor: pointer; }
    .swc-send:disabled, .swc-form textarea:disabled { opacity: .55; cursor: not-allowed; }
    .swc-foot { margin: 0; padding: 0 14px 10px; background: #fff; color: #7a7a6a; font-size: 10.5px; }

    @media (max-width: 575.98px) {
        .swc { right: 12px; bottom: 12px; }
        .swc-fab { width: 48px; height: 48px; }
        .swc-panel { position: fixed; right: 12px; bottom: 72px; left: 12px; width: auto; height: min(70vh, 520px); }
        .swc-tip { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .swc-fab, .swc-panel, .swc-tip { transition: none; animation: none; }
        .swc-typing span { animation: none; }
    }
    @media print { .swc { display: none; } }
</style>

<script src="{{ asset('js/spendwise-ai.js') }}"></script>
<script>
(function () {
    var root = document.getElementById('swAiCompanion');
    if (!root || !window.SpendWiseAI) return;

    var fab = document.getElementById('swAiFab');
    var panel = document.getElementById('swAiPanel');
    var log = document.getElementById('swAiLog');
    var form = document.getElementById('swAiForm');
    var input = document.getElementById('swAiInput');
    var chips = document.getElementById('swAiChips');
    var dot = document.getElementById('swAiDot');
    var send = form.querySelector('.swc-send');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var greeting = 'Hi! I’m your SpendWise AI companion. Need help understanding your finances or using SpendWise?';
    var busy = false;
    var seenKey = 'swc-alert-seen';
    var openKey = 'swc-open';

    function addBubble(text, kind) {
        var bubble = document.createElement('div');
        bubble.className = 'swc-bubble ' + kind;
        bubble.textContent = text;
        log.appendChild(bubble);
        log.scrollTop = log.scrollHeight;
        return bubble;
    }

    log.querySelectorAll('.swc-bubble.assistant').forEach(function (bubble) {
        window.SpendWiseAI.renderMarkdown(bubble, bubble.textContent);
    });
    log.scrollTop = log.scrollHeight;

    // The alert dot is a UI hint only; remembering that it was seen is not private data.
    if (root.dataset.alert && sessionStorage.getItem(seenKey) !== root.dataset.alert) {
        dot.hidden = false;
    }

    function setOpen(open, quiet) {
        panel.hidden = !open;
        root.classList.toggle('open', open);
        fab.setAttribute('aria-expanded', String(open));
        try { sessionStorage.setItem(openKey, open ? '1' : '0'); } catch (e) { /* storage unavailable */ }
        if (open) {
            if (root.dataset.alert) {
                sessionStorage.setItem(seenKey, root.dataset.alert);
                dot.hidden = true;
            }
            log.scrollTop = log.scrollHeight;
            if (!quiet) input.focus();
        }
    }

    function setBusy(state) {
        busy = state;
        send.disabled = state;
        input.disabled = state;
        form.dataset.loading = state ? 'true' : 'false';
        if (!state) input.focus();
    }

    function autosize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 96) + 'px';
    }

    async function ask(question) {
        var value = question.trim();
        if (!value || busy) return;

        setBusy(true);
        chips.hidden = true;
        addBubble(value, 'user');
        var loading = addBubble('', 'assistant');
        loading.classList.add('swc-typing');
        loading.innerHTML = '<span></span><span></span><span></span>';
        loading.setAttribute('aria-label', 'SpendWise AI is thinking');
        var bubble = null;

        try {
            await window.SpendWiseAI.streamChat({
                url: root.dataset.streamUrl,
                csrfToken: csrf,
                payload: { page: root.dataset.page || null, question: value },
                onFirstText: function () {
                    loading.remove();
                    bubble = addBubble('', 'assistant');
                },
                onText: function (text) {
                    if (!bubble) return;
                    window.SpendWiseAI.renderMarkdown(bubble, text);
                    log.scrollTop = log.scrollHeight;
                }
            });
        } catch (error) {
            loading.remove();
            if (bubble) bubble.remove();
            addBubble(error.message || 'Unable to reach the assistant. Please try again.', 'error');
        } finally {
            setBusy(false);
        }
    }

    fab.addEventListener('click', function () { setOpen(panel.hidden); });
    document.getElementById('swAiClose').addEventListener('click', function () { setOpen(false); fab.focus(); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden && root.contains(document.activeElement)) {
            setOpen(false);
            fab.focus();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var question = input.value;
        input.value = '';
        autosize();
        ask(question);
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
    input.addEventListener('input', autosize);

    chips.addEventListener('click', function (event) {
        var chip = event.target.closest('.swc-chip');
        if (chip) ask(chip.dataset.question);
    });

    document.getElementById('swAiNew').addEventListener('click', async function () {
        if (busy) return;
        try {
            var response = await fetch(root.dataset.resetUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            if (!response.ok) throw new Error('Unable to start a new conversation. Please try again.');
            log.replaceChildren();
            addBubble(greeting, 'assistant');
            chips.hidden = false;
        } catch (error) {
            addBubble(error.message, 'error');
        }
    });

    try { if (sessionStorage.getItem(openKey) === '1') setOpen(true, true); } catch (e) { /* storage unavailable */ }
})();
</script>
@endif
