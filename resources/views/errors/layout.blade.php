{{-- Kept free of database calls so it still renders when the database is down. --}}
<x-layout.base :title="$title">
    <header class="site-header">
        <div class="wrap">
            <a class="wordmark" href="{{ url('/') }}">
                <img src="{{ asset('favicon.svg') }}" alt="" width="28" height="28">
                {{ config('app.name') }}
            </a>
        </div>
    </header>
    <main id="main" class="site-main">
        <div class="wrap">
            <section class="card stack">
                <h1>{{ $title }}</h1>
                <p>{{ $message }}</p>
                <p><a class="btn" href="{{ $homeUrl ?? url('/') }}">{{ $homeLabel ?? __('errors.home') }}</a></p>
            </section>
        </div>
    </main>
</x-layout.base>
