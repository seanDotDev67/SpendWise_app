@php
    $footerLinks = [
        ['Dashboard', 'dashboard'],
        ['Transactions', 'transactions.page'],
        ['Categories', 'categories.index'],
        ['Budgets', 'budgets.index'],
        ['Reports', 'reports.index'],
    ];
@endphp
<style>
    .sw-footer { margin: 28px 0 0; padding: 18px 4px 22px; border-top: 3px solid rgba(221, 213, 191, .8); color: #2f4a3a; font-family: 'Inter', sans-serif; font-size: 12px; line-height: 1.5; }
    .sw-footer-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; }
    .sw-footer-brand { display: flex; align-items: center; gap: 10px; }
    .sw-footer-brand img { width: 38px; height: 38px; flex: none; display: block; object-fit: contain; padding: 4px; border-radius: 10px; background: #faf7ee; border: 1px solid rgba(221, 213, 191, .9); }
    .sw-footer-brand strong { display: block; font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; line-height: 1.1; }
    .sw-footer-brand span { color: #6b7a62; font-size: 11px; }
    .sw-footer-links { display: flex; flex-wrap: wrap; gap: 6px 18px; margin: 0; padding: 0; list-style: none; }
    .sw-footer-links a { color: inherit; text-decoration: none; opacity: .8; }
    .sw-footer-links a:hover { opacity: 1; text-decoration: underline; }
    .sw-footer-copy { color: #6b7a62; font-size: 11px; }
    body[data-theme="dark"] .sw-footer { color: #dfe8dc; border-color: #2b3a30; }
    body[data-theme="dark"] .sw-footer-brand span, body[data-theme="dark"] .sw-footer-copy { color: #9fb09a; }
    @media (prefers-color-scheme: dark) {
        body[data-theme="system"] .sw-footer { color: #dfe8dc; border-color: #2b3a30; }
        body[data-theme="system"] .sw-footer-brand span, body[data-theme="system"] .sw-footer-copy { color: #9fb09a; }
    }
    @media (max-width: 991px) {
        .sw-footer { padding-bottom: 84px; }
        .sw-footer-links { gap: 0 14px; }
        .sw-footer-links a { display: inline-block; padding: 12px 2px; }
    }
</style>
<footer class="sw-footer" role="contentinfo">
    <div class="sw-footer-inner">
        <div class="sw-footer-brand">
            <img src="{{ asset('images/spendwise-logo.png') }}" width="38" height="38" alt="SpendWise logo">
            <div>
                <strong>SpendWise</strong>
                <span>Spend Smart. Live Wise</span>
            </div>
        </div>
        <ul class="sw-footer-links">
            @foreach ($footerLinks as [$label, $routeName])
                @if (Route::has($routeName))
                    <li><a href="{{ route($routeName) }}">{{ $label }}</a></li>
                @endif
            @endforeach
        </ul>
        <div class="sw-footer-copy">&copy; {{ date('Y') }} SpendWise. Your data stays private to your account.</div>
    </div>
</footer>
