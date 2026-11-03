@props(['title' => null, 'active' => null])
@php $citizen = auth('citizen')->user(); @endphp
<x-layout.base :title="$title">
    <header class="site-header">
        <div class="wrap">
            <a class="wordmark" href="{{ route('home') }}">
                <img src="{{ asset('favicon.svg') }}" alt="" width="28" height="28">
                {{ config('app.name') }}
            </a>
            @if ($citizen)
                <nav class="site-nav" aria-label="{{ __('citizen.nav.label') }}">
                    <a href="{{ route('home') }}" @if ($active === 'home') aria-current="page" @endif>{{ __('citizen.nav.raise') }}</a>
                    <a href="{{ route('citizen.ward') }}" @if ($active === 'ward') aria-current="page" @endif>{{ __('citizen.nav.ward') }}</a>
                    <a href="{{ route('citizen.reports') }}" @if ($active === 'reports') aria-current="page" @endif>{{ __('citizen.nav.reports') }}</a>
                    <a href="{{ route('citizen.account') }}" @if ($active === 'account') aria-current="page" @endif>{{ __('citizen.nav.account') }}</a>
                </nav>
            @else
                <nav class="site-nav" aria-label="{{ __('citizen.nav.label') }}">
                    <a href="{{ route('citizen.sign-in') }}">{{ __('citizen.nav.sign_in') }}</a>
                </nav>
            @endif
        </div>
    </header>

    <main id="main" class="site-main">
        <div class="wrap stack">
            <x-flash />
            {{ $slot }}
        </div>
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <span>{{ __('civic.footer.non_partisan') }}</span>
            <div class="row">
                <a href="{{ route('privacy') }}">{{ __('civic.footer.privacy') }}</a>
                <form method="POST" action="{{ route('language') }}" class="inline-form">
                    @csrf
                    @if (app()->getLocale() === 'sw')
                        <button class="link-button" name="locale" value="en" lang="en">English</button>
                    @else
                        <button class="link-button" name="locale" value="sw" lang="sw">Kiswahili</button>
                    @endif
                </form>
                @if ($citizen)
                    <form method="POST" action="{{ route('citizen.sign-out') }}" class="inline-form">
                        @csrf
                        <button class="link-button">{{ __('citizen.nav.sign_out') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </footer>
</x-layout.base>
