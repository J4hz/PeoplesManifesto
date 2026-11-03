@props(['title' => null, 'active' => null])
@php $user = auth('web')->user(); @endphp
<x-layout.base :title="$title">
    <header class="site-header">
        <div class="wrap wrap-wide">
            <a class="wordmark" href="{{ $user && $user->isAdmin() ? route('admin') : route('dashboard') }}">
                <img src="{{ asset('favicon.svg') }}" alt="" width="28" height="28">
                {{ config('app.name') }}
            </a>
            @if ($user)
                <div class="row">
                    <span class="muted small">
                        {{ $user->name }}
                        @if ($user->leader) &middot; {{ $user->leader->office->name }} @endif
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-quiet btn-small"><x-icon name="log-out" /> {{ __('leader.nav.sign_out') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </header>

    <main id="main" class="site-main">
        <div class="wrap wrap-wide">
            @if ($user && $user->hasConfirmedTwoFactor())
                <div class="shell">
                    <nav class="side-nav" aria-label="{{ __('leader.nav.label') }}">
                        @can('leader-dashboard')
                            <a href="{{ route('dashboard') }}" @if ($active === 'overview') aria-current="page" @endif><x-icon name="home" /> {{ __('leader.nav.overview') }}</a>
                            <a href="{{ route('dashboard.ratings') }}" @if ($active === 'ratings') aria-current="page" @endif><x-icon name="star" /> {{ __('leader.nav.ratings') }}</a>
                            <a href="{{ route('dashboard.audit') }}" @if ($active === 'audit') aria-current="page" @endif><x-icon name="file" /> {{ __('leader.nav.audit') }}</a>
                        @endcan
                        @can('admin')
                            <a href="{{ route('admin') }}" @if ($active === 'admin') aria-current="page" @endif><x-icon name="home" /> {{ __('admin.nav.overview') }}</a>
                            <a href="{{ route('admin.wards') }}" @if ($active === 'wards') aria-current="page" @endif><x-icon name="map-pin" /> {{ __('admin.nav.wards') }}</a>
                            <a href="{{ route('admin.people') }}" @if ($active === 'people') aria-current="page" @endif><x-icon name="users" /> {{ __('admin.nav.people') }}</a>
                            <a href="{{ route('admin.intake') }}" @if ($active === 'intake') aria-current="page" @endif><x-icon name="message" /> {{ __('admin.nav.intake') }}</a>
                            <a href="{{ route('admin.ai-usage') }}" @if ($active === 'ai') aria-current="page" @endif><x-icon name="clock" /> {{ __('admin.nav.ai') }}</a>
                            <a href="{{ route('admin.audit') }}" @if ($active === 'audit-all') aria-current="page" @endif><x-icon name="file" /> {{ __('admin.nav.audit') }}</a>
                        @endcan
                        <a href="{{ route('staff.security') }}" @if ($active === 'security') aria-current="page" @endif><x-icon name="shield" /> {{ __('leader.nav.security') }}</a>
                    </nav>
                    <div class="stack">
                        <x-flash />
                        {{ $slot }}
                    </div>
                </div>
            @else
                <div class="stack narrow">
                    <x-flash />
                    {{ $slot }}
                </div>
            @endif
        </div>
    </main>
</x-layout.base>
