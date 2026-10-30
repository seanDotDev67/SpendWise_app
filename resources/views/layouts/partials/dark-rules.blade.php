@php
    /*
     * Dark palette shared by `data-theme="dark"` and `data-theme="system"` (inside prefers-color-scheme).
     * Keys are selector lists relative to the scope; a leading "&" targets the scope element (body) itself.
     */
    $text = '#e7eee8';
    $muted = '#b4c0b7';
    $line = '#38453c';
    $card = '#243029';
    $inset = '#1b241e';
    $hover = '#2d3b32';
    $accent = '#8fb996';
    $field = "border-color: {$line}; background: {$inset}; color: {$text};";
    $lift = 'linear-gradient(rgba(255, 255, 255, .38), rgba(255, 255, 255, .38))';

    $rules = [
        '&' => "
            --sw-cream: #1a221d; --sw-bg: #141a16; --sw-card: {$card}; --sw-card-beige: #2a362e;
            --sw-pink: #3b2a2f; --sw-track: #3a463e;
            --sw-ink: {$text}; --sw-muted: {$muted}; --sw-border: {$line}; --sw-surface: {$card};
            --sw-green: #3aa57f; --sw-green-soft: #1f3a2e; --sw-red-soft: #3a2528;
            --report-ink: {$text}; --report-muted: {$muted}; --report-border: rgba(231, 238, 232, .16);
            --cream: #111714; --beige: #171f1a; --ink: {$text}; --muted: {$muted}; --line: {$line};
            --green: {$accent}; --green-d: #a5cbac;
            --arc-ok: {$accent}; --arc-over: #e0838f;
        ",

        // Shell, navigation and search
        '.sw-topbar' => 'background-color: transparent;',
        '.sw-main h1, .sw-main h2, .sw-main h3, .report-heading h1, .report-card-heading h2, .report-card-heading h3' => "color: {$text};",
        '.sw-main' => "background-color: #1a221d; color: {$text};",
        '.sw-topbar' => "border-bottom-color: {$line};",
        '.sw-nav a.active' => "background-color: #1a221d; color: {$text};",
        '.sw-search' => "background-color: {$card}; border: 1px solid {$line}; color: {$muted};",
        '.sw-search input::placeholder, .search input::placeholder' => "color: {$muted}; opacity: .8;",
        '.sw-search-results' => "background-color: {$card}; border: 1px solid {$line}; box-shadow: 0 12px 30px rgba(0, 0, 0, .45);",
        '.sw-sr-item, .sw-sr-item:hover, .sw-sr-item.is-active, .sw-profile-link:hover' => "color: {$text};",
        '.sw-sr-item:hover, .sw-sr-item.is-active' => "background: {$hover};",
        '.sw-sr-group, .sw-sr-item small, .sw-sr-empty' => "color: #a9b99a;",
        '.sw-mobile-nav a' => "background-color: {$card}; color: {$text};",
        '.sw-mobile-nav a.active' => "background-color: #3f7a56; color: #fff;",
        '.text-dark, .btn-link.text-dark' => "color: {$text} !important;",
        '.bg-white, .bg-light' => "background-color: {$card} !important; color: {$text};",
        '.alert-success' => "border-color: #2f5a44; background-color: #1f3a2e; color: #b7e4c7;",
        '.btn-close' => "filter: invert(1) grayscale(1) brightness(1.6);",

        // Shared cards and dashboard
        '.sw-card, .sw-card-beige, .sw-stat.beige, .sw-stat.pink' => "color: {$text};",
        '.sw-card, .sw-card-beige, .sw-stat' => "box-shadow: 0 2px 8px rgba(0, 0, 0, .35);",
        '.sw-card, .sw-card-beige' => "background-color: {$card};",
        '.sw-stat' => "border-color: {$line}; color: {$text};",
        '.sw-stat.beige' => "background: #2a362e;",
        '.sw-stat.pink' => "background: #3b2a2f;",
        '.sw-right' => "border-left-color: {$line};",
        '.sw-coach' => "border-color: {$line};",
        '.sw-coach-title' => "color: {$text};",
        '.sw-coach-focus' => "background: rgba(255, 255, 255, .06);",
        '.sw-coach hr' => "border-color: {$line} !important; opacity: 1;",
        '.sw-cashflow-track' => "background: rgba(255, 255, 255, .12);",
        '.btn-sw-dark' => "background: #3f7a56; color: #fff;",
        '.btn-sw-dark:hover' => "background: #4a8d63; color: #fff;",
        '.btn-sw-light' => "background: #34413a; color: {$text};",
        '.btn-sw-light:hover' => "background: #405046; color: {$text};",
        '.sw-cal .head, .sw-cal .day.weekend' => "color: #f0919d;",
        '.sw-cal .wk' => "background: #4a2f35; color: #f0a8b1;",
        '.sw-cal .day.today' => "background: #3f7a56; color: #fff;",
        '.sw-habit' => "border-color: {$line}; background: {$card}; color: {$text};",
        '.sw-habit-bar-col' => "color: {$muted};",
        '.sw-habit-bar, .sw-habit-dot' => "background: #4a574d;",
        '.sw-habit-track' => "background: {$line};",
        '.sw-habit-bar.on, .sw-habit-fill.on, .sw-habit-dot.on' => "background: {$accent};",
        '.sw-habit-fill' => "background: #c9b98f;",
        '.sw-calendar-card .text-center[style*="--sw-dark"], .sw-stat-icon[style*="--sw-dark"]' => "background: #3f7a56 !important;",
        '.sw-donut-hole' => "background: {$card};",
        '.sw-donut::before' => "background-image: {$lift}, var(--sw-donut-gradient);",
        '.dot, .sw-fill, .report-category-swatch, .report-top-fill, .fill' => "background-image: {$lift} !important;",
        '.report-donut::before' => "content: ''; position: absolute; inset: 0; border-radius: 50%; background: rgba(255, 255, 255, .38);",

        // Reports: cards, SVG trend lines, bar and donut charts
        '.report-stat, .report-card, .report-ai-panel' => "border-color: {$line}; background-color: {$card}; color: {$text}; box-shadow: 0 2px 8px rgba(0, 0, 0, .35);",
        '.report-date-form' => "background: {$card}; border-color: {$line};",
        '.report-date-form input, .report-chat-form input' => $field . ' color-scheme: dark;',
        '.report-date-form button, .report-chat-form button, .report-ai-mark' => "background: #3f7a56; color: #fff;",
        '.report-eyebrow' => "color: {$accent};",
        '.report-stat-icon' => "background: #2a4636; color: {$accent};",
        '.report-donut-hole' => "background: {$card}; color: {$text};",
        '.report-grid-line' => "stroke: rgba(231, 238, 232, .18);",
        '.report-bar-pair' => "border-bottom-color: rgba(231, 238, 232, .22);",
        '.report-month-bars, .report-trend-dates, .report-category-legend .report-category-percent' => "color: {$muted};",
        '.report-category-legend li, .report-top-name, .report-goal-figures b, .report-table td' => "color: {$text};",
        '.report-top-track, .report-goal-track' => "background: {$line};",
        '.report-table tr, .report-table td, .report-table th' => "border-color: {$line};",
        '.report-type-pill' => "background: #1f3a2e; color: #7fd6a6;",
        '.report-type-pill.expense' => "background: #3a2528; color: #f0919d;",
        '.report-ai-heading' => "background: #2a362e;",
        '.report-ai-new, .report-ai-suggestion, .report-ai-welcome, .report-chat-bubble, .report-ai-collapse' => "border-color: {$line}; background: {$inset}; color: {$text};",
        '.report-ai-suggestion:hover' => "background: {$hover};",
        '.report-chat-bubble.user' => "border-color: #3f7a56; background: #3f7a56; color: #fff;",
        '.report-chat-bubble.error, .report-ai-config-warning' => "border-color: #e0838f; color: #f0919d;",
        '.report-chat-bubble.md code' => "background: rgba(143, 185, 150, .18);",
        '.report-focus' => "background: #2a362e;",

        // Transactions page and its modal
        '.sw-secondary-btn, .sw-icon-action, .sw-modal-content .sw-secondary-btn' => "border-color: {$line}; background: {$card}; color: {$text};",
        '.sw-secondary-btn:hover, .sw-icon-action:hover, .sw-modal-content .sw-secondary-btn:hover' => "background: {$hover}; color: {$text};",
        '.sw-primary-btn, .sw-modal-content .sw-primary-btn' => "border-color: #2f7a5c; background: #2f7a5c; color: #fff;",
        '.sw-primary-btn:hover, .sw-modal-content .sw-primary-btn:hover' => "border-color: #3a8d6c; background: #3a8d6c; color: #fff;",
        '.sw-summary-label, .sw-overview-stat-content span, .sw-insight p, .sw-no-results, .sw-count-badge, .sw-modal-subtitle' => "color: {$muted};",
        '.income-icon, .income-pill, .sw-live-badge' => "background: #1f3a2e; color: #7fd6a6;",
        '.expense-icon, .expense-pill' => "background: #3a2528; color: #f0919d;",
        '.balance-icon' => "background: #232c4a; color: #9db2f0;",
        '.sw-account-footer, .sw-count-badge' => "border-color: {$line}; background: {$inset};",
        '.sw-insight' => "border-color: #2f5a44; background: #1f3a2e;",
        '.sw-search-box, .sw-filter-select' => "border-color: {$line}; background: {$inset}; color: {$text};",
        '.sw-search-box input' => "color: {$text};",
        '.sw-filter-select option, .form-select option' => "background: {$card}; color: {$text};",
        '.sw-transaction-row' => "border-bottom-color: {$line};",
        '.sw-transaction-row:hover' => "background: {$hover};",
        '.sw-modal-content' => "border-color: {$line}; background: {$card}; color: {$text};",
        '.sw-modal-content .modal-header, .sw-modal-content .modal-footer' => "border-color: {$line};",
        '.sw-form-group label' => "color: {$muted};",
        '.sw-form-group .form-control, .sw-form-group .form-select, .sw-amount-input' => $field . ' color-scheme: dark;',
        '.sw-amount-input .form-control' => "background: transparent;",
        '.sw-amount-input > span' => "color: {$accent};",
        '.modal-content' => "background-color: {$card}; color: {$text};",

        // Budgets and Categories (standalone pages)
        '.side nav a.active' => "background: var(--cream); color: {$text};",
        '.top' => "border-bottom-color: {$line};",
        '.search input' => $field,
        '.search .l' => "color: {$muted};",
        '.uname span, .sub, .kv span, .amt span, .lft, .legend, .none, .lab, .pv, .gnote, .sec, .sub-h, .tile small, .rec .d, .mons span' => "color: {$muted};",
        '.mons span.cur' => "color: {$accent};",
        '.hero, .env, .ep, .tile, .fundbox, .fin, .panel, .row, .empty-panel' => "border-color: {$line}; background: {$card}; color: {$text};",
        '.fin' => "background: {$inset}; box-shadow: 0 .2rem 0 {$line}; color-scheme: dark;",
        '.fin:focus' => "box-shadow: 0 .2rem 0 {$accent};",
        '.row:hover' => "background: {$hover};",
        '.row.on' => "border-color: {$accent}; background: {$hover};",
        '.env.add' => "border-color: #4a594e; background: transparent; box-shadow: none; color: {$muted};",
        '.env.on' => "border-color: {$accent};",
        '.kv' => "border-bottom-color: {$line};",
        '.mb' => "background: {$hover}; color: {$text};",
        '.chip.ok' => "background: #1f3a2e; color: #7fd6a6;",
        '.chip.near' => "background: #3d3418; color: #e5c85a;",
        '.chip.over' => "background: #3a2528; color: #f0919d;",
        '.chip.closed' => "background: {$line}; color: {$muted};",
        '.pillbar' => "background: #3a2528; color: #f3b9c0;",
        '.flash.ok' => "background: #1f3a2e; color: #b7e4c7;",
        '.flash.err' => "background: #3a2528; color: #f3b9c0;",
        '.jar, .gcard .gbar, .track' => "background: {$line};",
        '.seg .empty' => "background: {$line};",
        '.lvl' => "background: {$text}; color: #1b241e;",
        '.pace' => "border-top-color: #cfe0c3;",
        '.qk, .pk' => "border-color: {$line}; background: {$inset}; color: {$accent}; box-shadow: none;",
        '.qk:hover' => "background: {$hover};",
        '.pk.on' => "border-color: {$accent}; background: {$hover};",
        '.pills button' => "border-color: {$line}; background: {$inset}; color: {$muted};",
        '.pills button.on' => "border-color: {$accent}; background: #3f7a56; color: #fff;",
        '.btn.dark, .btn-add.alt:hover, .btn-add.alt.on' => "background: #3f7a56; color: #fff;",
        '.btn.line, .btn-add.alt, .link, .gsum b, .mini' => "border-color: {$accent}; color: {$accent};",
        '.btn.ghost' => "border-color: #b86c78; color: #f0919d;",
        '.btn.ghost:hover' => "background: #3a2528;",
        '.mini.danger' => "background: #b5535f; border-color: #b5535f; color: #fff;",
        '.ib, .hic' => "color: {$muted};",
        '.hic:hover' => "background: {$hover};",
        '.eerr, .ferr, .rec .a, .msg' => "color: #f0919d;",
        '.rec .a.inc' => "color: #7fd6a6;",
        '.gauge-track' => "stroke: {$line};",
        '#gp, #tick' => "fill: {$text}; stroke: {$text};",
        '#gp' => "stroke: none;",
        '#tick' => "fill: none;",
        '.gauge-note' => "fill: {$muted};",

        // Profile
        '.profile-page, .profile-intro' => "background-color: {$card};",
        '.profile-page' => "background-color: transparent;",
        '.profile-theme-option input:checked + .profile-theme-card' => "border-color: {$accent}; box-shadow: 0 0 0 3px rgba(143, 185, 150, .22);",
        '.profile-theme-card i, .profile-saved, .profile-eyebrow' => "color: {$accent};",
        '.profile-page .profile-primary' => "background: #3f7a56; color: #fff;",
        '.profile-page .profile-secondary' => "border-color: {$line}; background: {$card}; color: {$text};",
        '.profile-motion-option' => "border-top-color: {$line};",
        '.profile-motion-option strong' => "color: {$text};",
        '.profile-page .profile-error' => "color: #f0919d;",

        // Assistant companion and generated charts
        '.swc' => "color: {$text};",
        '.swc-panel' => "border-color: {$line}; background: {$card}; box-shadow: 0 18px 44px rgba(0, 0, 0, .55);",
        '.swc-log, .swc-chips' => "background: {$card};",
        '.swc-bubble' => "border-color: {$line}; background: #2a362e; color: {$text};",
        '.swc-bubble.user' => "border-color: #3f7a56; background: #3f7a56; color: #fff;",
        '.swc-bubble.error' => "border-color: #e0838f; background: #3a2528; color: #f3b9c0;",
        '.swc-bubble.md code' => "background: rgba(143, 185, 150, .18);",
        '.swc-chip' => "border-color: {$line}; background: {$inset}; color: {$text};",
        '.swc-chip:hover' => "background: {$hover};",
        '.swc-chip-alert' => "border-color: #b86c78; background: #3a2528;",
        '.swc-note' => "background: #3a2528; color: #f3b9c0;",
        '.swc-form, .swc-foot' => "border-color: {$line}; background: {$card};",
        '.swc-form textarea' => $field . ' color-scheme: dark;',
        '.swc-foot' => "color: {$muted};",
        '.swv-card' => "border-color: {$line}; background: {$inset}; color: {$text};",
        '.swv-grid' => "stroke: rgba(231, 238, 232, .16);",
        '.swv-axis, .swv-note, .swv-legend' => "fill: {$muted}; color: {$muted};",
        '.swv-total' => "fill: {$text};",
        '.swv-track' => "background: {$line};",
        '.swv-badge' => "background: #3d3418; color: #e5c85a;",
        '.swv-summary, .swv-empty' => "background: #2a362e;",
        '.swv-summary.swv-positive, .swv-recs' => "border-color: #2f5a44; background: #1f3a2e;",
        '.swv-summary.swv-warning' => "background: #3d3418;",
    ];

    $css = '';
    foreach ($rules as $selectors => $declarations) {
        $scoped = collect(explode(',', $selectors))
            ->map(fn (string $selector): string => str_starts_with(trim($selector), '&')
                ? $scope.substr(trim($selector), 1)
                : $scope.' '.trim($selector))
            ->implode(', ');
        $css .= $scoped.' { '.trim(preg_replace('/\s+/', ' ', $declarations))." }\n";
    }
@endphp
{!! $css !!}
