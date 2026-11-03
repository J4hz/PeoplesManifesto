<x-layout.staff :title="__('leader.auth.login_title')">
    <section class="card stack">
        <h1>{{ __('leader.auth.login_title') }}</h1>
        <p class="muted">{{ __('leader.auth.login_intro') }}</p>
        <form method="POST" action="{{ route('login') }}" class="stack" novalidate>
            @csrf
            <div class="field">
                <label for="email">{{ __('leader.auth.email') }}</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required @class(['input-error' => $errors->has('email')])>
                <x-field-error name="email" />
            </div>
            <div class="field">
                <label for="password">{{ __('leader.auth.password') }}</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <x-field-error name="password" />
            </div>
            <button type="submit" class="btn btn-block">{{ __('leader.auth.sign_in') }}</button>
        </form>
        <p class="small"><a href="{{ route('password.request') }}">{{ __('leader.auth.forgot') }}</a></p>
        <p class="small muted">{{ __('leader.auth.residents_elsewhere') }} <a href="{{ route('citizen.sign-in') }}">{{ __('leader.auth.residents_link') }}</a></p>
    </section>
</x-layout.staff>
