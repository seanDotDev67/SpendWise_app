<aside class="sw-sidebar d-none d-lg-flex">
    <a class="sw-brand" href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}">
        <img class="logo" src="{{ asset('images/spendwise-logo.png') }}" alt="SpendWise logo">
        <div>
            <h1 class="serif">SpendWise</h1>
            <small>Spend Smart. Live Wise</small>
        </div>
    </a>

    <nav class="sw-nav" aria-label="Main navigation">
        @foreach ($navigationItems as [$label, $routeName, $icon])
            <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
               class="{{ request()->routeIs($routeName) ? 'active' : '' }}"
               @if (request()->routeIs($routeName)) aria-current="page" @endif>
                <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
            </a>
        @endforeach
    </nav>

    <form class="sw-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">
            <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
            <span>Log out</span>
        </button>
    </form>
</aside>
