@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sw-topbar d-flex align-items-center justify-content-between gap-3 py-2">
    <div class="sw-search-wrap position-relative flex-grow-1">
        <form class="sw-search d-flex align-items-center gap-3 mb-0" role="search" action="{{ route('transactions.page') }}" method="GET" autocomplete="off">
            <button type="submit" class="sw-search-btn" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
            <input type="search" id="swSearchInput" name="q" aria-label="Search transactions and categories" placeholder="Search transaction, categories..." aria-controls="swSearchResults" aria-expanded="false" role="combobox">
        </form>
        <div class="sw-search-results" id="swSearchResults" role="listbox" hidden></div>
    </div>

    <div class="sw-topbar-actions d-flex align-items-center gap-4">
        <button type="button" class="btn btn-link text-dark p-1" aria-label="Notifications">
            <i class="bi bi-bell fs-4" aria-hidden="true"></i>
        </button>
        <a href="{{ route('profile.edit') }}" class="sw-profile-link d-flex align-items-center gap-3" aria-label="Open profile settings">
            <div class="sw-avatar">{{ $initials }}</div>
            <div class="d-none d-sm-block lh-sm">
                <div class="fw-medium">{{ $user->name }}</div>
                <small class="text-muted">{{ $user->email }}</small>
            </div>
            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
        </a>
    </div>
</header>

<script>
(() => {
    const input = document.getElementById('swSearchInput');
    const box = document.getElementById('swSearchResults');
    if (!input || !box) return;

    const endpoint = @json(route('search'));
    let timer = null, controller = null, items = [], active = -1;

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const close = () => { box.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; };
    const highlight = (i) => {
        items.forEach((el, n) => el.classList.toggle('is-active', n === i));
        active = i;
        if (items[i]) items[i].scrollIntoView({ block: 'nearest' });
    };

    const render = (results) => {
        if (!results.length) {
            box.innerHTML = '<div class="sw-sr-empty">No matches found.</div>';
        } else {
            let group = '', html = '';
            results.forEach((r) => {
                if (r.group !== group) { group = r.group; html += '<div class="sw-sr-group">' + esc(group) + '</div>'; }
                html += '<a class="sw-sr-item" role="option" href="' + esc(r.url) + '"><i class="bi ' + esc(r.icon) + '" aria-hidden="true"></i>'
                    + '<span><b>' + esc(r.title) + '</b>' + (r.subtitle ? '<small>' + esc(r.subtitle) + '</small>' : '') + '</span></a>';
            });
            box.innerHTML = html;
        }
        items = Array.from(box.querySelectorAll('.sw-sr-item'));
        active = -1;
        box.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const search = async () => {
        const q = input.value.trim();
        if (!q) { close(); return; }
        controller?.abort();
        controller = new AbortController();
        try {
            const res = await fetch(endpoint + '?q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (res.ok) render((await res.json()).results || []);
        } catch (e) { /* aborted or offline */ }
    };

    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(search, 200); });
    input.addEventListener('focus', () => { if (input.value.trim() && box.innerHTML) box.hidden = false; });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { close(); return; }
        if (box.hidden || !items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight((active + 1) % items.length); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight((active - 1 + items.length) % items.length); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); items[active].click(); }
    });
    document.addEventListener('click', (e) => { if (!e.target.closest('.sw-search-wrap')) close(); });
})();
</script>

<nav class="sw-mobile-nav d-lg-none" aria-label="Main navigation">
    @foreach ($navigationItems as [$label, $routeName, $icon])
        <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
           class="{{ request()->routeIs($routeName) ? 'active' : '' }}"
           @if (request()->routeIs($routeName)) aria-current="page" @endif>
            <i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}
        </a>
    @endforeach
</nav>
