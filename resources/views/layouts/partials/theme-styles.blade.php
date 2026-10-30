<style>
    body[data-theme="dark"] {
        color-scheme: dark;
        background: #141a16 !important;
        color: #e7eee8;
    }

    @media (prefers-color-scheme: dark) {
        body[data-theme="system"] {
            color-scheme: dark;
            background: #141a16 !important;
            color: #e7eee8;
        }
    }

    body[data-theme="dark"] .sw-sidebar,
    body[data-theme="dark"] .sw-nav a.active {
        background-color: #24352a;
    }

    @media (prefers-color-scheme: dark) {
        body[data-theme="system"] .sw-sidebar,
        body[data-theme="system"] .sw-nav a.active {
            background-color: #24352a;
        }
    }

    body[data-theme="dark"] .sw-main,
    body[data-theme="dark"] .sw-topbar,
    body[data-theme="dark"] .profile-page,
    body[data-theme="dark"] .report-card,
    body[data-theme="dark"] .sw-search,
    body[data-theme="dark"] .sw-search-results,
    body[data-theme="dark"] .sw-card,
    body[data-theme="dark"] .sw-card-beige,
    body[data-theme="dark"] .sw-mobile-nav a,
    body[data-theme="dark"] .profile-intro,
    body[data-theme="dark"] .profile-page .profile-secondary {
        background-color: #202923;
        color: #e7eee8;
    }

    @media (prefers-color-scheme: dark) {
        body[data-theme="system"] .profile-theme-card,
        body[data-theme="system"] .profile-motion-option {
            border-color: #39453d;
            background: #151c17;
            color: #e7eee8;
        }

        body[data-theme="system"] .profile-theme-card small,
        body[data-theme="system"] .profile-motion-option small,
        body[data-theme="system"] .profile-motion-option strong {
            color: #b4c0b7;
        }

        body[data-theme="system"] .sw-main,
        body[data-theme="system"] .sw-topbar,
        body[data-theme="system"] .profile-page,
        body[data-theme="system"] .report-card,
        body[data-theme="system"] .sw-search,
        body[data-theme="system"] .sw-search-results,
        body[data-theme="system"] .sw-card,
        body[data-theme="system"] .sw-card-beige,
        body[data-theme="system"] .sw-mobile-nav a,
        body[data-theme="system"] .profile-intro,
        body[data-theme="system"] .profile-page .profile-secondary {
            background-color: #202923;
            color: #e7eee8;
        }
    }

    body[data-theme="dark"] .sw-main,
    body[data-theme="dark"] .profile-page,
    body[data-theme="dark"] .report-card,
    body[data-theme="dark"] .sw-card,
    body[data-theme="dark"] .sw-card-beige,
    body[data-theme="dark"] .profile-intro,
    body[data-theme="dark"] .profile-page .profile-secondary {
        background-color: #202923;
        color: #e7eee8;
    }

    body[data-theme="dark"] .sw-topbar,
    body[data-theme="dark"] .sw-search,
    body[data-theme="dark"] .sw-search-results,
    body[data-theme="dark"] .sw-mobile-nav a,
    body[data-theme="dark"] .profile-intro {
        border-color: #39453d;
    }

    body[data-theme="dark"] .sw-main,
    body[data-theme="dark"] .sw-profile-link,
    body[data-theme="dark"] .report-card h2,
    body[data-theme="dark"] .profile-title,
    body[data-theme="dark"] .profile-section > header h2,
    body[data-theme="dark"] .profile-label,
    body[data-theme="dark"] .profile-page .profile-label {
        color: #e7eee8;
    }

    body[data-theme="dark"] .text-muted,
    body[data-theme="dark"] .profile-subtitle,
    body[data-theme="dark"] .profile-section > header p,
    body[data-theme="dark"] .profile-page .profile-danger-section p,
    body[data-theme="dark"] .profile-account {
        color: #b4c0b7 !important;
    }

    body[data-theme="dark"] .profile-page .profile-input,
    body[data-theme="dark"] .sw-search input {
        border-color: #4a594e;
        background: #151c17;
        color: #e7eee8;
    }

    body[data-theme="dark"] .profile-section > header,
    body[data-theme="dark"] .profile-danger-section {
        border-color: #39453d;
    }

    body[data-theme="dark"] .report-goal-item + .report-goal-item,
    body[data-theme="dark"] .report-table td {
        border-color: #39453d;
    }

    body[data-theme="dark"] .report-chart-empty,
    body[data-theme="dark"] .report-empty {
        color: #b4c0b7;
    }

    body[data-theme="dark"] .report-recent-link,
    body[data-theme="dark"] .report-card-subtitle,
    body[data-theme="dark"] .report-top-amount,
    body[data-theme="dark"] .report-table th,
    body[data-theme="dark"] .report-goal-note {
        color: #b4c0b7;
    }

    body[data-theme="dark"] .report-focus {
        background: #29352d;
    }

    body[data-theme="dark"] .app {
        background: linear-gradient(90deg, #111714 0, #111714 19rem, #171f1a 100%);
    }

    body[data-theme="dark"] .main,
    body[data-theme="dark"] .body,
    body[data-theme="dark"] .head,
    body[data-theme="dark"] .head h1,
    body[data-theme="dark"] .head p,
    body[data-theme="dark"] .sec,
    body[data-theme="dark"] .sec em,
    body[data-theme="dark"] .uname,
    body[data-theme="dark"] .user-link,
    body[data-theme="dark"] .gsum {
        color: #e7eee8;
    }

    body[data-theme="dark"] .top,
    body[data-theme="dark"] .search,
    body[data-theme="dark"] .hero,
    body[data-theme="dark"] .env,
    body[data-theme="dark"] .ep,
    body[data-theme="dark"] .panel,
    body[data-theme="dark"] .row,
    body[data-theme="dark"] .empty-panel,
    body[data-theme="dark"] .tile,
    body[data-theme="dark"] .fundbox,
    body[data-theme="dark"] .fin,
    body[data-theme="dark"] .search input,
    body[data-theme="dark"] .search input:focus {
        border-color: #39453d;
        background: #202923;
        color: #e7eee8;
    }

    body[data-theme="dark"] .side {
        background: #24352a;
    }

    body[data-theme="dark"] .side-head {
        background: #1b2b21;
    }

    @media (prefers-color-scheme: dark) {
        body[data-theme="system"] .sw-main,
        body[data-theme="system"] .sw-profile-link,
        body[data-theme="system"] .report-card h2,
        body[data-theme="system"] .profile-title,
        body[data-theme="system"] .profile-section > header h2,
        body[data-theme="system"] .profile-label,
        body[data-theme="system"] .profile-page .profile-label,
        body[data-theme="system"] .main,
        body[data-theme="system"] .body,
        body[data-theme="system"] .head,
        body[data-theme="system"] .head h1,
        body[data-theme="system"] .head p,
        body[data-theme="system"] .sec,
        body[data-theme="system"] .sec em,
        body[data-theme="system"] .uname,
        body[data-theme="system"] .user-link,
        body[data-theme="system"] .gsum {
            color: #e7eee8;
        }

        body[data-theme="system"] .text-muted,
        body[data-theme="system"] .profile-subtitle,
        body[data-theme="system"] .profile-section > header p,
        body[data-theme="system"] .profile-page .profile-danger-section p,
        body[data-theme="system"] .profile-account,
        body[data-theme="system"] .report-recent-link,
        body[data-theme="system"] .report-card-subtitle,
        body[data-theme="system"] .report-top-amount,
        body[data-theme="system"] .report-table th,
        body[data-theme="system"] .report-goal-note {
            color: #b4c0b7 !important;
        }

        body[data-theme="system"] .profile-page .profile-input,
        body[data-theme="system"] .sw-search input {
            border-color: #4a594e;
            background: #151c17;
            color: #e7eee8;
        }

        body[data-theme="system"] .profile-section > header,
        body[data-theme="system"] .profile-danger-section,
        body[data-theme="system"] .report-goal-item + .report-goal-item,
        body[data-theme="system"] .report-table td {
            border-color: #39453d;
        }

        body[data-theme="system"] .report-focus {
            background: #29352d;
        }

        body[data-theme="system"] .app {
            background: linear-gradient(90deg, #111714 0, #111714 19rem, #171f1a 100%);
        }

        body[data-theme="system"] .top,
        body[data-theme="system"] .search,
        body[data-theme="system"] .hero,
        body[data-theme="system"] .env,
        body[data-theme="system"] .ep,
        body[data-theme="system"] .panel,
        body[data-theme="system"] .row,
        body[data-theme="system"] .empty-panel,
        body[data-theme="system"] .tile,
        body[data-theme="system"] .fundbox,
        body[data-theme="system"] .fin,
        body[data-theme="system"] .search input,
        body[data-theme="system"] .search input:focus {
            border-color: #39453d;
            background: #202923;
            color: #e7eee8;
        }

        body[data-theme="system"] .side {
            background: #24352a;
        }

        body[data-theme="system"] .side-head {
            background: #1b2b21;
        }

        body[data-theme="system"] .report-chart-empty,
        body[data-theme="system"] .report-empty {
            color: #b4c0b7;
        }
    }

    body[data-theme="dark"] .profile-theme-card,
    body[data-theme="dark"] .profile-motion-option {
        border-color: #39453d;
        background: #151c17;
        color: #e7eee8;
    }

    body[data-theme="dark"] .profile-theme-card small,
    body[data-theme="dark"] .profile-motion-option small,
    body[data-theme="dark"] .profile-motion-option strong {
        color: #b4c0b7;
    }

    @include('layouts.partials.dark-rules', ['scope' => 'body[data-theme="dark"]'])

    @media (prefers-color-scheme: dark) {
        @include('layouts.partials.dark-rules', ['scope' => 'body[data-theme="system"]'])
    }

    body[data-reduced-motion="true"] *,
    body[data-reduced-motion="true"] *::before,
    body[data-reduced-motion="true"] *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }

    .sw-avatar img,
    .avatar img,
    .profile-avatar img {
        width: 100%;
        height: 100%;
        border-radius: inherit;
        object-fit: cover;
    }
</style>
