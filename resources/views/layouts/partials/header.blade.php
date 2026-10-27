@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sw-topbar d-flex align-items-center justify-content-between gap-3 py-2">
    <label class="sw-search d-flex align-items-center gap-3 mb-0">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="text" aria-label="Search transactions and categories" placeholder="Search transaction, categories...">
        <i class="bi bi-sliders" aria-hidden="true"></i>
    </label>

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

<nav class="sw-mobile-nav d-lg-none" aria-label="Main navigation">
    @foreach ($navigationItems as [$label, $routeName, $icon])
        <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
           class="{{ request()->routeIs($routeName) ? 'active' : '' }}"
           @if (request()->routeIs($routeName)) aria-current="page" @endif>
            <i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}
        </a>
    @endforeach
</nav>
